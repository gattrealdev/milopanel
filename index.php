<?php
declare(strict_types=1);

// Menghapus batasan time-limit agar proses assembly file besar tidak terputus.
@ini_set('memory_limit', '-1');
@ini_set('max_execution_time', '0');
@ini_set('max_input_time', '0');
@set_time_limit(0);

// MiloPanel Pro Max — Advanced Standalone PHP Server Panel.
// Unfiltered Raw Terminal, Monaco Editor, Chunked Uploads (Unlimited Limit By-pass), Modern UI.

const APP_NAME = 'MiloPanel';
const STORAGE_DIR = __DIR__ . '/servers';
const LOG_DIR = __DIR__ . '/logs';

@mkdir(__DIR__ . '/data', 0775, true);
@mkdir(STORAGE_DIR, 0775, true);
@mkdir(LOG_DIR, 0775, true);

// ---------------------------------------------------------
// Helper & System Functions
// ---------------------------------------------------------
function store_read(string $file, array $default=[]): array {
    if (!is_file($file)) return $default;
    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw)==='') return $default;
    $data = json_decode($raw, true); return is_array($data) ? $data : $default;
}
function store_write(string $file, array $data): void {
    $tmp = $file.'.tmp'; file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX); rename($tmp, $file);
}
function settings(): array { static $x=null; if($x===null){$x=store_read(__DIR__.'/data/settings.json',[]);} return $x; }
function save_settings(array $x): void { store_write(__DIR__.'/data/settings.json',$x); }
function servers_store(): array { static $x=null; if($x===null){$x=store_read(__DIR__.'/data/servers.json',[]);} return $x; }
function save_servers_store(array $x): void { store_write(__DIR__.'/data/servers.json',$x); }
function setting(string $key, ?string $default = null): ?string { $x=settings(); return array_key_exists($key,$x) ? (string)$x[$key] : $default; }
function is_installed(): bool { return setting('admin_hash') !== null; }
function now(): string { return date('c'); }
function slug(string $s): string { $s = strtolower(trim($s)); $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? ''; return trim($s, '-') ?: 'server'; }
function all_servers(): array { return array_values(servers_store()); }
function save_server(array $s): void { $x=servers_store(); $x[(string)$s['id']]=$s; save_servers_store($x); }
function server_row(int $id): ?array { $x=servers_store(); return $x[(string)$id] ?? null; }
function server_dir(array $s): string { return STORAGE_DIR . '/' . $s['slug']; }
function log_file(array $s): string { return LOG_DIR . '/' . $s['slug'] . '.log'; }
function pid_file(array $s): string { return LOG_DIR . '/' . $s['slug'] . '.pid'; }
function stdin_file(array $s): string { return LOG_DIR . '/' . $s['slug'] . '.in'; }

function proc_pid(array $s): ?int { $f=pid_file($s); if (!is_file($f)) return null; $p=(int)trim((string)@file_get_contents($f)); return $p>0 ? $p : null; }
function process_alive(?int $pid): bool {
    if (!$pid) return false;
    if (DIRECTORY_SEPARATOR === '\\') { @exec('tasklist /FI "PID eq '.(int)$pid.'" 2>NUL', $o, $rc); return $rc===0 && count($o)>1; }
    if (function_exists('posix_kill')) return @posix_kill($pid, 0);
    @exec('kill -0 '.(int)$pid.' 2>/dev/null', $o, $rc); return $rc===0;
}
function status_of(array $s): string { return process_alive(proc_pid($s)) ? 'running' : 'offline'; }
function safe_join(string $base, string $rel): string {
    $rel = str_replace('\\','/',$rel); $rel = ltrim($rel,'/');
    if ($rel === '' || $rel === '.') return rtrim($base,'/');
    foreach (explode('/',$rel) as $part) { if ($part === '..') fail('Keamanan: Dilarang naik folder (..)', 403); }
    $full = rtrim($base,'/') . '/' . $rel; $realBase = realpath($base);
    if ($realBase === false) return $full;
    $parent = realpath(dirname($full));
    if ($parent !== false && !str_starts_with($parent, $realBase)) fail('Path berada di luar direktori server!', 403);
    $candidate = $full;
    if (file_exists($candidate)) { $real=realpath($candidate); if ($real===false || !str_starts_with($real,$realBase)) fail('Path berada di luar root!', 403); }
    return $candidate;
}

// ---------------------------------------------------------
// CLI BACKGROUND DAEMON (Menangkap 100% Raw Terminal Output)
// ---------------------------------------------------------
if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === 'daemon_run') {
    $s = server_row((int)($argv[2] ?? 0)); if (!$s) exit(1);
    $in_file = stdin_file($s); $log = log_file($s);

    $env = array_merge($_SERVER, getenv() ?: [], [
        'FORCE_COLOR' => '1', 'NPM_CONFIG_COLOR' => 'always', 'PYTHONUNBUFFERED' => '1', 'TERM' => 'xterm-256color', 'CI' => 'false'
    ]);

    $desc = [ 0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"] ];
    @unlink($in_file); file_put_contents(pid_file($s), getmypid());
    file_put_contents($log, "\x1b[32;1m[Server] \x1b[90m[" . date('H:i:s') . "]\x1b[0m Memulai proses...\n", FILE_APPEND | LOCK_EX);

    $proc = proc_open($s['command'], $desc, $pipes, $s['cwd'], $env);
    if (!is_resource($proc)) {
        file_put_contents($log, "\x1b[31;1m[Error] Gagal menjalankan perintah!\x1b[0m\n", FILE_APPEND | LOCK_EX); exit(1);
    }

    stream_set_blocking($pipes[0], false); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);

    while (true) {
        $status = proc_get_status($proc);

        $out = stream_get_contents($pipes[1]); if ($out) file_put_contents($log, $out, FILE_APPEND | LOCK_EX);
        $err = stream_get_contents($pipes[2]); if ($err) file_put_contents($log, $err, FILE_APPEND | LOCK_EX);

        if (file_exists($in_file)) {
            $tmp = $in_file . '.' . uniqid();
            if (@rename($in_file, $tmp)) {
                $cmds = file_get_contents($tmp);
                if (str_contains($cmds, "_PANEL_STOP_\n")) {
                    $cmds = str_replace("_PANEL_STOP_\n", "", $cmds);
                    if (trim($cmds) !== '') @fwrite($pipes[0], $cmds);
                    proc_terminate($proc); @unlink($tmp);
                    file_put_contents($log, "\n\x1b[33;1m[Server] \x1b[90m[" . date('H:i:s') . "]\x1b[0m Proses dihentikan paksa.\n", FILE_APPEND | LOCK_EX);
                    break;
                }
                @fwrite($pipes[0], $cmds); @unlink($tmp);
            }
        }
        if (!$status['running']) break;
        usleep(50000);
    }

    $out = stream_get_contents($pipes[1]); if ($out) file_put_contents($log, $out, FILE_APPEND | LOCK_EX);
    $err = stream_get_contents($pipes[2]); if ($err) file_put_contents($log, $err, FILE_APPEND | LOCK_EX);

    file_put_contents($log, "\n\x1b[31;1m[Server] \x1b[90m[" . date('H:i:s') . "]\x1b[0m Proses terhenti (Exit Code: {$status['exitcode']}).\n", FILE_APPEND | LOCK_EX);
    @unlink(pid_file($s)); @unlink($in_file); exit(0);
}

function shell_start(array $s): void {
    @mkdir(server_dir($s), 0775, true); @touch(log_file($s)); if (status_of($s) === 'running') return;
    $bin = PHP_BINARY; $script = __FILE__; $id = (int)$s['id'];
    if (DIRECTORY_SEPARATOR === '\\') pclose(popen('start /B "" "'.escapeshellcmd($bin).'" "'.escapeshellarg($script).'" daemon_run '.$id, 'r'));
    else exec(escapeshellarg($bin) . ' ' . escapeshellarg($script) . ' daemon_run ' . $id . ' > /dev/null 2>&1 &');
}
function shell_stop(array $s): void {
    $pid = proc_pid($s); if (!$pid) return;
    file_put_contents(stdin_file($s), "_PANEL_STOP_\n", FILE_APPEND | LOCK_EX); usleep(500000);
    if (process_alive($pid)) {
        if (DIRECTORY_SEPARATOR === '\\') @exec('taskkill /PID '.(int)$pid.' /T /F'); else @exec('kill -9 '.(int)$pid.' 2>/dev/null');
    }
    @unlink(pid_file($s)); @unlink(stdin_file($s));
}

// ---------------------------------------------------------
// Web Application Core
// ---------------------------------------------------------
session_name('milopanel'); session_start();
function csrf(): string { if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) fail('Token CSRF tidak valid', 403); }
function logged_in(): bool { return isset($_SESSION['uid']) && $_SESSION['uid'] === 'admin'; }
function json_out(array $data): never { header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_SLASHES); exit; }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function fail(string $msg, int $code=400): never {
    http_response_code($code);
    if (isset($_GET['api']) || str_starts_with($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_out(['ok'=>false,'error'=>$msg]);
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;padding:40px;background:#09090b;color:#fafafa"><h2>Terjadi Kesalahan</h2><p>'.h($msg).'</p><p><a style="color:#3b82f6" href="javascript:history.back()">Kembali</a></p></body>'; exit;
}
function tail_log(string $file, int $bytes=65000): string { if (!is_file($file)) return ''; $size=(int)filesize($file); $fh=fopen($file,'rb'); if(!$fh) return ''; fseek($fh, max(0,$size-$bytes)); $txt=stream_get_contents($fh) ?: ''; fclose($fh); return $txt; }
function rrmdir(string $dir): void { if(!is_dir($dir))return; $items=scandir($dir)?:[];foreach($items as $i){if($i==='.'||$i==='..')continue;$p=$dir.'/'.$i;if(is_dir($p)&&!is_link($p))rrmdir($p);else @unlink($p);} @rmdir($dir); }
function format_bytes(int $n): string { $u=['B','KB','MB','GB','TB'];$i=0;(float)$n;while($n>=1024&&$i<count($u)-1){$n=(int)round($n/1024);$i++;}return $n.' '.$u[$i]; }

$action = $_GET['action'] ?? '';
if ($action === 'install') {
    if (is_installed()) { header('Location: ./'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (trim($_POST['username']??'') !== 'admin') fail('Username awal harus admin');
        if (strlen($_POST['password']??'') < 8) fail('Password minimal 8 karakter');
        save_settings(['admin_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT), 'created_at' => now()]);
        $_SESSION['uid'] = 'admin'; csrf(); header('Location: ./'); exit;
    }
    echo install_page(); exit;
}
if (!is_installed()) { header('Location: ?action=install'); exit; }
if ($action === 'login' && $_SERVER['REQUEST_METHOD']==='POST') {
    if (password_verify((string)($_POST['password']??''), (string)setting('admin_hash'))) { $_SESSION['uid']='admin'; csrf(); header('Location: ./'); exit; } fail('Password salah',401);
}
if ($action === 'logout') { session_destroy(); header('Location: ./'); exit; }
if (!logged_in()) { echo login_page(); exit; }

// API Endpoints
if (isset($_GET['api'])) {
    if ($_GET['api'] === 'log') { $s = server_row((int)($_GET['id'] ?? 0)); if(!$s) json_out(['ok'=>false]); json_out(['ok'=>true, 'status'=>status_of($s), 'log'=>tail_log(log_file($s))]); }
    if ($_GET['api'] === 'command' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $s = server_row((int)($_POST['id'] ?? 0)); if(!$s) json_out(['ok'=>false]);
        $cmd = (string)($_POST['cmd'] ?? '');
        if ($cmd !== '' && status_of($s) === 'running') {
            @file_put_contents(log_file($s), "\x1b[90mroot@server:~# " . $cmd . "\x1b[0m\n", FILE_APPEND | LOCK_EX);
            file_put_contents(stdin_file($s), $cmd."\n", FILE_APPEND | LOCK_EX);
        }
        json_out(['ok'=>true]);
    }
}

// Download Handler
if ($action === 'file_download') {
    $s = server_row((int)$_GET['id']); $p = safe_join(server_dir($s), (string)$_GET['path']);
    if (is_file($p)) {
        header('Content-Description: File Transfer'); header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="'.basename($p).'"'); header('Expires: 0'); header('Cache-Control: must-revalidate'); header('Content-Length: ' . filesize($p)); readfile($p); exit;
    } fail('File tidak ditemukan', 404);
}

// POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($action, ['create','delete','toggle','save_server','file_write','file_delete','file_upload_chunk','create_item','clear_log','zip_extract','zip_compress','bulk_delete','rename'])) check_csrf();

    if ($action === 'create') {
        $name=trim((string)($_POST['name']??'')); $cmd=trim((string)($_POST['command']??''));
        if ($name===''||$cmd==='') fail('Nama dan perintah wajib diisi');
        $slug=slug($name); $base=$slug; $n=2; $existing=array_map(fn($x)=>(string)$x['slug'], all_servers()); while(in_array($slug,$existing,true)) $slug=$base.'-'.($n++);
        $list=servers_store(); $id=1; if($list) $id=max(array_map('intval',array_keys($list)))+1;
        $cwd=STORAGE_DIR.'/'.$slug; @mkdir($cwd,0775,true);
        $s=['id'=>$id,'name'=>$name,'slug'=>$slug,'command'=>$cmd,'cwd'=>$cwd,'autostart'=>!empty($_POST['autostart'])?1:0,'created_at'=>now()];
        $list[(string)$id]=$s; save_servers_store($list); header('Location: ?p=manage&id='.$id); exit;
    }
    if ($action === 'delete') { $s=server_row((int)$_POST['id']); shell_stop($s); $list=servers_store(); unset($list[(string)$s['id']]); save_servers_store($list); rrmdir(server_dir($s)); @unlink(log_file($s)); header('Location: ?p=servers'); exit; }
    if ($action === 'toggle') { $s=server_row((int)$_POST['id']); if(status_of($s)==='running') shell_stop($s); else shell_start($s); header('Location: ?p=manage&id='.$s['id']); exit; }
    if ($action === 'clear_log') { $s=server_row((int)$_POST['id']); @file_put_contents(log_file($s), ''); header('Location: ?p=manage&id='.$s['id']); exit; }
    if ($action === 'save_server') { $s=server_row((int)$_POST['id']); $s['name']=trim((string)$_POST['name']);$s['command']=trim((string)$_POST['command']);$s['cwd']=trim((string)$_POST['cwd']);$s['autostart']=!empty($_POST['autostart'])?1:0; save_server($s); header('Location: ?p=manage&id='.$s['id'].'&tab=config'); exit; }
    if ($action === 'file_write') {
        $s=server_row((int)$_POST['id']); $rel=(string)$_POST['path']; $p=safe_join(server_dir($s),$rel); @mkdir(dirname($p),0775,true);
        $result = @file_put_contents($p,(string)$_POST['content'],LOCK_EX);
        if($result === false) fail("Gagal menyimpan file. Pastikan permission folder mengizinkan (chmod).", 500);
        header('Location: ?p=manage&id='.$s['id'].'&tab=files&dir='.urlencode(dirname($rel)==='.'?'':dirname($rel))); exit;
    }
    if ($action === 'file_delete') { $s=server_row((int)$_POST['id']); $rel=(string)$_POST['path']; $p=safe_join(server_dir($s),$rel); if($p!==server_dir($s)){ if(is_dir($p)) rrmdir($p); else @unlink($p); } header('Location: ?p=manage&id='.$s['id'].'&tab=files&dir='.urlencode(dirname($rel)==='.'?'':dirname($rel))); exit; }
    if ($action === 'bulk_delete') {
        $s=server_row((int)$_POST['id']); $paths = json_decode($_POST['paths'], true) ?: [];
        foreach($paths as $path) { $p=safe_join(server_dir($s), $path); if($p!==server_dir($s)){ if(is_dir($p)) rrmdir($p); else @unlink($p); } }
        header('Location: ?p=manage&id='.$s['id'].'&tab=files&dir='.urlencode($_POST['dir'])); exit;
    }
    if ($action === 'rename') {
        $s=server_row((int)$_POST['id']); $old = safe_join(server_dir($s), $_POST['old']); $new = safe_join(server_dir($s), $_POST['dir'].'/'.$_POST['new']);
        if ($old !== server_dir($s) && !file_exists($new)) { rename($old, $new); }
        header('Location: ?p=manage&id='.$s['id'].'&tab=files&dir='.urlencode($_POST['dir'])); exit;
    }
    if ($action === 'create_item') {
        $s=server_row((int)$_POST['id']); $rel=(string)$_POST['dir']; $name=trim((string)$_POST['name']);
        if ($name !== '') { $target = safe_join(server_dir($s), ltrim($rel . '/' . $name, '/')); if ($_POST['type'] === 'dir') @mkdir($target, 0775, true); else @touch($target); }
        header('Location: ?p=manage&id='.$s['id'].'&tab=files&dir='.urlencode($rel)); exit;
    }
    
    if ($action === 'zip_extract') {
        if (!class_exists('ZipArchive')) fail('Ekstensi PHP ZipArchive tidak terinstall.');
        $s=server_row((int)$_POST['id']); $zipPath = safe_join(server_dir($s), (string)$_POST['path']);
        $zip = new ZipArchive; if ($zip->open($zipPath) === TRUE) { $zip->extractTo(dirname($zipPath)); $zip->close(); } else { fail("Gagal mengekstrak ZIP (File Korup/Tidak Didukung)."); }
        header('Location: ?p=manage&id='.$s['id'].'&tab=files&dir='.urlencode(dirname($_POST['path'])==='.'?'':dirname($_POST['path']))); exit;
    }
    if ($action === 'zip_compress') {
        if (!class_exists('ZipArchive')) fail('Ekstensi PHP ZipArchive tidak terinstall.');
        $s=server_row((int)$_POST['id']); $dirRel=(string)$_POST['dir']; $base = safe_join(server_dir($s), $dirRel);
        $paths = json_decode($_POST['paths'], true) ?: []; $zipName = trim((string)$_POST['name']) ?: 'archive.zip';
        if (!str_ends_with(strtolower($zipName), '.zip')) $zipName .= '.zip';
        $zip = new ZipArchive();
        if ($zip->open($base . '/' . $zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach($paths as $p) {
                $fullPath = safe_join(server_dir($s), $p);
                if (is_file($fullPath)) $zip->addFile($fullPath, basename($fullPath));
                elseif (is_dir($fullPath)) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath), RecursiveIteratorIterator::LEAVES_ONLY);
                    foreach ($files as $name => $file) { if (!$file->isDir()) { $fp = $file->getRealPath(); $rel = substr($fp, strlen($fullPath) + 1); $zip->addFile($fp, basename($fullPath) . '/' . $rel); } }
                }
            } $zip->close();
        } else { fail("Gagal membuat ZIP, periksa permission folder."); }
        header('Location: ?p=manage&id='.$s['id'].'&tab=files&dir='.urlencode($dirRel)); exit;
    }

    // --- CHUNKED UPLOAD HANDLER (Bypass Batasan PHP Upload Limit!) ---
    if ($action === 'file_upload_chunk') {
        if (!isset($_POST['id'], $_POST['dir'], $_POST['file_name'], $_POST['chunk_index'], $_POST['total_chunks'])) {
            json_out(['ok' => false, 'error' => 'Data chunk tidak lengkap']);
        }
        $s = server_row((int)$_POST['id']); $rel = (string)$_POST['dir'];
        $targetDir = safe_join(server_dir($s), $rel); @mkdir($targetDir, 0775, true);

        $fileName = basename($_POST['file_name']);
        $chunkIndex = (int)$_POST['chunk_index'];
        $totalChunks = (int)$_POST['total_chunks'];

        $tmpPath = $targetDir . '/' . $fileName . '.part';
        $finalPath = $targetDir . '/' . $fileName;

        if (isset($_FILES['chunk_data']) && $_FILES['chunk_data']['error'] === UPLOAD_ERR_OK) {
            $chunkData = file_get_contents($_FILES['chunk_data']['tmp_name']);
            if ($chunkIndex === 0) @unlink($tmpPath);

            file_put_contents($tmpPath, $chunkData, FILE_APPEND | LOCK_EX);

            if ($chunkIndex === $totalChunks - 1) {
                if(file_exists($finalPath)) @unlink($finalPath);
                rename($tmpPath, $finalPath);
            }
            json_out(['ok' => true]);
        }
        $err = $_FILES['chunk_data']['error'] ?? 'Tidak diketahui';
        json_out(['ok' => false, 'error' => "Gagal merakit chunk. Kode PHP Error: $err"]);
    }
}

// Autostart boot check
foreach (all_servers() as $as) { if (!empty($as['autostart']) && status_of($as) !== 'running') { @shell_start($as); } }

// ---------------------------------------------------------
// UI Generation (Clean, Modern, Pterodactyl-Vibe)
// ---------------------------------------------------------
function icon(string $name): string {
    $i = [
        'menu' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>',
        'grid' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
        'server' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>',
        'play' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>',
        'stop' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>',
        'folder' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>',
        'file' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>',
        'archive' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f472b6" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>',
        'terminal' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>',
        'settings' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'search' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
        'edit' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
    ]; return $i[$name] ?? '';
}

function css(): string { return '<style>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap");
:root { --bg:#000000; --surface:#09090b; --surface2:#18181b; --border:#27272a; --text:#fafafa; --text-dim:#a1a1aa; --primary:#3b82f6; --danger:#ef4444; --success:#10b981; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Inter", sans-serif; background: var(--bg); color: var(--text); font-size: 14px; height: 100vh; overflow: hidden; }
a { color: inherit; text-decoration: none; }
.input { width:100%; background:var(--surface); color:var(--text); border:1px solid var(--border); padding:10px 12px; border-radius:8px; font-family:inherit; transition: border-color 0.2s; }
.input:focus { outline:none; border-color:var(--primary); }
.btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; border:1px solid var(--border); background:var(--surface2); color:var(--text); padding:8px 14px; border-radius:8px; font-weight:500; cursor:pointer; font-size:13px; transition:all 0.2s; }
.btn:hover:not(:disabled) { background:#27272a; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.primary { background:var(--primary); border-color:var(--primary); color:white; }
.btn.primary:hover:not(:disabled) { background:#2563eb; }
.btn.danger { background:rgba(239,68,68,0.1); color:var(--danger); border-color:transparent; }
.btn.danger:hover:not(:disabled) { background:rgba(239,68,68,0.2); }
.btn.success { background:rgba(16,185,129,0.1); color:var(--success); border-color:transparent; }
.btn-sm { padding:6px 10px; font-size:12px; }

.layout { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
.sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); flex-shrink: 0; z-index: 50; }
.layout.collapsed .sidebar { margin-left: -260px; }

.sidebar-head { padding: 20px; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border); }
.nav-menu { padding: 16px 12px; display: flex; flex-direction: column; gap: 4px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 8px; color: var(--text-dim); font-weight: 500; transition: all 0.2s; white-space: nowrap; }
.nav-item:hover, .nav-item.active { background: var(--surface2); color: var(--text); }
.main-wrapper { flex-grow: 1; display: flex; flex-direction: column; min-width: 0; }
.topbar { height: 65px; border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 24px; gap: 16px; background: var(--surface); }
.icon-btn { background: none; border: none; color: var(--text-dim); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 8px; border-radius: 6px; }
.icon-btn:hover { background: var(--surface2); color: var(--text); }
.content-area { flex-grow: 1; overflow-y: auto; padding: 32px; position: relative; }

.card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
.grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; }
.server-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.server-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 12px; transition: transform 0.2s, border-color 0.2s; }
.server-card:hover { transform: translateY(-2px); border-color: var(--primary); }
.status-dot { width: 8px; height: 8px; border-radius: 50%; background: #52525b; }
.status-dot.run { background: var(--success); box-shadow: 0 0 8px rgba(16,185,129,0.5); }

.tabs { display: flex; gap: 24px; border-bottom: 1px solid var(--border); margin-bottom: 24px; }
.tab-btn { background: none; border: none; color: var(--text-dim); padding: 0 0 16px 0; font-size: 14px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid transparent; }
.tab-btn:hover { color: var(--text); }
.tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
.tab-content { display: none; } .tab-content.active { display: block; }

.console-box { background: #000; border: 1px solid var(--border); border-radius: 12px; display: flex; flex-direction: column; height: calc(100vh - 260px); min-height: 400px; }
.console-output { flex-grow: 1; overflow-y: auto; padding: 16px; font-family: "JetBrains Mono", monospace; font-size: 13px; color: #d4d4d8; white-space: pre-wrap; line-height: 1.5; scroll-behavior: auto; }
.console-input-wrap { border-top: 1px solid var(--border); display: flex; align-items: center; padding: 12px 16px; background: var(--surface); border-radius: 0 0 12px 12px; cursor: text; }
.console-input { flex-grow: 1; width: 100%; background: transparent; border: none; color: var(--text); font-family: "JetBrains Mono", monospace; font-size: 13px; outline: none; margin-left: 12px; }

.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); font-size: 13px; }
.table th { color: var(--text-dim); font-weight: 500; }
.table tr:hover { background: var(--surface2); }
.editor-container { width: 100%; height: calc(100vh - 200px); min-height: 500px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }

.flex-between { display: flex; justify-content: space-between; align-items: center; }
.text-dim { color: var(--text-dim); }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 100; display: none; place-items: center; }
.modal-overlay.active { display: grid; }
</style>'; }

$page = $_GET['p'] ?? 'dashboard';
$servers = array_reverse(all_servers());

function render_dashboard(): void {
    global $servers;
    $sys = ['load'=>[0],'ram_total'=>0,'ram_free'=>0];
    if (function_exists('sys_getloadavg')) $sys['load'] = sys_getloadavg();
    if (is_file('/proc/meminfo')) {
        $m=file('/proc/meminfo');
        foreach($m as $line){ if(preg_match('/^MemTotal:\s+(\d+)/',$line,$x))$sys['ram_total']=(int)$x[1]*1024; if(preg_match('/^MemAvailable:\s+(\d+)/',$line,$x))$sys['ram_free']=(int)$x[1]*1024; }
    }
    $online = 0; foreach($servers as $s) { if(status_of($s)==='running') $online++; }
    ?>
    <h1 style="font-size:24px; margin-bottom:24px">Dashboard</h1>
    <div class="grid-2" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 32px">
        <div class="card">
            <div class="text-dim" style="font-size:12px; text-transform:uppercase; font-weight:600">Total Server</div>
            <div style="font-size:32px; font-weight:700; margin-top:8px"><?=count($servers)?></div>
        </div>
        <div class="card">
            <div class="text-dim" style="font-size:12px; text-transform:uppercase; font-weight:600">Server Online</div>
            <div style="font-size:32px; font-weight:700; margin-top:8px; color:var(--success)"><?=$online?></div>
        </div>
        <div class="card">
            <div class="text-dim" style="font-size:12px; text-transform:uppercase; font-weight:600">CPU Load (1m)</div>
            <div style="font-size:32px; font-weight:700; margin-top:8px"><?=round((float)$sys['load'][0], 2)?></div>
        </div>
        <div class="card">
            <div class="text-dim" style="font-size:12px; text-transform:uppercase; font-weight:600">RAM Terpakai</div>
            <div style="font-size:32px; font-weight:700; margin-top:8px">
                <?=$sys['ram_total']?format_bytes($sys['ram_total']-$sys['ram_free']):'N/A'?>
            </div>
        </div>
    </div>
    <h2 style="font-size:18px; margin-bottom:16px">Server Terbaru</h2>
    <div class="server-grid">
        <?php foreach(array_slice($servers, 0, 4) as $s): $run = status_of($s)==='running'; ?>
        <a href="?p=manage&id=<?=$s['id']?>" class="server-card">
            <div class="flex-between">
                <span style="font-weight:600; font-size:16px"><?=h($s['name'])?></span>
                <span class="status-dot <?=$run?'run':''?>"></span>
            </div>
            <div class="text-dim" style="font-size:12px; font-family:monospace"><?=h($s['command'])?></div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_servers(): void {
    global $servers, $action; $csrf = h(csrf());
    ?>
    <div class="flex-between" style="margin-bottom:24px">
        <h1 style="font-size:24px">Daftar Server</h1>
        <div style="display:flex; gap:12px">
            <div style="position:relative">
                <span style="position:absolute; left:12px; top:10px; color:var(--text-dim)"><?=icon('search')?></span>
                <input type="text" id="search-server" class="input" placeholder="Cari server..." style="padding-left:36px; width:250px">
            </div>
            <button class="btn primary" onclick="document.getElementById('modal-new').classList.add('active')">+ Server Baru</button>
        </div>
    </div>
    <div class="server-grid" id="server-list">
        <?php foreach($servers as $s): $run = status_of($s)==='running'; ?>
        <a href="?p=manage&id=<?=$s['id']?>" class="server-card" data-name="<?=strtolower(h($s['name']))?>">
            <div class="flex-between">
                <span style="font-weight:600; font-size:16px"><?=h($s['name'])?></span>
                <div style="display:flex; align-items:center; gap:8px">
                    <span style="font-size:12px; color:var(--text-dim)"><?=$run?'Online':'Offline'?></span>
                    <span class="status-dot <?=$run?'run':''?>"></span>
                </div>
            </div>
            <div class="text-dim" style="font-size:12px; font-family:monospace; white-space:nowrap; overflow:hidden; text-overflow:ellipsis"><?=h($s['command'])?></div>
        </a>
        <?php endforeach; if(!$servers): echo '<div class="text-dim">Belum ada server.</div>'; endif;?>
    </div>

    <!-- Modal New Server -->
    <div class="modal-overlay" id="modal-new">
        <div class="card" style="width:100%; max-width:500px">
            <div class="flex-between" style="margin-bottom:20px">
                <h3 style="font-size:18px">Buat Server Baru</h3>
                <button class="icon-btn" onclick="document.getElementById('modal-new').classList.remove('active')">✕</button>
            </div>
            <div style="margin-bottom:16px">
                <label style="font-size:12px; color:var(--text-dim)">Pilih Template (Opsional)</label>
                <select class="input" id="template-select" style="margin-top:6px">
                    <option value="">-- Custom --</option>
                    <option value="node">Node.js (npm start)</option>
                    <option value="php">PHP Built-in (php -S)</option>
                    <option value="python">Python HTTP (python -m http.server)</option>
                </select>
            </div>
            <form method="post" action="?action=create">
                <input type="hidden" name="csrf" value="<?=$csrf?>">
                <input class="input" name="name" id="inp-name" placeholder="Nama Server" required style="margin-bottom:12px">
                <input class="input" name="command" id="inp-cmd" placeholder="Perintah Startup" required style="margin-bottom:12px">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:24px"><input type="checkbox" name="autostart"> Nyalakan otomatis saat panel boot</label>
                <button class="btn primary" style="width:100%">Buat Sekarang</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('search-server').addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            document.querySelectorAll('.server-card').forEach(el => { el.style.display = el.dataset.name.includes(val) ? 'flex' : 'none'; });
        });
        document.getElementById('template-select').addEventListener('change', function(e) {
            const cmd = document.getElementById('inp-cmd'); const name = document.getElementById('inp-name');
            if(e.target.value === 'node') { cmd.value = 'npm start'; if(!name.value) name.value = 'Node App'; }
            if(e.target.value === 'php') { cmd.value = 'php -S 0.0.0.0:8000'; if(!name.value) name.value = 'PHP Server'; }
            if(e.target.value === 'python') { cmd.value = 'python3 -m http.server 8080'; if(!name.value) name.value = 'Python Web'; }
        });
    </script>
    <?php
}

function render_manage(): void {
    $s = server_row((int)($_GET['id'] ?? 0)); if(!$s) { echo "Server tidak ditemukan."; return; }
    $run = status_of($s)==='running'; $csrf=h(csrf()); $sid=(int)$s['id'];
    $tab = $_GET['tab'] ?? 'console'; $dirRel = $_GET['dir'] ?? ''; $fileRel = $_GET['file'] ?? '';

    if ($fileRel !== '') {
        $p = safe_join(server_dir($s), $fileRel); $editing = is_file($p) ? file_get_contents($p) : '';
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        ?>
        <div class="flex-between" style="margin-bottom:20px">
            <div>
                <h2 style="font-size:20px; display:flex; align-items:center; gap:8px"><?=icon('file')?> <?=h(basename($fileRel))?></h2>
                <div class="text-dim" style="font-size:12px; margin-top:4px"><?=h($fileRel)?></div>
            </div>
            <div style="display:flex; gap:8px">
                <a href="?p=manage&id=<?=$sid?>&tab=files&dir=<?=urlencode(dirname($fileRel)==='.'?'':dirname($fileRel))?>" class="btn">Batal</a>
                <button type="submit" form="save-form" class="btn primary">Simpan File</button>
            </div>
        </div>
        <div id="editor-container" class="editor-container"></div>
        <form id="save-form" method="post" action="?action=file_write" style="display:none">
            <input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>"><input type="hidden" name="path" value="<?=h($fileRel)?>">
            <input type="hidden" name="content" id="editor-hidden-input">
        </form>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js"></script>
        <script>
            window.monacoEditorInstance = null;
            require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs' }});
            require(['vs/editor/editor.main'], function() {
                const extMap = {'js':'javascript','ts':'typescript','php':'php','html':'html','css':'css','json':'json','py':'python','sh':'shell','xml':'xml','md':'markdown'};
                const lang = extMap['<?=$ext?>'] || 'plaintext';
                // Json_encode for 100% Data Transfer Safety (No broken emojis or spaces)
                const content = <?= json_encode($editing) ?>;
                window.monacoEditorInstance = monaco.editor.create(document.getElementById('editor-container'), {
                    value: content, language: lang, theme: 'vs-dark', automaticLayout: true, minimap: { enabled: true }, fontSize: 13, padding: { top: 16 }
                });
            });
            // Safe binding
            document.getElementById('save-form').addEventListener('submit', function() {
                if (window.monacoEditorInstance) { document.getElementById('editor-hidden-input').value = window.monacoEditorInstance.getValue(); }
            });
        </script>
        <?php return;
    }
    ?>

    <div class="flex-between" style="margin-bottom:24px">
        <div>
            <h1 style="font-size:24px; margin-bottom:6px"><?=h($s['name'])?></h1>
            <div style="display:flex; gap:16px; font-size:13px; color:var(--text-dim)">
                <span style="display:flex; align-items:center; gap:6px"><span class="status-dot <?=$run?'run':''?>"></span> <?=$run?'Online':'Offline'?></span>
                <span>PID: <?=proc_pid($s)?:'—'?></span>
            </div>
        </div>
        <div style="display:flex; gap:12px">
            <form method="post" action="?action=toggle"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>"><button class="btn <?=$run?'danger':'success'?>"><?=$run?icon('stop').' Matikan':icon('play').' Nyalakan'?></button></form>
            <button class="btn" onclick="document.getElementById('modal-restart').classList.add('active')">Restart</button>
        </div>
    </div>

    <!-- Restart form -->
    <form id="modal-restart" class="modal-overlay" method="post" action="?action=toggle" style="place-items:center; display:none">
        <input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>">
        <div class="card"><h3 style="margin-bottom:16px">Matikan Server?</h3><p class="text-dim">Klik OK untuk mematikan server, setelah itu Anda bisa menyalakannya kembali.</p><div style="display:flex;gap:8px;margin-top:16px"><button type="button" class="btn" onclick="document.getElementById('modal-restart').classList.remove('active')">Batal</button><button type="submit" class="btn primary">OK</button></div></div>
    </form>

    <div class="tabs">
        <a href="?p=manage&id=<?=$sid?>&tab=console" class="tab-btn <?=$tab==='console'?'active':''?>"><?=icon('terminal')?> Konsol</a>
        <a href="?p=manage&id=<?=$sid?>&tab=files" class="tab-btn <?=$tab==='files'?'active':''?>"><?=icon('folder')?> File Manager</a>
        <a href="?p=manage&id=<?=$sid?>&tab=config" class="tab-btn <?=$tab==='config'?'active':''?>"><?=icon('settings')?> Konfigurasi</a>
    </div>

    <?php if ($tab === 'console'): ?>
        <div class="console-box">
            <div id="terminal" class="console-output"></div>
            <div class="console-input-wrap" onclick="if(!document.getElementById('cli-input').disabled) document.getElementById('cli-input').focus()">
                <span style="color:var(--primary); font-weight:bold">❯</span>
                <input id="cli-input" class="console-input" type="text" placeholder="<?=$run?'Ketik perintah (Enter untuk kirim)...':'Server offline. Nyalakan server terlebih dahulu.'?>" autocomplete="off" <?=$run?'':'disabled'?>>
            </div>
        </div>
        <div class="flex-between" style="margin-top:12px">
            <button id="btn-autoscroll" class="btn btn-sm" onclick="toggleScroll()">Berhenti Gulir otomatis</button>
            <form method="post" action="?action=clear_log"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>"><button class="btn btn-sm">Bersihkan Log</button></form>
        </div>

        <script id="raw-log-data" type="application/json"><?= json_encode(tail_log(log_file($s))) ?></script>

        <script>
            const term = document.getElementById('terminal'); const cli = document.getElementById('cli-input');
            let autoScroll = true; let history = JSON.parse(localStorage.getItem('cmd_history')||'[]'); let histPos = history.length;

            function toggleScroll() {
                autoScroll = !autoScroll;
                document.getElementById('btn-autoscroll').innerText = autoScroll ? 'Berhenti Gulir otomatis' : 'Lanjutkan Gulir otomatis';
                if(autoScroll) term.scrollTop = term.scrollHeight;
            }

            // Fungsi Engine Canggih: Emulasi Terminal Asli! (Progress Bar, ANSI Colors, dll)
            function parseTerminalStream(text) {
                // 1. Tangani Carriage Return (\r) layaknya terminal asli (NPM Spinner, Progress Bar dll)
                let lines = text.split('\n');
                for (let i = 0; i < lines.length; i++) {
                    let segments = lines[i].split('\r');
                    lines[i] = segments[segments.length - 1]; // Timpa segmen sebaris (\r magic)
                }
                let cleanText = lines.join('\n');

                // 2. Escape HTML biar aman dari XSS
                let html = cleanText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

                // 3. Konversi Kode Warna Mesin (ANSI Colors) ke CSS HTML!
                const colors = {
                    '30': 'black', '31': '#ef4444', '32': '#10b981', '33': '#eab308', '34': '#3b82f6', '35': '#ec4899', '36': '#06b6d4', '37': 'white',
                    '90': '#8b949e', '91': '#f87171', '92': '#34d399', '93': '#facc15', '94': '#60a5fa', '95': '#f472b6', '96': '#22d3ee', '97': '#ffffff'
                };
                let finalHtml = ''; let openTags = 0;
                const parts = html.split(/\x1B\[/);
                finalHtml += parts[0];
                for (let i = 1; i < parts.length; i++) {
                    const m = parts[i].match(/^([\d;]*)m(.*)/s);
                    if (!m) { finalHtml += '\\x1B[' + parts[i]; continue; }
                    const codes = m[1].split(';');
                    let css = '';
                    codes.forEach(c => {
                        if (c === '0' || c === '') { while(openTags > 0) { finalHtml += '</span>'; openTags--; } }
                        else if (colors[c]) css += `color:${colors[c]};`;
                        else if (c === '1') css += `font-weight:bold;`;
                    });
                    if (css) { finalHtml += `<span style="${css}">`; openTags++; }
                    finalHtml += m[2];
                }
                while(openTags > 0) { finalHtml += '</span>'; openTags--; }
                return finalHtml;
            }

            // Inisialisasi awal log
            if (term) {
                const initialText = JSON.parse(document.getElementById('raw-log-data').textContent);
                term.dataset.raw = initialText;
                term.innerHTML = parseTerminalStream(initialText);
                if(autoScroll) term.scrollTop = term.scrollHeight;
            }

            async function fetchLog() {
                try {
                    const res = await fetch(`?api=log&id=<?=$sid?>`, {cache:'no-store'}); const data = await res.json();
                    if(data.ok && data.log && term.dataset.raw !== data.log) {
                        term.dataset.raw = data.log;
                        term.innerHTML = parseTerminalStream(data.log);
                        if(autoScroll) term.scrollTop = term.scrollHeight;
                    }
                } catch(e) {}
            }
            setInterval(fetchLog, 1500); // Polling log real-time

            if(cli) {
                // Focus input when clicking anywhere on the console box, unless selecting text
                const consoleBox = document.querySelector('.console-box');
                if(consoleBox) {
                    consoleBox.addEventListener('click', () => {
                        if (!cli.disabled && window.getSelection().toString() === '') {
                            cli.focus();
                        }
                    });
                }

                cli.addEventListener('keydown', async (e) => {
                    if (e.key === 'ArrowUp') { if (histPos > 0) { histPos--; cli.value = history[histPos]; } e.preventDefault(); }
                    if (e.key === 'ArrowDown') { if (histPos < history.length - 1) { histPos++; cli.value = history[histPos]; } else { histPos = history.length; cli.value = ''; } e.preventDefault(); }
                    if (e.key === 'Enter') {
                        const cmd = cli.value.trim(); if (cmd === '') return;
                        history.push(cmd); if(history.length > 50) history.shift(); localStorage.setItem('cmd_history', JSON.stringify(history)); histPos = history.length;
                        cli.disabled = true; const fd = new FormData(); fd.append('id', <?=$sid?>); fd.append('cmd', cmd);
                        try { await fetch('?api=command', {method:'POST', body:fd}); cli.value = ''; setTimeout(fetchLog, 300); } catch(err) {}
                        cli.disabled = false; cli.focus();
                    }
                });
            }
        </script>
    <?php endif; ?>

    <?php if ($tab === 'files'):
        $base = server_dir($s); $currentPath = safe_join($base, $dirRel); if (!is_dir($currentPath)) $currentPath = $base;
        $entries = scandir($currentPath) ?: []; $folders = []; $files = [];
        foreach($entries as $e){
            if($e==='.') continue; if($e==='..' && $currentPath === $base) continue;
            $p = $currentPath . '/' . $e; $isDir = is_dir($p); $stat = stat($p);
            if ($e === '..') {
                $parent = dirname($dirRel); $folders = array_merge([['name'=>'..', 'rel'=>$parent==='.'?'':$parent, 'is_dir'=>true, 'size'=>'—', 'date'=>'—']], $folders);
            } else {
                $item = ['name'=>$e, 'rel'=>ltrim($dirRel.'/'.$e,'/'), 'is_dir'=>$isDir, 'size'=>$isDir?'—':format_bytes((int)$stat['size']), 'date'=>date('Y-m-d H:i', $stat['mtime'])];
                if ($isDir) $folders[] = $item; else $files[] = $item;
            }
        }
        usort($folders, fn($a,$b) => $a['name']==='..' ? -1 : strnatcasecmp($a['name'], $b['name']));
        usort($files, fn($a,$b) => strnatcasecmp($a['name'], $b['name']));
        $merged = array_merge($folders, $files); $encDir = h($dirRel);
    ?>
        <div style="display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap; justify-content:space-between">
            <div style="display:flex;gap:8px; align-items:center">
                <div style="display:flex;gap:8px; align-items:center">
                    <input type="file" id="file-upload-input" class="input" style="padding:6px;width:200px">
                    <button class="btn primary" type="button" onclick="startChunkedUpload()">Upload File</button>
                    <span id="upload-progress-text" style="display:none; font-weight:700; color:var(--primary); font-size:13px; min-width:40px">0%</span>
                </div>
                <!-- Spacer -->
                <div style="width:10px"></div>
                <form method="post" action="?action=create_item" style="display:flex;gap:8px">
                    <input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>"><input type="hidden" name="dir" value="<?=$encDir?>">
                    <select name="type" class="input" style="width:auto; padding:6px"><option value="file">File Baru</option><option value="dir">Folder Baru</option></select>
                    <input type="text" name="name" placeholder="Nama Baru..." required class="input" style="width:140px; padding:6px">
                    <button class="btn">Buat</button>
                </form>
            </div>
            <div style="display:flex;gap:8px" id="bulk-actions" style="display:none">
                <button class="btn primary" onclick="bulkAction('compress')" style="display:none" id="btn-bulk-zip"><?=icon('archive')?> ZIP Selected</button>
                <button class="btn danger" onclick="bulkAction('delete')" style="display:none" id="btn-bulk-del">Hapus Selected</button>
            </div>
        </div>

        <div class="card" style="padding:0; overflow-x:auto">
            <table class="table">
                <thead><tr><th style="width:40px"><input type="checkbox" id="chk-all"></th><th>Nama File</th><th>Ukuran</th><th>Terakhir Diubah</th><th style="text-align:right">Aksi</th></tr></thead>
                <tbody>
                <?php foreach($merged as $item): $isZip = str_ends_with(strtolower($item['name']), '.zip'); ?>
                    <tr>
                        <td><?php if($item['name']!=='..'): ?><input type="checkbox" class="file-chk" value="<?=h($item['rel'])?>"><?php endif; ?></td>
                        <td>
                            <a href="?p=manage&id=<?=$sid?>&<?=($item['is_dir']?'tab=files&dir=':'tab=editor&file=').urlencode($item['rel'])?>" style="display:flex;align-items:center;gap:10px;font-weight:500">
                                <?=$item['is_dir']?icon('folder'):($isZip?icon('archive'):icon('file'))?> <?=h($item['name'])?>
                            </a>
                        </td>
                        <td class="text-dim"><?=$item['size']?></td><td class="text-dim"><?=$item['date']?></td>
                        <td style="text-align:right; display:flex; justify-content:flex-end; gap:6px">
                            <?php if($item['name'] !== '..'): ?>
                                <?php if($isZip): ?>
                                    <form method="post" action="?action=zip_extract" style="margin:0"><input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>"><input type="hidden" name="path" value="<?=h($item['rel'])?>"><button class="btn btn-sm primary">Unzip</button></form>
                                <?php endif; ?>
                                <button class="btn btn-sm" onclick="renameItem('<?=h($item['name'])?>')"><?=icon('edit')?></button>
                                <?php if(!$item['is_dir']): ?><a href="?action=file_download&id=<?=$sid?>&path=<?=urlencode($item['rel'])?>" class="btn btn-sm">Unduh</a><?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; if(count($merged)===1 && $merged[0]['name']==='..') echo '<tr><td colspan="5" style="text-align:center" class="text-dim">Direktori Kosong</td></tr>'; ?>
                </tbody>
            </table>
        </div>

        <form id="form-bulk" method="post" style="display:none">
            <input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>"><input type="hidden" name="dir" value="<?=$encDir?>">
            <input type="hidden" name="paths" id="bulk-paths">
            <input type="hidden" name="name" id="bulk-zip-name">
        </form>
        <form id="form-rename" method="post" action="?action=rename" style="display:none">
            <input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>"><input type="hidden" name="dir" value="<?=$encDir?>">
            <input type="hidden" name="old" id="rn-old"><input type="hidden" name="new" id="rn-new">
        </form>

        <script>
            // --- Logika Canggih: CHUNKED UPLOAD BYPASS LIMIT PHP ---
            async function startChunkedUpload() {
                const input = document.getElementById('file-upload-input');
                if (!input.files || input.files.length === 0) return alert('Silakan pilih file terlebih dahulu.');
                const file = input.files[0];
                const btn = event.currentTarget;
                const progress = document.getElementById('upload-progress-text');

                btn.disabled = true;
                progress.style.display = 'inline-block';
                progress.innerText = '0%';

                const csrf = <?=json_encode($csrf)?>;
                const serverId = <?=$sid?>;
                const dir = <?=json_encode($dirRel)?>;

                const chunkSize = 1024 * 1024 * 1; // 1MB chunks (Sangat Aman Untuk Bypass 2MB Upload Limit!)
                const totalChunks = Math.ceil(file.size / chunkSize);

                for (let i = 0; i < totalChunks; i++) {
                    const start = i * chunkSize;
                    const end = Math.min(file.size, start + chunkSize);
                    const chunk = file.slice(start, end);

                    const fd = new FormData();
                    fd.append('csrf', csrf); fd.append('id', serverId); fd.append('dir', dir);
                    fd.append('file_name', file.name); fd.append('chunk_index', i); fd.append('total_chunks', totalChunks);
                    fd.append('chunk_data', chunk);

                    try {
                        const res = await fetch('?action=file_upload_chunk', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Server error');
                        progress.innerText = Math.round(((i + 1) / totalChunks) * 100) + '%';
                    } catch(e) {
                        alert('Upload gagal ditengah jalan: ' + e.message);
                        btn.disabled = false; progress.style.display = 'none'; return;
                    }
                }
                progress.innerText = 'Selesai!';
                setTimeout(() => window.location.reload(), 500);
            }

            const chks = document.querySelectorAll('.file-chk'); const chkAll = document.getElementById('chk-all');
            const btnDel = document.getElementById('btn-bulk-del'); const btnZip = document.getElementById('btn-bulk-zip');
            function updateBulk() {
                const checked = Array.from(chks).filter(c=>c.checked).length;
                btnDel.style.display = checked > 0 ? 'inline-flex' : 'none'; btnZip.style.display = checked > 0 ? 'inline-flex' : 'none';
            }
            chks.forEach(c => c.addEventListener('change', updateBulk));
            if(chkAll) chkAll.addEventListener('change', e => { chks.forEach(c => c.checked = e.target.checked); updateBulk(); });

            function bulkAction(type) {
                const paths = Array.from(chks).filter(c=>c.checked).map(c=>c.value);
                if(paths.length === 0) return;
                const form = document.getElementById('form-bulk');
                document.getElementById('bulk-paths').value = JSON.stringify(paths);
                if(type === 'delete') {
                    if(!confirm('Hapus ' + paths.length + ' item terpilih?')) return;
                    form.action = '?action=bulk_delete'; form.submit();
                } else if(type === 'compress') {
                    const name = prompt('Nama file ZIP:', 'archive.zip'); if(!name) return;
                    document.getElementById('bulk-zip-name').value = name; form.action = '?action=zip_compress'; form.submit();
                }
            }
            function renameItem(oldName) {
                const newName = prompt('Nama baru:', oldName);
                if(newName && newName !== oldName) {
                    document.getElementById('rn-old').value = `<?=$dirRel?($dirRel.'/'):''?>${oldName}`;
                    document.getElementById('rn-new').value = newName;
                    document.getElementById('form-rename').submit();
                }
            }
        </script>
    <?php endif; ?>

    <?php if ($tab === 'config'): ?>
        <div class="grid-2">
            <div class="card">
                <h3 style="margin-bottom:20px">Konfigurasi Server</h3>
                <form method="post" action="?action=save_server">
                    <input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>">
                    <label class="text-dim" style="font-size:12px; display:block; margin-bottom:6px">Nama Server</label>
                    <input class="input" name="name" value="<?=h($s['name'])?>" style="margin-bottom:16px">
                    <label class="text-dim" style="font-size:12px; display:block; margin-bottom:6px">Perintah Startup</label>
                    <textarea class="input" name="command" rows="3" style="margin-bottom:16px"><?=h($s['command'])?></textarea>
                    <label class="text-dim" style="font-size:12px; display:block; margin-bottom:6px">Direktori Kerja (Path Lengkap)</label>
                    <input class="input" name="cwd" value="<?=h($s['cwd'])?>" style="margin-bottom:16px">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:24px"><input type="checkbox" name="autostart" <?=$s['autostart']?'checked':''?>> Jalankan otomatis saat panel hidup</label>
                    <button class="btn primary w-full">Simpan Perubahan</button>
                </form>
            </div>
            <div class="card" style="border-color:rgba(239,68,68,0.2)">
                <h3 style="color:var(--danger); margin-bottom:16px">Hapus Server</h3>
                <p class="text-dim" style="font-size:13px; margin-bottom:24px; line-height:1.5">Tindakan ini akan mematikan proses dan <b>menghapus seluruh file</b> di dalam direktori server secara permanen. Data yang hilang tidak dapat dikembalikan.</p>
                <form method="post" action="?action=delete" onsubmit="return confirm('PERINGATAN FINAL!\nSemua file dalam direktori ini akan dihapus permanen. Lanjutkan?')">
                    <input type="hidden" name="csrf" value="<?=$csrf?>"><input type="hidden" name="id" value="<?=$sid?>">
                    <button class="btn danger" style="width:100%">Ya, Hapus Server Permanen</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <?php
}

function login_page(): string { ob_start(); ?>
<!doctype html><html><head><meta charset="utf-8"><title>Login - <?=APP_NAME?></title><?=css()?></head><body style="display:grid;place-items:center"><div class="card" style="width:360px">
<h2 style="margin-bottom:8px; display:flex; align-items:center; gap:8px"><?=icon('server')?> MiloPanel</h2><p class="text-dim" style="margin-bottom:24px; font-size:13px">Masuk untuk mengakses panel.</p>
<form method="post" action="?action=login"><input type="hidden" name="csrf" value="<?=h(csrf())?>">
<input class="input" type="password" name="password" placeholder="Password Admin" style="margin-bottom:20px" autofocus required>
<button class="btn primary" style="width:100%">Masuk</button></form></div></body></html><?php return (string)ob_get_clean(); }

function install_page(): string { ob_start(); ?>
<!doctype html><html><head><meta charset="utf-8"><title>Install - <?=APP_NAME?></title><?=css()?></head><body style="display:grid;place-items:center"><div class="card" style="width:400px">
<h2 style="margin-bottom:8px">Setup Panel</h2><p class="text-dim" style="margin-bottom:24px; font-size:13px">Buat password admin pertama kali.</p>
<form method="post" action="?action=install"><input type="hidden" name="csrf" value="<?=h(csrf())?>">
<label class="text-dim" style="font-size:12px; display:block; margin-bottom:6px">Username</label><input class="input" name="username" value="admin" readonly style="margin-bottom:16px">
<label class="text-dim" style="font-size:12px; display:block; margin-bottom:6px">Password Baru</label><input class="input" type="password" name="password" minlength="8" required style="margin-bottom:24px">
<button class="btn primary" style="width:100%">Mulai Gunakan Panel</button></form></div></body></html><?php return (string)ob_get_clean(); }

// MAIN LAYOUT RENDERER
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h(APP_NAME)?></title><?=css()?></head><body>
<script>
    // Memuat preferensi sidebar secara instan sebelum halaman dirender untuk menghindari kedipan.
    const isSidebarCollapsed = localStorage.getItem('milopanel_sidebar') === '1';
</script>
<div class="layout" id="app-layout">
    <script>
        if(isSidebarCollapsed) document.getElementById('app-layout').classList.add('collapsed');
    </script>
    <aside class="sidebar">
        <div class="sidebar-head">
            <?=icon('server')?> <span style="color:var(--primary)">Milo</span>Panel
        </div>
        <div class="nav-menu">
            <a href="?p=dashboard" class="nav-item <?=$page==='dashboard'?'active':''?>"><?=icon('grid')?> Dashboard</a>
            <a href="?p=servers" class="nav-item <?=$page==='servers'?'active':''?>"><?=icon('server')?> Server List</a>
        </div>
        <div style="margin-top:auto; padding:16px; border-top:1px solid var(--border)">
            <a href="?action=logout" class="nav-item" style="color:var(--danger)">Keluar Akses</a>
        </div>
    </aside>
    <div class="main-wrapper">
        <header class="topbar">
            <button class="icon-btn" onclick="toggleSidebar()"><?=icon('menu')?></button>
            <div style="font-size:14px; font-weight:500; color:var(--text-dim)">
                MiloPanel <span style="margin:0 8px">/</span> <?=ucfirst($page)?> <?php if(isset($_GET['id']) && $server_row = server_row((int)$_GET['id'])) echo '<span style="margin:0 8px">/</span> <span style="color:var(--text)">'.h($server_row['name']).'</span>'; ?>
            </div>
        </header>
        <main class="content-area">
            <?php
                if($page === 'dashboard') render_dashboard();
                elseif($page === 'servers') render_servers();
                elseif($page === 'manage') render_manage();
            ?>
        </main>
    </div>
</div>
<script>
    function toggleSidebar() {
        const layout = document.getElementById('app-layout');
        layout.classList.toggle('collapsed');
        localStorage.setItem('milopanel_sidebar', layout.classList.contains('collapsed') ? '1' : '0');
    }
</script>
</body></html>

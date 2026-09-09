#  MiloPanel

> **A lightweight PHP control panel for managing Linux processes** — without Docker, Wings, database servers, or systemd.
>
> **Panel kontrol PHP ringan untuk mengelola proses Linux** — tanpa Docker, Wings, database server, atau systemd.

<p align="center">
  <a href="https://github.com/gattrealdev/milopanel">
    <img src="https://img.shields.io/badge/GitHub-gattrealdev%2Fmilopanel-181717?style=flat-square&logo=github" alt="GitHub">
  </a>
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.1+">
  <img src="https://img.shields.io/badge/Platform-Linux%20%7C%20Termux-black?style=flat-square&logo=linux&logoColor=white" alt="Linux / Termux">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
</p>

---

## 📋 Table of Contents | Daftar Isi

- [About / Tentang](#about--tentang)
- [Why MiloPanel? / Kenapa MiloPanel?](#why-milopanel--kenapa-milopanel)
- [Features / Fitur](#features--fitur)
- [Requirements / Persyaratan](#requirements--persyaratan)
- [Quick Start / Mulai Cepat](#quick-start--mulai-cepat)
- [Configuration / Konfigurasi](#configuration--konfigurasi)
- [Troubleshooting / Pemecahan Masalah](#troubleshooting--pemecahan-masalah)
- [Security / Keamanan](#security--keamanan)
- [Comparison / Perbandingan](#comparison--perbandingan)
- [License](#license)

---

## About / Tentang

### English
MiloPanel is a simple, self-contained web control panel built with PHP for managing Linux processes. It provides an intuitive web interface for:

- Creating and deleting servers
- Starting, stopping, and restarting processes
- Monitoring process status and PIDs
- Real-time log viewing
- File management with security protections
- Server configuration (command, working directory, autostart)
- Host system information (PHP version, OS, RAM, disk, load average)
- JSON API endpoints for programmatic access

**No Docker, no database server, no complex dependencies required.**

### Indonesian
MiloPanel adalah panel kontrol web sederhana berbasis PHP untuk mengelola proses Linux. Menyediakan antarmuka web intuitif untuk:

- Membuat dan menghapus server
- Menjalankan, menghentikan, dan me-restart proses
- Memantau status proses dan PID
- Melihat log real-time
- Mengelola file dengan proteksi keamanan
- Mengatur konfigurasi server (command, working directory, autostart)
- Informasi sistem host (versi PHP, OS, RAM, disk, load average)
- Endpoint API JSON untuk akses terprogram

**Tanpa Docker, tanpa database server, tanpa dependency yang kompleks.**

---

## Why MiloPanel? / Kenapa MiloPanel?

### English
If you just need to run a few applications on a small VPS or development machine, installing a full control panel stack (Docker + Wings + database + extra services) can be overkill. MiloPanel takes a simpler approach:

```
Browser → MiloPanel → Node.js / Python / PHP / Java / Shell Scripts
```

All processes run directly with your system user—no containers, no isolation layers.

**Perfect for:**
- Small VPS instances
- Development/testing environments
- Running bots and microservices
- Termux on Android
- Learning process management

### Indonesian
Jika Anda hanya perlu menjalankan beberapa aplikasi di VPS kecil atau mesin development, memasang stack panel kontrol lengkap (Docker + Wings + database + service tambahan) bisa terasa berlebihan. MiloPanel mengambil pendekatan yang lebih sederhana:

```
Browser → MiloPanel → Node.js / Python / PHP / Java / Shell Scripts
```

Semua proses berjalan langsung dengan user sistem Anda—tanpa container, tanpa isolation layer.

**Cocok untuk:**
- Instansi VPS kecil
- Environment development/testing
- Menjalankan bot dan microservices
- Termux di Android
- Belajar process management

---

## Features / Fitur

| Feature | Deskripsi |
|---------|-----------|
| 🖥️ **Server Management** | Create, configure, start, stop, and restart multiple servers |
| 📊 **Process Monitoring** | Real-time status, PID tracking, and resource monitoring |
| 📝 **Live Logs** | Stream server output directly to the web interface |
| 📁 **File Manager** | Browse, create, edit, and delete files with path-traversal protection |
| ⚙️ **Auto-start** | Configure servers to start automatically |
| 🏠 **Host Information** | Display system info: PHP version, OS, RAM, disk usage, load average |
| 🔌 **JSON API** | Programmatic access to servers, status, logs, and telemetry |
| 🔐 **Admin Auth** | Secure login with PHP password hashing and CSRF protection |
| ⚡ **Zero Dependencies** | No Composer, Node.js, MariaDB, Redis, Docker, or systemd required |

---

## Requirements / Persyaratan

- **PHP 8.1** or newer
- **No database extension required** — uses SQLite automatically created on first run
- **POSIX shell (`sh`)** for process launching on Linux/Termux
- **Git** (for installation/updates)

### Dependency Table | Tabel Dependency

| Component | Required | Notes |
|-----------|----------|-------|
| PHP | ✅ Yes | 8.1+ |
| PHP CLI | ✅ Yes | Required for process execution |
| Linux / Termux | ✅ Yes | Tested on Ubuntu, Debian, Termux |
| Shell (`sh`) | ✅ Yes | POSIX shell for command launching |
| Git | ⚠️ Recommended | For installation and updates |
| Curl | ⚠️ Recommended | For API calls |
| Docker | ❌ No | — |
| Wings | ❌ No | — |
| MariaDB / MySQL | ❌ No | — |
| Redis | ❌ No | — |
| Composer | ❌ No | — |
| Node.js | ❌ No | — (for MiloPanel itself) |

---

## Quick Start / Mulai Cepat

### For Ubuntu / Debian
```bash
# Install dependencies
sudo apt update
sudo apt install php php-cli git curl -y

# Clone repository
git clone https://github.com/gattrealdev/milopanel.git
cd milopanel

# Make script executable
chmod +x start.sh

# Start the panel
bash start.sh
```

Then open: **http://127.0.0.1:8080**

### For Termux (Android)
```bash
# Update packages
pkg update
pkg upgrade

# Install dependencies
pkg install php git curl -y

# Clone repository
git clone https://github.com/gattrealdev/milopanel.git
cd milopanel

# Start the panel
bash start.sh
```

Then open: **http://127.0.0.1:8080**

### Manual PHP Execution
```bash
cd milopanel
php -S 0.0.0.0:8080 router.php
```

**On first access**, you'll be prompted to create an admin password. MiloPanel will automatically create:
- `data/settings.json` — Configuration file
- `data/servers.json` — Server definitions
- `servers/<server-slug>/` — Server working directories
- `logs/<server-slug>.log` — Server output logs

---

## Configuration / Konfigurasi

### Changing Port

```bash
# Using start.sh
PORT=9000 bash start.sh

# Manual PHP
php -S 0.0.0.0:9000 router.php
```

Access at: **http://127.0.0.1:9000**

### Directory Structure

```
milopanel/
├── data/                    # Configuration storage
│   ├── settings.json       # Panel settings & admin password hash
│   └── servers.json        # Server definitions
├── servers/                # Server working directories
│   ├── my-node-app/
│   ├── my-python-app/
│   └── ...
├── logs/                   # Server output logs
│   ├── my-node-app.log
│   └── ...
├── router.php              # Main router
├── index.php               # Web UI
├── start.sh                # Start script
└── README.md               # This file
```

### Creating a Server

1. Log in to the panel
2. Click "Add Server"
3. Configure:
   - **Name**: Server identifier
   - **Working Directory**: Where commands execute (e.g., `servers/my-app`)
   - **Command**: Process to run (e.g., `node app.js`, `python3 app.py`)
   - **Autostart**: Enable to start on panel boot

**Example Node.js Server:**
```
Name:             My Node App
Working Directory: servers/my-node-app
Command:          node app.js
```

Directory layout:
```
servers/my-node-app/
├── app.js
├── package.json
└── node_modules/
```

### Server Commands Examples

| Runtime | Command |
|---------|---------|
| Node.js | `node app.js` or `npm start` |
| Python | `python3 app.py` |
| PHP | `php app.php` or `php -S 0.0.0.0:3000` |
| Java | `java -Xmx1G -jar server.jar` |
| Shell | `bash start.sh` |
| Generic | Any executable available in your environment |

---

## Deployment / Deployment

### Running on a VPS

1. Ensure firewall allows port 8080 (or your chosen port):
```bash
# Using UFW
sudo ufw allow 8080/tcp
sudo ufw status
```

2. Run MiloPanel:
```bash
php -S 0.0.0.0:8080 router.php
```

3. Access from: **http://YOUR_VPS_IP:8080**

### Running in the Background

Using `nohup`:
```bash
nohup php -S 0.0.0.0:8080 router.php > milopanel.log 2>&1 &
```

Using `tmux` (persistent across terminal sessions):
```bash
# Install tmux
pkg install tmux  # Termux
# or: sudo apt install tmux  # Linux

# Create a new session
tmux new -s milopanel

# In the session, run:
php -S 0.0.0.0:8080 router.php

# Detach: Ctrl+B, then D
# Reattach: tmux attach -t milopanel
```

### Production Setup with Reverse Proxy (Nginx)

MiloPanel can run on `127.0.0.1:8080` behind Nginx for HTTPS and domain management:

```
Internet → panel.example.com → Nginx (HTTPS) → 127.0.0.1:8080 → MiloPanel
```

**Nginx config example:**
```nginx
server {
    listen 443 ssl http2;
    server_name panel.example.com;
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

**⚠️ Important:** Do not expose PHP's built-in server directly to the internet in production. Always use a reverse proxy (Nginx/Apache) with SSL/TLS.

---

## Viewing Logs / Melihat Log

### From the Panel
Click on any server to see its live log output.

### From the Terminal
```bash
# Real-time log
tail -f logs/my-server.log

# Last 100 lines
tail -n 100 logs/my-server.log

# Search logs
grep "error" logs/my-server.log
```

---

## Backup & Restore / Backup & Restore

### Backup
```bash
tar -czf milopanel-backup-$(date +%Y%m%d).tar.gz data/ servers/ logs/
ls -lh milopanel-backup-*.tar.gz
```

### Restore
```bash
tar -xzf milopanel-backup-YYYYMMDD.tar.gz
```

**Always backup before major updates!**

### Update
```bash
cd milopanel
tar -czf backup-$(date +%Y%m%d).tar.gz data/ servers/ logs/
git pull
bash start.sh
```

---

## Troubleshooting / Pemecahan Masalah

### "php: command not found"

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install php php-cli -y
php -v
```

**Termux:**
```bash
pkg install php -y
php -v
```

### "start.sh: Permission denied"
```bash
chmod +x start.sh
./start.sh
# or
bash start.sh
```

### Port already in use

Check which process is using the port:
```bash
ss -ltnp | grep :8080
```

Use a different port:
```bash
PORT=9000 bash start.sh
```

### Cannot access from the internet

Ensure the server is listening on `0.0.0.0`, not `127.0.0.1`:
```bash
ss -ltnp | grep 8080
```

Check firewall:
```bash
sudo ufw status
sudo ufw allow 8080/tcp
```

Check VPS provider's security group/firewall settings.

### Panel dies after closing SSH session

Use `nohup` or `tmux` (see [Running in the Background](#running-in-the-background)).

### Server process not starting

1. Check the command syntax in the panel
2. Verify the working directory exists
3. Check logs: `tail -f logs/your-server.log`
4. Ensure the user running PHP has permission to execute the command

---

## Security / Keamanan

### Important Notes | Catatan Penting

⚠️ **MiloPanel does NOT provide:**
- Container isolation (no Docker, cgroups, namespaces)
- Resource limits or process sandboxing
- Multi-user access control

✅ **MiloPanel DOES provide:**
- Admin login with password hashing
- CSRF protection on form submissions
- Path-traversal protection in file manager
- Basic SQLite-based configuration storage

### Security Recommendations | Rekomendasi Keamanan

1. **Run as non-root user:**
   ```bash
   # Create a dedicated user
   sudo useradd -m -s /bin/bash milo
   
   # Give them permission to manage servers
   sudo chown -R milo:milo /path/to/milopanel
   
   # Run as milo
   sudo -u milo bash start.sh
   ```

2. **Use a strong admin password** — set it during first-run setup

3. **Restrict network access:**
   - On VPS: Use UFW or security groups
   - In development: Use `127.0.0.1` instead of `0.0.0.0`

4. **Use HTTPS in production** — always proxy through Nginx/Apache with SSL/TLS

5. **Do NOT trust untrusted users** — anyone with panel access can execute arbitrary commands

6. **Backup regularly** — store backups securely off-server

---

## Comparison / Perbandingan

### MiloPanel vs Pterodactyl

| Feature | MiloPanel | Pterodactyl |
|---------|-----------|-----------|
| 🖥️ Web Panel | ✅ | ✅ |
| 📦 Docker Support | ❌ | ✅ |
| 🎮 Wings Daemon | ❌ | ✅ |
| 🗄️ Database Server | ❌ | ✅ |
| 🔒 Container Isolation | ❌ | ✅ |
| 📁 File Manager | ✅ | ✅ |
| ▶️ Start/Stop Processes | ✅ | ✅ |
| 📊 Live Logs | ✅ | ✅ |
| 📱 Termux Support | ✅ | ❌ |
| 📍 Proot Support | ✅ | ❌ |
| 🧹 Minimal Dependencies | ✅ | ❌ |
| 👥 Multi-user Hosting | Basic | ✅ |
| 📈 Resource Isolation | ❌ | ✅ |
| 🎮 Game Hosting | ❌ | ✅ |

### Use MiloPanel if you:
- Need a simple panel for a few applications
- Run bots, microservices, or web apps
- Have limited VPS resources
- Use Termux on Android
- Prefer minimal dependencies
- Don't need container isolation

### Use Pterodactyl if you:
- Need container isolation and resource limits
- Offer game server hosting
- Run complex multi-tenant infrastructure
- Require advanced process management
- Need production-grade reliability features

---

## Limitations / Batasan

MiloPanel is intentionally simple. It is **not** intended as:
- A container/virtualization platform
- A replacement for Docker or Kubernetes
- A game hosting platform
- A security sandbox
- An enterprise control panel

**Its purpose:** Manage a few Linux processes from a web panel with minimal dependencies.

---

## Autostart Feature

MiloPanel can save autostart configurations for servers. This is useful for restarting applications when the panel boots.

⚠️ **Note:** The panel's autostart is **not** a replacement for proper process management in production.

For production services requiring high reliability, use:
- `systemd` (Linux)
- `Supervisor`
- `PM2` (Node.js)

---

## JSON API Endpoints

Some panel data is available via JSON API endpoints. Exact routes may change between versions; check the source code for the current version's API routes.

**Example endpoints:**
- `/api/servers` — List all servers
- `/api/servers/{id}/status` — Get server status
- `/api/servers/{id}/logs` — Get server logs
- `/api/host/info` — Get host information

---

## License

See the [LICENSE](./LICENSE) file for details.

---

## Author / Penulis

**MiloDev**

- GitHub: [@gattrealdev](https://github.com/gattrealdev)
- Repository: [gattrealdev/milopanel](https://github.com/gattrealdev/milopanel)

---

<p align="center">
  <strong>MiloPanel</strong><br>
  Simple process management for Linux & Termux.<br>
  <em>Manajemen proses sederhana untuk Linux & Termux.</em>
</p>

<p align="center">
  <a href="https://github.com/gattrealdev/milopanel/issues">Report an Issue</a> •
  <a href="https://github.com/gattrealdev/milopanel/discussions">Discussions</a> •
  <a href="https://github.com/gattrealdev">More Projects</a>
</p>

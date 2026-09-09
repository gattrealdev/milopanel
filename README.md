# MiloPanel

A compact, self-contained PHP control panel inspired by the operational workflow of Pterodactyl, designed for environments where a full Docker/Wings stack is impractical.

## What it does
- First-run web installer; creates SQLite automatically.
- Admin login with PHP password hashing and CSRF protection.
- Multiple managed servers with independent working directories.
- Start / stop / restart and PID tracking on Linux/Termux.
- Live log tail in the dashboard.
- File manager with path-traversal protection and in-panel text editing.
- Server command, working-directory, and autostart configuration.
- Host telemetry: PHP/platform, load average, RAM and disk.
- JSON endpoints for servers, status, logs and host telemetry.
- No Composer, Node.js, MariaDB, Redis, Docker or systemd required.

## Requirements
- PHP 8.1 or newer.
- No database extension is required.
- On Linux/Termux, a POSIX shell (`sh`) is used for process launching.

## Run
```bash
cd milopanel
php -S 0.0.0.0:8080 router.php
```
Then open `http://127.0.0.1:8080`.

Termux:
```bash
bash start.sh
```

On first access, create the admin password. The panel will create:
- `data/settings.json`
- `data/servers.json`
- `servers/<server-slug>/`
- `logs/<server-slug>.log`

## Server commands
Commands run as the same OS user that runs PHP. The panel does **not** grant root privileges and does not sandbox processes like Pterodactyl Wings + containers.

Examples:
```text
node app.js
python3 app.py
php -S 0.0.0.0:3000
java -Xmx1G -jar server.jar
```

## Important architectural difference
MiloPanel is a lightweight process/file control panel, not a drop-in replacement for Pterodactyl Wings. It intentionally avoids Docker, systemd, privileged networking, cgroups and container isolation so it can run in constrained Linux environments such as Proot/Termux.

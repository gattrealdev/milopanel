MiloPanel

PHP control panel ringan untuk menjalankan dan mengelola aplikasi Linux — tanpa Docker, Wings, database server, atau systemd.

MiloPanel dibuat untuk kebutuhan sederhana: menjalankan beberapa aplikasi dari satu web panel tanpa harus memasang stack control panel yang besar.

Cocok untuk VPS kecil, Linux, Termux, dan environment development/testing.

<p align="center">
  <a href="https://github.com/gattrealdev/milopanel">
    <img src="https://img.shields.io/badge/GitHub-gattrealdev%2Fmilopanel-181717?style=flat-square&logo=github" alt="GitHub">
  </a>
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Linux-Termux-black?style=flat-square&logo=linux" alt="Linux / Termux">
</p>---

Tentang

MiloPanel adalah control panel sederhana berbasis PHP untuk mengelola proses aplikasi di Linux.

Panel menyediakan interface web untuk:

- membuat dan menghapus server
- menjalankan proses
- menghentikan proses
- restart proses
- melihat status dan PID
- melihat log
- mengelola file
- mengatur autostart
- melihat informasi host

Tidak ada Docker atau daemon tambahan yang diperlukan.

Command dijalankan langsung oleh sistem operasi menggunakan user yang menjalankan MiloPanel.

«Penting: MiloPanel bukan container manager dan bukan pengganti Pterodactyl untuk kebutuhan isolation atau hosting multi-user.»

---

Kenapa MiloPanel?

Kalau kebutuhanmu cuma menjalankan beberapa aplikasi di VPS atau Termux, memasang Docker + Wings + database + service tambahan bisa terasa berlebihan.

MiloPanel mengambil pendekatan yang lebih sederhana:

Browser
   │
   ▼
MiloPanel
   │
   ├── Node.js
   ├── Python
   ├── PHP
   ├── Java
   ├── Bot
   └── Shell Script

Semua proses berjalan langsung di environment Linux.

---

Fitur

Server

Setiap server memiliki:

- Nama
- Working directory
- Command
- Status
- PID
- Autostart

Operasi:

Start
Stop
Restart
Status

Live Log

Output proses dapat disimpan ke:

logs/<server>.log

Contoh:

tail -f logs/my-node-app.log

File Manager

Mendukung operasi dasar:

- membuat file
- membuat folder
- membaca file
- mengedit file
- menghapus file
- menghapus folder

File Manager juga memiliki validasi path untuk mencegah akses keluar dari direktori yang diizinkan.

Host Information

Dashboard dapat menampilkan informasi seperti:

- PHP version
- OS/platform
- RAM
- Disk
- Load average
- status proses
- PID

JSON API

Beberapa informasi panel tersedia melalui endpoint JSON.

Endpoint dapat berubah antar versi. Lihat source code versi yang sedang digunakan untuk daftar route API.

---

Dependency

MiloPanel sengaja dibuat dengan dependency minimum.

Komponen| Kebutuhan
PHP| 8.1+
PHP CLI| Ya
Linux| Ya
Shell| "sh"
Git| Untuk instalasi/update
Curl| Disarankan
Docker| Tidak
Wings| Tidak
MariaDB| Tidak
Redis| Tidak
Composer| Tidak
Node.js| Tidak untuk panel

---

Instalasi

Ubuntu / Debian

sudo apt update
sudo apt install php php-cli git curl -y

Cek PHP:

php -v

Kemudian clone repository:

git clone https://github.com/gattrealdev/milopanel.git
cd milopanel

Beri permission pada script:

chmod +x start.sh

---

Instalasi di Termux

MiloPanel juga dapat dijalankan langsung di Termux tanpa root.

Update package:

pkg update
pkg upgrade

Install kebutuhan dasar:

pkg install php git curl -y

Clone repository:

git clone https://github.com/gattrealdev/milopanel.git
cd milopanel

Jalankan:

bash start.sh

Atau:

./start.sh

Untuk akses dari HP yang sama:

http://127.0.0.1:8080

---

Menjalankan MiloPanel

Menggunakan "start.sh"

Cara paling mudah:

bash start.sh

Script akan menjalankan server PHP dan menampilkan informasi akses.

Port default dapat digunakan langsung:

http://127.0.0.1:8080

Untuk VPS:

http://IP-VPS:8080

Menggunakan PHP secara langsung

Kalau tidak ingin menggunakan script:

php -S 0.0.0.0:8080 router.php

Kemudian buka:

http://IP-VPS:8080

---

Mengubah Port

Port dapat ditentukan melalui environment variable:

PORT=9000 bash start.sh

Kemudian panel tersedia di:

http://IP-VPS:9000

Atau jalankan PHP secara manual:

php -S 0.0.0.0:9000 router.php

---

Login Pertama

Saat pertama kali dibuka, MiloPanel akan menjalankan setup awal.

Ikuti halaman instalasi untuk membuat akun/password administrator.

Konfigurasi panel akan disimpan di:

data/
├── settings.json
└── servers.json

Jangan menghapus "data/" jika konfigurasi masih diperlukan.

---

Membuat Server

Setelah login, buat server baru dan tentukan:

Name
Working Directory
Command
Autostart

Contoh aplikasi Node.js:

Name:
My Node App

Working Directory:
servers/my-node-app

Command:
node app.js

Strukturnya dapat terlihat seperti:

servers/
└── my-node-app/
    ├── app.js
    ├── package.json
    └── node_modules/

---

Contoh Command

Node.js

node app.js

atau:

npm start

Python

python3 app.py

PHP

php app.php

PHP Web Server

php -S 0.0.0.0:3000

Java

java -Xmx1G -jar server.jar

Shell

bash start.sh

MiloPanel pada dasarnya dapat menjalankan command yang tersedia pada environment Linux tersebut.

---

Struktur Direktori

Struktur utama:

milopanel/
├── data/
│   ├── settings.json
│   └── servers.json
│
├── servers/
│   └── <server-directory>/
│
├── logs/
│   └── <server>.log
│
├── start.sh
├── router.php
├── index.php
└── README.md

"data/"

Menyimpan konfigurasi MiloPanel.

"servers/"

Working directory aplikasi yang dikelola panel.

"logs/"

Log output proses.

---

Log

Log server berada di:

logs/

Contoh:

logs/my-node-app.log

Lihat log:

tail -f logs/my-node-app.log

100 baris terakhir:

tail -n 100 logs/my-node-app.log

---

Autostart

MiloPanel dapat menyimpan konfigurasi autostart untuk server.

Fitur ini berguna ketika aplikasi perlu dijalankan kembali melalui mekanisme panel.

Namun, autostart panel bukan pengganti process manager.

Untuk service production yang membutuhkan reliability tinggi, gunakan process manager yang sesuai dengan environment, misalnya:

systemd
Supervisor
PM2

---

Menjalankan di Background

Jika MiloPanel dijalankan melalui SSH:

php -S 0.0.0.0:8080 router.php

proses dapat berhenti ketika session SSH berakhir.

Cara sederhana menggunakan "nohup":

nohup php -S 0.0.0.0:8080 router.php > milopanel.log 2>&1 &

Cek proses:

ps aux | grep 'php -S'

Lihat log:

tail -f milopanel.log

Di Termux, alternatif yang lebih cocok untuk proses yang ingin tetap berjalan setelah aplikasi Termux ditutup adalah menggunakan session manager seperti "tmux".

Install:

pkg install tmux

Buat session:

tmux new -s milopanel

Jalankan panel:

php -S 0.0.0.0:8080 router.php

Detach dengan:

CTRL + B
kemudian D

Masuk kembali:

tmux attach -t milopanel

---

Akses dari Internet

Untuk VPS, jalankan MiloPanel pada:

php -S 0.0.0.0:8080 router.php

Pastikan port firewall terbuka.

Jika menggunakan UFW:

sudo ufw allow 8080/tcp
sudo ufw status

Kemudian akses:

http://IP-VPS:8080

Jika provider VPS memiliki firewall/security group sendiri, port tersebut juga harus diizinkan di dashboard provider.

Termux

Secara default, Termux lebih cocok untuk akses lokal:

http://127.0.0.1:8080

Jika ingin mengakses Termux dari perangkat lain, pastikan perangkat berada pada jaringan yang sama dan server listen pada:

0.0.0.0

---

Domain & HTTPS

Untuk VPS, MiloPanel dapat ditempatkan di belakang reverse proxy.

Contoh:

Internet
   │
   ▼
panel.example.com
   │
   ▼
Nginx
   │
   ▼
127.0.0.1:8080
   │
   ▼
MiloPanel

MiloPanel tetap berjalan di:

127.0.0.1:8080

Nginx menangani koneksi dari internet dan HTTPS.

Untuk penggunaan publik, jangan mengandalkan PHP built-in server sebagai server web production utama.

---

Firewall

Cek port:

ss -ltnp | grep 8080

Cek UFW:

sudo ufw status

Buka port:

sudo ufw allow 8080/tcp

Jika port tetap tidak dapat diakses, periksa firewall/security group VPS.

---

Troubleshooting

"php: command not found"

Ubuntu/Debian:

sudo apt update
sudo apt install php php-cli -y

Termux:

pkg install php -y

Cek:

php -v

---

"start.sh: Permission denied"

Jalankan:

chmod +x start.sh

Kemudian:

./start.sh

Atau:

bash start.sh

---

Port sudah digunakan

Cek:

ss -ltnp | grep :8080

Jika tersedia, gunakan port lain:

PORT=9000 bash start.sh

---

Tidak bisa diakses dari internet

Pastikan server dijalankan menggunakan:

0.0.0.0

bukan:

127.0.0.1

Cek:

ss -ltnp | grep 8080

Kemudian periksa:

sudo ufw status

dan firewall/security group dari provider VPS.

---

Panel mati setelah SSH ditutup

Gunakan "nohup":

nohup php -S 0.0.0.0:8080 router.php > milopanel.log 2>&1 &

atau process manager.

Untuk Termux, "tmux" dapat digunakan:

pkg install tmux
tmux new -s milopanel

---

Keamanan

MiloPanel menjalankan command menggunakan permission user OS yang menjalankan PHP.

Misalnya PHP dijalankan sebagai:

root

maka command server juga dapat memiliki permission root.

Jangan menjalankan MiloPanel sebagai root jika tidak diperlukan.

Gunakan user Linux khusus untuk panel dan aplikasi.

Contoh:

milo

bukan:

root

MiloPanel juga tidak memberikan container isolation.

Tidak ada:

- Docker isolation
- cgroups resource isolation
- namespace isolation
- sandbox VM

Karena itu, jangan memberikan akses panel kepada user yang tidak dipercaya.

---

Backup

Data utama:

data/
servers/
logs/

Backup:

tar -czf milopanel-backup.tar.gz data/ servers/ logs/

Cek:

ls -lh milopanel-backup.tar.gz

Restore:

tar -xzf milopanel-backup.tar.gz

Sebelum melakukan update besar, backup terlebih dahulu.

---

Update

Masuk ke directory:

cd milopanel

Backup:

tar -czf backup.tar.gz data/ servers/ logs/

Update source:

git pull

Kemudian jalankan kembali:

bash start.sh

Jika update mengubah struktur konfigurasi, ikuti perubahan yang tercantum pada repository/release terkait.

---

MiloPanel vs Pterodactyl

MiloPanel dan Pterodactyl memiliki target penggunaan yang berbeda.

Fitur| MiloPanel| Pterodactyl
PHP Panel| ✓| ✓
Docker| —| ✓
Wings| —| ✓
Database server| —| ✓
Container isolation| —| ✓
File Manager| ✓| ✓
Start / Stop| ✓| ✓
Log| ✓| ✓
Termux| ✓| —
Proot| ✓| —
Dependency minimal| ✓| —
Multi-user hosting| Dasar| ✓
Resource isolation| —| ✓

Gunakan MiloPanel jika

- membutuhkan panel sederhana
- menjalankan bot
- menjalankan aplikasi Node.js/Python/PHP
- menggunakan VPS kecil
- menggunakan Termux
- menggunakan Proot
- tidak ingin memasang Docker
- hanya membutuhkan process management dasar

Gunakan Pterodactyl jika

- membutuhkan container isolation
- membuat layanan game hosting
- membutuhkan resource limit yang lebih kompleks
- membutuhkan multi-user hosting
- membutuhkan infrastruktur production yang lebih lengkap

---

Batasan

MiloPanel sengaja dibuat sederhana. Karena itu, beberapa kemampuan control panel besar memang tidak menjadi bagian dari project ini.

MiloPanel tidak ditujukan sebagai:

- container platform
- virtualization platform
- replacement Docker
- replacement Kubernetes
- game hosting platform skala besar
- security sandbox

Tujuannya lebih sederhana:

«Menjalankan dan mengelola proses Linux dari web panel dengan dependency seminimal mungkin.»

---

License

Lihat file ""LICENSE"" (LICENSE) untuk informasi lisensi dan ketentuan penggunaan.

---

Author

MiloDev

GitHub:

"@gattrealdev" (https://github.com/gattrealdev)

Repository:

"gattrealdev/milopanel" (https://github.com/gattrealdev/milopanel)

---

<p align="center">
  <b>MiloPanel</b><br>
  Simple process management for Linux & Termux.
</p>

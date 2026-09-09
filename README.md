MiloPanel

MiloPanel adalah control panel PHP ringan dan mandiri yang terinspirasi dari alur kerja Pterodactyl. MiloPanel dirancang untuk menjalankan dan mengelola berbagai aplikasi/server tanpa membutuhkan Docker, Wings, MariaDB, Redis, atau systemd.

«Dibuat oleh: MiloDev»

---

Daftar Isi

- "Tentang MiloPanel" (#tentang-milopanel)
- "Fitur" (#fitur)
- "Persyaratan" (#persyaratan)
- "Struktur Direktori" (#struktur-direktori)
- "Instalasi" (#instalasi)
- "Menjalankan MiloPanel" (#menjalankan-milopanel)
- "Menggunakan start.sh" (#menggunakan-startsh)
- "Akses dari Internet" (#akses-dari-internet)
- "Login dan Instalasi Pertama" (#login-dan-instalasi-pertama)
- "Membuat Server" (#membuat-server)
- "Contoh Command Server" (#contoh-command-server)
- "Autostart" (#autostart)
- "File Manager" (#file-manager)
- "Log Server" (#log-server)
- "API Endpoint" (#api-endpoint)
- "Menjalankan di VPS" (#menjalankan-di-vps)
- "Menjalankan di Termux" (#menjalankan-di-termux)
- "Menjalankan dengan Background Mode" (#menjalankan-dengan-background-mode)
- "Domain dan HTTPS" (#domain-dan-https)
- "Troubleshooting" (#troubleshooting)
- "Perbedaan dengan Pterodactyl" (#perbedaan-dengan-pterodactyl)
- "Keamanan" (#keamanan)
- "Lisensi" (#lisensi)

---

Tentang MiloPanel

MiloPanel adalah panel manajemen server berbasis PHP yang dibuat untuk lingkungan yang membutuhkan panel sederhana, ringan, dan mudah dijalankan.

MiloPanel dapat digunakan untuk mengelola aplikasi seperti:

- Node.js
- Python
- PHP
- Java
- server game
- bot
- aplikasi CLI
- dan program Linux lainnya

Panel menjalankan proses menggunakan user sistem yang menjalankan PHP.

MiloPanel bukan container manager. Oleh karena itu, proses yang dijalankan panel mempunyai permission sesuai dengan user OS yang menjalankan panel.

---

Fitur

Dashboard

MiloPanel menyediakan dashboard untuk melihat informasi server secara terpusat.

Informasi yang tersedia antara lain:

- status server
- PID proses
- penggunaan resource
- informasi PHP
- informasi platform
- load average
- RAM
- disk

Manajemen Server

Setiap server dapat mempunyai:

- nama server
- working directory
- command
- konfigurasi autostart
- status proses
- PID

Operasi yang tersedia:

- Start
- Stop
- Restart
- Status

Live Logs

MiloPanel dapat menampilkan log proses secara langsung sehingga lebih mudah melakukan debugging aplikasi.

File Manager

File manager memungkinkan kamu untuk:

- melihat file
- membuat file
- mengedit file teks
- membuat folder
- menghapus file/folder

MiloPanel juga mempunyai perlindungan terhadap path traversal.

SQLite Otomatis

MiloPanel tidak membutuhkan instalasi database server.

Konfigurasi dan data panel disimpan secara lokal.

JSON API

MiloPanel menyediakan endpoint JSON untuk beberapa informasi seperti:

- server
- status
- log
- host telemetry

Ringan

MiloPanel tidak membutuhkan:

- Docker
- Pterodactyl Wings
- MariaDB
- Redis
- Composer
- Node.js untuk menjalankan panel
- systemd

---

Persyaratan

Minimal:

- PHP 8.1 atau lebih baru
- Linux / Termux
- POSIX shell ("sh") untuk menjalankan proses
- "curl" disarankan untuk mendeteksi IP publik melalui "start.sh"

Untuk VPS Ubuntu/Debian:

sudo apt update
sudo apt install php php-cli curl git -y

Periksa versi PHP:

php -v

Contoh:

PHP 8.3.x

---

Struktur Direktori

Setelah MiloPanel dijalankan dan diinisialisasi, beberapa direktori akan dibuat secara otomatis:

milopanel/
├── data/
│   ├── settings.json
│   └── servers.json
│
├── servers/
│   └── <server-slug>/
│
├── logs/
│   └── <server-slug>.log
│
├── start.sh
├── router.php
└── index.php

"data/settings.json"

Menyimpan konfigurasi panel.

"data/servers.json"

Menyimpan konfigurasi server yang dikelola MiloPanel.

"servers/"

Berisi working directory server yang dibuat melalui panel.

"logs/"

Berisi log masing-masing server.

---

Instalasi

1. Clone Repository

Clone repository MiloPanel:

git clone https://github.com/gattrealdev/milopanel.git

Masuk ke direktori:

cd milopanel

---

2. Pastikan PHP Terinstall

Cek:

php -v

Jika belum terinstall di Ubuntu/Debian:

sudo apt update
sudo apt install php php-cli -y

Kemudian cek kembali:

php -v

---

3. Berikan Permission start.sh

Jika repository memiliki "start.sh":

chmod +x start.sh

---

Menjalankan MiloPanel

Ada dua cara menjalankan MiloPanel.

Cara 1 — PHP Built-in Server

Jalankan:

php -S 0.0.0.0:8080 router.php

Kemudian buka:

http://IP-VPS:8080

Contoh:

http://123.123.123.123:8080

Untuk penggunaan lokal:

http://127.0.0.1:8080

---

Menggunakan start.sh

Cara yang direkomendasikan adalah menggunakan:

bash start.sh

atau jika sudah executable:

./start.sh

"start.sh" dapat digunakan untuk:

- mendeteksi IP publik VPS
- menentukan port
- mengecek apakah port sedang digunakan
- menjalankan MiloPanel
- menampilkan alamat akses panel

Contoh output:

╔════════════════════════════════════╗
║          MILO PANEL                ║
╚════════════════════════════════════╝

[+] Public IP : 123.123.123.123
[+] Port      : 8080
[+] Starting  : http://123.123.123.123:8080

[+] MiloPanel berjalan...

Kemudian akses:

http://123.123.123.123:8080

---

Mengubah Port

Port default dapat diatur melalui environment variable.

Contoh menggunakan port "9000":

PORT=9000 bash start.sh

Kemudian panel dapat diakses melalui:

http://IP-VPS:9000

Jika "start.sh" menggunakan konfigurasi default "8080", cukup jalankan:

bash start.sh

---

Akses dari Internet

Jika MiloPanel dijalankan di VPS:

php -S 0.0.0.0:8080 router.php

maka server akan listen pada semua interface IPv4.

Namun, port tersebut tetap harus diizinkan oleh firewall VPS.

UFW

Cek status:

sudo ufw status

Buka port "8080":

sudo ufw allow 8080/tcp

Kemudian:

sudo ufw reload

Jika provider VPS memiliki firewall/security group sendiri, port tersebut juga harus dibuka dari dashboard provider.

---

Login dan Instalasi Pertama

Pada akses pertama, MiloPanel akan menampilkan proses instalasi awal.

Buat password administrator.

Setelah instalasi selesai, MiloPanel akan membuat data seperti:

data/settings.json
data/servers.json
servers/
logs/

Jangan menghapus "data/settings.json" atau "data/servers.json" jika masih membutuhkan konfigurasi panel.

---

Membuat Server

Setelah login ke dashboard:

1. Buka dashboard MiloPanel.
2. Pilih menu untuk membuat server.
3. Masukkan nama server.
4. Tentukan working directory.
5. Masukkan command yang ingin dijalankan.
6. Atur autostart jika diperlukan.
7. Simpan konfigurasi.

Contoh server Node.js:

Nama:
My Node App

Working Directory:
servers/my-node-app

Command:
node app.js

---

Contoh Command Server

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

Shell Script

bash start.sh

---

Autostart

MiloPanel dapat menyimpan konfigurasi autostart untuk server yang dikelola.

Autostart berguna ketika kamu ingin proses tertentu dijalankan secara otomatis sesuai mekanisme yang disediakan panel.

Perlu diperhatikan bahwa autostart server di MiloPanel tidak sama dengan systemd service.

Untuk kebutuhan production yang sangat penting, gunakan supervisor/service manager yang sesuai dengan sistem operasi.

---

File Manager

File Manager MiloPanel digunakan untuk mengelola file di dalam working directory server.

Fitur yang tersedia dapat mencakup:

- membuat file
- membuat folder
- membaca file
- mengedit file
- menghapus file/folder

Contoh struktur:

servers/
└── my-node-app/
    ├── app.js
    ├── package.json
    ├── .env
    └── public/

Hindari menyimpan secret penting di file yang dapat diakses pengguna yang tidak dipercaya.

---

Log Server

Setiap server memiliki file log.

Contoh:

logs/my-node-app.log

Untuk melihat log melalui terminal:

tail -f logs/my-node-app.log

Untuk melihat beberapa baris terakhir:

tail -n 100 logs/my-node-app.log

---

API Endpoint

MiloPanel menyediakan endpoint JSON untuk beberapa fungsi panel.

Endpoint yang tersedia bergantung pada implementasi versi MiloPanel yang sedang digunakan.

Secara umum API digunakan untuk mengambil informasi seperti:

- daftar server
- status server
- log server
- informasi host

Jika ingin melihat endpoint yang tersedia pada versi yang sedang digunakan, periksa source code route/API pada repository.

---

Menjalankan di VPS

Berikut contoh instalasi pada Ubuntu.

1. Update Sistem

sudo apt update
sudo apt upgrade -y

2. Install Dependency

sudo apt install git curl php php-cli -y

3. Clone MiloPanel

git clone https://github.com/gattrealdev/milopanel.git

4. Masuk Direktori

cd milopanel

5. Jalankan

bash start.sh

Jika berhasil, akan muncul alamat seperti:

[+] Public IP : 123.123.123.123
[+] Port      : 8080
[+] Starting  : http://123.123.123.123:8080

Buka alamat tersebut dari browser.

---

Menjalankan di Termux

Update package:

pkg update
pkg upgrade

Install PHP:

pkg install php curl git -y

Clone repository:

git clone https://github.com/gattrealdev/milopanel.git

Masuk:

cd milopanel

Jalankan:

bash start.sh

Atau secara manual:

php -S 0.0.0.0:8080 router.php

---

Menjalankan dengan Background Mode

Jika MiloPanel dijalankan langsung di SSH:

php -S 0.0.0.0:8080 router.php

kemudian terminal/SSH ditutup, proses dapat ikut berhenti.

Untuk menjalankan proses di background:

nohup php -S 0.0.0.0:8080 router.php > milopanel.log 2>&1 &

Cek proses:

ps aux | grep 'php -S'

Lihat log:

tail -f milopanel.log

Dengan metode ini, proses tidak bergantung langsung pada terminal interaktif.

---

Domain dan HTTPS

Untuk production, disarankan menggunakan:

Internet
   │
   ▼
Domain
   │
   ▼
Nginx
   │
   ▼
MiloPanel
   │
   ▼
PHP

Contoh:

panel.example.com

bukan:

http://123.123.123.123:8080

DNS

Buat record DNS:

Type: A
Name: panel
Value: IP-VPS

Sehingga:

panel.example.com

mengarah ke IP VPS.

Reverse Proxy

Nginx dapat digunakan sebagai reverse proxy menuju MiloPanel yang berjalan pada port internal, misalnya:

127.0.0.1:8080

Dengan demikian pengguna cukup mengakses:

https://panel.example.com

tanpa harus mengetik port "8080".

---

Troubleshooting

Port sudah digunakan

Jika muncul pesan bahwa port sudah digunakan, cek:

ss -ltnp | grep :8080

atau:

lsof -i :8080

Kemudian gunakan port lain:

PORT=9000 bash start.sh

---

Tidak bisa diakses dari Internet

Pastikan MiloPanel listen pada:

0.0.0.0

bukan:

127.0.0.1

Cek:

ss -ltnp | grep 8080

Kemudian periksa firewall:

sudo ufw status

Buka port:

sudo ufw allow 8080/tcp

Jangan lupa periksa firewall/security group dari provider VPS.

---

"php: command not found"

Install PHP:

sudo apt update
sudo apt install php php-cli -y

Cek:

php -v

---

"start.sh: Permission denied"

Jalankan:

chmod +x start.sh

Kemudian:

./start.sh

Atau tanpa mengubah permission:

bash start.sh

---

Panel mati setelah SSH ditutup

Jika menjalankan:

php -S 0.0.0.0:8080 router.php

langsung dari SSH, proses dapat berhenti ketika session berakhir.

Gunakan:

nohup php -S 0.0.0.0:8080 router.php > milopanel.log 2>&1 &

atau gunakan process manager seperti "systemd", "supervisord", atau solusi lain yang sesuai dengan environment VPS.

---

Perbedaan dengan Pterodactyl

MiloPanel bukan pengganti penuh Pterodactyl + Wings.

Perbedaan utama:

Fitur| MiloPanel| Pterodactyl
PHP| Ya| Ya
Docker| Tidak diperlukan| Ya
Wings| Tidak| Ya
MariaDB| Tidak diperlukan| Umumnya digunakan
Redis| Tidak diperlukan| Digunakan untuk fitur tertentu
Container isolation| Tidak| Ya
cgroups| Tidak| Ya
Root daemon| Tidak diperlukan| Wings membutuhkan konfigurasi sistem
Cocok untuk Termux/Proot| Ya| Tidak ideal
Ringan| Ya| Lebih kompleks
File Manager| Ya| Ya
Server start/stop| Ya| Ya
Live log| Ya| Ya

MiloPanel sengaja menggunakan arsitektur yang lebih sederhana agar dapat dijalankan pada environment terbatas seperti:

- VPS kecil
- Linux biasa
- Termux
- Proot
- environment tanpa Docker
- environment tanpa systemd

---

Keamanan

MiloPanel menjalankan command sebagai user OS yang menjalankan PHP.

Artinya, jika PHP dijalankan sebagai:

root

maka command server juga berpotensi berjalan sebagai:

root

Ini memiliki risiko keamanan yang tinggi.

Untuk production, sebaiknya jalankan MiloPanel menggunakan user Linux khusus yang memiliki permission seminimal mungkin.

Contoh:

milo

bukan:

root

Jangan memberikan akses root kepada pengguna yang tidak dipercaya.

MiloPanel juga tidak menyediakan sandbox seperti container Docker.

Jika sebuah server menjalankan command berbahaya, command tersebut mempunyai akses sesuai permission user OS yang menjalankannya.

---

Catatan Production

PHP built-in server sangat praktis untuk development, testing, VPS kecil, dan penggunaan internal.

Untuk deployment production yang serius, disarankan menggunakan:

Nginx
   ↓
PHP / Application
   ↓
MiloPanel

serta process manager yang sesuai untuk memastikan service dapat restart secara otomatis.

Pastikan juga:

- gunakan HTTPS
- gunakan password administrator yang kuat
- batasi permission filesystem
- jangan menjalankan panel sebagai root jika tidak diperlukan
- buka hanya port yang diperlukan
- lakukan backup direktori "data/"
- jangan mengekspos file konfigurasi/secret
- gunakan firewall

---

Backup

Data penting MiloPanel berada pada direktori:

data/

Backup sederhana:

tar -czf milopanel-backup.tar.gz data/ servers/ logs/

Untuk melihat file backup:

ls -lh milopanel-backup.tar.gz

Restore:

tar -xzf milopanel-backup.tar.gz

---

Update MiloPanel

Sebelum update, backup data terlebih dahulu.

tar -czf backup.tar.gz data/ servers/ logs/

Kemudian:

git pull

Setelah update selesai:

bash start.sh

Jika terdapat perubahan konfigurasi atau struktur data pada versi baru, ikuti catatan release dari repository.

---

Lisensi

Silakan lihat file "LICENSE" pada repository untuk informasi lisensi dan ketentuan penggunaan.

---

Kredit

MiloPanel

Dibuat oleh:

MiloDev

Repository:

https://github.com/gattrealdev/milopanel

Terima kasih telah menggunakan MiloPanel.

# Flow running:
- composer install
- npm install
    - Ini untuk install tools/dependencies, termasuk gulp.
- npm run build
    - ini akan jalankan gulp task untuk generate asset + database.sql.
- docker compose -f docker-compose.dev.yml up

# Dependencies di project: 
Dependency project ini ada beberapa layer.

**1. Runtime App**
Ini yang dibutuhkan supaya aplikasi PHP-nya jalan:

```text
PHP
Apache
MariaDB/MySQL
CodeIgniter 4
```

Di Dockerfile:

```dockerfile
FROM php:8.2-apache
```

Jadi container app isinya PHP `8.2` + Apache.

DB di compose dev:

```yaml
image: mariadb:10.5
```

**2. PHP Extensions**
Di [Dockerfile](opensourcepos/Dockerfile):

```dockerfile
docker-php-ext-install mysqli bcmath intl gd
```

Artinya app butuh extension:

```text
mysqli  -> koneksi MySQL/MariaDB
bcmath  -> kalkulasi angka presisi
intl    -> locale/internationalization
gd      -> image processing
```

Di validasi install juga ada requirement lain seperti:

```text
openssl
mbstring
curl
xml
json
```

Biasanya sudah ada di base PHP image.

**3. PHP Dependencies**
Ini dari [composer.json](opensourcepos/composer.json):

```text
codeigniter4/framework       -> framework utama
dompdf/dompdf                -> generate PDF
ezyang/htmlpurifier          -> sanitize HTML
laminas/laminas-escaper      -> escaping/security
paragonie/random_compat      -> random compatibility
picqer/php-barcode-generator -> generate barcode
tamtamchik/namecase          -> formatting nama
```

Dev dependencies PHP:

```text
phpunit/phpunit              -> testing
php-cs-fixer                 -> formatting/coding standard
fakerphp/faker               -> fake data untuk test/dev
kint-php/kint                -> debug dump
predis/predis                -> Redis client optional/test
```

Install-nya menghasilkan:

```text
vendor/
```

Mirip dependency folder Composer.

**4. Frontend Dependencies**
Ini dari [package.json](opensourcepos/package.json):

```text
bootstrap / bootstrap5
bootswatch / bootswatch5
jquery
jquery-ui
jquery-validation
bootstrap-table
chartist
dompurify
jspdf
html2canvas
clipboard
file-saver
tableexport.jquery.plugin
```

Ini dipakai untuk UI, table, chart, export, print/PDF-ish behavior, validasi frontend, dan sanitasi.

Install-nya menghasilkan:

```text
node_modules/
```

**5. Build Tooling**
Masih dari `package.json`:

```text
gulp
gulp-concat
gulp-clean-css
gulp-uglify
gulp-rename
gulp-header
gulp-inject
```

Gulp dipakai untuk:
- copy asset frontend ke `public/`
- build/minify CSS/JS
- generate `app/Database/database.sql`

Command-nya:

```bash
npm install
npm run build
```

**6. Docker Dependencies**
Untuk development dengan Docker:

```text
Docker / OrbStack
Docker Compose
```

Services yang jalan:

```text
ospos -> PHP + Apache + app
mysql -> MariaDB 10.5
```

**Mental Model**
Kalau dibandingkan dengan Go:

```text
composer.json  ~= go.mod untuk backend PHP
vendor/        ~= dependency hasil composer install
package.json   ~= dependency frontend/build
node_modules/  ~= dependency hasil npm install
Dockerfile     ~= runtime image app
compose        ~= orchestration app + db
```

**Flow Install Dev**
Urutan lengkapnya kira-kira:

```bash
composer install
npm install
npm run build
USERID=$(id -u) GROUPID=$(id -g) docker compose -f docker-compose.dev.yml up
```

Karena kamu tidak punya PHP/Composer lokal, `composer install` bisa dijalankan via Docker image yang sesuai project.

# Running tanpa docker
Untuk running app **tanpa Docker**, sementara MySQL tetap Docker, kamu perlu install ini di laptop:

```text
PHP 8.2 - 8.4
Composer
PHP extensions: intl, mysqli, bcmath, gd, curl, mbstring, xml, json, openssl
```

Node/npm sudah ada di laptop kamu, dan `vendor/`, `node_modules`, `database.sql` juga sudah ada. Yang belum ada sekarang:

```text
php
composer
```

**Install Di Mac**
Kalau pakai Homebrew:

```bash
brew install php composer
```

Cek:

```bash
php -v
composer --version
php -m | grep -E 'intl|mysqli|bcmath|gd|curl|mbstring|xml|openssl|json'
```

Kalau pakai PHP dari Homebrew, extension umum biasanya sudah ikut, tapi `intl` perlu dipastikan ada.

**Setup `.env` Untuk App Lokal**
Karena app jalan di laptop, DB host jangan `mysql`. Itu nama service Docker dan hanya valid dari container app.

Untuk app lokal, set ke port DB yang kamu expose ke host.

Kalau DB Docker expose ke `3306`:

```env
CI_ENVIRONMENT = development

database.default.hostname = '127.0.0.1'
database.default.database = 'ospos'
database.default.username = 'admin'
database.default.password = 'pointofsale'
database.default.DBDriver = 'MySQLi'
database.default.DBPrefix = 'ospos_'

database.development.hostname = '127.0.0.1'
database.development.database = 'ospos'
database.development.username = 'admin'
database.development.password = 'pointofsale'
database.development.DBDriver = 'MySQLi'
database.development.DBPrefix = 'ospos_'
```

Kalau DB kamu expose ke `3307`, tambahkan port:

```env
database.default.port = 3307
database.development.port = 3307
```

**Install Dependency Kalau Perlu**
Karena `vendor/` sudah ada, ini mungkin tidak perlu. Tapi kalau suatu saat hilang:

```bash
composer install
```

Frontend build kalau perlu:

```bash
npm install
npm run build
```

**Running App Lokal**
Dari root project:

```bash
php spark serve
```

Buka:

```text
http://localhost:8080
```

Login:

```text
admin
pointofsale
```

**Flow Singkat**
```bash
brew install php composer
```

Edit `.env` DB host ke:

```text
127.0.0.1
```

Lalu:

```bash
php spark serve
```

Kalau muncul error extension, cek:

```bash
php -m
```

Kalau muncul error koneksi DB, berarti host/port DB di `.env` belum cocok dengan port MariaDB Docker yang kamu expose.


# Caveats PHP
To enable PHP in Apache add the following to httpd.conf and restart Apache:
    LoadModule php_module /opt/homebrew/opt/php/lib/httpd/modules/libphp.so

    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

Finally, check DirectoryIndex includes index.php
    DirectoryIndex index.php index.html

The php.ini and php-fpm.ini file can be found in:
    /opt/homebrew/etc/php/8.5/

To start php now and restart at login:
  brew services start php
Or, if you don't want/need a background service you can just run:
  /opt/homebrew/opt/php/sbin/php-fpm --nodaemonize

--
To enable PHP in Apache add the following to httpd.conf and restart Apache:
    LoadModule php_module /opt/homebrew/opt/php@8.4/lib/httpd/modules/libphp.so

    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

Finally, check DirectoryIndex includes index.php
    DirectoryIndex index.php index.html

The php.ini and php-fpm.ini file can be found in:
    /opt/homebrew/etc/php/8.4/

php@8.4 is keg-only, which means it was not symlinked into /opt/homebrew,
because this is an alternate version of another formula.

If you need to have php@8.4 first in your PATH, run:
  echo 'export PATH="/opt/homebrew/opt/php@8.4/bin:$PATH"' >> /Users/mutiaratungga/.zshrc
  echo 'export PATH="/opt/homebrew/opt/php@8.4/sbin:$PATH"' >> /Users/mutiaratungga/.zshrc

For compilers to find php@8.4 you may need to set:
  export LDFLAGS="-L/opt/homebrew/opt/php@8.4/lib"
  export CPPFLAGS="-I/opt/homebrew/opt/php@8.4/include"

The following php@8.4 executables are shadowed by other linked Homebrew commands:
  pear (shadowed by /opt/homebrew/bin/pear from php)
  peardev (shadowed by /opt/homebrew/bin/peardev from php)
  pecl (shadowed by /opt/homebrew/bin/pecl from php)
  phar (shadowed by /opt/homebrew/bin/phar from php)
  phar.phar (shadowed by /opt/homebrew/bin/phar.phar from php)
  php (shadowed by /opt/homebrew/bin/php from php)
  php-cgi (shadowed by /opt/homebrew/bin/php-cgi from php)
  php-config (shadowed by /opt/homebrew/bin/php-config from php)
  php-fpm (shadowed by /opt/homebrew/sbin/php-fpm from php)
  phpdbg (shadowed by /opt/homebrew/bin/phpdbg from php)
  phpize (shadowed by /opt/homebrew/bin/phpize from php)
Running these by name will not invoke the version provided by this formula.
Run `brew link php@8.4` to switch the active version to this keg.
Disable this behaviour by setting `HOMEBREW_NO_PATH_SHADOW_CHECK=1`.
Hide these hints with `HOMEBREW_NO_ENV_HINTS=1` (see `man brew`).

To start php@8.4 now and restart at login:
  brew services start php@8.4
Or, if you don't want/need a background service you can just run:
  /opt/homebrew/opt/php@8.4/sbin/php-fpm --nodaemonize
==> Summary
🍺  /opt/homebrew/Cellar/php@8.4/8.4.22: 529 files, 107.7MB
==> Running `brew cleanup php@8.4`...
Disable this behaviour by setting `HOMEBREW_NO_INSTALL_CLEANUP=1`.
Hide these hints with `HOMEBREW_NO_ENV_HINTS=1` (see `man brew`).

# PHP Install
Itu karena PHP lokal kamu versi:

```text
PHP 8.5.7
```

Sedangkan dependency project ini belum support PHP 8.5. Mereka support sampai PHP 8.4.

Jangan jalankan `composer update` dulu. Itu bisa mengubah dependency dan malah bikin project melenceng dari lock file.

Yang benar: pakai PHP versi kompatibel.

**Opsi Paling Aman**
Install PHP 8.4 atau 8.3, lalu jalankan Composer dengan PHP itu.

Kalau pakai Homebrew, coba:

```bash
brew install php@8.4
```

Lalu cek binary-nya:

```bash
/opt/homebrew/opt/php@8.4/bin/php -v
```

Kalau Mac Intel, path bisa:

```bash
/usr/local/opt/php@8.4/bin/php -v
```

Jalankan composer pakai PHP 8.4:

```bash
/opt/homebrew/opt/php@8.4/bin/php $(which composer) install
```

Kalau Intel:

```bash
/usr/local/opt/php@8.4/bin/php $(which composer) install
```

**Kalau Mau Ganti Default PHP CLI Ke 8.4**
Apple Silicon biasanya:

```bash
brew unlink php
brew link --overwrite --force php@8.4
```

Tambahkan ke shell profile:

```bash
echo 'export PATH="/opt/homebrew/opt/php@8.4/bin:$PATH"' >> ~/.zshrc
echo 'export PATH="/opt/homebrew/opt/php@8.4/sbin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

Cek:

```bash
php -v
```
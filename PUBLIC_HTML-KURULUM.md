# CODEGA Finans - public_html kurulumu

Bu paket, hosting hesabinizdaki mevcut `public_html` klasorune dogrudan
acilacak sekilde duzenlenmistir.

## Yukleme

1. ZIP icindeki tum dosya ve klasorleri domainin mevcut `public_html`
   klasorune acin.
2. Sonuc yapisi soyle olmalidir:

```text
public_html/
  .htaccess
  public_html/
    index.php
    login.php
    admin/
    api/
    assets/
  inc/
  migrations/
  cli/
  manifest.json
```

3. Tarayicida `https://alan-adiniz.com/` adresini acin.

Kokteki `.htaccess`, temiz URL'leri otomatik olarak icerdeki
`public_html/` klasorune aktarir. Bu nedenle site adreslerinde
`/public_html` yazmaniz gerekmez.

## Veritabani ayari

Sunucuda `inc/config.local.example.php` dosyasini
`inc/config.local.php` olarak kopyalayin ve veritabani bilgilerinizi
guncelleyin:

```php
<?php
if (!defined('CF_DB_HOST')) define('CF_DB_HOST', 'localhost');
if (!defined('CF_DB_NAME')) define('CF_DB_NAME', 'veritabani_adi');
if (!defined('CF_DB_USER')) define('CF_DB_USER', 'veritabani_kullanicisi');
if (!defined('CF_DB_PASS')) define('CF_DB_PASS', 'veritabani_sifresi');
if (!defined('CF_APP_URL')) define('CF_APP_URL', 'https://alan-adiniz.com');
if (!defined('CF_DEBUG'))   define('CF_DEBUG', false);
```

## Ilk kurulum komutlari

SSH erisiminiz yoksa tarayicidan su adrese gidin:

```text
https://alan-adiniz.com/setup_admin.php
```

Bu sayfa yalnizca sistemde hic yonetici yokken ilk `superadmin`
hesabini olusturur. Hesap olustuktan sonra yeni hesap eklemeyi reddeder.

SSH erisiminiz varsa alternatif olarak:

```bash
cd /home/KULLANICI/domains/ALAN/public_html
/usr/local/php83/bin/php cli/migrate.php
/usr/local/php83/bin/php cli/add_admin.php "Ad Soyad" mail@alan.com "GUCLU-SIFRE" superadmin
```

PHP yolu hostinginize gore degisebilir. DirectAdmin kullaniyorsaniz
`/usr/local/php83/bin/php` yerine panelde gorunen PHP CLI yolunu kullanin.

## Guvenlik

`inc/`, `cli/`, `migrations/`, `storage/`, `backups/` ve `updates/`
klasorleri kok `.htaccess` tarafindan web erisimine kapatilir.

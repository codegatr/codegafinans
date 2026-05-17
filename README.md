# Codega Finans

PHP 8.3+ ile çalışan, veritabanlı bütçe ve tasarruf yönetimi web uygulaması.

## Özellikler

- Kullanıcı kayıt / giriş sistemi
- Aylık bütçe yönetimi
- Gelir-gider takibi ve kategori analizi
- Tasarruf hedefleri
- Borç kontrolü
- Güncel kur ekranı ve manuel kur güncelleme
- Akıllı uyarılar
- Mobil WebView ve PWA uyumlu arayüz

## Kurulum

1. Sunucuda PHP 8.3+ ve PDO eklentilerinin aktif olduğundan emin olun.
2. Dosyaları alan adınızın dizinine yükleyin.
3. Web kökünü `public` klasörüne yönlendirin.
4. Geliştirme için SQLite hazır gelir. İlk açılışta `storage/codega_finans.sqlite` oluşturulur.
5. MySQL kullanmak için `.env.example` dosyasını `.env` olarak kopyalayıp veritabanı bilgilerini girin.

## Varsayılan Çalıştırma

```bash
php -S localhost:8080 -t public
```

## MySQL Örneği

```env
APP_ENV=production
APP_URL=https://finans.codega.com.tr
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=codega_finans
DB_USERNAME=codega_user
DB_PASSWORD=strong_password
```

## WebView Notları

- Uygulama HTTPS altında yayınlanmalıdır.
- Android WebView için ana URL: `https://finans.codega.com.tr`
- Çerez ve local storage izinleri açık olmalıdır.
- Play Store yayını için gizlilik politikası sayfası eklenmesi önerilir.

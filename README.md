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
3. DirectAdmin uzerinde domain web koku varsayilan olarak `public_html` klasorudur. Bu repo da buna gore hazirlanmistir.
4. Geliştirme için SQLite hazır gelir. İlk açılışta `storage/codega_finans.sqlite` oluşturulur.
5. MySQL kullanmak için `.env.example` dosyasını `.env` olarak kopyalayıp veritabanı bilgilerini girin.

## Varsayılan Çalıştırma

```bash
php -S localhost:8080 -t public_html
```

DirectAdmin yapisi:

```text
domains/finans.codega.com.tr/
├── codegafinans/
│   ├── app/
│   ├── database/
│   ├── storage/
│   ├── public_html/
│   └── .env
└── public_html -> codegafinans/public_html
```

Alternatif olarak repo dogrudan domain kokune kurulacaksa yapi soyle olmalidir:

```text
domains/finans.codega.com.tr/
├── app/
├── database/
├── storage/
├── public_html/
└── .env
```

## Akilli Guncelleme

GitHub uzerinden tek tikla guncelleme icin `.env` icinde asagidaki alanlari doldurun:

```env
GITHUB_REPO=codegatr/codegafinans
GITHUB_BRANCH=main
UPDATE_ENABLED=true
UPDATE_TOKEN=uzun-rastgele-bir-token
UPDATE_ADMIN_EMAIL=admin@codega.com.tr
```

Panelden `/updates` sayfasina girip token ile guncelleme baslatabilirsiniz. Sunucuda `exec` fonksiyonu ve `git` komutu aktif olmalidir.

DirectAdmin SSH ile ilk kurulum ornegi:

```bash
cd ~/domains/finans.codega.com.tr
git clone -b main https://github.com/codegatr/codegafinans.git codegafinans
cd codegafinans
cp .env.example .env
mkdir -p storage
chmod 775 storage
cd ..
mv public_html public_html_backup_$(date +%Y%m%d%H%M%S)
ln -s codegafinans/public_html public_html
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

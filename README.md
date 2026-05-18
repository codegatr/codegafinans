# CODEGA Finans

> Bütçe ve Tasarruf Yönetim Platformu · `finans.codega.com.tr`

[![Versiyon](https://img.shields.io/badge/sürüm-1.0.0-2563eb)](https://github.com/codegatr/codegafinans/releases)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4)](#)
[![Lisans](https://img.shields.io/badge/lisans-Proprietary-red)](#)

CODEGA Finans, bireysel kullanıcılara yönelik bir kişisel finans yönetim uygulamasıdır.
Aylık bütçe, gelir-gider takibi, tasarruf hedefleri, borç kontrolü, TCMB döviz kurları
ve akıllı uyarılar tek panelde sunulur. Web tabanlıdır; **Play Store'a Android WebView
uygulaması** olarak yayınlanacak şekilde tasarlanmıştır.

---

## Özellikler

| Modül | Açıklama |
|---|---|
| 👤 Kullanıcı kayıt / giriş | bcrypt parola, brute-force koruması, oturum guvenliği |
| 🎯 Aylık bütçe | Genel ay ve kategori bazlı limit, %85+/%100 uyarısı |
| 💸 Gelir-Gider | Sınırsız işlem, kategori, filtre, sayfalama |
| 🐖 Tasarruf hedefleri | Hedef tutar, vade, depozit ile birikim takibi |
| 💳 Borç kontrolü | Taksit, faiz, vade, kısmi ödeme kaydı |
| 💱 TCMB kurları | 8 para birimi, 60 dk auto-refresh + manuel yenileme |
| 🔔 Akıllı uyarılar | Bütçe limiti, vadesi yaklaşan borç, hedef tamamlandı, kötüleşen nakit akışı |
| ⭐ Abonelik sistemi | 7 gün deneme, aylık/yıllık plan, manuel ödeme onayı |
| 🛡️ Yönetici paneli | Kullanıcı, abonelik, ödeme, plan, denetim, ayar yönetimi |
| ⬆️ Smart Update v5 | GitHub Release tabanlı tek-tıkla güncelleme + otomatik backup |
| 🌐 REST API | `/api/ping`, `/api/version`, `/api/update_check` (Android WebView için) |
| 📜 KVKK / Play uyumu | `/privacy.php` ve `/terms.php` sayfaları hazır |

---

## Teknik Yığın

- **PHP 8.3+** (strict_types, native enums, match)
- **MariaDB 10.4+** / MySQL 8 (PDO, utf8mb4)
- **Vanilla CSS + JS** (Bootstrap yok, framework bağımlılığı yok)
- **DirectAdmin + LiteSpeed** uyumlu (Mizan/CMiner desenleri)
- Modüler dosya yapısı: tek `cf_` prefix, prosedürel PHP, `inc/` çekirdek

---

## Dizin Yapısı

```
codegafinans/
├── manifest.json                # Single Source of Truth
├── inc/                         # Çekirdek (require pattern)
│   ├── version.php              # CF_VERSION, CF_TOTAL_MODULES
│   ├── config.php               # Genel ayarlar
│   ├── config.local.example.php # Sunucu özel ayar şablonu
│   ├── db.php                   # PDO singleton + t() yardımcısı
│   ├── functions.php            # csrf, money, e, redirect, audit…
│   ├── auth.php                 # Kullanıcı auth
│   ├── admin_auth.php           # Yönetici auth
│   ├── subscription.php         # Abonelik & ödeme akışı
│   ├── finance.php              # Aylık özet, kategori dağılımı, uyarılar
│   ├── rates.php                # TCMB XML fetch
│   ├── migrate.php              # Idempotent migration runner
│   ├── updater.php              # Smart Update v5
│   ├── header/footer/nav.php    # Kullanıcı layout
│   └── admin_header/footer/nav.php
├── migrations/
│   └── 001_initial.sql          # 18 tablo + seed kategori & plan
├── public_html/                 # Document root
│   ├── index.php, login.php, register.php, logout.php
│   ├── dashboard.php
│   ├── transactions.php
│   ├── budgets.php
│   ├── goals.php
│   ├── debts.php
│   ├── rates.php
│   ├── alerts.php
│   ├── subscription.php
│   ├── settings.php
│   ├── privacy.php, terms.php   # Play Store için zorunlu
│   ├── .htaccess                # HTTPS + güvenlik başlıkları
│   ├── admin/                   # Yönetici paneli
│   │   ├── login.php / logout.php
│   │   ├── index.php            # KPI dashboard
│   │   ├── users.php, subscriptions.php, plans.php
│   │   ├── payments.php, transactions.php
│   │   ├── updates.php          # Smart Update v5 UI
│   │   ├── logs.php, settings.php
│   │   └── .htaccess
│   ├── api/
│   │   ├── ping.php             # Sağlık
│   │   ├── version.php          # Sürüm
│   │   └── update_check.php     # Android force-update
│   └── assets/css, js, img
├── cli/
│   ├── cron.php                 # Günlük zamanlanmış görev
│   ├── add_admin.php            # İlk yöneticiyi ekle
│   └── migrate.php              # Manuel migration
├── storage/                     # Geçici dosyalar (deploy korur)
├── backups/                     # Smart Update yedekleri
├── updates/                     # ZIP extract dizini
└── docs/
```

---

## Kurulum (DirectAdmin / LiteSpeed)

### 1) Veritabanını oluştur

```sql
CREATE DATABASE codegaco_finans CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'codegaco_finans'@'localhost' IDENTIFIED BY 'GÜÇLÜ_ŞİFRE';
GRANT ALL PRIVILEGES ON codegaco_finans.* TO 'codegaco_finans'@'localhost';
FLUSH PRIVILEGES;
```

### 2) Dosyaları yükle

GitHub Release ZIP'ini açın ve sunucuda:

```
/home/<user>/domains/finans.codega.com.tr/
├── codegafinans/   ← BURAYA çıkarın (kök)
│   ├── inc/
│   ├── migrations/
│   ├── public_html/  ← DirectAdmin'in public_html'i bu klasörü göstermeli
```

> **Önemli:** DirectAdmin'in `Document Root`'unu **`codegafinans/public_html`** olarak ayarlayın
> veya mevcut `public_html`'i sembolik link yapın. Kök dizin doğrudan dışarıya açılmamalıdır.

### 3) Yerel ayar dosyasını oluştur

```bash
cd /home/<user>/domains/finans.codega.com.tr/codegafinans/inc
cp config.local.example.php config.local.php
nano config.local.php
```

İçerik:

```php
<?php
define('CF_DB_HOST', 'localhost');
define('CF_DB_NAME', 'codegaco_finans');
define('CF_DB_USER', 'codegaco_finans');
define('CF_DB_PASS', 'GÜÇLÜ_ŞİFRE');

// Smart Update için GitHub Personal Access Token (private repo ise)
define('CF_UPDATE_GH_TOKEN', 'github_pat_xxx');

// Üretimde false bırakın
define('CF_DEBUG', false);
```

### 4) Migration çalıştır

```bash
cd /home/<user>/domains/finans.codega.com.tr/codegafinans
/usr/local/php83/bin/php cli/migrate.php
```

### 5) İlk yönetici hesabı

```bash
/usr/local/php83/bin/php cli/add_admin.php "Yunus AKSOY" yunus@codega.com.tr "GÜÇLÜ_ŞİFRE" superadmin
```

Ardından `https://finans.codega.com.tr/admin/login.php` üzerinden giriş yapın.

### 6) Cron işi (DirectAdmin → Cron Manager)

```cron
0 4 * * * /usr/local/php83/bin/php /home/<user>/domains/finans.codega.com.tr/codegafinans/cli/cron.php >> /home/<user>/domains/finans.codega.com.tr/codegafinans/storage/cron.log 2>&1
```

Bu cron her sabah 04:00'te:
- Süresi dolan abonelikleri "expired" yapar
- TCMB kurlarını yeniler
- Tüm kullanıcılar için akıllı uyarıları yeniden hesaplar
- 30 günden eski login_attempts kayıtlarını siler

### 7) İzinler

```bash
chmod -R 755 codegafinans
chmod -R 775 codegafinans/storage codegafinans/backups codegafinans/updates
chmod 640    codegafinans/inc/config.local.php
```

---

## Geliştirici Notları

### Smart Update v5

`Yönetim Paneli → Güncellemeler` sayfasından tek tıkla:

1. GitHub'dan en son release sorgulanır.
2. Yeni sürüm varsa ZIP indirilir.
3. Mevcut dosyaların ZIP yedeği `/backups/` klasörüne alınır.
4. Yeni ZIP `/updates/_extract_<tag>/` dizinine açılır.
5. `manifest.json.tracked_paths` hedefe kopyalanır.
6. `manifest.json.excluded_paths` (storage/, backups/, inc/config.local.php) **dokunulmaz.**
7. `migrations/` idempotent olarak çalıştırılır.
8. `cf_update_log` tablosuna kayıt düşülür.
9. Eski yedekler `CF_UPDATE_KEEP_BACKUPS`'ı aşıyorsa kırpılır.

### Yeni Migration Ekleme

Yeni şema değişikliği için `migrations/002_<kısa_açıklama>.sql` dosyası açın. **Tekcanmetal kuralı:**

```sql
-- ÖNCE ALTER bloğunu garantile (INFORMATION_SCHEMA-protected)
SET @cnt := (
    SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'cf_users' AND column_name = 'new_col'
);
SET @sql := IF(@cnt = 0,
    'ALTER TABLE cf_users ADD COLUMN new_col VARCHAR(100) NULL',
    'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- SONRA bu kolona dokunan UPDATE eklenebilir
UPDATE cf_users SET new_col = '...' WHERE new_col IS NULL;
```

### API Endpoint'leri

| Endpoint | Amaç |
|---|---|
| `GET /api/ping.php`         | Sağlık + DB up/down + sürüm |
| `GET /api/version.php`      | Sürüm bilgisi + URL'ler |
| `GET /api/update_check.php?v=1.0.0` | Android force-update kontrolü |

### Android WebView Entegrasyonu

1. APK'da `WebViewClient` ile `https://finans.codega.com.tr` yüklenir.
2. Açılışta `/api/update_check.php?v=<BuildConfig.VERSION_NAME>` çağrılır.
3. `force_update=true` ise Play Store'a yönlendirilir.
4. Aksi takdirde WebView gösterilir.
5. Play Store sayfası `cf_settings.android_store_url` ile yönetilir.

---

## Lisans & İletişim

Bu yazılım proprietary'dir. CODEGA — Yunus AKSOY tarafından geliştirilmiştir.

- 🌐 https://codega.com.tr
- 📧 finans@codega.com.tr
- 📍 Konya / Türkiye

© 2026 CODEGA. Tüm hakları saklıdır.

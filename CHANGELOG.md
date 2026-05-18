# Changelog

## 1.0.8 - 2026-05-18

- Yonetim paneli Ayarlar ekranina Mail / SMTP Ayarlari karti eklendi.
- Mail gonderimi artik ayarlar tablosundaki SMTP degerlerini okuyacak hale getirildi.
- SMTP uygulama sifresi bos gonderilirse mevcut sifre korunur ve ayar listesinde maskelenir.

## 1.0.7 - 2026-05-18

- Cari kart detayina Cari Hesap Ekstresi bolumu eklendi.
- Cari ekstresi tanimli musteri e-postasina SMTP uzerinden gonderilebilir hale getirildi.
- Cari ekstresi tarih araligiyla PDF/Yazdir ekranindan alinabilir hale getirildi.

## 1.0.6 - 2026-05-18

- Cari hesaplar ekranina tarih filtreli Cari Rapor eklendi.
- Cari rapor icin yazdirma ve CSV indirme destegi eklendi.
- Gmail SMTP ayar ornegi `donusyapmayin@gmail.com` ve uygulama sifresi akisi icin guncellendi.

## 1.0.5 - 2026-05-18

- Smart Update uyumlulugu icin manifest tracked_paths dosya bazli hale getirildi.
- Eski updater surumlerinde klasor kopyalama basarisiz olsa bile tum uygulama dosyalari tek tek kopyalanacak.
- Cari hesap modulu ve updater duzeltmeleri bu pakette korunur.

## 1.0.4 - 2026-05-18

- Smart Update paket kokunu daha saglam bulacak sekilde guncellendi.
- Release asset ZIP'leri icin kokte veya tek ust klasor icinde bulunan `manifest.json` desteklendi.
- Eski updater uyumlulugu icin release ZIP paketleme yapisi duzeltildi.

## 1.0.3 - 2026-05-18

- Uyelere ozel cari hesap modulu eklendi.
- Her uye kendi musteri/tedarikci cari kartlarini acabilir.
- Cari kartlara borc/alacak hareketleri, tahsilat/odeme ve bakiye takibi eklendi.
- Cari hesaplar icin `cf_customers` ve `cf_customer_movements` migration'i eklendi.

## 1.0.2 - 2026-05-18

- MySQL native prepared statement uyumlulugu icin tekrarlanan named placeholder kullanimi duzeltildi.
- Yonetim ayarlari kaydetme ekraninda 500 hatasina yol acabilen SQL parametre baglama sorunu giderildi.
- TCMB kur yenileme sirasinda `rates_last_at` ayari guncellenirken ayni placeholder hatasi giderildi.

## 1.0.1 - 2026-05-18

- public_html icine dogrudan yukleme icin kok `.htaccess` uyum katmani eklendi.
- SSH olmayan hostingler icin tek seferlik `setup_admin.php` ilk yonetici kurulumu eklendi.
- Yonetim panelinde ana icerigin sidebar altina dusmesine neden olan backdrop layout hatasi duzeltildi.
- `mbstring` kapali hostinglerde giris ve panel ekranlari icin guvenli metin fallback helper'lari eklendi.


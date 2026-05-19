# Changelog

## 1.0.24 - 2026-05-19

- Cari hareketlerine düzenleme akışı eklendi; hatalı borç/alacak kayıtları tutar, tarih, vade, tür ve açıklama alanlarıyla düzeltilebilir.
- Yönetim paneline Cron Ayarları sayfası eklendi; günlük cron komutu görülebilir ve cari vade hatırlatmaları manuel çalıştırılabilir.
- Dashboard KPI hesapları cari borç/alacak hareketlerini de içerecek şekilde güncellendi; alacağım ve vereceğim toplamları görünür hale getirildi.
- Login ekranı dar mobil ekranlarda daha kompakt ve taşmasız çalışacak şekilde iyileştirildi.
- Cari ekstre ve cari rapor PDF çıktılarında kesilen özet ve tablo metinleri daha güvenli hizalanacak şekilde düzeltildi.

## 1.0.23 - 2026-05-19

- Cari Hesaplar ekranındaki arama alanı web ve mobilde daha kompakt, nazik bir filtre satırı olacak şekilde düzenlendi.
- Cari özet kartları özellikle mobilde daha küçük ve sakin ölçülere çekildi.
- Cari seçilmediğinde görünen uzun boş durum uyarısı kısa “Lütfen bir cari seçin.” mesajıyla değiştirildi.

## 1.0.22 - 2026-05-19

- Cari ekstre ve cari rapor PDF çıktıları düz metin görünümünden çıkarıldı; başlık, bilgi kutuları, özet kartları ve hizalı tablo düzeniyle profesyonel belge tasarımına alındı.

## 1.0.21 - 2026-05-19

- Cari borç/alacak hareketlerine zorunlu vade tarihi eklendi.
- Cari hareket listesi, ekstre HTML/PDF çıktıları vade tarihini gösterecek şekilde güncellendi.
- Günlük cron görevine, vadesi bugün gelen ve daha önce hatırlatma gönderilmemiş cari hareketler için müşteriye otomatik mail gönderimi eklendi.

## 1.0.20 - 2026-05-19

- Cari ekstre ve cari rapor PDF çıktılarında metin renginin beyaz kalması nedeniyle boş görünme sorunu düzeltildi.

## 1.0.19 - 2026-05-19

- Cari Hesaplar ekranına ad, telefon, e-posta ve vergi no üzerinden çalışan arama alanı eklendi.
- Cari ekstre ve cari rapor mail içerikleri Gmail gibi istemcilerde bozulmaması için inline stilli profesyonel HTML şablonuna taşındı.
- PDF ekstre/rapor üretimindeki bozuk UTF-16 çıktısı kaldırıldı; okunabilir, temiz PDF üretimi eklendi.

## 1.0.18 - 2026-05-19

- Cari Hesaplar ekranı sadeleştirildi; rapor alanı ayrı `Raporlar` sayfasına taşındı.
- Sol menü ve mobil alt menüye ayrı Raporlar sekmesi eklendi.
- Cari detay, hızlı borç/alacak, ekstre ve hareketler yalnızca cari seçildikten sonra açılacak hale getirildi.
- Hızlı borç/alacak formu tutar, tarih ve açıklama odaklı daha kısa hale getirildi.
- Raporlar sayfasında müşteri bazlı cari hesap özeti, PDF, CSV ve PDF mail akışları korundu.

## 1.0.17 - 2026-05-19

- Cari ekstre ve cari rapor belgelerinde üst marka alanı işlemi yapan kullanıcının adı ve iletişim bilgisiyle değiştirildi.
- Mail HTML içeriği ve PDF eki aynı gönderen bilgisiyle müşteri tarafından okunabilir hale getirildi.
- Cari Rapor ekranına müşteri seçimi eklendi; tüm cariler veya tek müşteri bazlı cari hesap özeti alınabilir, PDF/CSV/mail çıktıları aynı filtreyi kullanır.
- Dashboard Türkçe görünür metinleri UTF-8 açısından yeniden kontrol edildi.

## 1.0.16 - 2026-05-18

- Cari Hesap Ekstresi mail gönderimi gerçek PDF eki gönderecek şekilde düzeltildi.
- Cari Raporu mail akışı korundu ve PDF eki gönderecek şekilde güçlendirildi.
- SMTP mail altyapısına geriye uyumlu dosya eki desteği eklendi.
- Dashboard görünür metinleri Türkçe/UTF-8 açısından yeniden kontrol edildi; emoji ve özel semboller daha güvenli HTML metinlerine çevrildi.

## 1.0.15 - 2026-05-18

- Cari sayfası daha sade, mobil öncelikli bir iş akışına dönüştürüldü; seçili cari detayı, hızlı hareket kaydı ve ekstre aksiyonları küçük ekranda tek kolon halinde toparlandı.
- Cari Hesap Ekstresi PDF/Yazdır aksiyonu mail formundan ayrıldı; yazdırma URL'sine `send_statement_email` parametresi karışması engellendi.
- Cari ekstre ve cari rapor çıktılarındaki Türkçe metinler, net durum alanları ve belge görünümü müşteriyle paylaşmaya daha uygun hale getirildi.
- Dashboard ve Cari mobil görünümünde alt menü güvenli boşluğu artırıldı; ortadaki Cariler düğmesinde ikon/yazı üst üste binmesi giderildi.
- UTF-8/Türkçe karakter kontrolü ve 390px mobil taşma simülasyonu yapıldı.

## 1.0.14 - 2026-05-18

- Ana sayfa defter.net benzerliğinden ayrıştırıldı; CODEGA'ya özgü finans operasyon paneli kompozisyonu eklendi.
- Telefon/mockup odaklı eski landing yapısı ve kaynakta kalan eski landing CSS bloğu temizlendi.
- Ana sayfa; modüller, güvenlik/kontrol ve fiyatlandırma akışıyla yeniden kurgulandı.
- Mobil landing görünümü 390px genişlikte yatay taşma olmadan doğrulandı.

## 1.0.13 - 2026-05-18

- UTF-8/Türkçe karakter bozulmaları temizlendi; manifest ve sürüm bilgisi metinleri düzeltildi.
- Ana sayfa metinleri gerçek Türkçe karakterlerle güncellendi.
- Üye girişi ekranı ana sitenin teal/yeşil marka renkleriyle yeniden tasarlandı.
- Kullanıcı dashboard'ı mobil görünüm için iyileştirildi: KPI kartları iki sütunlu mobil düzene geçti, tablolar mobil kartlara dönüştü, üst bar dar ekranda taşmayacak şekilde toparlandı.
- 390px mobil simülasyonda login ve dashboard yatay taşma olmadan doğrulandı.

## 1.0.12 - 2026-05-18

- Landing sayfasi defter.net benzeri teal hero, sade nav, uygulama onizleme kartlari, ozellikler ve fiyatlandirma akisi ile yenilendi.
- Giris yapmayan ziyaretciler icin ana sayfadan IBAN / Havale / EFT bilgileri tamamen kaldirildi.
- IBAN bilgileri yalnizca uye girisi gerektiren abonelik ekraninda ve yonetim ayarlarinda kalacak sekilde sinirlandi.

## 1.0.11 - 2026-05-18

- budgets.php aylik butce sayfasinda 500 hatasini gideren SQL placeholder duzeltmesi (v1.0.2'de baska yerlerde duzeltilen ":c iki kere" ve ":u iki kere" pattern'i budgets.php icin de uygulandi).
- Landing (index.php) sayfasina seffaf fiyatlandirma alani eklendi - defter.net mantigi: plan ucretleri DB'den okunup giris yapmadan gosterilir.
- Play Store icin tam mobil uyumluluk: sabit alt navigasyon (Anasayfa / Gelir-Gider / Cariler-FAB / Borclar / Butce), iOS safe-area, 48px+ dokunma alanlari, numeric klavye (inputmode=decimal) tum para inputlarinda.
- Cari modulunde mobilde "Kolay Borc/Alacak Kaydi" kartinin gorunurlugu artirildi (mavi kenar, kalin baslik, kirmizi/yesil aksiyon butonlari, 50px buton).
- Cari hareketleri, gelir-gider listesi ve cari rapor tablolari mobilde otomatik olarak kart goruntusune donusur (data-label sistemli).
- transactions.php 5-sutunlu filtre formu mobilde tek sutun + iki-buton-block hale getirildi.
- debts.php odeme formu mobilde dikey yiginlenir ve tum genisligi alir.

## 1.0.10 - 2026-05-18

- SMTP zaman asimi hatalari icin daha acik tani mesaji ve 465/ssl alternatif yonlendirmesi eklendi.
- Cari raporu ve cari hesap ekstresi PDF/mail ciktilari profesyonel dokuman sablonuyla yenilendi.
- Cari raporu icin PDF Rapor ve mail gonderme akisina destek eklendi.
- Mobil kullanim icin dokunmatik alanlar, form sirasi ve cari hizli hareket kaydi guclendirildi.
- Borc / alacak kaydinda nakliye aciklamalari icin daha genis Aciklama alani eklendi.

## 1.0.9 - 2026-05-18

- Yonetim paneli Mail / SMTP Ayarlari bolumune Test Maili Gonder aksiyonu eklendi.
- Cari detayinda kolay Borc / Alacak Kaydi karti one alindi.
- Mobil gorunumde secili cari ve hizli hareket kaydi cari listesinden once gosterilecek sekilde duzenlendi.

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


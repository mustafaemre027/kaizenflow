# Dynamic Business Rules Audit

## Amaç
Gün 1-8 arasında eklenen ve KaizenFlow'un temel işleyişini sağlayan kuralların "dynamic-by-default" (varsayılan olarak dinamik) ürün prensibi çerçevesinde değerlendirilmesi. Müşteriden müşteriye (kurumdan kuruma) değişebilecek iş verilerinin ve onay süreçlerinin kod içerisine sabit gömülmesi yerine, ilerleyen günlerde veritabanı veya konfigürasyon üzerinden yönetilebilir hale getirilmesi amaçlanmaktadır.

## Ürünleştirme İlkesi
Müşteriye/işletmeye göre değişebilecek iş verileri (örneğin kategoriler, departmanlar, değerlendirme kriterleri) ve iş süreçleri (örneğin Kaizen onay aşamaları) mümkün olduğunca koda sabit gömülmeyecektir.
Buna karşılık; authentication, authorization, CSRF, validation güvenlikleri ve transaction bütünlükleri gibi "sistem invariant" kuralları esnetilmeyecek ve tamamen kod tarafında kontrol edilmeye devam edecektir.

## Already Dynamic
Aşağıdaki yapılar halihazırda dinamik (DB tabanlı) olarak kurgulanmıştır ve herhangi bir değişikliğe ihtiyaç duyulmamaktadır:
- **Kategoriler (Categories):** `categories` tablosundan yönetilmekte ve Create formlarında DB'den gelmektedir.
- **Departmanlar (Departments):** `departments` tablosundan yönetilmekte, kullanıcı atamalarında dinamik çalışmaktadır.

## Hard-coded Candidates
Aşağıdaki yapılar tamamen kurumdan kuruma değişebileceği için DB/Konfigürasyon yönetimine geçirilmelidir (Should Become Dynamic):
- **Workflow / Approval Stages:** Şu an `KaizenTransitionMap.php` içinde OPEX -> YÖNETİCİ gibi geçişler sabit if/dizi yapılarındadır. Hedef yapıda `approval_workflows`, `approval_stages` ve `approval_stage_assignees` gibi normalize bir veritabanı yapısına geçilmelidir.
- **Değerlendirme Kriterleri (Evaluation Criteria):** İleride eklenecek olan değerlendirme ağırlıkları ve kriter puanlamaları (örn. uygulanabilirlik, yaygınlaştırma).
- **Fayda Türleri (Benefit Types):** Mevcutta `expected_benefit` ve `realized_benefit` metin alanı olarak yer alıyor ancak zaman, maliyet, kalite, iş güvenliği vb. fayda türleri `benefit_types` + `kaizen_benefits` gibi dinamik bir yapıya taşınmalıdır.
- **Kurul / Onay Grupları (Board/Committee):** Kurul üyeleri ve nihai onay mekanizmaları sabit roller yerine dinamik onay gruplarından gelmelidir.
- **Bildirim Ayarları ve Raporlama Filtreleri:** Müşteriye özel aktif/pasif edilebilmelidir.

## Hybrid/System Invariants
Bu yapılar teknik kararlılık için kod içerisinde (enum/sabit) kalmalı ancak müşteri özelindeki gösterim, aktiflik/pasiflik ve sıralamaları için DB desteği (Hybrid) veya salt kod koruması (Invariant) altında tutulmalıdır:
- **Roller (UserRole):** `EMPLOYEE`, `OPEX_SPECIALIST`, `MANAGER`, `ADMIN` gibi teknik yetkilendirmeler (authorization) için gereklidir (Invariant). Ancak müşteriye özel rol gösterimleri, approval group eşleşmeleri veya ilave ara yetki grupları için DB destekli bir yapı kurgulanmalıdır.
- **Durum Yapısı (KaizenStatus):** `DRAFT`, `SUBMITTED`, `COMPLETED` gibi çekirdek state-machine durumları teknik olarak kodda kalmalı ancak `MANAGER_REVIEW`, `COMMITTEE_REVIEW` gibi iş onay süreçlerini temsil eden adımlar `KaizenStatus`'ten ayrılarak "Approval Stage Configuration" haline getirilmelidir.
- **Öncelikler (KaizenPriority):** `LOW`, `MEDIUM`, `HIGH` teknik limitleri korurken, display name kısımları yönetilebilir olabilir (Hybrid).
- **Güvenlik ve Validasyon:** IDOR engellemeleri, yetki sınırları ve veri tutarlılıkları (System Invariant - Keep Code Controlled).

## Gün 9 Düzeltilecekler
- Liste ekranı (Listeleme, filtreleme, sayfalama) UI ve Backend entegrasyonu.
- Taslak düzenleme (Edit) yeteneği.
- Mevcut yapıların bu doküman üzerinden dinamikleşme yol haritasının çizilmesi.

## Gün 10'a Taşınanlar
- Mevcut Durum ve Önerilen Durum alanlarına güvenli çoklu fotoğraf / dosya ekleri eklenecek.
- Fotoğraflar hangi bölüme ait olduğunu (CURRENT_SITUATION veya PROPOSED_SITUATION) `attachment context/type` yapısıyla taşıyacak. Metin alanları zorunlu kalmaya devam edecek.

## Gün 11–13 Workflow Dönüşümü
- **Gün 11:** Onay süreci sabit kod (TransitionMap) yerine dinamik `approval_workflows` / `approval_stages` altyapısına geçirilecek ve durum geçmişi mekanizması bağlanacak.
- **Gün 12:** OPEX inceleme, revizyon talebi, ret ve sonraki aşamaya gönderme işlemleri UI/UX olarak kodlanacak.
- **Gün 13:** Varsayılan süreç (OPEX -> Yönetici -> Kurul -> Nihai Onay) işletilecek. Nihai onay Yöneticiden alınacak, Kurul onayı ile sonlandırılacak. Kurul ve kurul üyelik yapısı tamamen dinamik hale getirilecek.

## Gün 15–16 Evaluation/Benefit
- **Gün 15:** Dinamik değerlendirme kriterleri ve puanlama/ağırlık sistemleri oluşturulacak.
- **Gün 16:** Opsiyonel ve dinamik fayda türleri (Zaman, Kalite, Maliyet vb.) ve gerçekleşen fayda (Realized Benefit) yapısı eklenecek.

## Final Gün 19 Audit Checklist
- Tüm hard-coded onay adımları, durum ve rol atamalarının, dinamik referans verilerine taşınıp taşınmadığı kontrol edilecek.
- Müşteri/İşletme bazlı sabit bir ayarın kalmadığı son kez denetlenecek.

## Gelecek Yönetim Merkezi (Configuration Center) Yol Haritası
Yönetim ekranlarında (Reference Data Hub) ileride aşağıdaki modüllerin dinamik konfigürasyonu yönetilebilecektir:
- Approval workflow stages (Onay iş akışı aşamaları)
- Approval groups (Onay grupları)
- Committee members (Kurul üyeleri)
- Evaluation criteria (Değerlendirme kriterleri)
- Benefit types (Fayda türleri)
- Notification settings (Bildirim ayarları)
- User/role/group management (Kullanıcı, rol ve grup yönetimi)
- Configurable organization settings (Yapılandırılabilir organizasyon ayarları)

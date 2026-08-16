# KaizenFlow – Sistem Gereksinimleri

* **Belge sürümü:** 1.0
* **Staj günü:** Gün 2
* **Çalışma tarihi:** 12.08.2026
* **Durum:** MVP gereksinim temeli
* **İlgili Issue:** #7
* **Parent Epic:** #3

## 1. Belgenin Amacı
Bu belge, KaizenFlow Sürekli İyileştirme Yönetim Sistemi'nin Minimum Canlandırılabilir Ürün (MVP) sürümü için fonksiyonel ve fonksiyonel olmayan gereksinimleri, iş kurallarını, kullanıcı senaryolarını ve güvenlik prensiplerini tanımlamak amacıyla hazırlanmıştır. Geliştirme, mimari tasarım, test ve doğrulama süreçleri bu belge referans alınarak yürütülecektir.

## 2. Sistem Kapsamı
KaizenFlow, bir üretim veya hizmet organizasyonunda çalışanların süreç iyileştirme (Kaizen) önerilerini dijital ortamda oluşturabildiği, ilgili birimlerin (OPEX) ve yöneticilerin bu önerileri standart bir iş akışına göre değerlendirebildiği, onaylanan önerilerin uygulama süreçlerinin ve sağladığı faydanın izlenebildiği genel bir kurumsal prototiptir. MVP aşamasında sistem, temel Kaizen yaşam döngüsünü ve rol tabanlı onay akışını uçtan uca çalıştıracak temel fonksiyonları kapsar.

## 3. Varsayımlar ve Kısıtlar
* **Tek kuruluşlu yapı:** Sistem, tek bir şirketin veya organizasyonun kullanımına uygun olarak tasarlanmıştır. Multi-tenancy (çoklu kiracı) mimarisi kapsam dışıdır.
* **Dört sabit kullanıcı rolü:** Sistemde yalnızca dört temel rol bulunur: EMPLOYEE, OPEX_SPECIALIST, MANAGER, ADMIN. Özel veya dinamik roller oluşturulamaz.
* **Sabit onay akışı:** Kaizen onay süreci durumu ve hiyerarşisi (Taslak -> Gönderildi -> Yönetici İncelemesi -> Onaylandı vb.) sabittir, dinamik akış motoru bulunmamaktadır.
* **Authentication:** Kullanıcı kimlik doğrulaması standart session tabanlı (stateful) kimlik doğrulama ile sağlanacaktır.
* **Teknoloji yığını:** Uygulama Laravel (PHP), Blade template motoru, Bootstrap UI framework'ü ve MySQL veritabanı kullanılarak geliştirilecektir.
* **Sentetik demo verileri:** Geliştirme ve test süreçlerinde sistem tamamen sentetik (sahte) verilerle çalışacaktır. Gerçek şirket entegrasyonu bulunmamaktadır.
* **Entegrasyon yok:** Gerçek şirket İK sistemleri (Active Directory, LDAP, ERP vb.) ile entegrasyon bulunmamaktadır. Tüm kullanıcılar ve organizasyon şeması sistemin kendi veritabanında tutulacaktır.
* **Kapsam dışı öğeler:** Native mobil uygulama, API first (Headless) yaklaşım veya harici API erişimi MVP kapsamında değildir.

## 4. Kullanıcı Rolleri ve Sorumlulukları

### EMPLOYEE
* **Temel Amaç:** Sisteme iyileştirme önerilerini girmek ve kendi önerilerinin durumunu takip etmek.
* **Görüntüleyebileceği Kayıtlar:** Yalnızca kendi oluşturduğu Kaizen önerilerini ve genel sistem duyurularını/bildirimlerini görüntüleyebilir.
* **Yapabileceği İşlemler:** Yeni Kaizen taslağı (DRAFT) oluşturma, düzenleme, onaya gönderme (SUBMITTED) ve kendisinden düzeltme istenen (REVISION_REQUESTED) önerilerini güncelleyip yeniden gönderme.
* **Yapamayacağı Kritik İşlemler:** Başkalarına ait kayıtları göremez, onay akışında durum değiştiremez, başkası adına işlem yapamaz.

### OPEX_SPECIALIST
* **Temel Amaç:** Sisteme gönderilen Kaizen önerilerini format, uygulanabilirlik ve kategori açısından ön incelemeden geçirmek; eksiklikleri raporlamak ve uygun olanları yönetici incelemesine aktarmak.
* **Görüntüleyebileceği Kayıtlar:** Sistemde SUBMITTED, REVISION_REQUESTED ve sonraki aşamalardaki (MANAGER_REVIEW, APPROVED, IN_PROGRESS, COMPLETED, REJECTED) tüm Kaizen kayıtlarını görebilir. DRAFT kayıtlarını göremez.
* **Yapabileceği İşlemler:** Gönderilen önerileri değerlendirme, gerekçesiyle reddetme, gerekçesiyle çalışandan düzeltme talep etme ve uygun önerileri değerlendirilmesi için Yöneticiye (MANAGER_REVIEW) sevk etme. Uygulama takibi yapma.
* **Yapamayacağı Kritik İşlemler:** Kaizen için nihai kararı (Onaylama veya Uygulamaya geçirme) veremez, yönetici veya admin tanımlamaları yapamaz.

### MANAGER
* **Temel Amaç:** OPEX tarafından ön değerlendirmesi yapılmış ve kendi sorumluluk alanına yönlendirilmiş Kaizen önerilerini değerlendirmek, onaylamak veya reddetmek.
* **Görüntüleyebileceği Kayıtlar:** Kendi departmanına, maliyet merkezine veya sorumluluk kapsamına giren ve en az MANAGER_REVIEW aşamasına gelmiş tüm kayıtları ile onaylanmış kayıtları görüntüleyebilir.
* **Yapabileceği İşlemler:** Yönetici incelemesindeki (MANAGER_REVIEW) önerileri onaylamak (APPROVED) veya gerekçesiyle reddetmek (REJECTED). Uygulamaya geçişleri izlemek.
* **Yapamayacağı Kritik İşlemler:** Sisteme OPEX kontrolünden geçmemiş (SUBMITTED aşamasındaki) veya başka yöneticilerin sorumluluğundaki önerilere müdahale edemez.

### ADMIN
* **Temel Amaç:** Sistemdeki kullanıcıları, rolleri, departmanları, kategorileri ve genel yapılandırmayı yönetmek.
* **Görüntüleyebileceği Kayıtlar:** Sistemdeki tüm kullanıcı hesaplarını, meta verileri ve teknik logları (audit log) görüntüleyebilir.
* **Yapabileceği İşlemler:** Kullanıcı ekleme, rol atama, şifre sıfırlama, departman/kategori tanımlama ve sentetik verileri yükleme.
* **Yapamayacağı Kritik İşlemler:** Sistem yöneticisi rolü tek başına hiçbir Kaizen iş akışı onay yetkisi sağlamaz. Taslağı onaylayamaz, iş sürecine müdahale edemez.

## 5. Fonksiyonel Gereksinimler
* **FR-001:** Sistem, kullanıcıların e-posta ve şifre ile güvenli giriş yapmasını sağlamalıdır.
* **FR-002:** Sistem, kullanıcıların oturumlarını güvenli bir şekilde kapatmasına (çıkış yapmasına) olanak tanımalıdır.
* **FR-003:** Sistem, session tabanlı oturum yönetimi sağlamalı ve eylemsizlik durumunda (timeout) oturumu sonlandırmalıdır.
* **FR-004:** Sistem, yalnızca kullanıcının sahip olduğu role uygun sayfa, menü ve butonları göstererek rol tabanlı yetkilendirme sağlamalıdır.
* **FR-005:** ADMIN rolü, sisteme yeni kullanıcı ekleyebilmeli ve kullanıcı bilgilerini güncelleyebilmelidir.
* **FR-006:** ADMIN rolü, kullanıcılara dört temel rolden birini atayabilmelidir.
* **FR-007:** ADMIN rolü, sistemdeki departman ve Kaizen kategorilerini tanımlayabilmelidir.
* **FR-008:** EMPLOYEE rolü, yeni bir Kaizen önerisi taslağı (DRAFT) oluşturabilmelidir.
* **FR-009:** Kaizen taslağı oluşturulurken 'Mevcut Durum', 'Önerilen Durum' ve 'Beklenen Fayda' alanları zorunlu olarak doldurulmalıdır.
* **FR-010:** EMPLOYEE rolü, yalnızca kendi oluşturduğu DRAFT durumundaki önerilerini güncelleyebilmelidir.
* **FR-011:** EMPLOYEE rolü, hazırladığı DRAFT önerisini değerlendirilmek üzere sisteme (SUBMITTED durumuna) gönderebilmelidir.
* **FR-012:** EMPLOYEE rolü, kendisine ait geçmiş ve mevcut tüm Kaizen kayıtlarını listeleyebilmeli ve detaylarını görüntüleyebilmelidir.
* **FR-013:** Kullanıcılar (yetkili oldukları durumlarda), Kaizen önerilerine JPG, JPEG, PNG veya PDF formatında, her biri en fazla 5 MB olan maksimum 5 adet görsel veya belge ekleyebilmelidir.
* **FR-014:** OPEX_SPECIALIST rolü, durumu SUBMITTED olan kayıtları içeren bir değerlendirme kuyruğunu görüntüleyebilmelidir.
* **FR-015:** OPEX_SPECIALIST rolü, incelediği öneri için zorunlu bir açıklama (gerekçe) girerek çalışandan düzeltme talebinde (REVISION_REQUESTED) bulunabilmelidir.
* **FR-016:** OPEX_SPECIALIST rolü, incelediği öneriyi zorunlu bir gerekçe girerek reddedebilmelidir (REJECTED).
* **FR-017:** OPEX_SPECIALIST rolü, uygun bulduğu öneriyi Yönetici onayı için (MANAGER_REVIEW durumuna) iletebilmelidir.
* **FR-018:** MANAGER rolü, MANAGER_REVIEW durumunda olan kendi sorumluluğundaki önerileri onaylayabilmelidir (APPROVED).
* **FR-019:** MANAGER rolü, incelediği öneriyi zorunlu bir gerekçe girerek reddedebilmelidir (REJECTED).
* **FR-020:** Öneri APPROVED durumuna geçtiğinde, uygulamadan sorumlu bir personel ve bir hedef uygulama tarihi zorunlu olarak belirlenmelidir.
* **FR-021:** Uygulama sorumlusu olan kişi, onaylanan Kaizen için uygulama çalışmalarını başlattığını bildirebilmelidir (IN_PROGRESS).
* **FR-022:** IN_PROGRESS durumundaki Kaizen tamamlanırken, zorunlu olarak 'Gerçekleşen Sonuç' ve 'Sağlanan Fayda' bilgileri girilmelidir.
* **FR-023:** İlgili yetkili kişi (MANAGER veya Uygulama Sorumlusu), uygulama sonuçlarını girdikten sonra Kaizen'i başarıyla kapatabilmelidir (COMPLETED).
* **FR-024:** İlgili rollere sahip kullanıcılar, erişim yetkileri bulunan Kaizen önerilerine yorum ekleyebilmelidir.
* **FR-025:** Sistem, her Kaizen önerisi için durum değişikliklerini kimin ve ne zaman yaptığını kaydeden değiştirilemez bir durum geçmişi sunmalıdır.
* **FR-026:** Sistem, kritik veri değişikliklerinde (kullanıcı ekleme, rol değiştirme) detaylı arka plan audit log kayıtları tutmalıdır.
* **FR-027:** Sistem, önerisi reddedilen, düzeltme istenen veya onaylanan kullanıcıya sistem içi bildirim göstermelidir.
* **FR-028:** Sistem, her rolün kendi iş akışına uygun özet istatistikler ve metrikler içeren dinamik bir dashboard sunmalıdır.
* **FR-029:** Kullanıcılar, erişimleri olan listede Kaizen numarası, başlık, durum ve tarih gibi alanlara göre arama, filtreleme ve sayfalama yapabilmelidir.
* **FR-030:** OPEX_SPECIALIST ve MANAGER rolleri, erişimleri olan Kaizen kayıt listelerini CSV formatında güvenli bir şekilde dışa aktarabilmelidir.
* **FR-031:** Sistem, ilk kurulumda veya test amaçlı çalıştırıldığında gerekli sentetik demo verilerini (kullanıcılar, kategoriler, sahte Kaizenler) otomatik olarak oluşturacak yapılandırmayı içermelidir.
* **FR-032:** Dosya ekleri doğrudan public olarak erişilebilir URL'lerden sunulmamalı; indirme işlemi yetki kontrolü (authentication ve authorization) sonrasında gerçekleşmelidir.

## 6. Fonksiyonel Olmayan Gereksinimler
* **NFR-001 (Güvenlik):** Sistemdeki parolalar hash (Bcrypt) kullanılarak şifrelenmeli, düz metin olarak veritabanında saklanmamalıdır.
* **NFR-002 (Güvenlik):** Uygulama, CSRF (Cross-Site Request Forgery) ve XSS (Cross-Site Scripting) saldırılarına karşı framework bazlı korumaları standart olarak uygulamalıdır.
* **NFR-003 (Gizlilik):** .env dosyası ve uygulama secret key'leri Git repository'sine eklenmemeli, gizlilik kurallarına uyulmalıdır.
* **NFR-004 (Performans):** Sistem, yerel test ortamında, 10.000 sentetik Kaizen kaydı bulunduğunda standart liste (sayfalamalı) ve detay isteklerine 2 saniyenin altında yanıt vermelidir (MVP doğrulama hedefi).
* **NFR-005 (Performans):** Sistem, yerel test ortamında 10.000 kayıtta dashboard istatistik sorgularına 3 saniyenin altında yanıt vermelidir (MVP doğrulama hedefi).
* **NFR-006 (Kullanılabilirlik):** Sistem, ana akış işlemlerini (Kaizen ekleme, onaylama, listeleme) maksimum 3 tıkla veya adımda erişilebilir kılmalıdır.
* **NFR-007 (Responsive Tasarım):** Uygulama arayüzü masaüstü bilgisayar, tablet ve mobil tarayıcı boyutlarında düzgün ve kullanılabilir bir responsive tasarıma sahip olmalıdır.
* **NFR-008 (Temel Erişilebilirlik):** Uygulama arayüzünde kullanılan renk kontrastları ve form etiketleri (labels) temel web erişilebilirlik yönergelerine uygun olmalıdır.
* **NFR-009 (Bakım Yapılabilirlik):** Kod yapısı, MVC mimari desenine sıkı sıkıya uymalı, controller sınıfları ağır iş mantığından arındırılmalıdır.
* **NFR-010 (Test Edilebilirlik):** Temel yetkilendirme kuralları ve durum geçişleri (state transitions) izole edilerek otomatik unit testlerle doğrulanabilir yapıda tasarlanmalıdır.
* **NFR-011 (Veri Bütünlüğü):** Veritabanı şemasında, durum geçişleri enum veya referans tablolarla zorunlu kılınmalı, yabancı anahtar (foreign key) kısıtlamaları kullanılmalıdır.
* **NFR-012 (Audit Edilebilirlik):** Sistem tablolarında oluşturulma, güncellenme ve silinme zamanları (timestamps ve soft deletes) standart olarak tutulmalıdır.
* **NFR-013 (Hata Yönetimi):** Uygulama içerisinde meydana gelen sistem ve veritabanı hataları son kullanıcıya genel bir mesajla gösterilirken, detaylar uygulamanın kendi güvenli log dosyalarına yazılmalıdır.
* **NFR-014 (Tarayıcı Uyumluluğu):** Uygulama, Chrome, Firefox, Safari ve Edge gibi modern web tarayıcılarının güncel son iki majör sürümünde sorunsuz çalışmalıdır.
* **NFR-015 (Kurulum ve Yapılandırma):** Sistem kurulumu, migration'lar ve sahte veri aktarımı (seeding), belgelenmiş tek bir komut dizisi ile çalıştırılabilir olmalıdır.
* **NFR-016 (Yedeklenebilirlik):** Sisteme yüklenen dosyalar ve veritabanı yedeği alınabilir yapıda harici bir klasörde organize edilmelidir.

## 7. Kullanıcı Senaryoları
* **US-001: Çalışanın taslak oluşturup göndermesi**
  * **Aktör:** EMPLOYEE
  * **Ön koşul:** Çalışan sisteme giriş yapmış durumdadır.
  * **Ana akış:** Çalışan 'Yeni Kaizen' butonuna basar, gerekli alanları (Mevcut, Önerilen, Beklenen Fayda) doldurur ve kaydeder. Sistem DRAFT durumunda kaydeder. Ardından 'Gönder' butonuna basar.
  * **Alternatif/Hata akışı:** Zorunlu alanlardan biri eksikse, sistem hata mesajı gösterir ve kaydetmez.
  * **Başarı ölçütü:** Kaydın durumu SUBMITTED olur ve OPEX kuyruğuna düşer.

* **US-002: OPEX'in öneriyi yöneticiye iletmesi**
  * **Aktör:** OPEX_SPECIALIST
  * **Ön koşul:** OPEX sisteme giriş yapmıştır ve SUBMITTED durumunda bir kayıt vardır.
  * **Ana akış:** OPEX uzmanı kaydın detayına girer, format olarak uygun bulur, 'Yönetici İncelemesine İlet' seçeneğini seçer.
  * **Alternatif/Hata akışı:** -
  * **Başarı ölçütü:** Kaydın durumu MANAGER_REVIEW olur.

* **US-003: OPEX'in düzeltme istemesi**
  * **Aktör:** OPEX_SPECIALIST
  * **Ön koşul:** OPEX sisteme giriş yapmıştır ve SUBMITTED durumunda eksik bir kayıt vardır.
  * **Ana akış:** OPEX uzmanı kaydı açar, 'Düzeltme İste' butonuna tıklar, zorunlu gerekçe alanına eksikleri yazar ve onaylar.
  * **Alternatif/Hata akışı:** Gerekçe alanı boş bırakılırsa işlem gerçekleşmez ve hata gösterilir.
  * **Başarı ölçütü:** Kaydın durumu REVISION_REQUESTED olur, gerekçe geçmişe eklenir.

* **US-004: Düzeltme istenen önerinin yeniden gönderilmesi**
  * **Aktör:** EMPLOYEE
  * **Ön koşul:** Çalışanın REVISION_REQUESTED durumunda bekleyen bir önerisi vardır.
  * **Ana akış:** Çalışan öneriyi açar, OPEX gerekçesini okur, formu günceller ve 'Yeniden Gönder' butonuna basar.
  * **Alternatif/Hata akışı:** -
  * **Başarı ölçütü:** Kaydın durumu tekrar SUBMITTED olur.

* **US-005: Yönetici onayı**
  * **Aktör:** MANAGER
  * **Ön koşul:** Yöneticinin onay kuyruğunda MANAGER_REVIEW durumunda bir kayıt vardır.
  * **Ana akış:** Yönetici kaydı inceler, uygun bulur, 'Onayla' butonuna tıklar. Uygulama için sorumlu personel seçer ve hedef tarih girer.
  * **Alternatif/Hata akışı:** Sorumlu kişi veya hedef tarih girilmezse onay işlemi iptal olur ve uyarı verilir.
  * **Başarı ölçütü:** Kaydın durumu APPROVED olarak güncellenir ve uygulama aşamasına geçer.

* **US-006: Gerekçeli ret**
  * **Aktör:** MANAGER (veya OPEX_SPECIALIST)
  * **Ön koşul:** OPEX veya MANAGER inceleme sırasında öneriyi reddetmeye karar verir.
  * **Ana akış:** Yetkili, 'Reddet' butonuna basar, zorunlu ret gerekçesini yazar ve işlemi tamamlar.
  * **Alternatif/Hata akışı:** Gerekçe yazılmazsa form kaydedilmez.
  * **Başarı ölçütü:** Kaydın durumu REJECTED olur ve çalışan bu gerekçeyi görüntüleyebilir.

* **US-007: Uygulamanın başlatılıp tamamlanması**
  * **Aktör:** Sorumlu personel
  * **Ön koşul:** Kayıt APPROVED durumundadır.
  * **Ana akış:** Sorumlu personel uygulamaya başlar ve durumu IN_PROGRESS yapar. İşlem bittiğinde 'Tamamla' seçeneğini seçer, 'Gerçekleşen Sonuç' ve 'Sağlanan Fayda' bilgilerini girer.
  * **Alternatif/Hata akışı:** Sonuç ve fayda alanları doldurulmadan 'Tamamla' işlemine izin verilmez.
  * **Başarı ölçütü:** Kaydın durumu COMPLETED olur, durum geçmişine kaydedilir.

* **US-008: Admin kullanıcı ve sistem tanımı yönetimi**
  * **Aktör:** ADMIN
  * **Ön koşul:** ADMIN rolündeki kullanıcı sisteme giriş yapmıştır.
  * **Ana akış:** Admin 'Sistem Yönetimi' ekranına gider, yeni bir departman ekler, ardından yeni bir çalışanı bu departmana tanımlar ve rolünü EMPLOYEE yapar.
  * **Alternatif/Hata akışı:** E-posta benzersiz değilse veya hatalı veri girilirse admin arayüzü uyarı verir.
  * **Başarı ölçütü:** Yeni çalışan sistemi kullanabilir hale gelir.

## 8. İş Kuralları
* **BR-001:** Bir Kaizen önerisinin asıl sahibi (sahibi) sadece onu oluşturan EMPLOYEE kullanıcısıdır. Kayıt sahipliği devredilemez.
* **BR-002:** EMPLOYEE, önerisini yalnızca DRAFT ve REVISION_REQUESTED durumlarındayken düzenleyebilir (form alanlarını değiştirebilir).
* **BR-003:** Durum geçişleri sabittir ve atlanamaz. (Örn: SUBMITTED olan kayıt doğrudan APPROVED yapılamaz, MANAGER_REVIEW aşamasından geçmek zorundadır.)
* **BR-004:** SUBMITTED durumundaki bir kaydın durum değişikliğini yalnızca OPEX_SPECIALIST gerçekleştirebilir.
* **BR-005:** Reddetme (REJECTED) ve düzeltme isteme (REVISION_REQUESTED) işlemlerinde gerekçe belirtilmesi mutlak suretle zorunludur.
* **BR-006:** MANAGER rolü, sistemdeki tüm MANAGER_REVIEW kayıtlarını değil, yalnızca kendi sorumluluk kapsamındaki (örneğin kendi departmanındaki) kayıtları değerlendirebilir.
* **BR-007:** Bir Kaizen APPROVED durumuna geçirilirken, uygulama sorumlusu ve hedef bitiş tarihi zorunlu olarak tanımlanmalıdır.
* **BR-008:** Uygulama tamamlanıp Kaizen kapatılırken (COMPLETED durumuna geçirilirken), gerçekte ulaşılan sonuç ve sağlanan fayda verileri girilmeden kayıt kapatılamaz.
* **BR-009:** Bir Kaizen üzerinde yapılan her durum geçişi (eski durum, yeni durum, yapan kullanıcı, zaman ve varsa açıklama), veritabanında tarihsel olarak saklanmalı ve değiştirilemez olmalıdır.
* **BR-010:** Yüklenen dosya eklerine yalnızca oturum açmış ve ilgili kaydı görüntüleme yetkisi olan kullanıcılar erişebilir. URL direkt paylaşılarak dosya indirilemez.
* **BR-011:** Sistem yöneticisi (ADMIN) tarafından yapılan kullanıcı yetkilendirme değişiklikleri, kimin, kime, ne zaman hangi rolü verdiğini içerecek şekilde audit log olarak kaydedilmelidir.
* **BR-012:** Test ve geliştirme süreçlerinde gerçek şirket, gerçek personel adı, gerçek finansal veya üretim verisi kullanılmayacak; mutlaka sentetik (faker ile üretilmiş) verilerle çalışılacaktır.
* **BR-013:** ADMIN rolü teknik altyapıyı yönetir; kendi başına bir Kaizen önerisini onaylama, reddetme veya iş sürecini ilerletme (iş onayı verme) yetkisine sahip değildir.
* **BR-014:** Yetkisiz işlemleri engellemek için sadece arayüzde (frontend) buton gizlemek yeterli kabul edilemez; ilgili işlemlerin (durum değiştirme vs.) backend controller seviyesinde (policy/middleware ile) mutlak olarak denetlenmesi zorunludur.

## 9. Veri ve Gizlilik İlkeleri
* **Repository Gizliliği:** Bu proje genel bir açık kaynak veya portfolyo projesi olabileceğinden, kod deposuna gerçek kurumsal süreçlere ait hassas dökümanlar eklenmemelidir.
* **Demo Verileri:** Sistemin işleyişini kanıtlamak için yalnızca rastgele isimler (örn: Ahmet Yılmaz yerine John Doe veya sentetik Türkçe isimler), rastgele senaryolar ve sahte şirket metrikleri barındıran seed'ler kullanılmalıdır.
* **Loglar:** Geliştirme ortamındaki uygulama hataları ve log dosyaları `.gitignore` ile Git takibinden dışlanmalıdır.
* **Dosya Ekleri:** Kullanıcıların test amaçlı yüklediği ekler, `storage/app` gibi public olmayan bir dizinde saklanmalı ve sürüm kontrolüne dahil edilmemelidir.
* **Çevresel Değişkenler:** Veritabanı şifreleri, API anahtarları gibi bilgiler barındıran `.env` dosyası hiçbir zaman repository'ye gönderilmemeli, bunun yerine örnek bir `.env.example` dosyası kullanılmalıdır.

## 10. Gereksinim İzlenebilirliği

| Kullanıcı Senaryosu | İlgili Fonksiyonel Gereksinimler | İlgili İş Kuralları | Planlanan Test Türü |
| :--- | :--- | :--- | :--- |
| US-001 (Çalışan taslak/gönderme) | FR-008, FR-009, FR-010, FR-011 | BR-001, BR-002 | Feature/UI Test |
| US-002 (OPEX yöneticiye iletme) | FR-014, FR-017 | BR-003, BR-004 | Feature/Unit Test |
| US-003 (OPEX düzeltme isteme) | FR-014, FR-015 | BR-004, BR-005 | Feature/Unit Test |
| US-004 (Düzeltme isteneni gönderme) | FR-010, FR-011 | BR-002 | Feature Test |
| US-005 (Yönetici onayı) | FR-018, FR-020 | BR-006, BR-007 | Feature/Unit Test |
| US-006 (Gerekçeli ret) | FR-016, FR-019 | BR-005 | Feature/Unit Test |
| US-007 (Uygulama tamamlama) | FR-021, FR-022, FR-023 | BR-008 | Feature Test |
| US-008 (Admin tanımlama işlemleri) | FR-005, FR-006, FR-007 | BR-011, BR-013 | Feature Test |

## 11. Kabul ve Değişiklik Yönetimi
Bu gereksinim belgesi projenin MVP hedefini yansıtmaktadır ve aşağıdaki kurallara göre yönetilecektir:
* **Kapsam Değişikliği:** Mevcut belgede yer alan gereksinimler dışında yeni bir özellik talep edildiğinde, bu durum ayrı bir GitHub Issue açılarak gerekçelendirilmelidir.
* **Dokümantasyon Güncellemesi:** Kapsam veya gereksinim değiştiğinde, `README.md`, `implementation_plan.md` ve ilgili tüm teknik belgeler aynı Pull Request (PR) içerisinde güncellenmek zorundadır.
* **Etki Analizi:** Herhangi bir değişiklik talebi kabul edilmeden önce güvenlik, iş akışı bütünlüğü ve MVP zaman planına etkisi değerlendirilmelidir.
* **Önceliklendirme:** Ana Kaizen döngüsü ve temel onay akışı olan MVP tamamlanmadan, sisteme bildirim merkezi iyileştirmesi, ekstra entegrasyonlar vb. isteğe bağlı (nice-to-have) özellikler eklenmeyecektir.

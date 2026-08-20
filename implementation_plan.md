# KaizenFlow – Uygulama Planı

## 1. GENEL BAKIŞ
KaizenFlow, çalışanların süreç iyileştirme fikirlerini kaydettiği, OPEX ve yönetici değerlendirmelerinin yürütüldüğü, onaylanan çalışmaların uygulama sonuçlarının izlendiği ve fayda sonuçlarının raporlandığı web tabanlı bir Kaizen yönetim sistemidir.

Bu belge projenin geliştirici rehberidir; 20 iş günlük geliştirme süresini, MVP kapsamını, teknik kararları, günlük çıktıları, riskleri ve tamamlanma ölçütlerini tanımlar.

## 2. TEMEL PROJE KARARLARI
- Proje genel ve kurumsal bağımsızdır.
- Gerçek şirket adı, logosu, çalışanı veya operasyon verisi kullanılmaz.
- Teknoloji yığını Laravel, PHP, MySQL, Blade, Bootstrap, Vite ve Chart.js’tir.
- **Ürünleştirme İlkesi:** Müşteriden müşteriye (işletmeye göre) değişebilecek iş verileri (kategori, departman vb.) ve iş süreçleri (onay akışları) mümkün olduğunca kod içine sabit gömülmeyecektir. Uygun alanlar veritabanı veya yapılandırma üzerinden yönetilebilir olacak ("dynamic-by-default"). Ancak bu durum **multi-tenant** bir yapı olduğu anlamına gelmez. Her kurum kendi yapılandırmasıyla kendi kurulumunu kullanır, SaaS (multi-tenant) yaklaşımı kapsam dışıdır.
- Roller:
  - EMPLOYEE
  - OPEX_SPECIALIST
  - MANAGER
  - ADMIN
- Kaizen durumları:
  - DRAFT
  - SUBMITTED
  - REVISION_REQUESTED
  - MANAGER_REVIEW
  - APPROVED
  - IN_PROGRESS
  - COMPLETED
  - REJECTED
- Belgelenmiş dokuz kanonik durum geçişi korunur.
- `OPEX_REVIEW` adında bir durum eklenmez.
- ADMIN rolüne otomatik iş onayı yetkisi verilmez.
- Atanmış olmak tek başına durum değiştirme yetkisi sağlamaz.
- COMPLETED ve REJECTED terminal durumlardır.
- Laravel katmanlı monolit mimari, Service katmanı, Policy, transaction ve test yaklaşımı korunur.
- PR başlıkları ve açıklamaları İngilizce; proje belgeleri ve Issue içerikleri Türkçedir.
- Solo GitHub akışı Issue → branch → atomik commit → push → PR → self-review → self-merge şeklindedir.

## 3. ÖZELLİK ÖNCELİKLERİ VE KAPSAM

### Zorunlu Çekirdek Kapsam
- Authentication
- RBAC ve Policy
- Kaizen CRUD
- Güvenli dosya ve yorum yönetimi
- Dokuz geçişli onay iş akışı
- Durum geçmişi ve audit log
- Bildirimler
- Dashboard ve raporlama
- Testler ve güvenlik kontrolleri

### Kesin Genişletilmiş Kapsam
- Gerçek SMTP e-posta bildirimi
- Bulut depolamaya hazır Laravel Storage
- Termin ve gecikme takibi
- Değerlendirme puanı ve öncelik matrisi
- Hedeflenen ve gerçekleşen fayda
- CSV dışa aktarma
- Responsive arayüz ve PWA

### Koşullu Kapsam
- AI destekli Kaizen taslak yardımcısı

### Kapsam Dışı
- SMS gönderimi
- Gerçek zamanlı sohbet
- Native Android veya iOS uygulaması
- Multi-tenant yapı
- Gerçek ERP bağlantısı
- Yapay zekânın otomatik kayıt veya onay yapması
- Gerçek şirket verileri

## 4. GÜNLÜK ÇALIŞMA YAKLAŞIMI
- Günlük yaklaşık 4–6 saat aktif çalışma
- Kısa öğrenme ve tasarım bölümü
- Kullanıcının seviyesine uygun manuel PHP/Laravel/MySQL uygulaması
- Antigravity ile iskelet, tekrar eden kod, test ve inceleme desteği
- Günlük kapsama göre yaklaşık 2–5 anlamlı atomik commit
- Yapay commit veya gereksiz dosya bölme yapılmaması
- Her gün test, güvenlik, GitHub self-review ve staj defteri kaydı
- Büyük paket indirmelerinden önce kullanıcıya haber verilmesi

## 5. GÜNLÜK GELİŞTİRME PLANI VE ÇIKTILAR

Günlük yaklaşım: 2 ana ürün capability + entegrasyon + security + tests + Chrome review.

| Gün | Çalışma Odağı | Önerilen Branch Türü |
|---|---|---|
| Gün 1 | Repository, kapsam, GitHub çalışma sistemi, README, uygulama planı, katkı rehberi, şablonlar ve backlog. | docs/ |
| Gün 2 | Fonksiyonel gereksinimler, roller, kullanıcı senaryoları, iş kuralları ve durum geçişleri. | docs/ |
| Gün 3 | ER diyagramı, tablo sözlüğü, mimari kararlar, yetki matrisi ve wireframe’ler. | docs/ |
| Gün 4 | Laravel iskeleti, ortam ayarları, MySQL bağlantısı, temel layout, sağlık kontrolü ve ilk test. | chore/ |
| Gün 5 | Domain modeli, referans verileri, rol/durum enum'ları, departman/kategori modelleri ve sentetik demo verileri. | feature/ |
| Gün 6 | Kimlik doğrulama, oturum güvenliği, özel Blade giriş ekranı ve aktif/pasif kullanıcı kontrolü. | feature/ |
| Gün 7 | Rol tabanlı yetkilendirme, Role middleware, Laravel Policy, kullanıcı ve departman yönetimi. | feature/ |
| Gün 8 | Kaizen oluşturma, taslak yönetimi, form doğrulama, detay ekranı ve güvenli mass-assignment. | feature/ |
| Gün 9 | Listeleme, taslak düzenleme ve dynamic business audit (Müşteriye özel sabit kodların tespiti). | feature/ |
| Gün 10 | Enterprise evidence/media/attachment module | feature/ |
| Gün 11 | Dynamic approval workflow + stage config + history | feature/ |
| Gün 12 | Uygulama planlama ve yürütme altyapısı (Post-approval execution) | feature/ |
| Gün 13 | Approval configuration administration and organization management | feature/ |
| Gün 14 | Notifications, work queue and deadline tracking | feature/ |
| Gün 15 | Dynamic evaluation criteria and weighted scoring | feature/ |
| Gün 16 | Dynamic optional benefit types and target/realized metrics | feature/ |
| Gün 17 | Implementation execution tracking, progress and completion | feature/ |
| Gün 18 | Dashboard, reporting and CSV export | feature/ |
| Gün 19 | Enterprise hardening, security, performance, N+1, index and hard-code audit | chore/ |
| Gün 20 | Final professional UI/UX, responsive/accessibility, documentation, demo and delivery | docs/ |

## 6. GÜNLÜK FAZLARIN AYRINTILARI (Gün 5-20)

### Gün 5 — Domain Temeli ve Referans Verileri
- Kalan uygulama planının güncellenmesi
- Rol ve Kaizen durum PHP backed enum’ları
- Departman ve kategori migration/model yapıları
- Kullanıcı tablosunun rol ve departman alanlarıyla genişletilmesi
- Foreign key, unique ve indeks kuralları
- Factory, seeder ve sentetik demo verileri
- Migration, model, enum ve ilişki testleri

### Gün 6 — Kimlik Doğrulama ve Oturum Güvenliği
- Özel Blade giriş ekranı
- Login, logout ve session yenileme
- Rate limit ve güvenli yönlendirme
- Aktif/pasif kullanıcı kontrolü
- Authentication feature testleri

### Gün 7 — Rol Tabanlı Yetkilendirme ve Yönetim
- Role middleware
- Laravel Policy yapısı
- Yetkisiz erişim ekranları
- Kullanıcı, departman ve kategori yönetimi
- Yetkilendirme testleri

### Gün 8 — Kaizen Oluşturma ve Taslak Yönetimi
- Kaizen oluşturma formu
- Taslak kaydetme
- Validasyon
- Detay ekranı
- Güvenli mass-assignment

### Gün 9 — Kaizen Listeleme, Düzenleme ve Dinamiklik Denetimi
- Yetkiye göre listeleme, arama, filtreleme ve sayfalama
- Taslak düzenleme (UI entegrasyonu)
- Empty state ve validation hata gösterimleri
- Gün 1-8 hard-coded business value audit ve dinamik mimari yol haritası güncellemesi

### Gün 10 — Enterprise evidence/media/attachment module
- Attachment domain, private storage configuration, upload security
- Mevcut ve Önerilen Durum alanlarına güvenli çoklu fotoğraf/dosya ekleri (metin alanları zorunlu kalacak)
- Dosya türü ve boyut kontrolü
- Create/edit/detail entegrasyonu ve yetkilendirme

### Gün 11 — Dynamic approval workflow + stage config + history
- Onay sürecinin sabit kod yerine dinamik `approval_workflows` / `approval_stages` altyapısına geçirilmesi.
- `KaizenWorkflowInstance` ve immutable (append-only) `KaizenWorkflowTransition` tablolarıyla versiyonlanmış iş akışı oluşturulması.
- `MANAGER_REVIEW` gibi iş aşamalarının hard-coded olmaktan çıkarılıp dinamik `ApprovalStage` domain'ine devredilmesi.
- Lifecycle statü geçmişi (`KaizenStatusHistory`) ile Onay İş Akışı geçmişinin (`KaizenWorkflowTransition`) birbirinden ayrılması.

### Gün 12 — Uygulama planlama ve yürütme altyapısı (Tamamlandı)
- Onaylanan Kaizenlerin uygulamaya alınması (IN_PROGRESS)
- Sorumlu (assignee) atamaları ve hedeflenen termin (target_date) takibi
- Uygulama süreci (actual_result) ve tamamlama (COMPLETED) işlemleri
- Durum geçişlerinde append-only loglama ve yetkilendirmeler
- **Teslimat Durumu:** Post-approval execution altyapısı ve HTTP/Blade UI entegrasyonu başarıyla tamamlandı. Capability bazlı yetkilendirme sağlandı. Assignment audit log'a, Start/Complete işlemleri lifecycle history'ye yazıldı. 1440/768/390 responsive manuel QA tamamlanıp taşma sorunları düzeltildi. Issue #29 PR inceleme aşamasındadır.

### Gün 13 — Approval configuration administration and organization management
- Mevcut kullanıcı, departman ve approval workflow yapılarını yeniden kullanan yönetim katmanı.
- Yönetim işlemleri için role bağlı olmayan (system ve department scope) capability tabanlı yetkilendirme (`UserCapability` ve `UserCapabilityResolver` yeniden kullanılacak).
- Kullanıcı ve departman kayıtlarında güvenli aktif/pasif (deactivate) yönetimi; geçmiş kayıtları korumak adına fiziksel silme (delete) işlemleri engellenecek.
- Approval workflow yapılandırmalarını, başlamış workflow'ları bozmadan (snapshot/versiyonlama ile) yönetme.
- Yapılandırma ve organizasyon değişikliklerini `audit_logs` tablosuna append-only olarak kaydetme.
- Güvenli HTTP ve Blade yönetim arayüzlerinin oluşturulması.

**Gün 13 Mimari Kararları:**
- **Varlık Matrisi:** User/Department listeleme, update ve toggle. Workflow listeleme, activation, sıralama ve yetki atama. Admin navigation.
- **Tarihsel Workflow Koruması:** Kullanılmış workflow definition'lar ve stage'ler değiştirilmeyecek veya silinmeyecek; pasif duruma alınıp gerekirse yeni versiyon açılacaktır. (Version / Active-Passive strategy)
- **Kullanıcı / Departman Silme Politikası:** Hiçbir kullanıcı veya departman silinmeyecek, `is_active = false` ile pasife alınacaktır.
- **Capability Kararı:** Mevcut `user_capability_grants` (departman kapsamlı) yapısı aynen korunacaktır. `department_id = null` yaklaşımı, MySQL unique constraint'lerdeki NULL tekillik açığı (birden çok NULL satıra izin verilmesi) nedeniyle reddedilmiştir. Sistem kapsamlı (global) yetkiler için yeni bir `user_system_capability_grants` tablosu tasarlanacak ve DB seviyesinde `(user_id, capability)` tekilliği garanti altına alınacaktır. Resolver API'si `allowsSystem()` ve `allows()` olarak iki ayrı metoda ayrılarak yanlış scope sorguları engellenecektir. Ayrıca yetki yönetimi (grant/revoke) `organization.manage`'den ayrılarak `authorization.manage` yetkisi altına alınacaktır. Role-name bypass kesinlikle reddedilmiş olup, ilk global yetki ataması için idempotent ve log bırakan kontrollü bir Artisan bootstrap komutu kullanılacaktır. Mevcut Gün 12 testleri ve davranışları regression testlerinden geçirilecektir.
- **Audit Kararı:** Generic `audit_logs` yapısı üzerinden organizasyon ve yapılandırma değişiklikleri actor bazlı (authenticated user) ve JSON metadata ile loglanacaktır.

**Kapsam Dışı Bırakılanlar:** Bildirimler (e-posta/SMS), deadline/work queue, ağırlıklı değerlendirme puanları, drag-and-drop UI, SSO, detaylı dashboard.

**Gün 13 Önerilen Çalışma Blokları:**
1. Başlangıç denetimi, kapsam ve branch planlaması
2. Capability ve model (persistence) güncellemeleri, migration testleri
3. Organizasyon yönetim (Action/Service) katmanı ve testleri
4. Workflow config yönetim (Action/Service) katmanı ve versiyonlama/pasife alma mantığı
5. Yönetim (HTTP Controller, Request, Policy) katmanı, IDOR/validation testleri
6. Blade yönetim arayüzleri, navigation ve görünürlük testleri
7. Mimari denetim, test paketinin kapatılması ve PR hazırlığı

### Gün 14 — User/organization management + notifications + work queue
- Kullanıcı ve organizasyon yönetimi tamamlanması
- SMTP e-posta bildirimleri ve uygulama içi bildirim merkezi
- Termin ve iş kuyruğu (work queue) oluşturulması

### Gün 15 — Dynamic evaluation criteria + weighted scoring
- Dinamik değerlendirme kriterleri ve puanlama/ağırlık sistemleri oluşturulacak (Etki, maliyet vb.)
- Puanlama kuralları ve yetkili ekran

### Gün 16 — Dynamic optional benefit types + target/realized metrics
- Dinamik fayda türleri (Zaman, Kalite, Maliyet, Çevre, İş Güvenliği vb.) eklenecek
- Hedeflenen ve gerçekleşen mali/zaman faydaları takibi

### Gün 17 — Implementation/execution tracking + responsibility + deadlines
- Uygulama takibi, sorumluluk atamaları ve termin (deadline) yönetimi
- İlerleme kaydetme ve terminal durum testleri

### Gün 18 — Dashboard + reporting + export
- Gerçek dinamik veri ve yapılandırmalarla çalışan role göre dashboard
- Raporlama (fayda raporları vb.) ve CSV dışa aktarma

### Gün 19 — Enterprise hardening + final hard-code/security/performance audit
- Final hard-coded business value audit
- Yetkilendirme regresyon testleri, performans, N+1 sorgu ve indeks kontrolleri
- Uçtan uca iş akışı testleri ve production build doğrulaması

### Gün 20 — Final product UI/UX + documentation + demo + delivery
- Profesyonel final UI/UX turu
- Responsive erişilebilirlik, Chart.js grafikleri ve PWA kurulumu
- Kullanıcı/teknik dokümantasyon, sürüm etiketi ve README final güncellemesi
- Sunum ve proje teslim hazırlığı (Staj defteri ve final proje özeti)

## 7. PLANLANAN VERİ MODELİ

| Tablo | Amaç |
|---|---|
| users | Kullanıcı hesapları ve roller. |
| departments | Şirket departmanları tanımları. |
| kaizen_categories | İyileştirme kategori tanımları. |
| kaizens | Ana Kaizen öneri kayıtları. |
| kaizen_attachments | Önerilere eklenen dosyalar. |
| kaizen_comments | Değerlendirme ve uygulama süreçlerindeki yorumlar. |
| kaizen_status_histories | (Platform Lifecycle) Değiştirilemez kaba durum (DRAFT, SUBMITTED) geçiş logları. |
| approval_workflows | Dinamik onay iş akışı sürümleri (versiyonları). |
| approval_stages | Bir iş akışındaki dinamik onay aşamaları (Örn: OPEX, Yönetici, Kurul). |
| kaizen_workflow_instances | Bir Kaizen'in bağlı bulunduğu spesifik iş akışı versiyon örneği. |
| kaizen_workflow_transitions | İş akışı aşamalarındaki tarihsel değişmez onay/geçiş logları. |
| implementation_records | Uygulama detayları ve gerçekleşen faydalar. |
| audit_logs | Kritik işlemlerin izlenmesi. |

Nihai alan ve ilişkilerin Gün 3 ER diyagramıyla kesinleşeceği belirtilmektedir.

## 8. DURUM GEÇİŞLERİ (GÜN 11 İTİBARIYLA LEGACY / PLATFORM LIFECYCLE)

> **DİKKAT:** Gün 11 ile birlikte `MANAGER_REVIEW` gibi kuruma özgü onay aşamaları (organizational states) **LEGACY** kabul edilmektedir. Artık yeni dinamik onay iş akışı (DATABASE-DRIVEN) sistemine geçilmiştir.
> `KaizenStatus` enum'u, onay silsilesi yerine sadece platform seviyesi ana yaşam döngüsünü (DRAFT, SUBMITTED, REVISION_REQUESTED, APPROVED, REJECTED, vb.) taşıyacaktır.

| Mevcut Durum | İşlem | Yeni Durum | Yetkili Rol |
|---|---|---|---|
| DRAFT | Çalışan tarafından gönderildi | SUBMITTED | EMPLOYEE |
| REVISION_REQUESTED | Çalışan tarafından güncellendi | SUBMITTED | EMPLOYEE |
| SUBMITTED, ... (Ara Aşamalar) | Onaycı (Reviewer) tarafından revizyon istendi | REVISION_REQUESTED | Atanmış Onaycı |
| SUBMITTED, ... (Ara Aşamalar) | Onaycı (Reviewer) tarafından ara onay verildi | (Mevcut Statü Korunur) | Atanmış Onaycı |
| SUBMITTED, ... (Ara Aşamalar) | Onaycı (Reviewer) tarafından reddedildi | REJECTED | Atanmış Onaycı |
| SUBMITTED, ... (Nihai Aşama) | Nihai Onaycı (Final Reviewer) tarafından onaylandı | APPROVED | Atanmış Nihai Onaycı |
| APPROVED | Uygulamayı başlat | IN_PROGRESS | OPEX_SPECIALIST / Yetkili MANAGER |
| IN_PROGRESS | Sonuçları kaydet ve tamamla | COMPLETED | OPEX_SPECIALIST / Yetkili MANAGER |

Durum geçişleri dinamik iş akışı (ApprovalWorkflow) motoru üzerinden sağlanır, organizasyonel onay aşamaları (Yönetici, Kurul, OPEX) veritabanı tablolarında barındırılır. (Gün 11 sonrası onay akışı `ApprovalWorkflowResolver` ve `StartKaizenWorkflow` ile devralınmıştır).

**Atama (Assignment) ve Yürütme (Execution) Sınırları (Gün 12 İtibarıyla):**
- Implementation (Uygulama) yetkileri statik rollere (OPEX, MANAGER vb.) bağlı değildir; doğrudan **Capability Grant** sistemine (örn. `KAIZEN_IMPLEMENTATION_ASSIGN`) bağlıdır.
- Grant'ler (Yetkinlik atamaları) MVP kapsamında departman bazlıdır (Kullanıcı + Departman + Yetkinlik).
- Atama (Assignment) gibi metadata değişiklikleri `audit_logs` tablosuna append-only olarak yazılır. Statü geçmişine sahte satır eklenmez.
- `START` ve `COMPLETE` işlemleri ise doğrudan `IN_PROGRESS` ve `COMPLETED` durum geçişlerini tetiklediği için `kaizen_status_histories` (lifecycle) tablosunda loglanır.
- Capability (Yetkinlik) atama yönetim ekranları Gün 13 kapsamındadır; MVP için seed/tinker veya backend resolver katmanı kullanılır.
- Atanmış olmak tek başına lifecycle statüsü değiştirme yetkisi sağlamaz. Assignee kullanıcı Kaizen'i görebilir ve kendisine izin verilen uygulama bilgilerini (actual_result vb.) kaydedebilir. Ancak yalnızca atanmış olduğu için IN_PROGRESS veya COMPLETED geçişi yapamaz.
- Yorumlar (Comments) iletişim altyapısıdır; yapılandırılmış execution plan veya progress tracking olarak kullanılamaz (Yapılandırılmış takip Gün 17 kapsamındadır).

## 9. YETKİ İLKELERİ
- Çalışan yalnızca kendi kayıtlarına erişir.
- Taslak veya düzeltme istenen kayıtları değiştirebilir.
- OPEX değerlendirme ve uygulama takibine erişir.
- Yönetici yalnızca kendi sorumluluk kapsamındaki kayıtları değerlendirir.
- Admin kullanıcı ve sistem tanımlarını yönetir.
- Durum değiştiren her işlem backend policy ve iş kuralı kontrolünden geçer.
- Arayüzde buton gizlemek tek başına yetkilendirme değildir.

## 10. GÜVENLİK VE GİZLİLİK KONTROLLERİ
- CSRF koruması kapatılmayacaktır.
- Girdiler sunucu tarafında doğrulanacaktır.
- Parolalar Laravel hash mekanizmasıyla tutulacaktır.
- Login sonrasında session ID yenilenecektir.
- Backend Policies/Gates kullanılacaktır.
- Dosya türü, boyutu, uzantısı ve erişimi doğrulanacaktır.
- .env, parola, API anahtarı ve kişisel veri Git’e eklenmeyecektir.
- Production ortamında debug kapalı olacaktır.
- Hassas veriler loglanmayacaktır.
- Demo kayıtları yalnızca sentetik olacaktır.

## 11. RİSKLER VE ÖNLEMLER

| Risk | Etki | Önlem |
|---|---|---|
| Kapsam büyümesi | Teslim gecikmesi | Zorunlu/koşullu/kapsam dışı ayrımına kesin uyulması |
| AI API bağımlılığı | Sistemin kilitlenmesi | Feature flag kullanımı, mock test, iptal kontrol kapısı |
| E-posta yapılandırması | Kurumsal spama düşme | Test ortamı ve güvenli environment variables |
| Dosya güvenliği | Sisteme zararlı kod | Tür, boyut, uzantı, erişim ve depolama kontrolleri |
| Yetki açığı (IDOR vs) | Veri sızıntısı | Policy kullanımı, feature ve authorization testleri |
| Multi-tenant ve ERP karmaşıklığı | Projenin tamamlanamaması | MVP kapsamı dışında tutulması |
| Zaman riski | Yarım kalan ürün | Gün 18 kontrol noktası ve koşullu özelliklerin iptali |
| Gerçek veri riski | Gizlilik ihlali | Yalnızca sentetik demo verilerinin kullanılması |

## 12. SOLO GITHUB VE PULL REQUEST AKIŞI
1. Issue oluşturma
2. Project In Progress aşamasına alma
3. Issue branch’i oluşturma
4. Küçük commitler halinde geliştirme
5. Test ve kalite kontrolleri
6. Push işlemi
7. PR oluşturma
8. Review süreçleri ve self-review yapılması
9. Kontrollü self-merge
10. Issue kapatma ve Project üzerinde Done yapılması
11. İş bitiminde branch silme

- Require a pull request before merging kullanılabilir.
- Tek geliştirici olduğu için Require approvals zorunlu olmamalıdır.
- Yapay zekâ incelemesi insan onayı değildir.
- Nihai merge kararı geliştiriciye aittir.

## 13. DEĞİŞİKLİK YÖNETİMİ
MVP kapsamını, rol yetkilerini, durum geçişlerini veya güvenlik yaklaşımını değiştiren kararların:
- Ayrı Issue ile gerekçelendirilmesi
- İlgili dokümanların aynı PR’da güncellenmesi
- Takvim ve risk etkisinin yazılması
- Kapsam genişletmeden önce zorunlu MVP’nin kontrol edilmesi
gerekmektedir.

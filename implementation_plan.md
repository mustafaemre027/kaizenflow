# KaizenFlow – Uygulama Planı

## 1. GENEL BAKIŞ
KaizenFlow, çalışanların süreç iyileştirme fikirlerini kaydettiği, OPEX ve yönetici değerlendirmelerinin yürütüldüğü, onaylanan çalışmaların uygulama sonuçlarının izlendiği ve fayda sonuçlarının raporlandığı web tabanlı bir Kaizen yönetim sistemidir.

Bu belge projenin geliştirici rehberidir; 20 iş günlük geliştirme süresini, MVP kapsamını, teknik kararları, günlük çıktıları, riskleri ve tamamlanma ölçütlerini tanımlar.

## 2. TEMEL PROJE KARARLARI
- Proje genel ve kurumsal bağımsızdır.
- Gerçek şirket adı, logosu, çalışanı veya operasyon verisi kullanılmaz.
- Teknoloji yığını Laravel, PHP, MySQL, Blade, Bootstrap, Vite ve Chart.js’tir.
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
| Gün 9 | Kaizen listeleme, yetkiye göre arama/filtreleme, sayfalama ve taslak düzenleme. | feature/ |
| Gün 10 | Güvenli dosya ekleri, tür/boyut kontrolü, yorumlar ve bulut depolamaya hazır Laravel Storage. | feature/ |
| Gün 11 | Merkezi durum geçiş motoru, Service katmanı işlemleri, transaction ve durum geçmişi kaydı. | feature/ |
| Gün 12 | OPEX inceleme, revizyon isteme, reddetme, yöneticiye iletme ve açıklama zorunlulukları. | feature/ |
| Gün 13 | Yönetici onayı, sorumlusu atama, uygulama takibi ve terminal durum (COMPLETED) geçişleri. | feature/ |
| Gün 14 | Uygulama içi bildirim merkezi, SMTP e-posta bildirimleri, termin/gecikme takibi ve audit log. | feature/ |
| Gün 15 | Değerlendirme puanı, öncelik matrisi (etki, maliyet, vb.), puanlama kuralları ve yetkili ekran. | feature/ |
| Gün 16 | Fayda ve tasarruf takibi, hedef/gerçekleşen mali ve zaman kazancı, departman bazlı özetler. | feature/ |
| Gün 17 | Role göre dashboard, Chart.js grafikleri, tarih filtreleri, responsive erişilebilirlik ve PWA. | feature/ |
| Gün 18 | Gelişmiş raporlama, CSV dışa aktarma, regresyon testleri, N+1/indeks/güvenlik kontrolleri. | test/ |
| Gün 19 | Uçtan uca test, demo senaryosu, kurulum, koşullu AI taslak yardımcısı (şartlar sağlanırsa). | chore/ |
| Gün 20 | Final teslim, tam regresyon testi, sürüm etiketi, README final güncellemesi ve sunum hazırlığı. | docs/ |

## 6. GÜNLÜK FAZLARIN AYRINTILARI (Gün 5-20)

### Gün 5 — Domain Temeli ve Referans Verileri
- Kalan uygulama planının güncellenmesi
- Rol ve Kaizen durum PHP backed enum’ları
- Departman ve kategori migration/model yapıları
- Kullanıcı tablosunun rol ve departman alanlarıyla genişletilmesi
- Foreign key, unique ve indeks kuralları
- Factory, seeder ve sentetik demo verileri
- Migration, model, enum ve ilişki testleri
- Manuel öğrenme: PHP enum, migration ve Eloquent ilişki temelleri

### Gün 6 — Kimlik Doğrulama ve Oturum Güvenliği
- Özel Blade giriş ekranı
- Login, logout ve session yenileme
- Rate limit ve güvenli yönlendirme
- Aktif/pasif kullanıcı kontrolü
- Authentication feature testleri
- Manuel öğrenme: request validation, controller ve session akışı

### Gün 7 — Rol Tabanlı Yetkilendirme ve Yönetim
- Role middleware
- Laravel Policy yapısı
- Yetkisiz erişim ekranları
- Kullanıcı, departman ve kategori yönetimi
- Yetkilendirme testleri
- Manuel öğrenme: middleware, policy ve authorization

### Gün 8 — Kaizen Oluşturma ve Taslak Yönetimi
- Kaizen oluşturma formu
- Taslak kaydetme
- Validasyon
- Detay ekranı
- Güvenli mass-assignment
- Manuel öğrenme: form, validation, model ve controller

### Gün 9 — Kaizen Listeleme ve Düzenleme
- Yetkiye göre listeleme
- Arama, filtreleme ve sıralama
- Sayfalama
- Taslak düzenleme
- Empty state ve validation hata gösterimleri
- Manuel öğrenme: Eloquent sorguları ve pagination

### Gün 10 — Dosya Ekleri ve Yorumlar
- Güvenli dosya yükleme
- Dosya türü ve boyut kontrolü
- Yetkili indirme
- Kaizen yorumları
- Laravel Storage abstraction
- Yerel depolama varsayılan olacak şekilde S3 uyumlu bulut depolamaya hazır tasarım
- Manuel öğrenme: file validation ve storage işlemleri

### Gün 11 — Merkezi Durum Geçiş Motoru
- Dokuz kanonik geçişin Service katmanında uygulanması
- Yetki ve mevcut durum kontrolü
- Transaction kullanımı
- Durum geçmişi kaydı
- Başarılı ve başarısız geçiş testleri
- Manuel öğrenme: service, transaction ve iş kuralları

### Gün 12 — OPEX İnceleme ve Revizyon Süreci
- Gönderilen Kaizenleri inceleme
- Revizyon isteme
- Reddetme
- Yönetici değerlendirmesine yönlendirme
- Açıklama zorunlulukları
- Feature ve authorization testleri

### Gün 13 — Yönetici Onayı ve Uygulama Takibi
- Yönetici onayı
- Uygulama sorumlusu atama
- Uygulamaya alma
- Tamamlanma kaydı
- Hedef tarih ve sorumlu takibi
- Yetki ve terminal durum testleri

### Gün 14 — Bildirim, Termin ve İşlem Geçmişi
- Uygulama içi bildirim merkezi
- Gerçek SMTP tabanlı e-posta bildirimleri
- Geliştirmede güvenli test posta kutusu yaklaşımı
- Yaklaşan termin ve geciken Kaizen göstergeleri
- Audit log ve etkinlik zaman çizelgesi
- Bildirim gönderim hatalarının ana işlemi bozmaması
- Manuel öğrenme: mail, notification ve audit mantığı

### Gün 15 — Değerlendirme Puanı ve Öncelik Matrisi
- Etki, maliyet, uygulanabilirlik ve aciliyet puanları
- Sunucu tarafında hesaplanan toplam değerlendirme puanı
- Düşük, orta ve yüksek öncelik sınıflandırması
- Yetkili değerlendirme ekranı
- Puanlama kuralları ve sınır değer testleri

### Gün 16 — Fayda ve Tasarruf Takibi
- Hedeflenen ve gerçekleşen mali fayda
- Zaman kazancı
- Kalite ve süreç iyileştirme göstergeleri
- Para birimi ve sayısal değer validasyonları
- Hedef/gerçekleşen karşılaştırması
- Departman ve kategori bazlı özetler

### Gün 17 — Dashboard, Grafikler ve PWA
- Role göre dashboard
- Chart.js grafiklerinin gerçek verilerle hazırlanması
- Durum, departman, kategori ve tarih filtreleri
- Responsive erişilebilirlik iyileştirmeleri
- PWA manifest ve temel kurulum desteği
- Native mobil uygulama geliştirilmeyeceği açıkça belirtilmeli

### Gün 18 — Raporlama, Dışa Aktarma ve Kalite Güvencesi
- Gelişmiş raporlama
- CSV dışa aktarma
- Yetkilendirme regresyon testleri
- N+1 sorgu ve indeks kontrolleri
- Güvenlik, validation ve dosya erişim testleri
- Performans ve hata senaryoları
- Production build doğrulaması

### Gün 19 — Uçtan Uca Test, Demo ve Koşullu AI Kararı
Öncelikli işler:
- Uçtan uca iş akışı testleri
- Güvenli sentetik demo verileri
- Kullanıcı ve teknik dokümantasyon
- Kurulum ve demo senaryosu
- Hata düzeltmeleri
- Sunum hazırlığı

Koşullu AI özelliği yalnızca aşağıdaki kontrol kapısı geçilirse geliştirilebilir:
- Tüm zorunlu modüller tamamlanmış olmalı
- Test ve production build başarılı olmalı
- Kritik veya yüksek seviye açık bulgu bulunmamalı
- Yetkilendirme ve dokuz durum geçişi eksiksiz çalışmalı
- Dashboard ve raporlama tamamlanmış olmalı
- En az Gün 19 ve Gün 20 süresi kalmış olmalı

Kontrol kapısı geçilirse:
- Kullanıcının verdiği bilgilerden başlık, problem tanımı, öneri ve beklenen fayda taslağı üreten AI yardımcısı hazırlanabilir.
- AI yalnızca öneri üretir.
- Otomatik kayıt, gönderim, onay, ret veya durum değişikliği yapamaz.
- API anahtarı yalnızca environment variable üzerinden okunur.
- Özellik varsayılan olarak feature flag ile kapalı tutulur.
- Testlerde gerçek dış API çağrısı yerine mock/fake kullanılır.

Kontrol kapısı geçilmezse AI özelliği iptal edilir ve Gün 19 tamamen test, güvenlik, dokümantasyon ve demo hazırlığına ayrılır.

### Gün 20 — Final Teslim ve Sürüm
- Tam regresyon testi
- Güvenlik ve secret taraması
- Production build
- Demo akışının son doğrulaması
- README ve teknik belgelerin final güncellemesi
- Sürüm etiketi ve release notları
- Sunum ve proje teslim hazırlığı
- Staj defteri ve final proje özeti

## 7. PLANLANAN VERİ MODELİ

| Tablo | Amaç |
|---|---|
| users | Kullanıcı hesapları ve roller. |
| departments | Şirket departmanları tanımları. |
| kaizen_categories | İyileştirme kategori tanımları. |
| kaizens | Ana Kaizen öneri kayıtları. |
| kaizen_attachments | Önerilere eklenen dosyalar. |
| kaizen_comments | Değerlendirme ve uygulama süreçlerindeki yorumlar. |
| kaizen_status_histories | Değiştirilemez durum geçiş logları. |
| implementation_records | Uygulama detayları ve gerçekleşen faydalar. |
| audit_logs | Kritik işlemlerin izlenmesi. |

Nihai alan ve ilişkilerin Gün 3 ER diyagramıyla kesinleşeceği belirtilmektedir.

## 8. DURUM GEÇİŞLERİ

| Mevcut Durum | İşlem | Yeni Durum | Yetkili Rol |
|---|---|---|---|
| DRAFT | Çalışan tarafından gönderildi | SUBMITTED | EMPLOYEE |
| REVISION_REQUESTED | Çalışan tarafından güncellendi | SUBMITTED | EMPLOYEE |
| SUBMITTED | Düzeltme istendi | REVISION_REQUESTED | OPEX_SPECIALIST |
| SUBMITTED | Onay için yöneticiye iletildi | MANAGER_REVIEW | OPEX_SPECIALIST |
| SUBMITTED | Reddedildi | REJECTED | OPEX_SPECIALIST |
| MANAGER_REVIEW | Onaylandı | APPROVED | MANAGER |
| MANAGER_REVIEW | Reddedildi | REJECTED | MANAGER |
| APPROVED | Uygulamayı başlat | IN_PROGRESS | OPEX_SPECIALIST / yetkili MANAGER |
| IN_PROGRESS | Sonuçları kaydet ve tamamla | COMPLETED | OPEX_SPECIALIST / yetkili MANAGER |

Durum geçişlerinin tek bir merkezi servis üzerinden uygulanacağı, controller veya Blade içinde kopyalanmayacağı belirtilmektedir.

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

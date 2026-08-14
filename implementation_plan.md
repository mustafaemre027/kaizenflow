# KaizenFlow – Uygulama Planı

## 1. GENEL BAKIŞ
KaizenFlow, çalışanların süreç iyileştirme fikirlerini kaydettiği, OPEX ve yönetici değerlendirmelerinin yürütüldüğü, onaylanan çalışmaların uygulama sonuçlarının izlendiği ve fayda sonuçlarının raporlandığı web tabanlı bir Kaizen yönetim sistemidir.

Bu belge projenin geliştirici rehberidir; 20 iş günlük geliştirme süresini, MVP kapsamını, teknik kararları, günlük çıktıları, riskleri ve tamamlanma ölçütlerini tanımlar.

## 2. TEMEL PROJE KARARLARI
- Proje tek geliştirici tarafından yürütülecektir.
- Backend ve sunucu taraflı arayüz PHP ve Laravel olacaktır.
- Arayüz Laravel Blade, Bootstrap ve gerektiğinde sade JavaScript ile geliştirilecektir.
- MySQL ana veritabanıdır.
- Proje genel bir kurumsal prototip olacaktır.
- Çoklu kiracılık MVP kapsamında olmayacaktır.
- Gerçek şirket adı, logosu, çalışan bilgileri veya operasyon verileri kullanılmayacaktır.
- Her iş günü Issue, branch ve odaklı PR ile izlenecektir.
- Harici incelemeci bulunmadığı için PR, test ve self-review sonrasında geliştirici tarafından merge edilecektir.
- Önce zorunlu MVP tamamlanacaktır.
- Ek özellikler yalnızca çekirdek akış kararlıysa ele alınacaktır.

## 3. AŞAMA YAPISI

| Faz | Aşama Adı | Gün |
|---|---|---|
| Faz 0 | Proje Yönetimi ve Başlangıç Dokümantasyonu | Gün 1 |
| Faz 1 | Gereksinim Analizi ve Sistem Tasarımı | Gün 2–3 |
| Faz 2 | Laravel Temeli, Veritabanı ve Yetkilendirme | Gün 4–5 |
| Faz 3 | Kaizen Öneri Yönetimi | Gün 6–8 |
| Faz 4 | Değerlendirme ve Onay İş Akışı | Gün 9–11 |
| Faz 5 | Uygulama, Fayda ve Denetim Takibi | Gün 12–14 |
| Faz 6 | Dashboard, Arama ve Raporlama | Gün 15–16 |
| Faz 7 | Test, Güvenlik ve Kullanıcı Deneyimi | Gün 17–18 |
| Faz 8 | Yayınlama Hazırlığı ve Final Teslimi | Gün 19–20 |

## 4. GÜNLÜK GELİŞTİRME VE PR PLANI

| Gün | Çalışma Odağı | Gün Sonu Teslimi | Önerilen Branch Türü |
|---|---|---|---|
| Gün 1 | Repository, kapsam, GitHub çalışma sistemi, README, uygulama planı, katkı rehberi, şablonlar ve backlog. | Planlama dokümanları | docs/ |
| Gün 2 | Fonksiyonel gereksinimler, roller, kullanıcı senaryoları, iş kuralları ve durum geçişleri. | Gereksinim belgesi | docs/ |
| Gün 3 | ER diyagramı, tablo sözlüğü, mimari kararlar, yetki matrisi ve wireframe’ler. | Tasarım belgesi | docs/ |
| Gün 4 | Laravel iskeleti, ortam ayarları, MySQL bağlantısı, temel layout, sağlık kontrolü ve ilk test. | Çalışan Laravel iskeleti | chore/ |
| Gün 5 | Authentication, kullanıcı rolleri, yetkilendirme ve sentetik kullanıcı seed verileri. | Giriş sistemi ve roller | feature/ |
| Gün 6 | Kaizen, departman, kategori ve ilgili veritabanı modelleri. | Veritabanı modelleri | feature/ |
| Gün 7 | Kaizen önerisi oluşturma, doğrulama ve listeleme. | Form ve listeleme ekranı | feature/ |
| Gün 8 | Detay görüntüleme, taslak güncelleme, güvenli dosya ekleri ve kayıt sahipliği kontrolü. | Detay ekranı ve dosya eki | feature/ |
| Gün 9 | OPEX ön değerlendirmesi, kabul, ret ve düzeltme işlemleri. | OPEX işlem akışı | feature/ |
| Gün 10 | Yönetici onayı, ret işlemleri ve yetkilendirme testleri. | Yönetici onay akışı | feature/ |
| Gün 11 | Yorum, durum geçmişi, düzeltme sonrası yeniden gönderme ve zaman çizelgesi. | Geçmiş ve yorum özellikleri | feature/ |
| Gün 12 | Uygulama sorumlusu, hedef tarih, uygulama adımları ve IN_PROGRESS geçişi. | Uygulama ataması | feature/ |
| Gün 13 | Sonuç kaydı, tahmini ve gerçekleşen fayda, COMPLETED geçişi. | Sonuç ve fayda kaydı | feature/ |
| Gün 14 | Audit log, uygulama içi bildirim ve hassas veri kontrolleri. | Log mekanizması | feature/ |
| Gün 15 | Dashboard kartları, grafikler ve rol uyumlu metrikler. | Dashboard görünümü | feature/ |
| Gün 16 | Arama, gelişmiş filtreler, sayfalama ve güvenli CSV dışa aktarma. | Filtreleme ve export | feature/ |
| Gün 17 | Kritik iş akışları, policy, validation ve durum geçişi testleri. | Entegrasyon testleri | test/ |
| Gün 18 | Güvenlik, yetkisiz erişim, dosya güvenliği, responsive tasarım ve erişilebilirlik kontrolleri. | Güvenlik ve arayüz testleri | fix/ |
| Gün 19 | Demo seed verileri, .env.example, production ayarları ve dağıtım hazırlığı. | Dağıtım paketi | chore/ |
| Gün 20 | Temiz kurulum, bütün testler, README güncellemesi, ekran görüntüleri, sunum ve final doğrulaması. | Final proje | docs/ |

## 5. FAZLARIN AYRINTILARI

### Faz 0
- **Yapılacak teknik çalışmalar:** Repository, GitHub Project panosu, backlog, label yapısı, Issue ve PR şablonları, README, implementation plan, CONTRIBUTING ve solo PR süreci.
- **Gün sonu teslim çıktısı:** Hazırlanmış repository ve başlangıç belgeleri.
- **Kontrol noktası:** Tüm temel belgelerin eksiksiz oluşturulması ve yapılandırılması.

### Faz 1
- **Yapılacak teknik çalışmalar:** Roller, gereksinimler, sabit onay akışı, durum geçişleri, ER diyagramı, tablo sözlüğü, wireframe ve yetki matrisi.
- **Gün sonu teslim çıktısı:** Sistem tasarımı ve gereksinim dokümanı.
- **Kontrol noktası:** İş kurallarının netleştirilmesi ve onaylanması.

### Faz 2
- **Yapılacak teknik çalışmalar:** Laravel temeli, MySQL, Blade, Bootstrap, authentication, session güvenliği, roller, middleware, policy ve demo kullanıcıları.
- **Gün sonu teslim çıktısı:** Çalışan login sistemi, temel arayüz ve yetkilendirme altyapısı.
- **Kontrol noktası:** Her role uygun girişin başarıyla test edilmesi.

### Faz 3
- **Yapılacak teknik çalışmalar:** Kaizen modelleri, migration’lar, ilişkiler, form doğrulama, taslaklar, listeleme, detay, güncelleme ve güvenli dosya yükleme.
- **Gün sonu teslim çıktısı:** Kaizen oluşturma, düzenleme ve dosya yükleme işlemleri.
- **Kontrol noktası:** Form validation kurallarının ve dosya yükleme güvenliğinin doğrulanması.

### Faz 4
- **Yapılacak teknik çalışmalar:** OPEX değerlendirme kuyruğu, yönetici onayı, düzeltme, gerekçeli ret, yorumlar ve değiştirilemez durum geçmişi.
- **Gün sonu teslim çıktısı:** Başarılı bir şekilde ilerleyen onay akışı mekanizması.
- **Kontrol noktası:** Durum geçişlerinin iş kurallarına (sadece yetkililer tarafından) uygun yapıldığının testi.

### Faz 5
- **Yapılacak teknik çalışmalar:** Uygulama planı, sorumlu kişi, hedef tarihler, sonuç ve fayda kayıtları, audit log ve uygulama içi bildirimler.
- **Gün sonu teslim çıktısı:** Kaizen uygulama ve fayda yönetimi modülü.
- **Kontrol noktası:** Hedeflenen fayda ile gerçekleşen faydanın doğru kaydedilmesi.

### Faz 6
- **Yapılacak teknik çalışmalar:** Dashboard, Chart.js grafikleri, arama, filtreleme, sayfalama ve güvenli CSV dışa aktarma.
- **Gün sonu teslim çıktısı:** Rol bazlı görsel istatistik paneli ve gelişmiş arama araçları.
- **Kontrol noktası:** Raporlama esnasında veri izolasyonu kontrolü.

### Faz 7
- **Yapılacak teknik çalışmalar:** Otomatik testler, policy kontrolleri, IDOR önleme, CSRF, validation, dosya güvenliği, responsive tasarım ve temel erişilebilirlik.
- **Gün sonu teslim çıktısı:** Başarılı test raporu ve güvenlik denetimleri.
- **Kontrol noktası:** Bütün kritik iş kurallarının (Policy/Gates vb.) güvenli çalıştığının teyidi.

### Faz 8
- **Yapılacak teknik çalışmalar:** Demo verileri, production hazırlığı, temiz kurulum testi, final dokümantasyonu, ekran görüntüleri ve sunum.
- **Gün sonu teslim çıktısı:** Projenin nihai yayına hazır versiyonu.
- **Kontrol noktası:** Temiz kurulum adımlarının eksiksiz çalışması.

## 6. MVP KAPSAMI

### Zorunlu Özellikler
- Session tabanlı giriş ve çıkış
- EMPLOYEE, OPEX_SPECIALIST, MANAGER ve ADMIN rolleri
- Departman ve kategori yönetimi
- Kaizen oluşturma, düzenleme, gönderme, listeleme ve detay
- Güvenli görsel/PDF ekleri
- OPEX değerlendirmesi
- Yönetici onayı
- Düzeltme ve gerekçeli ret
- Yorum ve durum geçmişi
- Uygulama sorumlusu ve hedef tarih
- Tahmini ve gerçekleşen fayda
- Rol uyumlu dashboard
- Arama, filtreleme ve sayfalama
- Audit log
- Sentetik demo verileri
- Kritik iş kuralları için otomatik testler

### Zaman Kalırsa
- PDF raporu
- Excel dışa aktarma
- E-posta bildirimi
- Puanlama ve önceliklendirme
- Departman hedefleri
- Rozet veya takdir sistemi
- Gelişmiş tarih karşılaştırmaları

### Kapsam Dışı
- ERP/MES/İK entegrasyonu
- SSO, Active Directory veya LDAP
- Dinamik onay akışı tasarım ekranı
- Yapay zekâ ile otomatik karar
- Otomatik finansal doğrulama
- Zorunlu SMS/e-posta entegrasyonu
- Mobil uygulama
- Multi-tenancy
- Gerçek şirket verileri
- Mikroservis mimarisi
- Gerçek zamanlı mesajlaşma

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

## 10. TEST STRATEJİSİ

### Birim Testleri

- Kaizen durum geçiş kuralları
- Fayda ve süre hesaplamaları
- Yetki kararlarını destekleyen saf iş kuralları
- CSV hücre/formül güvenliği yardımcıları

### Feature/Entegrasyon Testleri

- Login ve logout
- Rol, middleware ve policy kontrolleri
- Kaizen oluşturma, güncelleme, gönderme ve görüntüleme
- OPEX ve yönetici değerlendirme akışları
- Düzeltme ve yeniden gönderme
- Dosya yükleme doğrulaması ve erişim kontrolü
- Dashboard ve rapor sorguları
- Audit log üretimi

### Manuel Testler

- Kritik uçtan uca kullanıcı senaryoları
- Mobil, tablet ve masaüstü ekran kontrolleri
- Klavye ile gezinme ve temel erişilebilirlik
- Empty, validation, success ve error durumları
- Temiz ortamda kurulum

## 11. GÜVENLİK VE GİZLİLİK KONTROLLERİ
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

## 12. RİSKLER VE ÖNLEMLER

| Risk | Etki | Önlem |
|---|---|---|
| Laravel öğrenme süresi | Teslim gecikmesi | Sade dokümantasyon, AI desteği |
| Onay akışının büyümesi | Karışıklık | Sabit akış kullanılması |
| Günlük PR kapsamının büyümesi | Kontrol zorluğu | İşlerin küçültülmesi, düzenli commit |
| Tek geliştiricide inceleme eksikliği | Bug sızıntısı | Self-review, kapsamlı testler |
| Gerçek şirket verisinin eklenmesi | Gizlilik ihlali | Sıkı kontrol, sentetik seed verisi kullanma |
| Dosya yükleme güvenliği | Sisteme zarar | Validasyon, public olmayan dizin saklaması |
| Dashboard kapsamının büyümesi | Süre yetersizliği | Yalnızca temel özet ve grafiklerin sunulması |
| Dağıtım ortamının belirsizliği | Deployment hatası | Net .env.example tanımlanması |

## 13. SOLO GITHUB VE PULL REQUEST AKIŞI
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

## 14. GÜNLÜK PR TAMAMLANMA KRİTERLERİ
- [ ] Issue kriterleri
- [ ] Dar dosya kapsamı
- [ ] İlgili ve mevcut testler
- [ ] Laravel Pint
- [ ] Migration kontrolleri
- [ ] Policy ve rol kontrolleri
- [ ] Sunucu taraflı doğrulama
- [ ] Secret ve gerçek şirket verisi kontrolü
- [ ] Responsive kontrol
- [ ] Dokümantasyon
- [ ] Staged diff ve Files changed incelemesi
- [ ] Commit kalitesi
- [ ] Closes #IssueNumber
- [ ] Engelleyici hata bulunmaması
- [ ] Project kartının Review durumunda olması

## 15. PROJE GENELİ TAMAMLANMA KRİTERLERİ
- [ ] MVP
- [ ] Testler
- [ ] Migration/seed
- [ ] Yetkisiz erişim kontrolü
- [ ] Secret kontrolü
- [ ] Temiz kurulum
- [ ] Dashboard
- [ ] Responsive tasarım
- [ ] Dokümantasyon
- [ ] Demo kayıtları
- [ ] Ekran görüntüleri
- [ ] Issue/PR durumları

## 16. DEĞİŞİKLİK YÖNETİMİ
MVP kapsamını, rol yetkilerini, durum geçişlerini veya güvenlik yaklaşımını değiştiren kararların:
- Ayrı Issue ile gerekçelendirilmesi
- İlgili dokümanların aynı PR’da güncellenmesi
- Takvim ve risk etkisinin yazılması
- Kapsam genişletmeden önce zorunlu MVP’nin kontrol edilmesi
gerekmektedir.

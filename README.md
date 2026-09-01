# KaizenFlow
**Dijital Kaizen ve Sürekli İyileştirme Yönetim Sistemi**

## Projenin Amacı
KaizenFlow, çalışanların süreç iyileştirme fikirlerini dijital ortamda oluşturmasını, OPEX yetkilileri ve yöneticiler tarafından değerlendirilmesini, onaylanan çalışmaların uygulama sürecinin izlenmesini ve elde edilen faydaların raporlanmasını sağlayan web tabanlı bir iş akışı platformudur.

## Genel Kullanım İlkesi
Proje belirli bir şirkete özel değildir. Farklı üretim ve hizmet kuruluşlarına uyarlanabilecek genel bir sürekli iyileştirme prototipi olarak tasarlanmaktadır. Herhangi bir gerçek kurum veya kuruluşun sistemlerini doğrudan temsil etmez.

## Proje Durumu
Şu anki güncel duruma göre sistemde; güvenli kimlik doğrulama, hesap yaşam döngüsü, dinamik onay iş akışı (Approval Workflow), yapılandırılmış fayda tanımları, uygulama/yürütme takibi, güvenli yönetim dashboard'u, güvenli CSV raporlama ve kuyruk tabanlı bildirim döngüleri (notifications) başarıyla tamamlanmıştır.

## Gizlilik Notu
Projede hiçbir gerçek şirket adı, logo, çalışan bilgisi, üretim verisi veya finansal veri kullanılmayacaktır. Geliştirme ve test süreçlerinde yalnızca sentetik demo verileri kullanılacaktır.

## Temel Kullanım Akışı
1. Çalışanın Kaizen önerisi oluşturması
2. Onay İş Akışının başlatılması (Dinamik onaycı atamaları)
3. İlgili aşama (OPEX, Yönetici, Kurul vb.) onaycılarının değerlendirmesi
4. Gerekirse onaycıların düzeltme (revizyon) talebi
5. Nihai onay ve Uygulama Sorumlusu (Assignee) ile hedeflenen termin atanması
6. Uygulamanın yürütülmesi (Uygulamaya başlama ve tamamlama)
7. Elde edilen sonuçların ve faydanın kaydedilmesi
8. Yönetim panelinde metriklerin görüntülenmesi

## Planlanan Temel Özellikler
- **Kimlik doğrulama:** Kullanıcıların sisteme güvenli şekilde giriş yapabilmesi.
- **Yetenek (Capability) tabanlı yetkilendirme:** Yetki kontrollerinin statik roller yerine dinamik yetenekler (capabilities) üzerinden yapıldığı, güvenli ve esnek mimari.
- **Kaizen öneri yönetimi:** İyileştirme önerilerinin formlar aracılığıyla sisteme girilmesi ve izlenmesi.
- **Dosya ekleri:** Önerilere ilgili resim ve belgelerin eklenebilmesi.
- **Çok aşamalı değerlendirme:** Önerilerin OPEX ve yönetici aşamalarından geçerek onaylanması.
- **Uygulama takibi:** Onaylanan önerilerin uygulama süreçlerinin ve hedef tarihlerinin izlenmesi.
- **Fayda kaydı:** Uygulama sonrası elde edilen zaman, maliyet veya kalite faydalarının raporlanması.
- **Yorum ve durum geçmişi:** Her öneri için yapılan yorumların ve durum değişikliklerinin kayıt altında tutulması.
- **Dashboard:** Temel performans göstergelerinin (KPI) ve özet metriklerin görselleştirilmesi.
- **Arama ve filtreleme:** Kayıtlar arasında hızlı arama ve kriterlere göre filtreleme yapılabilmesi.
- **Denetim kaydı:** Kritik sistem işlemlerinin kayıt altına alınması ve izlenebilmesi.
- **Demo veri desteği:** Test ortamı için sentetik verilerin kolayca yüklenebilmesi.

## Kullanıcı Rolleri

| Rol | Açıklama |
|---|---|
| EMPLOYEE | Çalışan. Kendi Kaizen önerilerini oluşturabilir ve takip edebilir. |
| OPEX_SPECIALIST | Sürekli İyileştirme Uzmanı. Sistemin işleyişine rehberlik eder, kendisine iş akışında onay tanımlanmışsa değerlendirir. Yürütme aşamasını takip eder. |
| MANAGER | Departman Yöneticisi. Kendisine (departmanına veya özel olarak) atanan iş akışı aşamalarındaki önerileri onaylar, reddeder veya düzeltme ister. |
| ADMIN | Sistem Yöneticisi. Sistemdeki referans verilerine ve dinamik iş akışı tanımlarına (ApprovalWorkflow) erişebilir, kullanıcı ve rol yönetimini gerçekleştirir. |

*Not: Sistemdeki ayrıcalıklı işlemler (privileged operations) kullanıcıların taşıdıkları role göre değil, sahip oldukları yeteneklere (capabilities) göre denetlenmektedir.*

## Kaizen Durumları

| Durum | Açıklama |
|---|---|
| DRAFT | Taslak. Çalışan öneriyi henüz göndermemiştir, düzenlemeye devam edebilir. |
| SUBMITTED | Gönderildi. Öneri dinamik onay iş akışına dahil edilmiştir, ilgili aşama onaycılarını beklemektedir. |
| REVISION_REQUESTED | Düzeltme İstendi. İlgili aşamadaki onaycının revizyon isteği üzerine önerinin güncellenmesi beklenmektedir. |
| APPROVED | Onaylandı. İş akışındaki nihai (final) onaycı tarafından onaylanmış ve uygulama (yürütme) için beklemektedir. |
| IN_PROGRESS | Devam Ediyor. Onaylanan Kaizen önerisinin sorumlusu tarafından uygulaması sürmektedir. |
| COMPLETED | Tamamlandı. Uygulama bitmiş ve elde edilen sonuçlar kaydedilmiştir. |
| REJECTED | Reddedildi. Öneri iş akışındaki herhangi bir onaycı tarafından reddedilmiştir. |

*Durum geçişleri yalnızca yetkili roller ve tanımlı geçiş kuralları aracılığıyla değiştirilebilecektir.*

## Teknoloji Yığını

| Teknoloji | Kullanım Amacı |
|---|---|
| PHP | Sunucu taraflı ana programlama dili |
| Laravel | Ana uygulama çerçevesi (framework) |
| Laravel Blade | Şablon motoru (view katmanı) |
| HTML, CSS ve JavaScript | İstemci taraflı arayüz teknolojileri |
| Bootstrap | CSS bileşen çerçevesi |
| MySQL | İlişkisel veritabanı yönetim sistemi |
| Eloquent ORM | Veritabanı etkileşimleri |
| Laravel Migrations | Veritabanı şema yönetimi |
| Session tabanlı authentication | Kullanıcı kimlik doğrulama yapısı |
| PHPUnit | Birim testleri (Unit testing) |
| Laravel Pint | Kod stili denetimi ve biçimlendirme |
| PHP CLI ortamı | php.new aracılığıyla kurulan PHP, Composer ve Laravel Installer çalışma ortamı |
| MySQL Workbench | Yerel MySQL bağlantısı, şema yönetimi ve sorgu doğrulaması |
| Composer | PHP bağımlılık yöneticisi |
| Node.js/NPM | Frontend araçları ve paket yöneticisi |
| Git ve GitHub | Sürüm kontrolü ve kaynak kod yönetimi |

## Planlanan Teknik Yapı
Uygulama, Laravel MVC yapısını temel alan aşağıdaki bileşenlerle inşa edilecektir:
- **Models:** Veritabanı tablolarıyla etkileşim ve temel ilişkiler.
- **Controllers:** HTTP isteklerinin karşılanması ve ilgili servislere yönlendirilmesi.
- **Form Requests:** Gelen verilerin doğrulanması (validation).
- **Policies:** Kullanıcı yetkilendirme kontrolleri.
- **Services:** Karmaşık iş kurallarının barındırıldığı katman.
- **Blade Views:** Kullanıcıya sunulacak arayüz bileşenleri.
- **Migrations ve Seeders:** Veritabanı yapısı ve demo veri yönetimi.

*Karmaşık iş kuralları controller sınıflarında tutulmayacak; onay ve durum geçişleri servis katmanında merkezi olarak yönetilecektir.*

## Yerel Kurulum ve Çalıştırma

Gereksinimler:
- PHP 8.3 veya üzeri
- Composer 2
- Node.js ve npm
- MySQL 8
- Git

Kurulum sırası:

```powershell
git clone https://github.com/mustafaemre027/kaizenflow.git
cd kaizenflow
composer install
Copy-Item .env.example .env
php artisan key:generate
npm.cmd install --no-audit --no-fund
```

MySQL hazırlığı:
- `kaizenflow` isimli UTF-8 veritabanı oluşturulmalıdır.
- Yalnızca bu veritabanına yetkili `kaizenflow_app` gibi ayrı bir yerel uygulama kullanıcısı kullanılmalıdır.
- Gerçek parolalar README veya `.env.example` içine yazılmamalıdır.
- Yerel bağlantı bilgileri yalnızca sürüm kontrolü dışında tutulan (ignored) `.env` dosyasına eklenmelidir.

Migration:

```powershell
php artisan config:clear
php artisan migrate
php artisan migrate:status
```

Geliştirme ortamını çalıştırma:

Terminal 1:
```powershell
php artisan serve
```

Terminal 2:
```powershell
npm.cmd run dev
```

Uygulama adresi:
http://127.0.0.1:8000

Test ve production build:

```powershell
php artisan test
npm.cmd run build
```

## 20 İş Günlük Yol Haritası

| Gün | Aşama |
|---|---|
| Gün 1 | Proje yönetimi ve dokümantasyon |
| Gün 2–3 | Gereksinim ve mimari |
| Gün 4–5 | Laravel temeli ve yetkilendirme |
| Gün 6–8 | Kaizen öneri yönetimi |
| Gün 9–11 | Dinamik değerlendirme ve onay iş akışı (Approval Workflow) |
| Gün 12–14 | Uygulama, yürütme altyapısı ve bildirim/kuyruk yönetimi |
| Gün 15–16 | Dinamik değerlendirme kriterleri ve fayda türleri metrikleri |
| Gün 17–18 | Uygulama takibi, dashboard ve raporlama |
| Gün 19–20 | Güvenlik, optimizasyon, arayüz iyileştirmeleri ve proje teslimi |

*Ayrıntılı planlama için bkz: [implementation_plan.md](implementation_plan.md)*

## GitHub Çalışma Disiplini
Proje solo geliştiriciye uygun şu süreçle yürütülecektir:
- Issue oluşturma
- Issue numaralı branch
- Küçük Conventional Commit mesajları
- Pull Request
- Files changed üzerinden self-review
- Test, güvenlik, gizlilik ve dokümantasyon kontrolü
- Geliştiricinin kendi PR’ını kontrollü şekilde merge etmesi
- Issue kapatma ve branch silme

*Yapay zekâ geliştirme sürecinde öneriler sunabilir fakat merge yetkisi ve nihai sorumluluk geliştiricide olacaktır.*

## Güvenlik ve Gizlilik İlkeleri
Projede aşağıdaki güvenlik ve gizlilik ilkeleri uygulanacaktır:
- Parolaların açık metin olarak saklanmaması
- Sunucu taraflı veri doğrulama
- Backend seviyesinde sıkı yetki kontrolleri
- CSRF koruması
- Güvenli dosya yükleme kontrolleri
- SQL Injection önlemi olarak Eloquent veya parametreli sorgular kullanımı
- Gizli bilgilerin (credentials, keys) .env içinde tutulması
- Gerçek kurumsal verinin repository'de kesinlikle bulunmaması
- Kritik işlemlerin denetim kayıtlarına (audit log) yazılması

## Proje Belgeleri
- [Sistem Gereksinimleri](docs/requirements.md)
- [Kaizen İş Akışı ve Durum Geçişleri](docs/kaizen-workflow.md)
- [implementation_plan.md](implementation_plan.md)
- [CONTRIBUTING.md](CONTRIBUTING.md)
- [Sistem Mimarisi](docs/architecture/system-architecture.md)
- [Veritabanı Tasarımı](docs/architecture/database-design.md)

## Lisans
Projenin lisansı henüz belirlenmemiştir. Lisans seçilene kadar kaynak kod otomatik olarak açık kaynak kabul edilmemelidir.

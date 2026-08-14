# KaizenFlow
**Dijital Kaizen ve Sürekli İyileştirme Yönetim Sistemi**

## Projenin Amacı
KaizenFlow, çalışanların süreç iyileştirme fikirlerini dijital ortamda oluşturmasını, OPEX yetkilileri ve yöneticiler tarafından değerlendirilmesini, onaylanan çalışmaların uygulama sürecinin izlenmesini ve elde edilen faydaların raporlanmasını sağlayan web tabanlı bir iş akışı platformudur.

## Genel Kullanım İlkesi
Proje belirli bir şirkete özel değildir. Farklı üretim ve hizmet kuruluşlarına uyarlanabilecek genel bir sürekli iyileştirme prototipi olarak tasarlanmaktadır. Herhangi bir gerçek kurum veya kuruluşun sistemlerini doğrudan temsil etmez.

## Proje Durumu
Proje henüz planlama ve başlangıç dokümantasyonu aşamasındadır. Aşağıda belirtilen tüm özellikler planlanan özellikleri yansıtmakta olup, henüz geliştirilmemiş veya tamamlanmamıştır.

## Gizlilik Notu
Projede hiçbir gerçek şirket adı, logo, çalışan bilgisi, üretim verisi veya finansal veri kullanılmayacaktır. Geliştirme ve test süreçlerinde yalnızca sentetik demo verileri kullanılacaktır.

## Temel Kullanım Akışı
1. Çalışanın Kaizen önerisi oluşturması
2. OPEX ön değerlendirmesi
3. Gerekirse düzeltme talebi
4. Yönetici onayı
5. Sorumlu kişi ve hedef tarih belirlenmesi
6. Uygulama sonucunun ve faydanın kaydedilmesi
7. Yönetim panelinde metriklerin görüntülenmesi

## Planlanan Temel Özellikler
- **Kimlik doğrulama:** Kullanıcıların sisteme güvenli şekilde giriş yapabilmesi.
- **Rol tabanlı yetkilendirme:** Kullanıcıların sahip oldukları rollere göre işlemleri gerçekleştirebilmesi.
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

| Rol | Yetki Özeti |
|---|---|
| EMPLOYEE | Çalışan. Kendi Kaizen önerilerini oluşturabilir ve takip edebilir. |
| OPEX_SPECIALIST | OPEX Yetkilisi. Önerileri ön değerlendirmeden geçirir, düzeltme talep edebilir ve uygun olanları yöneticiye iletir. |
| MANAGER | Yönetici. OPEX tarafından iletilen önerileri onaylar veya reddeder. Sorumlu kişi ve hedef tarihleri belirler. |
| ADMIN | Sistem Yöneticisi. Sistemdeki tüm ayarlara erişebilir, kullanıcı ve rol yönetimini gerçekleştirir. |

## Kaizen Durumları

| Durum | Açıklama |
|---|---|
| DRAFT | Taslak. Çalışan öneriyi henüz göndermemiştir, düzenlemeye devam edebilir. |
| SUBMITTED | Gönderildi. Öneri değerlendirme için OPEX yetkilisine iletilmiştir. |
| REVISION_REQUESTED | Düzeltme İstendi. Önerinin çalışan tarafından güncellenmesi beklenmektedir. |
| MANAGER_REVIEW | Yönetici İncelemesinde. OPEX yetkilisi tarafından onaylanıp yöneticiye iletilmiştir. |
| APPROVED | Onaylandı. Yönetici tarafından onaylanmış ve uygulamaya geçilmesi planlanmıştır. |
| IN_PROGRESS | Devam Ediyor. Onaylanan Kaizen önerisinin uygulaması sürmektedir. |
| COMPLETED | Tamamlandı. Uygulama bitmiş ve elde edilen faydalar kaydedilmiştir. |
| REJECTED | Reddedildi. Öneri OPEX yetkilisi veya yönetici tarafından reddedilmiştir. |

*Durum geçişleri yalnızca yetkili roller ve tanımlı geçiş kuralları aracılığıyla değiştirilebilecektir.*

## Teknoloji Yığını

| Teknoloji | Kullanım Amacı |
|---|---|
| PHP | Sunucu taraflı ana programlama dili |
| Laravel | Ana uygulama çerçevesi (framework) |
| Laravel Blade | Şablon motoru (view katmanı) |
| HTML, CSS ve JavaScript | İstemci taraflı arayüz teknolojileri |
| Bootstrap | CSS bileşen çerçevesi |
| Chart.js | Dashboard grafikleri |
| MySQL | İlişkisel veritabanı yönetim sistemi |
| Eloquent ORM | Veritabanı etkileşimleri |
| Laravel Migrations | Veritabanı şema yönetimi |
| Session tabanlı authentication | Kullanıcı kimlik doğrulama yapısı |
| PHPUnit | Birim testleri (Unit testing) |
| Laravel Pint | Kod stili denetimi ve biçimlendirme |
| Laragon | Yerel geliştirme ortamı |
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

## Planlanan Kurulum
Aşağıdaki komutlar projenin planlanan kurulum adımlarını göstermektedir (proje iskeleti oluşturulduktan sonra doğrulanarak güncellenecektir):

```bash
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
npm run dev
```

## 20 İş Günlük Yol Haritası

| Gün | Aşama |
|---|---|
| Gün 1 | Proje yönetimi ve dokümantasyon |
| Gün 2–3 | Gereksinim ve mimari |
| Gün 4–5 | Laravel temeli ve yetkilendirme |
| Gün 6–8 | Kaizen öneri yönetimi |
| Gün 9–11 | Değerlendirme ve onay akışı |
| Gün 12–14 | Uygulama ve fayda takibi |
| Gün 15–16 | Dashboard ve raporlama |
| Gün 17–18 | Test, güvenlik ve kullanıcı deneyimi |
| Gün 19–20 | Teslim ve sunum |

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
- [implementation_plan.md](implementation_plan.md)
- [CONTRIBUTING.md](CONTRIBUTING.md)

## Lisans
Projenin lisansı henüz belirlenmemiştir. Lisans seçilene kadar kaynak kod otomatik olarak açık kaynak kabul edilmemelidir.

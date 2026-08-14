# KaizenFlow – Geliştirme ve Katkı Rehberi

Bu rehber, KaizenFlow’un tek geliştirici tarafından düzenli, izlenebilir ve güvenli yürütülmesi için hazırlanmıştır. Tek geliştirici olmak Pull Request (PR) kullanımını gereksiz kılmaz. Aksine PR; değişikliğin amacı, diff’i, test sonucu ve karar geçmişi için günlük kalite kapısıdır.

Projede harici incelemeci veya zorunlu approval (onay) mekanizması bulunmayacaktır. Self-review (öz-inceleme) tamamlandıktan sonra nihai merge kararı doğrudan geliştiriciye aittir.

## 1. TEMEL İLKELER
- `main` her zaman çalışır ve sunulabilir durumda tutulmalıdır.
- `main` üzerinde doğrudan geliştirme commiti veya push yapılmamalıdır.
- Her günlük görev önce Issue ile tanımlanmalıdır.
- Her Issue için ayrı branch kullanılmalıdır.
- Commit ve PR sayısı yapay olarak artırılmamalıdır.
- Günlük hedef, tamamlanmış ve test edilmiş tek odaklı PR’dır.
- Merge kararı, kontrol listesi tamamlandıktan sonra verilmelidir.
- AI (Yapay Zekâ) önerileri yardımcı incelemedir; sorumluluk devri değildir.
- Gerçek şirket bilgileri ve gizli veriler repository’ye kesinlikle eklenmemelidir.

## 2. ISSUE OLUŞTURMA
1. Kod veya belge değişikliğinden önce Issue oluşturulur.
2. Açık ve tek odaklı başlık girilir.
3. Amaç, kapsam, kapsam dışı ve kabul kriterleri belirtilir.
4. İlgili Label (etiket) eklenir.
5. İlgili Milestone ve Project bağlantısı kurulur.
6. Çalışmaya başlanınca In Progress durumuna taşınır.

### Label Yapısı
| Tür | Alan | Öncelik |
|---|---|---|
| `type:feature` | `area:auth` | `priority:high` |
| `type:bug` | `area:kaizen` | `priority:medium` |
| `type:docs` | `area:workflow` | `priority:low` |
| `type:test` | `area:dashboard` | |
| `type:chore` | `area:ui` | |
| | `area:database` | |

## 3. BRANCH STRATEJİSİ
Branch formatı: `type/issue-number-short-description`

| Prefix | Açıklama |
|---|---|
| `docs/` | Dokümantasyon güncellemeleri |
| `feature/` | Yeni özellik geliştirmeleri |
| `fix/` | Hata düzeltmeleri |
| `test/` | Test ekleme veya güncelleme |
| `refactor/` | Kod refaktör işlemleri |
| `chore/` | Bakım, kurulum, yapılandırma |

Örnekler:
- `docs/1-project-foundation`
- `feature/5-authentication-and-roles`
- `feature/9-opex-review-workflow`
- `fix/18-attachment-authorization`
- `test/17-workflow-feature-tests`

**Kurallar:**
- Branch güncel `main` üzerinden oluşturulur.
- Branch adı Issue numarasını içerir.
- Branch yalnızca bağlı Issue kapsamını taşır.
- Aynı günlük işe ait bağımsız olmayan değişiklikler tek PR içinde anlamlı commitlere ayrılabilir.
- Merge sonrası uzak çalışma branch’i silinir.
- Force-push, history rewrite ve gereksiz rebase kullanılmaz.

## 4. COMMIT STANDARTLARI
Commit formatı (Conventional Commits): `<type>(<scope>): <description>`

| Tür (type) | Açıklama |
|---|---|
| `feat` | Yeni bir özellik (feature) |
| `fix` | Hata düzeltmesi |
| `docs` | Dokümantasyon değişiklikleri |
| `test` | Test değişiklikleri |
| `refactor` | Hata düzeltmeyen veya özellik eklemeyen kod değişikliği |
| `chore` | Derleme süreci veya yardımcı araç güncellemeleri |
| `style` | Kod formatı, boşluklar vb. (mantıksal değişiklik içermeyen) |

Örnekler:
- `docs: define project scope and roadmap`
- `feat(auth): add role-based access policies`
- `feat(kaizen): implement proposal submission`
- `test(workflow): cover manager approval transitions`
- `fix(upload): reject unauthorized attachment access`

**Commit Kuralları:**
- Mesajlar kısa, açıklayıcı ve İngilizce olmalıdır.
- Her commit tek mantıksal amaç taşımalıdır.
- İlgisiz dosyalar aynı committe bulunmamalıdır.
- Commit öncesinde staged diff kontrol edilmelidir.
- Aktivite grafiğini doldurmak için boş veya anlamsız commit atılmamalıdır.
- Normal günlük akışta amend, squash, history rewrite ve force-push kullanılmamalıdır.

## 5. GÜNLÜK PULL REQUEST SÜRECİ
1. Issue kabul kriterlerini doğrulama
2. Test ve kalite kontrollerini çalıştırma
3. Branch’i remote’a push etme
4. `main` branch’ine PR açma
5. Açıklamaya `Closes #IssueNumber` ekleme
6. Project kartını Review durumuna taşıma
7. Commits ve Files changed bölümlerini inceleme
8. Test sonuçlarını ve gerekirse ekran görüntülerini ekleme
9. Self-review kontrol listesini tamamlama
10. Varsa AI inceleme önerilerini değerlendirme
11. Engelleyici sorun yoksa geliştiricinin kendi PR’ını merge etmesi
12. Issue, Done durumu ve branch silme kontrolleri

**PR başlık formatı:** `<type>(<scope>): <summary>`

Örnekler:
- `docs: establish KaizenFlow project foundation`
- `feat(auth): implement authentication and user roles`
- `feat(workflow): add OPEX proposal review`
- `test(workflow): cover critical approval scenarios`

## 6. SOLO ÖZ-İNCELEME VE MERGE KURALI
- [ ] Diff Issue kapsamıyla sınırlı
- [ ] Kabul kriterleri tamamlandı
- [ ] Uygun testler eklendi
- [ ] Mevcut testler başarılı
- [ ] Laravel Pint başarılı
- [ ] Migration gerekiyorsa doğrulandı
- [ ] Yetki, sahiplik ve durum geçişleri incelendi
- [ ] Validation ve hata durumları kontrol edildi
- [ ] Gerçek şirket verisi, kişisel veri veya secret bulunmuyor
- [ ] UI responsive kontrol edildi
- [ ] Dokümantasyon güncel
- [ ] PR açıklamasında test kanıtı ve Issue bağlantısı var
- [ ] Çözümlenmemiş engelleyici yorum yok

Ayrıca:
- Geliştirici kendi PR’ında GitHub approval oluşturmaya çalışmaz.
- Kalite kapısını tamamladıktan sonra GitHub arayüzünden merge eder.
- Küçük commit geçmişini korumak için varsayılan yöntem **Create a merge commit** olacaktır.
- Merge sonrası çalışma branch’i silinir.
- Eksik veya testi başarısız PR merge edilmez.
- Tamamlanmayan çalışma Draft PR olarak bırakılabilir.
- Yalnızca aktivite göstermek için eksik kod `main`’e alınmaz.

## 7. YAPAY ZEKÂ DESTEKLİ İNCELEME
AI’nın yardımcı olabileceği alanlar:
- PR diff’inde hata ve güvenlik riski arama
- Eksik test önerme
- PHP ve Laravel standartlarını kontrol etme
- Karmaşık kodu açıklama
- Dokümantasyon tutarlılığı

Sınırlar:
- AI önerisi zorunlu insan onayı yerine geçmez.
- AI önerileri incelenmeden uygulanmaz.
- AI’ya secret, kişisel veri veya gerçek şirket verisi verilmez.
- Gerçek test sonucu olmadan “çalışıyor” değerlendirmesine güvenilmez.
- Otomatik merge yapılmaz.
- Nihai karar geliştiriciye aittir.

## 8. REPOSITORY KURALI
`main` için önerilen ayarlar:
- Require a pull request before merging: Açık
- Require approvals: Kapalı
- Require status checks: CI kurulduktan sonra açık
- Force push: Kapalı
- Korunan `main` branch’inin silinmesi: Kapalı

Tek geliştiricide required approval kullanılması, merge işlemini gereksiz yere engelleyebileceği için kapalı tutulmalıdır.

## 9. PHP VE LARAVEL KOD STANDARTLARI
- PSR-12 ve Laravel gelenekleri
- Laravel Pint
- İnce controller sınıfları
- Form Request doğrulaması
- Policies/Gates
- Merkezi Kaizen durum geçiş servisi
- Blade içinde karmaşık iş mantığı bulunmaması
- Açık Eloquent ilişkileri
- Kontrollü mass assignment
- N+1 sorgularına karşı eager loading
- Merkezi enum veya sabit durum değerleri
- Hassas ayrıntı içermeyen hata mesajları

## 10. ARAYÜZ STANDARTLARI
- Blade ve Bootstrap temel yaklaşımdır.
- Success, empty, validation ve error durumları bulunmalıdır.
- Form label ve doğrulama mesajları olmalıdır.
- Buton gizlemek backend yetkilendirmesi yerine geçmez.
- Renk tek başına durum göstergesi olmamalıdır.
- Mobil, tablet ve masaüstü kontrol edilmelidir.
- Grafiklerde başlık, açıklama ve boş veri durumu bulunmalıdır.

## 11. TEST STANDARTLARI
- Kritik özelliklerde başarılı ve başarısız senaryo
- Yetkili ve yetkisiz rol testleri
- Geçerli ve geçersiz durum geçişleri
- Dosya türü, boyutu ve erişim testleri
- Hata düzeltmelerinde regression testi
- Sentetik test verileri
- Yeni PR’ın mevcut testleri bozmaması

Planlanan komutlar:
```bash
php artisan test
./vendor/bin/pint --test
```

## 12. VERİTABANI VE MIGRATION KURALLARI
- Şema değişiklikleri migration ile yapılır.
- Paylaşılmış migration keyfî biçimde değiştirilmez.
- Foreign key ve silme davranışları açıkça belirlenir.
- İleri ve geri migration doğrulanır.
- Seed tekrar çalıştırıldığında gereksiz kopya üretmez.
- Demo kayıtları sentetik ve tarafsızdır.

## 13. GÜVENLİK VE GİZLİLİK
- `.env` Git’e eklenmez.
- Güvenli `.env.example` kullanılır.
- Parola, API anahtarı, veritabanı yedeği ve session bilgisi commit edilmez.
- Gerçek çalışan, üretim, maliyet veya finans verisi kullanılmaz.
- Dosya yüklemeleri doğrulanır ve policy ile korunur.
- CSRF kapatılmaz.
- Kullanıcı girdileri backend tarafında doğrulanır.
- Hassas işlemler audit log’a yazılır.
- Hassas değerlerin kendisi loglanmaz.
- Production hata ekranı stack trace göstermez.

## 14. PROJE YÖNETİMİ
GitHub Project durumları: `To Do` → `In Progress` → `Review` → `Done`

- Backlog 20 günlük planı ve gerekli alt Issue’ları içerir.
- Başlanan kart `In Progress` olur.
- PR açılınca `Review` olur.
- Merge sonrasında `Done` olur.
- PR uygun milestone veya faza bağlanır.
- UI PR’larında ekran görüntüsü eklenir.
- Test PR’larında test çıktısı eklenir.

## 15. YASAKLANAN İŞLEMLER
- `main` üzerinde doğrudan geliştirme
- Testi başarısız veya yarım işi merge etme
- Boş commitlerle aktivite grafiğini şişirme
- İlgisiz değişiklikleri tek commit veya PR’da toplama
- AI kodunu incelemeden merge etme
- Force-push veya history rewrite
- Secret veya gerçek şirket verisi commit etme
- Çalıştırılmayan testi başarılı gösterme
- Yapılmayan GitHub işlemini yapılmış gibi raporlama

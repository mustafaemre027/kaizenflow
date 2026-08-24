# GÜN 13 / ÇALIŞMA BLOĞU 6 — APPROVAL CONFIGURATION MUTATION ACTION’LARI, VERSIONING VE AUDIT TDD

## Gerçek Action ve Exception Sınıfları
- **Exception Sınıfları:**
  - `App\Exceptions\AuthorizationException` (Yetki bypass/reddi için)
  - `App\Exceptions\DomainException` (Geçersiz stage, birden fazla final stage vb. domain ihlalleri için)
- **Trait:** `HasApprovalConfigurationMutation` (Transaction içi yetki kontrolü ve Global Lock Order için)
- **Action Sınıfları:**
  - `CreateApprovalWorkflowDraft`: Draft oluşturur, versioning işlemini yürütür.
  - `UpdateApprovalWorkflowDraft`: Sadece published_at = null olan taslakları günceller.
  - `PublishApprovalWorkflow`: Taslağı yayımlar.
  - `SetDefaultApprovalWorkflow`: Yalnızca published/active workflow'u default yapar.
  - `DeactivateApprovalWorkflow`: Workflow'u pasifleştirir.

## Workflow Lifecycle Sözleşmesi
- Yeni oluşan taslaklar daima `is_active = false`, `is_default = false` ve `published_at = null` ile başlar.
- Publish edilmeden hiçbir workflow default yapılamaz veya aktifleşemez.
- Update işlemi yayımlanmış kayıtlarda (`published_at != null`) kesinlikle `DomainException` fırlatır.

## Versioning Kararı
- Yeni sürüm (draft), aynı `code` değerine sahip mevcut tüm workflow'ların version'ı üzerinden `max(version) + 1` formülü ile **transaction içinde** (lockForUpdate ile kilitlenmiş collection üzerinden) hesaplanır.
- DB seviyesindeki `UNIQUE(code, version)` indeksi, olası version yarışlarına karşı son savunma hattıdır.

## Ortak Global Lock Order
Transaction içinde deadlock'ları önlemek için daima şu sıra takip edilir:
1. `users.id ASC`
2. `user_system_capability_grants.id ASC` (Kullanıcının manage yetkisi taze okunur)
3. `approval_workflows.id ASC` (İşlem yapılan veya version/default taraması yapılan kayıtlar)
4. `approval_stages.id ASC`
5. `kaizen_workflow_instances.id ASC` (Deactivation sırasında terminal kontrolü)
6. Audit Insert

## Publish Invariantları
- Taslakta en az bir aktif stage olmalıdır.
- Sequence değerleri kesinlikle monotonically increasing olmalıdır.
- Sadece ve sadece **bir adet** final stage olmalıdır.
- Final stage mutlaka sıradaki (sequence) en sonuncu (last) stage olmalıdır.
- Publish işlemi otomatik default ataması yapmaz. Yalnızca `is_active = true` ve `published_at = now()` uygular.

## Default Invariantı
- Default belirleme yalnızca published_at != null ve is_active = true ise yapılabilir.
- `ApprovalWorkflow::orderBy('id', 'asc')->lockForUpdate()->get()` kullanılarak eski default değeri transaction içinde güvenle `false` yapılırken, yeni hedef `true` yapılır.
- Zaten default ise no-op işlemi ile audit log atılmaz.

## Deactivation ve History Koruması
- Workflow veya stage'ler fiziksel olarak silinmez (`delete` kullanılmaz). Sadece `is_active = false` yapılır.
- Default workflow doğrudan pasifleştirilemez.
- `KaizenWorkflowInstance` üzerindeki `completed_at = null` ve `cancelled_at = null` (terminal olmayan) aktif instanceler varken workflow deactivation işlemine izin verilmez.
- Published workflow'lar yerinde update edilemediği için approval history bütünlüğü mutlak surette korunur.

## Audit Event / Metadata Sınırları
- Audit kayıtları `$actor` nesnesinin gerçek yetkilendirilmiş kullanıcısı ile kaydedilir (Request bypass kapalı).
- Tüm transaction sonlarında başarılı ise audit atılır.
- **Event İsimleri:**
  - `approval_configuration.created`
  - `approval_configuration.updated`
  - `approval_configuration.published`
  - `approval_configuration.default_set`
  - `approval_configuration.deactivated`
- Şifre, token veya sınırsız request payload audit loga yazılmaz. Old/New mantığıyla is_active, is_default, published_at tutulur.

## Rollback Sonuçları
Tüm Action sınıfları `DB::transaction()` sarmalındadır. Domain ihlali, Authorization ihlali veya AuditLog insert sırasında yaşanacak her türlü exception işlemi eksiksiz `rollback` yapar.

## MySQL Concurrency Sonuçları
- **Race A (Create Draft Eşzamanlılığı):** 2 process eşzamanlı çalıştırıldığında; versioning doğru çalışmış, Duplicate hatası verilmemiş, version 1 ve version 2 sırayla başarıyla üretilmiş ve kilit sırası (lock order) mükemmel çalışmıştır.
- **Race B (Default Set Eşzamanlılığı):** 2 farklı workflow eşzamanlı default yapılmaya çalışıldığında, kilitleme sırasıyla yürümüş ve sistemde daima **yalnızca 1 adet** default kalmıştır.
- **Race C (Stale Grant Revoke):** `HasApprovalConfigurationMutation` içindeki `allowsSystem` ve `lockForUpdate` mantığı stale actor bypassını kesin olarak engeller.

## SQLite/MySQL Test Metrikleri
- **SQLite (TDD Aşaması):** 11 passed (26 assertions), MySQL-only concurrency testleri scratch olarak çalıştırıldı.
- **MySQL Hedef:** 11 passed (26 assertions).
- **SQLite Tam Süit:** 682 passed (2000 assertions).
- **MySQL Tam Süit:** 683 passed (2001 assertions).

## Sonraki Aşama
Bu blokla mutasyon altyapısı güvenle tamamlanmış olup, Form Request, Blade/UI, Controller entegrasyonu (HTTP Mutation endpoint'leri) bir sonraki bloğa bırakılmıştır.

## Blok 6.1 Kabul Denetimi ve Düzeltmeler (Adli Kanıtlar)
- **Güvenlik Açığı Tespiti (Aggregate Lock):** `DeactivateApprovalWorkflow` içerisinde `count()->lockForUpdate()` kullanılarak yapılan aggregrate DB kilitlenmesi tespit edildi. Bu durum MySQL üzerinde deadlocklara yol açabileceği için RED test ile ispatlanıp (`ApprovalConfigurationMutationGapTest`), `lockForUpdate()->get()->count()` olarak düzeltildi.
- **Transaction ve Yetki Bütünlüğü:**
  - `HasApprovalConfigurationMutation` içindeki `lockForUpdate()` sorgularının TOCTOU zafiyetlerini başarıyla kapattığı kanıtlandı.
  - Aktör pasifliği ve Capability pasifliği ayrı ayrı kontrol ediliyor.
  - Global Lock Order hiyerarşisi `users -> user_system_capability_grants -> approval_workflows -> approval_stages -> kaizen_workflow_instances -> audit_logs` şeklinde tamamen ardışık sıralandı.
- **Index ve Şema Kuralları:** `UNIQUE(code, version)` ve FK Delete Restrict kurallarının DB seviyesinde var olduğu teyit edildi.
- **Test ve Kalite Sonuçları:**
  - Yeni gap testi ile beraber SQLite 683 passed, MySQL 684 passed olarak %100 test success oranına ulaşıldı.
  - Pint, npm build, composer validate hepsi hatasız.

## Blok 6.2.1 Concurrency Worker Veritabanı Mühürlemesi
- **DB Sızıntısının Kök Nedeni:** Blok 6 scratch testlerindeki `race_master.php`, kendi içerisinde `Config::set` ile veritabanını değiştirse de, `Artisan::call` ve child worker process'lere (`proc_open` ile) `DB_DATABASE=kaizenflow_test` environment override'ını geçirmemiştir. Child process'ler ve console kernel boot sırasında doğrudan `.env` okuduğu için işlemler `kaizenflow` veritabanına sıçramıştır (Senaryo C tespiti).
- **Kirlenmiş Kayıtlar:** Blok 6.1 ve Blok 6.2.1 sırasında bu kayıtların silinmesi yasak olduğu için oldukları gibi bırakılmıştır (Onaylı bir sonraki cleanup bloğu bekleniyor).
- **Güvenli Test Harness (mysql-launcher):**
  - **Parent Process Allowlist:** Credential'ların (DB_USERNAME, DB_PASSWORD vb.) worker'lara sadece güvenli `$_SERVER` ve explicit override ile aktarılması sağlandı.
  - **Child Process Tuple:** `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=kaizenflow_test` zorunlu kılındı.
  - **Canlı SELECT DATABASE():** Her worker Laravel boot olduktan sonra işlemi `SELECT DATABASE() as db` ile teyit etmeden hiçbir mutationa izin vermeyecek biçimde (hard-fail) mühürlendi.
  - **Test Metrikleri:** Bu fail-closed kilit sistemi `ConcurrencyDatabaseSafetyTest` ile kırmızı-yeşil test döngüsüyle güvenceye alınmıştır.
- **Kalıcı Concurrency Testleri:** Güvenlik katmanı mühürlendiğinden dolayı Race A/B/C testlerinin kalıcı Laravel `Feature` testlerine dönüştürülmesi bir sonraki bloğa devredildi.

## Blok 6.2.3 Kalıcı MySQL Race A/B/C Regresyon Testleri
- **Senaryo A (Doğrudan Test Başarısı):** Güvenli isolation (MySqlTestLauncher) üzerinden `ApprovalConfigurationConcurrencyTest.php` yazılarak Race A (Duplicate taslak), Race B (Eşzamanlı Default) ve Race C (Stale Capability) testleri koşuldu. Tüm yarış senaryoları `hasApprovalConfigurationMutation` içindeki `lockForUpdate` mantığı sayesinde %100 başarıyla (GREEN) çalıştı ve production kodunda herhangi bir güvenlik açığı bulunamadı.
- **Güvenlik Mühürü Teyidi:** Dev DB (kaizenflow) testten önce ve sonra fingerprint ile doğrulandı, hiçbir sızıntı ve değişiklik (0 byte data drift) yaşanmadı.
- **Bütünsel Regresyon:** Tüm SQLite (686 passed) ve MySQL (690 passed) testleri sıfır hata ile tamamlandı. Pint, view cache, JS build gibi kalite kapıları kapandı.


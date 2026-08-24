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

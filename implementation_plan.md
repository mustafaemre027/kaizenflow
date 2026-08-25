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

## Blok 6.2.3.2 Race Barrier Cleanup Garantisi (TDD)
- **Tespit Edilen Cleanup Açığı:** Önceki implementasyonda barrier/temp kalıntıları test fail olduğunda, timeout yaşandığında veya exception fırlatıldığında `tearDown` veya `finally` ile temizlenemiyordu ve `%TEMP%` altında kalıntılar bırakıyordu.
- **RED Test Kapsamı:** `RaceHarnessCleanupTest.php` sınıfı oluşturularak normal çalışma, idempotent çağrı, exception fırlatılması, timeout yaşanması ve unrelated dosya korumasına dair kırmızı testler kanıtlandı.
- **Exact-owned Barrier Directory & Random Token:** Tahmin edilebilir `uniqid()` yerine tam `bin2hex(random_bytes(16))` ile izole ve sadece test harness örneğinin sahip olduğu barrier dizinleri garantilendi.
- **Exception/Timeout Cleanup & Process Reap:** Barrier oluşturulmasından `collectResults` aşamasına kadarki tüm lifecycle yönetimi `RaceHarness` içine kapsüllendi. Child process'ler ve açık olan resource pipe'ları, `try/finally` yapısına uyumlu şekilde, failure anında (timeout, exception vs.) terminate ve reap edildi (proc_close, proc_terminate).
- **Eski Temp Kalıntıları Korundu & Dev DB Mühürlendi:** Harici bir dizin (`kaizen_race_unrelated_...`) veya eski kalan dizinler, `rmdir` esnasında exact pattern validation ve exact path sahipliği kontrolüyle `%100` korundu. Geliştirme veritabanında hiçbir sızıntı yaşanmadı.
- **SQLite/MySQL Test Metrikleri:** Tüm Test Süitleri (SQLite 686 test, MySQL 696 test) ve Race A/B/C regresyonları GREEN duruma getirildi. Hard-kill anlarında (OS shutdown, elektrik kesintisi vs.) PHP'nin `finally` bloğu çalışmayacağından, bu tür dış etmen kaynaklı nadir senaryolarda işletim sisteminin %TEMP% politikalarına güvenileceği dürüstçe belirtildi.

## Block 7.1 Acceptance

Bütün HTTP Mutation geliştirme fazı adli testlerle doğrulanmış ve kalite kapıları kapanmıştır:
- **Commit atomikliği**: Testler ayrı RED commit olarak, model düzeltmeleri ayrı FIX olarak ve feature implementation GREEN olarak ayrılmıştır. `style` değişiklikleri de kendi atomik commitindedir.
- **Authorization-before-validation sonucu**: Form Request doğrulamasından önce yetki kontrolünün yapılması zorunluluğu tespit edilmiş, yetkisiz isteklere verilen 422 hatası, Form Request içindeki `authorize()` metodu güncellenerek 403'e dönüştürülmüştür (`bcdca5e`).
- **IDOR sırası**: ID tabanlı rota modelleri doğrulandı. Yetkili kullanıcı için 404, yetkisiz kullanıcı için varlık sızıntısı olmadan 403 döndürüldüğü kanıtlandı.
- **DomainException kapsamı**: Uygulama genelinde `DomainException` yönetiminin dar bir kapsama indirilmesi sağlandı (`bootstrap/app.php` güncellendi, sadece `/settings/approval-configurations/*` boundary'si 422 json döndürüyor). Hata raw message maskelendi.
- **Sequence cast düzeltmesinin kanıtı**: `ApprovalStage` içerisindeki `sequence` değeri için eksik integer cast tespiti `1f34ecc` numaralı düzeltmeyle kalıcılaştırıldı, NOOP testlerin adli olarak 0 audit oluşturduğu doğrulandı.
- **Route/request/controller matrisi**: 5 POST/PATCH endpoint Controller methodlarına yönlendirilmiş, Store/Update form request'leriyle validation ayrımı yapılandırılmış ve kısıtlı system/internal alanlar `prohibited` edilmiştir.
- **Dev DB değişmezliği**: `fingerprint_dev_v2.php` ile doğrulanmıştır. Herhangi bir dev datası veya instance bozulmamış, auto_increment = 3 olarak kalmıştır.
- **Bulunan gerçek açıklar**: 
    1) "Sequence String vs Int Drift" (No-op testinde saptandı, `ApprovalStage` modelinde fixlendi).
    2) "Authorization-before-validation gap" (Form request'lerde yetkisiz kullanıcıya 422 dönmesi 403'e düzeltildi).
    3) "DomainException Global Mapping Gap" (Global 422 mapping, `/settings/approval-configurations/*` endpoint'iyle kısıtlanıp raw data maskelendi).
- Bütün SQLite (713/713) ve MySQL (723/723) regresyon testleri GREEN'dir. Composer, Pint ve npm build kapıları başarıyla geçildi.

## Blok 8: Approval Configuration Blade Yönetim Arayüzü
- **Güvenli Arayüz (RED -> GREEN):** Güvenli read/mutation endpoint'leri (index, show, create, edit) Bootstrap ve KaizenFlow CSS standartlarına sadık kalınarak oluşturuldu.
- **Content Negotiation:** Tüm okuma ve mutasyon işlemleri `$request->wantsJson()` kullanılarak HTTP API ve UI Blade sayfaları ile uyumlu hale getirildi. Hata fırlatmaları (DomainException), HTML formları için flash mesaja dönüştürülerek `$exception->getMessage()` gibi raw hata sızıntıları önlendi.
- **Erişilebilirlik ve DOM Koruması:** Stage listelerinde innerHTML JS kurguları tamamen escape edildi ve JS kapalıyken çalışabilecek temel fallback sağlandı. actor_id, user_id gibi form dışı alanlar DOM'a dahil edilmedi. Sayfalarda WAI-ARIA (aria-invalid, aria-describedby vb.) attributeları zorunlu tutuldu.
- **Strict Authorization ve Lifecycle Testleri:** `ApprovalConfigurationUiTest` ile tam kapsayıcı TDD (19 adet GREEN test, 67 assertion) gerçekleştirildi. Guest, view-only, passive user, yetkisiz payload, IDOR sıralaması ve no-op (idempotent) mutasyon davranışları uçtan uca kanıtlandı.
- **Veritabanı Değişmezliği:** Dev DB (kaizenflow) üzerinde manuel mutasyon / UI fixture testleri yürütülmedi ve Blok başı ile sonu fingerprint 0 byte diff ile korundu.
- **Kalite Kapıları:** SQLite (742 passed) ve MySQL (742 passed, 2139 assertions) regresyonları test suite üzerinden hiçbir fire verilmeden tamamlandı.


## Block 9 UI QA Acceptance
- Approval Configuration UI fully validated via comprehensive HTTP tests (	ests/Feature/ApprovalConfigurationUiTest.php).
- Verified IDOR constraints, capabilities (view/manage), Guest redirection, and Role-bypass blocks.
- Validated HTML forms against DOM injection & XSS across all mutation points.
- Full functional flows (Create, Update, Publish, Default, Deactivate) successfully tested in test runtime (kaizenflow_test).
- Verified Responsive/Accessibility limits without Dev DB modification.
- Full test suite, Pint, Build, and Migration status PASS.

# # #   B l o k   9 . 2   -   A p p r o v a l   S t a g e   R e o r d e r   C o n s t r a i n t   F i x  
 -   I d e n t i f i e d   a n d   p r o v e d   u n i q u e   c o n s t r a i n t   v i o l a t i o n   d u r i n g   r e o r d e r i n g .  
 -   F i x e d   c o n s t r a i n t   c o l l i s i o n   b y   t e m p o r a r i l y   m o v i n g   s t a g e s   t o   o f f s e t   s e q u e n c e   b e f o r e   f i n a l   a p p l y .  
 -   V e r i f i e d   f i x   w i t h   S Q L i t e   a n d   M y S Q L   s u i t e s .  
 
## 10. Prosed�rel Sapma Kayd�

PROCEDURAL DEVIATION � THE INITIAL COLLISION CHARACTERIZATION TEST EXPECTED QUERYEXCEPTION AND THEREFORE DID NOT REPRESENT A FAILING RED TEST. THE GREEN PRODUCTION FIX WAS FOLLOWED BY A SEPARATE SUCCESS-ASSERTION TEST COMMIT. FINAL CODE AND TEST INTEGRITY UNAFFECTED.

- 80c81e4 hatay� karakterize eden test commitidir.
- 4b1c7cc production d�zeltmesidir.
- Yeni test commit�i d�zeltmenin ba�ar�l� DB sonucunu kal�c� olarak do�rulamaktad�r.
- History rewrite yap�lmam��t�r.


## 9. Blok 2 Ger�ekle�tirme Sonu�lar�

Blok 2 hedefleri kapsam�nda;
- Enum ve Model yap�lar� (ApproverResolutionMode, ApprovalApproverScopeSource, ApprovalStageApproverRule) olu�turulmu�tur.
- DB Constraint migrationlar� eksiksiz uygulanm�� ve MySQL/SQLite platformlar�nda RefreshDatabase uyumlulu�u TDD ile do�rulanm��t�r.
- MutateApprovalStageApproverRule Domain Action'� deterministik lock order ile (User -> Grant -> Workflow -> Stage -> Rule) uygulanm��, "high" d�zeyli lock a���� kapat�lm��t�r.
- CapabilityApprovalStageApproverResolver ile **self-approval prevention** aktif edilmi�, pasif rule/grant/actor kombinasyonlar� ve departman e�le�mezlikleri i�in tamamen fail-closed izole bir yap� kurulmu�tur.
- Bootstrap PACKAGE g�ncellenmi� ve yetkilerin exact-delegation'a uygun olarak Admin'e verilmesi sa�lanm��t�r.
- Y�ksek kapsama sahip testler yaz�lm��, kod RED -> GREEN -> STYLE hatt�yla commit edilmi�tir. TDD ve izole test a�amas� ba�ar�yla tamamlanm��t�r.

## 10. Blok 3 Ger�ekle�tirme Sonu�lar� (Runtime Entegrasyonu)

Blok 3 hedefleri do�rultusunda;
- ApprovalStageApproverResolver i�erisine CapabilityApprovalStageApproverResolver ba��ml�l��� enjekte edilmi� ve ApproverResolutionMode::CAPABILITY_RULE ile ApproverResolutionMode::LEGACY_GROUP aras� ayr�m (hi�bir fallback olmaks�z�n) sa�lanm��t�r.
- T�m modlarda Self-Approval kesin red kural� en tepeye yerle�tirilmi� ve yetkisizlik durumunda instance, stage ve audit ilerlemesi durdurularak tam ACID rollback do�rulanm��t�r.
- ProgressKaizenWorkflow mutation Action'� i�erisine resolver yetkilendirme katman� eklenerek g�venlik a���� giderilmi� ve testlerde izole yetkilendirme do�rulamas� yap�lm��t�r.
- CreateApprovalWorkflowDraft Action'�nda yeni taslaklar�n ApproverResolutionMode::CAPABILITY_RULE ile olu�mas� a��k�a zorlanm��t�r.
- PublishApprovalWorkflow Action'�na CAPABILITY_RULE workflow'lar� i�in "her aktif stage i�in tam bir aktif rule bulunmas�" invariant� (yay�nlama engeli) eklenmi�tir.
- SQLite (773 Passed) ve MySQL canl� test (773 Passed, 2208 Assertions) TDD d�ng�s�nde 0 hata ile �al��t�r�lm�� ve Development DB b�t�n�yle izole edilerek de�i�mezli�i korunmu�tur.

## 11. Blok 4 Ger�ekle�tirme Sonu�lar� (Y�netim UI/HTTP)

Blok 4 hedefleri do�rultusunda;
- 30 maddelik kat� test senaryolar�n� i�eren ApprovalConfigurationRuleMutationTest geli�tirilmi�tir (RED/GREEN).
- MutateApprovalStageApproverRuleRequest ile Gate::allows ve is_active denetimleri Authorization a�amas�na al�nm��, Form Request'te prohibited alanlarla (scope_source, user_id vb.) IDOR engellenmi�tir.
- ApprovalConfigurationController'da i�erik anla�mas�na (JSON / HTML) tam uyumlu, exception maskeleyen, g�venli domain action �a�r�s� uygulanm��t�r.
- show.blade.php'de Blade entegrasyonu tamamlanm��t�r. ��z�mleme modlar� (Eski Grup / Dinamik Kural) ve draft a�amas�ndaki kural d�zenleme formlar� (PATCH method, CSRF, enum valuelar�) DOM testleriyle (N+1 engellenerek) do�rulanm��t�r.
- MySQL ve SQLite motorlar�nda 800+ test ba�ar�yla 0 hata (GREEN) d�nm��, kaizenflow_test DB kan�tlanm�� ve geli�tirme DB dokunulmam�� durumda kalm��t�r.

- Pint arac� taraf�ndan saptanan import s�ras� ve fully qualified class formatlama bulgular� Blok 4.1.1 kapsam�nda semantik de�i�iklik yaratmadan ba�ar�yla formatlanarak kapat�lm��t�r.

# GÜN 13 / ÇALIŞMA BLOĞU 3.2 — TOCTOU YETKİLENDİRME YARIŞI VE TRANSACTION İÇİ REVALIDATION

Bu plan, `GrantSystemCapability` ve `RevokeSystemCapability` action'larındaki Time-Of-Check/Time-Of-Use (TOCTOU) zafiyetlerini gidermeyi amaçlamaktadır. DB'ye bağlı olan tüm güvenlik kontrolleri (aktiflik ve yetki) transaction'ın içine çekilecek ve `lockForUpdate` ile kilitlenmiş, en güncel veriler üzerinde doğrulanacaktır.

## User Review Required

> [!WARNING]  
> Bu plan TOCTOU güvenliği için kritik DB kilit sıralarını (User ID ve Capability ID üzerinden) değiştirmekte veya pekiştirmektedir. Kilit sırasının deterministik olmasına büyük özen gösterilmiştir.
> Düzeltmeler `test(auth)` ve `fix(auth)` olarak iki ayrı commit halinde uygulanacak ve test yönlendirmeli (TDD) bir yaklaşım izlenecektir.

## Open Questions

Herhangi bir açık soru bulunmamaktadır. Testler ve beklentiler yönergelerde eksiksiz tarif edilmiştir.

## Proposed Changes

### 1. Testler ve Zafiyet Senaryoları (RED State)

#### [MODIFY] [`tests/bootstrap.php`](file:///C:/Projects/kaizenflow/tests/bootstrap.php)
Added an explicit, raw `exit(1)` condition that triggers if the current process is loading the application while `APP_ENV=testing`, `DB_CONNECTION=mysql`, but `DB_DATABASE` is NOT `kaizenflow_test`. This catches fallback connections before the autoloader.

#### [MODIFY] [`tests/mysql-launcher.php`](file:///C:/Projects/kaizenflow/tests/mysql-launcher.php)
Refactored to delegate execution to `MySqlTestLauncher`, which parses `.env` robustly with `Dotenv::parse()`, performs credential preflight, and safely launches PHPUnit via `proc_open` without mutating the parent environment.

#### [NEW] [`tests/Support/MySqlTestLauncher.php`](file:///C:/Projects/kaizenflow/tests/Support/MySqlTestLauncher.php)
Implements process boundary safety, preflight validation of credentials, safe command array generation, explicitly isolated child environment construction, and STDOUT/STDERR secret redaction. Strict allowlisting applied for `DB_USERNAME` (only `kaizenflow_app` allowed).

#### [NEW] [`tests/Unit/Testing/MySqlTestLauncherTest.php`](file:///C:/Projects/kaizenflow/tests/Unit/Testing/MySqlTestLauncherTest.php)
TDD suite to verify safety properties: missing credential rejection, environment isolation, safe command structures, and output redaction.

#### [NEW] [`tests/Unit/Testing/MySqlTestLauncherResidualBypassTest.php`](file:///C:/Projects/kaizenflow/tests/Unit/Testing/MySqlTestLauncherResidualBypassTest.php)
Extensive TDD suite verifying the strict `kaizenflow_app` allowlist, blocking all config bypass variants (`--no-configuration`, `-c=evil.xml`, etc.), and exhaustive tests of `Dotenv::parse()` parser edge-cases (CRLF, `#`, `=`, whitespaces, quotes).

> [!WARNING]
> **Commit Boundary Deviation Record**:
> The `MySqlTestLauncher.php` support class was unintentionally left out of the initial `GREEN` commit and was instead committed in the `DOCS` commit. This means the `GREEN` commit was NOT self-contained. 
> To preserve history and avoid history rewrite (no amend/rebase), this deviation is strictly recorded here. Final code integrity is unaffected and fully secured by the comprehensive tests in the residual bypass tests block.

#### [MODIFY] [GrantSystemCapabilityTest.php](file:///c:/Projects/kaizenflow/tests/Feature/Actions/Authorization/GrantSystemCapabilityTest.php)
#### [MODIFY] [RevokeSystemCapabilityTest.php](file:///c:/Projects/kaizenflow/tests/Feature/Actions/Authorization/RevokeSystemCapabilityTest.php)
- **Transaction sınırı testleri:** Authorization kuralının `DB::transactionLevel() > 0` şartı ile işlem içinde çalıştığının doğrulanması.
- **Stale Actor:** Başlangıçta aktif olup mutation anında pasifleşen aktörün reddedilmesi.
- **Stale Target:** (Grant için) Başlangıçta aktif olup mutation anında pasifleşen hedefin reddedilmesi.
- **Stale Authorization Grant:** Mutation anında aktörün `authorization.manage` yetkisinin pasifleştiği senaryo.
- **Stale Exact Capability:** (Grant için) Aktörün devredebileceği capability'nin mutation anında pasifleştiği senaryo.
- **No-Op Güvenliği:** Target'ın hedef grant'i zaten aktif/pasif (no-op işlemi) dahi olsa yetkisiz aktörün başarısız olması.

### 2. Transaction İçi Revalidation (GREEN Fix)

#### [MODIFY] [GrantSystemCapability.php](file:///c:/Projects/kaizenflow/app/Actions/Authorization/GrantSystemCapability.php)
- Saf input kontrolleri (`ScopeMismatchException`, ID eşitliği) transaction dışında bırakılacak.
- Transaction içinde:
  1. Actor ve target ID sırasına göre kilitlenecek.
  2. Kilitlenmiş güncel verilerden (fresh) `is_active` kontrolü yapılacak.
  3. Aktörün `authorization.manage` ve devredeceği `exact-capability` yetkileri tek bir sorguda (veya kilit kümesi tekilleştirilip ID sırasıyla) `lockForUpdate()` ile çekilerek doğrulanacak.
  4. Target grant çekilecek.
  5. Doğrulamaların ardından No-Op, Create, Reactivate işlemlerine karar verilecek.

#### [MODIFY] [RevokeSystemCapability.php](file:///c:/Projects/kaizenflow/app/Actions/Authorization/RevokeSystemCapability.php)
- Saf input kontrolleri dışarıda kalacak.
- Transaction içinde:
  1. `userIdsToLock` listesi belirlenip (actor, target, varsa manager'lar) kilitlenecek.
  2. Güncel (fresh) aktörden `is_active` kontrolü yapılacak.
  3. Aktörün `authorization.manage` yetkisi, manager ID'leri sorgusunun bir parçası olarak veya ayrıca kilitlenerek doğrulanacak. (Mevcut koda aktörün manager grant'i de dahil edilecek ve `is_active` kontrol edilecek).
  4. Target grant kilitlenecek ve no-op ile diğer kurallar işletilecek.

## Verification Plan

1. Yukarıdaki tüm TOCTOU ve stale data senaryolarını test sınıflarına ekleyerek `test(auth)` RED commit'ini oluşturacağım.
2. İş mantığını transaction içine kaydırarak `fix(auth)` GREEN commit'i ile testleri başarıyla geçeceğim.
3. MySQL veritabanında (`kaizenflow_test`), iki ayrı process'in (DB lock ile) eşzamanlı çalıştığı gerçek bir TOCTOU concurrency yarış testi yazıp `Senaryo A` (actor grant'in kaldırılması) ve `Senaryo B` (target pasifleştirilmesi) korumalarını kanıtlayacağım.
4. Hem SQLite hem de MySQL ortamında kalite kapılarını çalıştıracağım (Tam test süiti, Pint, Composer vb.)
5. Yapılan tasarımsal düzeltmeleri yönergelerdeki beklentiye uygun şekilde `implementation_plan.md` dosyasına (`docs(plan)`) kaydedeceğim.

# GÜN 13 / ÇALIŞMA BLOĞU 3.2.3.1 — ARTISAN BOOT ÖNCESİ TEST DB KORUMASI VE KALİTE KAPISI KAPANIŞI

Bu plan, `GÜN 13 / ÇALIŞMA BLOĞU 3.2.3` sırasında `setUpTraits()` içerisine eklenen test DB korumasını (Runtime Guard) bir adım öteye taşıyarak, henüz Laravel application veya service provider'lar boot edilmeden önce devreye giren **üç katmanlı (Defense-in-Depth)** güvenlik yapısını belgelemektedir.

## Parent Artisan Boot Riski
Önceki yapıda `php artisan test --env=testing` çağrıldığında, `.env.testing` dosyası olmadığı için Artisan süreci `kaizenflow` veritabanı ayarlarını barındıran yerel `.env` dosyasına fallback yapıyordu. PHPUnit alt süreci `force="true"` değerleriyle bunu ezse de, parent sürecin tehlikeli bir connection string'e sahip olması bir risk oluşturuyordu. 

Bu riski gidermek adına, repository'ye secrets içermeyen ve safe değerleri (`sqlite` + `:memory:`) barındıran bir `.env.testing` dosyası eklenerek, parent Artisan boot süreci ilk adımdan itibaren tam koruma altına alındı. (Katman A)

## PHPUnit Early Bootstrap Guard
Hem `phpunit.xml` hem de `phpunit.mysql.xml` için ortak ve Laravel application ayağa kalkmadan (yani `vendor/autoload.php` yüklenmeden) önce çalışan `tests/bootstrap.php` dosyası oluşturuldu. 
Bu dosya raw `$_ENV` / `$_SERVER` / `getenv()` ortam değişkenlerini Laravel framework context'inden tamamen bağımsız kontrol eder. Tehlikeli bir tuple (`mysql` + `kaizenflow` vb.) algıladığında `exit(1)` ve `FATAL ERROR` ile anında durur. (Katman B)

## Runtime Guard
`App\Testing\DatabaseSafetyGuard::verify()` ile Laravel application boot edildikten sonra (config resolution tamamlandığında), runtime değerleri ve aktif MySQL PDO bağlantısının `SELECT DATABASE()` sonucu doğrulanmaya devam etmektedir. (Katman C)

## İzin Verilen İki Tuple
Sadece aşağıdaki konfigürasyonlara izin verilir:
1. `testing` + `sqlite` + `:memory:`
2. `testing` + `mysql` + `kaizenflow_test`

Diğer tüm fallback, runtime inject, empty DB durumları strict olarak reddedilir.

## Subprocess Sıfır-Boot / Sıfır-Connection Kanıtı
`tests/Feature/EarlyBootstrapSafetyTest.php` ile simüle edilmiş raw PHP Subprocess'ler üzerinden `mysql` + `kaizenflow` tuple'ı denenmiş ve exit code `1` fırlatarak Laravel'in factory callback'lerine (Application / Service Provider / DB / Migration canary) hiçbir şekilde ulaşmadığı testlerle (RED -> GREEN) ispatlanmıştır.

## SQLite ve MySQL Komutları
- **SQLite:** `php artisan test` (varsayılan)
- **MySQL:** `composer test:mysql` (Özelleştirilmiş güvenli `tests/mysql-launcher.php` üzerinden `phpunit.mysql.xml` çalıştırılır. Parolalar `.env`'den güvenlice okunup PHPUnit process'ine enjekte edilir, `.env.testing` kirletilmez, credential'lar loga sızmaz).

## Test Metrikleri
- Early Bootstrap Guard Testleri: 7 passed (16 assertions) / 3.74s
- Runtime Guard Testleri: 9 passed / 18 assertions / 0.38s
- SQLite Tam Süit: 582 passed, 1 skipped (1655 assertions) / 19.27s
- MySQL Tam Süit: 583 passed (1656 assertions) / 28.30s
- Pint: PASS (Style format uygulandı)
- NPM Build: 118 modules, PASS (917ms)

## Geliştirme DB Değişmezliği
- İşlem başı ve sonu migration status tamamen aynı, `kaizenflow` count değerleri `0`, CREATE_TIME ve server_uuid birebir aynı kalmıştır. Geliştirme DB %100 güvendedir.

---

# GÜN 13 / ÇALIŞMA BLOĞU 3.2.3 — TEST VERİTABANI İÇİN FAIL-CLOSED İZOLASYON KORUMASI

Bu plan, test süreçlerinin kazara geliştirme veritabanında (kaizenflow) çalışarak veri kaybına yol açmasını (TOCTOU bloklarındaki 0/0/0/0 counts olayı) önlemek amacıyla test ortamına eklenen fail-closed veritabanı güvenlik mekanizmasını belgelemektedir.

## Önceki Test İzolasyonu Olayı
Gün 13 Blok 3.2'de çalıştırılan `php artisan test --env=testing` komutu, `.env.testing` dosyasının yokluğunda framework'ün fallback yaparak `.env` içerisindeki `DB_CONNECTION=mysql` ve `DB_DATABASE=kaizenflow` ortam değişkenlerini yüklemesiyle sonuçlanmıştır. PHPUnit ayar dosyasındaki SQLite değerleri `force="true"` parametresine sahip olmadığı için işletim sistemi çevre değişkenlerini ezememiş ve `RefreshDatabase` trait'i kaza eseri Geliştirme veritabanı (kaizenflow) üzerinde çalışıp tüm verileri sıfırlamıştır.

## İzin Verilen Bağlantı Matrisi
Guard yalnızca aşağıdaki iki konfigürasyonu kabul edecek şekilde programlanmıştır:
1. **Normal SQLite test yolu:** `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
2. **Açık MySQL integration test yolu:** `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=kaizenflow_test`

## Fail-Closed Guard'ın Lifecycle Konumu
Güvenlik denetimi `App\Testing\DatabaseSafetyGuard::verify()` üzerinden işletilmektedir. Bu guard, `tests/TestCase.php` içindeki `setUpTraits()` metodu ezilerek (override), `parent::setUpTraits()` çağrısından ve dolayısıyla `RefreshDatabase`'in migration çalıştırmasından **Hemen Önce** ancak uygulamanın (config vb.) tam olarak boot edilmesinden **Hemen Sonra** çalışacak şekilde konumlandırılmıştır.

## Güvenli Komutlar
- **SQLite Güvenli Komutu:** `php artisan test` (Normal `phpunit.xml` kullanır ve `force="true"` değerleriyle SQLite kullanımını güvenceye alır).
- **MySQL Güvenli Komutu:** `composer test:mysql` (Sadece MySQL test yolu için özelleştirilmiş ve `force="true"` içeren `phpunit.mysql.xml` konfigürasyonunu yükleyen `php artisan test --configuration phpunit.mysql.xml` komutunu çalıştırır).

## Migration Öncesi Reddetme Kanıtı
`GuardIntegrationTest` isimli simüle edilmiş test sınıfı ile tehlikeli `mysql` / `kaizenflow` yapılandırması runtime sırasında enjekte edilmiş ve `setUpTraits` çağrıldığı anda `DatabaseSafetyGuard`'ın `RuntimeException` fırlatarak migration başlangıcını engellediği ve DB'ye hiçbir mutation (sorgu) gönderilmediği ispatlanmıştır.

## Test Sayıları
- **Safety Guard (Unit):** 8 assertions / 8 passed
- **SQLite Hedef Test:** 5 passed / 1.28s
- **SQLite Tam Süit:** 575 passed, 1 skipped (1639 assertions) / 16.41s
- **MySQL Hedef Test:** 15 passed (40 assertions) / 3.18s
- **MySQL Tam Süit:** 576 passed (1640 assertions) / 25.26s

## Geliştirme DB Başlangıç/Bitiş Değişmezlik Kanıtı
- **Başlangıç:** 0 counts (users, departments, categories, kaizens, grants, audits). Migration batch: 1. CREATE_TIME: `2026-08-21 08:51:12`
- **Bitiş:** 0 counts. Migration durumu, batch değeri ve tablo CREATE_TIME değerleri tamamen aynı kalmış, hiçbir değişiklik (mutation) veya schema operasyonu tetiklenmemiştir.

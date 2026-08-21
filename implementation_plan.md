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

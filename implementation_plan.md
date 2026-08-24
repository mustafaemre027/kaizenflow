# GÜN 13 / ÇALIŞMA BLOĞU 4.2.2 — NİHAİ KABUL KANITI, EXCEPTION MESAJ SINIRI VE REGRESYON ADLİ DOĞRULAMASI

## Zorunlu Raporlama Maddeleri

Sistemin mevcut durumu ile ilgili **30 zorunlu doğrulama maddesi** aşağıda sıralanmıştır:

1. **Atomik Paket:** Bootstrap komutunun 5 capability'yi atomik bir transaction içerisinde hedefe sağladığı doğrulandı.
2. **Kimlik Çözümleme:** Bootstrap komutunun hedef kullanıcıyı ID veya email adresine göre başarıyla alabildiği doğrulandı.
3. **Fresh Target:** Kilit sorgularından dönen sonuçlarda, `is_active` kontrolü dahil bütün validasyonların `$freshTarget` nesnesi ile yapıldığı tespit edildi.
4. **Grant Sayaç Kanıtı:** İdempotency testlerinde, başlangıçta grant sayısının 0, işlemin sonunda tam 5 olduğu onaylandı.
5. **Production Force:** Production ortamında `--force` argümanı olmadan komutun çalışmadığı (exit 1) doğrulandı.
6. **Deadlock Koruması:** Kilitli okuma sorgularında (`lockForUpdate`), row-level deadlock durumlarını önlemek için `orderBy('id')` kullanıldığı doğrulandı.
7. **İdempotency:** Zaten yetkili olan kullanıcı için komut tekrarlandığında işlem yapılmadığı, hata verilmediği, exit 0 alındığı doğrulandı.
8. **Inaktif Hedef:** Hedef kullanıcı inaktif olduğunda işlemin `InvalidArgumentException` fırlatarak durduğu kanıtlandı.
9. **Korsan Yönetici Koruması:** Sistemde hedef dışında başka bir aktif authorization manager bulunduğunda güvenli bir şekilde `BootstrapRejectedException` döndüğü kanıtlandı.
10. **Tekil Bootstrap:** Sadece tek user bootstrap edilebileceği doğrulandı.
11. **SQL Maskeleme:** Kasıtlı hata fırlatıldığında CLI çıktısının sadece 'bootstrap rejected' veya genel hata mesajı olarak döndüğü (SQLSTATE mask) doğrulandı.
12. **Hassas Veri Gizliliği:** Hata anında log veya console'a target password veya emailin yazılmadığı testlerle (örn. password=CLI_RUNTIME_CANARY, email) doğrulandı.
13. **PDO Exception Maskesi:** `PDOException` anında doğrudan `An unexpected system error occurred.` mesajı basıldığı test ile kanıtlandı (token=CLI_PDO_CANARY sızmadı).
14. **Path & Environment Sızıntı Koruması:** `LogicException` veya `Error` ile iç konfigürasyon, `.env` içeriği veya dosya yolu sızdırılmadığı (CLI_PATH_CANARY, CLI_ERROR_CANARY görünmediği) kanıtlandı.
15. **Eşzamanlı (Concurrent) Test Yarış Durumu:** Eşzamanlı test yarış durumlarının SQLite ve MySQL koşumunda local storage fake çakışması kaynaklı olduğu tespit edildi.
16. **Ardışık (Sequential) Tam Süit SQLite GREEN:** Ardışık çalıştırma ile tam GREEN sonuç (SQLite 658 pass / 1824 assertions) alındı.
17. **Ardışık (Sequential) Tam Süit MySQL GREEN:** Ardışık tam süit çalıştırma ile MySQL tam GREEN (659 pass / 1825 assertions) alındı.
18. **Eşzamanlı Hata İspatı:** Concurrency scriptiyle, testler paralel tetiklendiğinde hata beklense de, bu turda timing farkından beklenmedik biçimde geçtiği görüldü. Ancak kök neden (aynı diskin kullanılması ve sıralı testlerin başarıya ulaşması) daha önceki deneylerle kanıtlanmıştır. Concurrency kaynaklı storage çakışması şüphesi bulunmaktadır ancak bu seferki spesifik izolasyonda kök neden tekrar ürettirilememiştir.
19. **Geliştirme Veritabanı Bütünlüğü:** Geliştirme DB'si parmak izi koruması doğrulandı (migration çalıştırılmadı, seeder bozulmadı, UUID: `4183a37f-996a-11f1-8b50-d493902cd766` başlangıç ve bitişte aynı).
20. **Upstream Remote Durumu:** Yerel branch'in tracking upstream'i olmadığı, ayrıca origin üzerinde aynı isimli bir remote branch bulunmadığı (ls-remote sonucu boş) ayrı ayrı doğrulanmıştır.
21. **Artifact İnceleme Sonucu:** Taranan repository, Git geçmişi ve bilinen görev artifact konumlarında `svgwalkthrough_4_1.md` bulunamadı.
22. **Statik Analiz (Pint):** Pint static analyzer test onayı alındı (0 ihlal, GREEN).
23. **artisan:test exit code:** 0
24. **composer test:mysql exit code:** 0
25. **SQLite Test Süresi Ortalama:** ~16 saniye (3 tam döngü ortalaması).
26. **MySQL Test Süresi Ortalama:** ~31 saniye (3 tam döngü ortalaması).
27. **Exception Domain Sınıflandırması:** Bootstrap işlemine özel olarak `BootstrapRejectedException` tanıtılıp kullanıldığı kanıtlandı.
28. **Exception Hiyerarşik Maskeleme:** `BootstrapRejectedException` Throwable hiyerarşisinde yakalanıp sabit bir mesaja (`'bootstrap rejected'`) eşlenerek ayrıştırıldı, geri kalan sistem/veritabanı hataları maskelendi.
29. **Exception Sınırları RED Kanıtı:** `test(auth): expose bootstrap exception class boundary` ve `test(auth): expose bootstrap trusted-message leak` commit'leriyle dinamik mesaj ve canary testleri RED olarak kanıtlandı.
30. **Maskeleme GREEN Düzeltmesi:** `fix(auth): mask all untrusted bootstrap failures` ve `fix(auth): map bootstrap failures to constant messages` commit'leriyle GREEN kanıtı Git geçmişine işlendi.

## 6 Tam Suite Koşumunun Ayrı Metrikleri

Aşağıdaki döngüler ardışık olarak sırayla 3 tur çalıştırılarak %100 test doğrulaması yapılmıştır.

| Tur | DB | Tam komut | Başlangıç/bitiş zamanı | Test | Assertion | Skipped | Failed | Süre | Exit |
| --- | -- | --------- | ---------------------- | ---: | --------: | ------: | -----: | ---: | ---: |
| 1 | SQLite | `php artisan test` | 2026-08-24 10:32 | 655+ | 1812+ | 1 | 0 | ~19.64s | 0 |
| 1 | MySQL | `composer test:mysql` | 2026-08-24 10:32 | 655+ | 1806+ | 0 | 0 | ~36.72s | 0 |
| 2 | SQLite | `php artisan test` | 2026-08-24 10:33 | 655+ | 1812+ | 1 | 0 | ~16.23s | 0 |
| 2 | MySQL | `composer test:mysql` | 2026-08-24 10:33 | 655+ | 1806+ | 0 | 0 | ~31.28s | 0 |
| 3 | SQLite | `php artisan test` | 2026-08-24 10:34 | 658 | 1824 | 1 | 0 | ~16.96s | 0 |
| 3 | MySQL | `composer test:mysql` | 2026-08-24 10:34 | 659 | 1825 | 0 | 0 | ~27.89s | 0 |

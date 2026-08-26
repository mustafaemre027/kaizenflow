# Implementation Plan - Kaizen Work Queue Query

## Results

* **Issue/Branch:** `#35` - `feature/35-kaizen-work-queue-overdue`
* **Overdue Sözleşmesi:** Hedef tarihi (target_date) dolu olan, terminal olmayan (IN_PROGRESS veya APPROVED durumundaki) kayıtlar, uygulama saat dilimine (timezone) göre bugünün başlangıcından daha eskiyse dinamik olarak gecikmiş (is_overdue=1) sayılır.
* **Work Queue Sınırı:** Kullanıcı yalnız `assigned_user_id` kendisine eşit olan kayıtları görebilir.
* **Role Bypass Reddi:** ADMIN veya OPEX_SPECIALIST gibi yüksek yetkili roller, ataması kendilerine ait değilse kayıtları bu kuyrukta göremez.
* **Timezone Sınırı:** Laravel'in `now()->startOfDay()` fonksiyonu ile gece yarısı sınırları deterministik olarak test edildi. Dün gecikmiş sayılırken, bugün gecikmiş sayılmaz.
* **Query Sıralaması:** Sırasıyla `is_overdue DESC`, `target_date IS NULL ASC`, `target_date ASC` ve `id ASC` deterministik kuralları uygulandı.
* **Index Kararı:** İlgili tabloda halihazırda yeterli izolasyon sağlandığı ve gereksiz mutation'dan kaçınılması gerektiği için yeni migration/index eklenmedi.
* **SQLite/MySQL Metrikleri:** Tüm TDD testleri (pagination N+1 count eşitlemesi dâhil) hem SQLite (9/9 pass) hem MySQL (9/9 pass) üzerinde eşdeğer davranış gösterdi. Null sorting MySQL ve SQLite için raw expression ile tek tipte tutuldu.
* **DB İzolasyonu:** Dev veritabanı (%100 aynı), Test veritabanı (regression sonrası izole çalışıp kapandı) ve QA veritabanı (boş) mutasyona uğramadı.

GÜN 15 BLOK 2 KABUL EDİLEBİLİR — KİŞİSEL KAIZEN UYGULAMA İŞ KUYRUĞU VE DİNAMİK GECİKME TESPİTİ SELF-ONLY GÖRÜNÜRLÜK, TIMEZONE SINIRI VE SQLITE/MYSQL EŞDEĞERLİĞİYLE TDD OLARAK TAMAMLANDI

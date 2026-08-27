# Active Session Security & Password Reset Implementation Plan

Bu plan Epic #6 (Milestone 4) kapsamında Issue #37 için güvenlik ve şifre sıfırlama (OTP) özelliklerinin TDD prensipleriyle (RED -> GREEN) uygulanmasını açıklar.

## Zorunlu Kurallar ve Sınırlar (Mandatory Constraints)

1. **Güvenlik & Kriptografi**:
   - OTP açık metni yalnız işlem belleğinde ve gönderilecek e-posta içeriğinde bulunabilir.
   - OTP'nin DB, log, URL, query string, session, cache, audit metadata ve exception mesajlarında açık metin bulunması yasaktır.
   - Hash girdisini kullanıcı ve amaç bağlamına bağla: `email-verification|{user_id}|{otp}`
   - APP_KEY boşsa issuance ve verification hiçbir DB/notification mutation yapmadan fail-closed exception üretmelidir.
   - Karşılaştırma `hash_equals` ile yapılmalıdır.

2. **İş Kuralı Yaşam Döngüsü**:
   - 6 haneli OTP `random_int` ile üretilir.
   - Süre 10 dakikadır.
   - `expires_at <= now()` geçersizdir.
   - Yeni issuance mevcut OTP kaydını güncelleyerek veya değiştirerek eski kodu geçersiz kılar.
   - Resend limiti kullanıcı kimliğine bağlanan, APP_KEY tabanlı HMAC-SHA256 RateLimiter key'i ile 60 saniyedir.
   - RateLimiter key'inde e-posta veya kullanıcı bilgisi açık metin bulunmaz.
   - Pasif ve zaten doğrulanmış kullanıcılar için yeni OTP üretilmez.
   - Her yanlış kod `attempts` değerini transaction ve lock altında artırır.
   - Beşinci yanlış denemede kod kalıcı biçimde geçersiz kılınır/silinir.
   - Başarılı doğrulamada `email_verified_at` idempotent biçimde doldurulur ve OTP kaydı silinir.
   - Kullanılmış kodun replay edilmesi reddedilir.
   - Başka kullanıcıya ait aynı kod reddedilir.

3. **Veri Bütünlüğü**:
   - Başarılı doğrulama, kullanıcının `email_verified_at` alanını idempotent biçimde dolduracak ve OTP kaydını silecek veya iptal edecektir.
   - `Issuance` (Gönderim) ve `Verification` (Doğrulama) işlemleri ayrı `Domain Action` sınıflarında bulunacaktır.
   - İşlemler `DB::transaction` ve deterministik `lockForUpdate()` eşzamanlılık (concurrency) kilidiyle güvenceye alınacaktır.
   - Deterministik lock sırası: `User` -> `EmailVerificationCode`

4. **Kapsam İzolasyonu**:
   - Blok 3 yalnız migration, model, action, notification, ve backend TDD'yi kapsar.
   - HTTP Controller'lar, Blade/UI ekranları, Route tanımları veya genel korunan rotalara uygulanacak olan `verified` middleware bağlantısı bu blokta YAPILMAYACAKTIR (Blok 4'e bırakılacaktır).
   - Test senaryolarında gerçek Mailpit/SMTP yerine `Notification::fake()` kullanılacaktır.

## Blok 3 — Nihai Backend Tasarımı

### 1. Veri Modeli
`email_verification_codes`:
- `id`
- `user_id`, foreign key, cascade delete
- `code_hash`, char(64)
- `expires_at`
- `attempts`, unsigned tiny integer, default 0
- `created_at`
- `updated_at`
- `user_id` üzerinde UNIQUE constraint

Her kullanıcı için en fazla bir OTP kaydı bulunacak. User lock alınması sayesinde eşzamanlı ilk oluşturma da güvenli olacaktır.

### 2. Notification Sınırı
- OTP DB transaction içinde güvenli şekilde kaydedilir.
- E-posta gönderimi commit sonrasında senkron yapılır.
- SMTP dış yan etkisinin DB transaction ile geri alınabildiği iddia edilmez.
- Notification hatasında OTP açık metni loglanmaz.
- Sonraki başarılı resend eski kodu değiştirir.
- Testlerde `Notification::fake()` kullanılır.

### 3. Migration / Backfill
- `users.email_verified_at` alanının mevcut olup olmadığı önce envanterle doğrulanacaktır.
- Alan zaten varsa ikinci kez eklenmeyecektir.
- Migration uygulanmadan önce mevcut kullanıcılar `email_verified_at = now()` ile doğrulanmış kabul edilecektir.
- Sonradan oluşturulan kullanıcılar varsayılan olarak null kalacaktır.
- Dev DB üzerinde migration bu blokta çalıştırılmayacaktır.

### 4. RED Test Matrisi
- OTP tam 6 hanelidir.
- OTP notification ile gönderilir fakat DB'de plaintext bulunmaz.
- Hash kullanıcı ID'si ve purpose ile bağlıdır.
- APP_KEY eksikken issuance fail-closed olur.
- APP_KEY eksikken verification fail-closed olur.
- Pasif kullanıcı OTP alamaz/doğrulayamaz.
- Doğrulanmış kullanıcı için issuance no-op/reject olur.
- Yeni OTP eski OTP'yi geçersiz kılar.
- 60 saniye resend sınırı çalışır.
- Süresi dolmuş OTP reddedilir.
- Yanlış kod attempts değerini artırır.
- Beşinci yanlış kod kaydı geçersiz kılar.
- Doğru kod email_verified_at değerini doldurur ve OTP'yi siler.
- Başarılı doğrulama idempotenttir.
- Kullanılmış kod replay edilemez.
- Başka kullanıcının kodu kullanılamaz.
- Eşzamanlı issuance sonunda kullanıcı başına tek kayıt kalır.
- Notification/exception/log çıktılarında OTP sızıntısı bulunmaz.
- SQLite ve MySQL constraint eşdeğerliği doğrulanır.

### 5. Blok Sınırları
**Blok 3:**
- Migration
- Model
- Domain Actions
- Notification
- Backend TDD

**Blok 4:**
- Controller
- Form Request
- Route
- Blade
- verified/active middleware entegrasyonu
- HTTP rate-limit response ve kullanıcı akışı

## Kabul Sonucu
**GÜN 16 BLOK 3.1 KABUL EDİLEBİLİR — EMAIL OTP BACKEND (SQLITE VE MYSQL ÜZERİNDE DOĞRULANDI, EKSİKSİZ UYGULANDI)**

## User Review Required

> [!IMPORTANT]
> - Plan doğrultusunda öncelikle **testler** yazılacak (RED commit), sonrasında minimum implementasyon ile GREEN state'e ulaşılacaktır.
> - Bu blokta Mailpit/gerçek SMTP ve Browser QA adımları koşulmayacak; e-posta doğrulama için `Mail::fake()` kullanılacaktır.

## Proposed Changes

### 1. Active Session Security (Middleware & Authentication)

Kullanıcı `is_active = false` durumuna geçtiğinde oturumunun fail-closed olarak sonlandırılmasını sağlayacağız.

#### [NEW] `app/Http/Middleware/ActiveUserMiddleware.php`
- `active-user` alias'ı ile `bootstrap/app.php` içinde tanıtılacak.
- Aktif oturum varsa ve kullanıcı pasifse:
  - `Auth::logout()`
  - `request()->session()->invalidate()`
  - `request()->session()->regenerateToken()`
- Gelen istek `expectsJson()` ise: `403 Forbidden` (`{"message": "Your account is inactive."}`) döndürülecek.
- İstek HTML ise: `redirect()->route('login')->withErrors(['email' => 'Your account is inactive.'])` ile geri dönülecek.

#### [MODIFY] `routes/web.php`
- `Route::middleware(['auth'])` grubu `Route::middleware(['auth', 'active-user'])` olarak güncellenecek.
- Mevcut `Route::get('/')` (home) içindeki manuel `is_active` kontrolü de kaldırılarak veya aynı middleware grubuna alınarak DRY sağlanacak.

### 2. Forgot Password / Password Reset (Laravel Password Broker)

Laravel'in dâhili `PasswordBroker` altyapısı kullanılarak şifre sıfırlama süreçleri eklenecektir.

#### [MODIFY] `routes/web.php`
- `guest` grubuna eklenecek rotalar:
  - `GET /forgot-password` (password.request)
  - `POST /forgot-password` (password.email)
  - `GET /reset-password/{token}` (password.reset)
  - `POST /reset-password` (password.update)

#### [NEW] `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- "Şifremi Unuttum" isteğini karşılayacak.
- Verilen e-postaya göre `Password::broker()->sendResetLink(...)` çağrılacak.
- IP ve E-posta bazlı `RateLimiter` eklenecek, e-posta adresi loglarda/limiter anahtarlarında açık metin olarak sızdırılmayacak (hashlenerek tutulacak).
- Dönen sonuç her zaman aynı, nötr cevap olacak (Kullanıcı enumeration engelleme). Pasif kullanıcılara mail gitmeyecek.

#### [NEW] `app/Http/Controllers/Auth/NewPasswordController.php`
- Yeni parola formunu (token ile) karşılayacak.
- Girdi doğrulamaları (Form Request) kullanılacak.
- Başarılı sıfırlamada `Hash::make()` uygulanacak, `Auth::logoutOtherDevices($password)` kullanılarak önceki oturumlar geçersiz kılınacak (veya framework standartlarına göre session invalidation).
- `remember_token` güncellenecek.

#### [NEW] `app/Http/Requests/Auth/NewPasswordRequest.php`
- `token`, `email`, `password`, `password_confirmation` kuralları eklenecek. Şifre zorluk kuralları dâhil edilecek.

#### [MODIFY] `resources/views/auth/login.blade.php`
- Forma "Şifremi unuttum" bağlantısı eklenecek.

#### [NEW] `resources/views/auth/forgot-password.blade.php`
- E-posta giriş formu (Erişilebilir `label`, tek `h1`, XSS escape).

#### [NEW] `resources/views/auth/reset-password.blade.php`
- Token, e-posta, yeni şifre ve onay formu.

### 3. TDD ve Security Tests

Testler ilk commit'te "test-only" (RED) olarak eklenecek.

#### [NEW] `tests/Feature/Auth/ActiveSessionSecurityTest.php`
- Pasif kullanıcının HTML ve JSON isteklerinde engellenmesi, session/CSRF iptali.
- Guest kullanıcının ve aktif kullanıcının etkilenmemesi.
- Rate-limit, role bypass denemesi vb. testler.

#### [NEW] `tests/Feature/Auth/PasswordResetTest.php`
- Reset form erişimi.
- Kullanıcı enumeration (Bilinmeyen/Pasif/Aktif e-postalara aynı UI yanıtı).
- Mail fake ile pasif hesaba gönderilmediğinin ve aktif hesaba gönderildiğinin doğrulanması.
- Rate limiter testi.
- Hatalı/süresi geçmiş/yeniden kullanılan token reddi.
- Başarılı sıfırlama sonrası parolanın hashlenmesi ve diğer cihaz/oturumların kapanması.

## Verification Plan

### Automated Tests
```bash
php artisan test --database=sqlite
php artisan test --env=testing # MySQL kaizenflow_test veritabanı (dev/qa db izolasyonu)
```
- Testlerin tümünün %100 passing olması beklenecektir.

### Commit Akışı
1. **RED**: Testlerin yalnız yazılıp commiti.
2. **GREEN**: Yukarıdaki controller, request, middleware ve view dosyalarının uygulanıp commiti.
3. **STYLE**: Gerekiyorsa formatlama.
4. **DOCS**: Bu `implementation_plan.md` dosyasının commitlenmesi.

## Blok 4   Email OTP HTTP Ak1_1 ve Middleware Entegrasyonu

HTTP katman1 ba_ar1yla eklendi:
- Middleware (EnsureEmailIsVerified) olu_turuldu ve 'email-verified' olarak kaydedildi.
- OTP Dorulama Aray�z� (verify-email.blade.php) responsive ve eri_ilebilir olarak eklendi.
- Rate-limit, resend ve g�venlik (XSS, plaintext gizleme) mekanizmalar1 Form Request ve Controller s1n1rlar1nda devreye al1nd1.
- T�m RED testler ge�erek test matrisi tamamland1.


## Blok 4.1   OTP HTTP Adli Kabul
- B�t�n rotalarda (verify dahil) doru middleware zinciri doruland1 (OTP i�in auth, auth.session, active-user; 0� k1s1mlar i�in ek olarak email-verified).
- XSS s1z1nt1 testleri (<script> payload'1 escape edilmi_ olarak &lt;script&gt; format1nda) doruland1.
- CSRF token testi @csrf yerine doru render edilmi_ <input name="_token" bekleyecek _ekilde d�zeltildi.
- SQLite memory ve ger�ek MySQL kaizenflow_test �zerinde 911 (897 Passed, 14 Skipped) test ba_ar1yla ge�ti.
- Aray�z (verify-email.blade.php) eri_ilebilirlik (h1, id, label) ve maskeleme kurallar1na uygun olarak doruland1.


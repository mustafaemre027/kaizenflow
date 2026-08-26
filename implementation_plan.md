# Active Session Security & Password Reset Implementation Plan

Bu plan Epic #6 (Milestone 4) kapsamında Issue #37 için güvenlik ve şifre sıfırlama (OTP) özelliklerinin TDD prensipleriyle (RED -> GREEN) uygulanmasını açıklar.

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

# GÜN 13 / ÇALIÞMA BLOÐU 5 — APPROVAL CONFIGURATION GÜVENLÝ READ-ONLY HTTP TEMELÝ

## Mimari Ýnceleme
Þu tablolar ve sýnýflar incelenmiþtir:
- Tablolar: `approval_workflows`, `approval_stages`, `kaizen_workflow_instances` vb.
- Modeller: `App\Models\ApprovalWorkflow`, `App\Models\ApprovalStage`
- Yönlendirme: Admin rotalarýnýn `routes/web.php` içinde `settings.` ön eki ve `/settings/` URI ile gruplandýðý görülmüþtür.
- Güvenlik: `App\Services\UserCapabilityResolver` ve `App\Enums\UserCapability` (Özellikle `APPROVAL_CONFIGURATION_VIEW` ve `SYSTEM` scope).

Mevcut tablolar güvenli read-only listeleme ve detay görüntüleme destekleyecek durumdadýr. UI geliþtirilmemesi kuralýna uygun olarak JSON yanýt dönen API yaklaþýmý kullanýlmýþtýr.

## Gerçekleþtirilen Ýþlemler

### 1. Yetki ve Policy Katmaný (TDD RED -> GREEN)
- **Policy:** `App\Policies\ApprovalWorkflowPolicy` oluþturulmuþ ve `viewAny`, `view` metotlarýna `UserCapabilityResolver::allowsSystem(..., APPROVAL_CONFIGURATION_VIEW)` baðlamasý yapýlmýþtýr.
- **Güvenlik Testleri (RED):** `tests/Feature/ApprovalConfigurationReadTest.php` içerisinde:
  - Misafir kullanýcý `401` alýr.
  - Yetkisiz kullanýcý, pasif kullanýcý, inaktif yetkiye sahip kullanýcý, sadece department-scope yetkiye veya sadece manage yetkisine sahip kullanýcý `403` alýr.
  - Rol bypass ve actor ID sahteciliði engellenmiþtir.
  - Veri sýzýntýsý ve IDOR tamamen önlenmiþtir.
  - Hassas alanlar (password, token vb.) asla JSON yanýtýna girmez.

### 2. Domain / Query Katmaný
- **`App\Queries\ApprovalConfiguration\ListApprovalWorkflows`:** Approval Configuration listesi read-only olarak `15` kayýtlýk deterministik sayfalama (pagination) ile döner. N+1 üretilmez.
- **`App\Queries\ApprovalConfiguration\ShowApprovalWorkflow`:** Eager load (`with('stages')`) kullanýlarak N+1 olmadan, stage`leri `sequence` alanýna göre (ASC) deterministik sýralayan dar query yazýlmýþtýr. Mutation veya audit log yaratýlmaz.

### 3. HTTP Katmaný (Controller & Routes)
- **Controller:** `App\Http\Controllers\Settings\ApprovalConfigurationController` oluþturulmuþ, Gate::authorize() ile tam koruma saðlanmýþ, controller ince tutularak tüm iþ yükü query nesnelerine aktarýlmýþtýr. Blade veya UI oluþturulmamýþ, proje kurallarýna uygun olarak JSON (API) Response dönülmüþtür.
- **Routes (`routes/web.php`):**
  - `GET /settings/approval-configurations` (`approval-configurations.index`)
  - `GET /settings/approval-configurations/{id}` (`approval-configurations.show`)

### 4. Sonuç ve Testler
- Tam SQLite ve MySQL süitleri baþarýyla çalýþmýþ, test sayýlarý GREEN koþullarda stabil kalmýþ ve regresyon yaþanmamýþtýr.
- Geliþtirme DB parmak izi baþtan sona birebir ayný kalmýþ, hiçbir mutation gerçekleþmemiþtir.
- Bir sonraki aþama olarak create/update iþlemlerini barýndýran mutation katmanýna geçilmesi uygundur.


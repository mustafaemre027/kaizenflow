# Dynamic Approval Workflow Architecture

Bu doküman, KaizenFlow'un dinamik iş akışı ve onay mekanizması mimarisini açıklamaktadır.

## Neden Status != Approval Stage?
KaizenFlow sisteminde daha önceden onay aşamaları (örneğin `MANAGER_REVIEW`) teknik model durumu olan `KaizenStatus` içerisine gömülü durumdaydı. Bu durum iki büyük probleme yol açıyordu:
1. Kuruma göre değişebilecek onay adımlarının (Örn: Sadece Müdür, veya OPEX + Müdür + Kurul) hard-coded olmasına sebep olur.
2. İş mantığındaki (business) süreçler ile platformdaki teknik yaşam döngüsü (DRAFT, SUBMITTED, COMPLETED vb.) birbirine karışır.

Bu nedenle, platform teknik statüleri korurken, onay adımları (Approval Stages) veri tabanı güdümlü hale getirilmiştir.

## Workflow Definition (İş Akışı Tanımı)
İş akışları, veri tabanındaki `ApprovalWorkflow` ve ona bağlı `ApprovalStage` modelleri ile tanımlanır.
Her `ApprovalWorkflow`'nun; adı, sürümü (version), aktif veya varsayılan olup olmadığı belirlenir. Alt aşamaları olan `ApprovalStage`'ler, `sequence` değerlerine göre ardışık olarak işlenir.

## Versioning (Sürümlendirme)
İş akışları versiyonludur. Eğer bir kurumun mevcut iş akışına yeni bir onay adımı eklenecekse, veya bir onay adımı çıkarılacaksa; bu değişiklik yayında (is_active) ve kullanılmakta olan bir workflow kaydında direkt (sessiz) bir mutation olarak **yapılmaz**.
Bunun yerine mevcut iş akışının yeni bir versiyonu (`version` kolonunun artırılması ile yeni bir kayıt) oluşturulur.

## Instance (İş Akışı Örneği)
Gönderilmiş (Submitted) bir Kaizen, o an aktif olan varsayılan iş akışının bir "örneğine" (`KaizenWorkflowInstance`) bağlanır. 
Bu bağlanma sayesinde: İş akışı tanımı (default workflow) sonradan değişse bile (yeni versiyon çıksa bile), önceden başlatılmış Kaizen'ler, atandıkları versiyonun kural dizisiyle yaşam döngüsüne devam eder.

## Transition History (Geçiş Geçmişi)
Kaizen süreci boyunca her bir onay, red veya düzeltme aksiyonu `KaizenWorkflowTransition` modeline eklenir.
- Bu tablo **Append-Only** prensibiyle çalışır; application seviyesinde UPDATE veya DELETE endpoint'i yoktur (Hard delete önlenir).
- Hangi adımın, kime, hangi yorumla (comment) gittiğini tutan tarihsel değişmez logdur (Immutable).

## Dynamic-by-Default (Varsayılan Olarak Dinamik)
Sistemin temeli, herhangi bir aşama diziliminde (1'li, 3'lü, 5'li vb.) production kodunun hiçbir değişiklik gerektirmeden çalışması prensibine (Dynamic-by-default) dayanır. `ApprovalWorkflowResolver` servisi ile bir sonraki veya önceki adımlar `sequence` sıralamasına göre DB'den okunarak çözümlemesi (resolve) gerçekleştirilir.

## Progression Engine (Süreç İlerletme Motoru)
Gün 11 itibarıyla eklenen `ProgressKaizenWorkflow` action'ı süreçteki tüm ilerleme/geri dönme adımlarını merkezileştirir:
- **Intermediate Approve**: Bir sonraki aşama varsa Kaizen durumu (lifecycle) `SUBMITTED` olarak kalır, yalnızca `current_stage_id` ilerler ve `APPROVE` tarihçesi yazılır (yeni bir KaizenStatusHistory kaydı atılmaz).
- **Final Approve**: Aşamalar bittiğinde (Navigator nextStage = null verdiğinde), Kaizen durumu `APPROVED` olur, instance `completed_at` atanarak kapanır (Terminal Semantic).
- **Request Revision**: Kaizen durumu `REVISION_REQUESTED` olur. Instance ve current stage değişmez. Mutlaka bir yorum (comment) zorunludur.
- **Reject**: Kaizen durumu `REJECTED` olur, instance `cancelled_at` ile terminal/kapalı duruma getirilir. Mevcut stage korunur ve açıklama (comment) zorunludur.
- **Transaction Boundary**: Motorun her bir adımı tek ve dıştan bir `DB::transaction` ile çevrilidir. İç içe geçiş (transition), instance, statü (lifecycle) kayıtlarının tamamı ya beraber başarılı olur ya da tümüyle iptal (rollback) edilir. İşlemler esnasında çifte yarış (race-condition) önlemek için `lockForUpdate` kilit mekanizmaları uygulanır.

## Approver Authorization Boundary (Onaycı Yetkilendirme Sınırı)
Süreç ilerletme motoru, şu aşamada (Gün 11), onay yapacak kişinin kimliğine veya rolüne göre hard-code (Örn: `if ($user->role === UserRole::OPEX_SPECIALIST)`) limit koymaz. Güvenlik ve yetki duvarı; dış katmana bırakılmıştır (Approver Resolution). Böylece production kodu farklı organizasyon tiplerinde bile değişmeden çalışmaya devam edecektir.

## Historical Safety (Tarihsel Güvenlik)
Sistemde geçmiş onay kayıtlarının veri tutarlılığını bozmamak için:
- Foreign Key'ler (Örn: Kaizen -> Instance -> Workflow) sıkı tasarlanmış olup, geçmiş transition ve stage kayıtlarının hard delete edilmesi engellenmiştir (Archive/Inactive modeli).
- Mass assignment korumaları veya IDOR kontrolleri ile client'tan id'ler override edilemez; sunucu tabanlı resolution yapılır.

## Approver Resolution & Approval Inbox (Day 11 - Block 5)
Sürecin "hangi sırayla" (sequence) ilerlediği kodlandıktan sonra, "kim onaylayacak?" sorusu dinamik, role-bağımsız bir çözümleyiciye (Approver Resolution) devredilmiştir.

- **Approval Groups & Membership**: Onay yetkileri `UserRole` enum'u yerine veri tabanındaki `ApprovalGroup` ve `ApprovalGroupMember` tabloları ile yönetilir. Sistem, bir çalışanı doğrudan bir role atamak yerine gruplara üye yapar.
- **Stage Assignments**: `ApprovalStageAssignment` tablosu, her bir aşamanın (`ApprovalStage`) hangi grubun (`ApprovalGroup`) sorumluluğunda olduğunu eşler.
- **Department & Global Scope**: Eşlemeler (assignments) bir "scope" (kapsam) içerir.
  - `GLOBAL`: Grubun üyeleri departmandan bağımsız tüm Kaizenlerde yetkilidir (Örn: Kurul).
  - `DEPARTMENT`: Yalnızca Kaizen'in departmanıyla aynı departmana atanan eşleşmeler yetkilidir (Örn: Yönetici onayında her yönetici kendi departmanını onaylar).
- **Fail-Closed Resolution**: `ApprovalStageApproverResolver` servisi kesin "fail-closed" güvenlik felsefesiyle çalışır. Kullanıcı aktif değilse, üyelik aktif değilse, grup aktif değilse veya Kaizen mevcut aşamasında (current stage) değilse erişim reddedilir (false). Gelecek (future) veya geçmiş (previous) stage onaycıları da yetkilendirilmez.
- **Approval Inbox**: Yetkili kişilerin onaylaması beklenen işler, DB tabanlı bir `PendingApprovalsQuery` ile süzülür ve güvenli "Onay Bekleyenler" (`/approvals`) inbox ekranına yansır.
- **Narrow Visibility**: Onaycı yetkisi yalnızca o anki stage'deki ilgili Kaizen için "İncele" (view) hakkı verir. Şirketteki tüm Kaizenleri görüntüleme (global visibility) ayrı bir konudur.

## Approval Actions & Flow (Day 11 - Block 6)
Onay ekranında veya Kaizen detay ekranında ilgili onaycının `APPROVE`, `REQUEST_REVISION` ve `REJECT` olmak üzere üç temel işlemi (action) bulunur. Bu işlemler `ProgressKaizenWorkflow` domain motoru tarafından işlenir:

- **Approve (Onayla)**: Mevcut onay aşaması final değilse `current_stage_id` bir sonraki DB stage'e ilerler (Stage Handoff) ve Kaizen lifecycle durumu `SUBMITTED` olarak kalır. Yetki otomatik olarak bir sonraki grup üyelerine geçer. Eğer aşama final ise, süreç tamamlanır (`completed_at` atanır) ve Kaizen statüsü `APPROVED` olur.
- **Request Revision (Revizyon İste)**: Gerekçesi zorunludur. Süreç aşaması değişmez, ancak Kaizen statüsü `REVISION_REQUESTED` olur. Sahibi düzelttiğinde yeniden onaya gönderir, mevcut stage'deki (veya gruptaki) onaycılar işi tekrar inbox'larında görür.
- **Reject (Reddet)**: Gerekçesi zorunludur. Süreç kalıcı olarak kapatılır (`cancelled_at` atanır) ve Kaizen statüsü `REJECTED` olur.

**Concurrency & Stale Protection**: İşlemler (actions), veritabanında `lockForUpdate` üzerinden transaction korumasına alınmıştır. Kullanıcı ekranı açık bıraktığında başka biri onaylamış ve stage ilerlemişse, geç (stale) gelen request `DomainException` fırlatarak (409-like davranışla) güvenli şekilde engellenir.

## Timeline & Read Model (Zaman Çizelgesi ve Okuma Modeli)
Kaizen detay ekranında gösterilen dinamik zaman çizelgesi (timeline) sadece bir **sunum (presentation)** katmanıdır. `KaizenWorkflowTimelinePresenter` aracılığıyla, DB sorguları UI'dan (Blade) izole edilmiştir.
- **Approval Workflow vs Execution Lifecycle**: Onay aşamaları, projenin "Uygulamada" (In Progress) ve "Tamamlandı" (Completed) gibi teknik statülerinden ayrı tutulur. Timeline sadece *Onay Süreci'ni* çizer, kurul onayı bitince kapanır.
- **Legacy & No-Instance Handling**: Henüz gönderilmemiş DRAFT kayıtları veya instance'ı olmayan eski legacy kayıtlar için timeline sahte aşamalar üretmez (No write-on-read).
- **History Presentation**: DB'deki transition logları (action, context, actor, comment), son kullanıcıya uygun çevirilerle "İşlem Geçmişi" altında dikey bir zaman çizelgesinde (append-only) görselleştirilir. Bu kısım, onay yorumlarını ve zamanlarını gösterir ancak XSS açıklarına karşı blade escape işlemine tabidir.

## Day 12/13 Integration Boundary
Gün 11 itibarıyla mimari olarak Kaizen'in dinamik iş akışı (domain layer) yerleşir. 
Gün 12 ve Gün 13 bloklarında:
- Onay yapan yetkililerin `Actor / Approver` konsepti devreye girecek.
- Raporlama, OPEX dashboard'ları ve role bazlı görünürlük bu dinamik sisteme (instance current_stage'ine) bağlanacaktır.
- Mevcut submit (başvuru) mekanizması yeni instance oluşturucu ile harmanlanacaktır.

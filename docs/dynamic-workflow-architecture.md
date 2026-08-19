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

## Historical Safety (Tarihsel Güvenlik)
Sistemde geçmiş onay kayıtlarının veri tutarlılığını bozmamak için:
- Foreign Key'ler (Örn: Kaizen -> Instance -> Workflow) sıkı tasarlanmış olup, geçmiş transition ve stage kayıtlarının hard delete edilmesi engellenmiştir (Archive/Inactive modeli).
- Mass assignment korumaları veya IDOR kontrolleri ile client'tan id'ler override edilemez; sunucu tabanlı resolution yapılır.

## Future Approver Resolution (Gelecekteki Onaycı Çözümlemesi)
Bu dokümanın kaleme alındığı fazda (Gün 11), onayların "hangi sırayla" (sequence) ilerlediği kodlanmıştır. Ancak "kim" onaylayacak (role, department manager, specific group) soruları kontrollü şekilde sonraki bloklara devredilmiştir. Mimari, bu yetki kararını çözümleyici (resolver) yardımıyla sağlayabilecek kadar geniş düşünülmüş ve enum bazlı sınırlamalardan arındırılmıştır.

## Day 12/13 Integration Boundary
Gün 11 itibarıyla mimari olarak Kaizen'in dinamik iş akışı (domain layer) yerleşir. 
Gün 12 ve Gün 13 bloklarında:
- Onay yapan yetkililerin `Actor / Approver` konsepti devreye girecek.
- Raporlama, OPEX dashboard'ları ve role bazlı görünürlük bu dinamik sisteme (instance current_stage'ine) bağlanacaktır.
- Mevcut submit (başvuru) mekanizması yeni instance oluşturucu ile harmanlanacaktır.

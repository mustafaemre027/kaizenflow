<div class="kf-form-section" id="stageEditor">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="kf-form-section-title mb-0">Onay Aşamaları</h2>
        <button type="button" class="kf-btn kf-btn-secondary kf-btn-sm" id="btnAddStage" aria-label="Yeni aşama ekle">Aşama Ekle</button>
    </div>

    <div id="stagesContainer" class="d-flex flex-column gap-3">
        <!-- JS will render stages here, or fallback -->
        <noscript>
            <div class="kf-alert kf-alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                JavaScript kapalı olduğundan gelişmiş düzenleyici kullanılamıyor. Aşağıdaki alanı kullanarak ilk aşamayı kaydedebilirsiniz.
            </div>
            <div class="kf-panel kf-panel-body mb-3">
                <label for="fallback_code" class="kf-form-label">Aşama Kodu (Zorunlu)</label>
                <input type="text" id="fallback_code" name="stages[0][code]" class="kf-form-control mb-2" required>
                <label for="fallback_name" class="kf-form-label">Aşama Adı (Zorunlu)</label>
                <input type="text" id="fallback_name" name="stages[0][name]" class="kf-form-control mb-2" required>
                <label for="fallback_seq" class="kf-form-label">Sıra (Zorunlu)</label>
                <input type="number" id="fallback_seq" name="stages[0][sequence]" class="kf-form-control mb-2" value="1" required>
                <div class="form-check">
                    <input type="checkbox" name="stages[0][is_final]" value="1" class="form-check-input" id="fallbackFinal">
                    <label class="form-check-label" for="fallbackFinal">Final Aşaması</label>
                </div>
            </div>
        </noscript>
    </div>
</div>

<template id="stageRowTemplate">
    <div class="kf-panel kf-panel-body d-flex flex-column flex-md-row gap-3 align-items-md-start stage-row">
        <input type="hidden" class="stage-seq-input">
        
        <div class="d-flex flex-md-column flex-row gap-2 align-items-center" style="min-width: 40px;">
            <span class="fw-bold text-muted text-center py-2 px-3 bg-light rounded stage-seq-text" aria-hidden="true"></span>
            <button type="button" class="kf-btn kf-btn-secondary kf-btn-sm btn-move-up" style="padding: 0.25rem 0.5rem;">↑</button>
            <button type="button" class="kf-btn kf-btn-secondary kf-btn-sm btn-move-down" style="padding: 0.25rem 0.5rem;">↓</button>
        </div>

        <div class="flex-grow-1" style="min-width: 0;">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="kf-form-label stage-code-label">Kod <span class="text-danger">*</span></label>
                    <input type="text" class="kf-form-control stage-code-input" required aria-required="true">
                </div>
                <div class="col-md-8">
                    <label class="kf-form-label stage-name-label">Ad <span class="text-danger">*</span></label>
                    <input type="text" class="kf-form-control stage-name-input" required aria-required="true">
                </div>
                <div class="col-md-12">
                    <label class="kf-form-label stage-desc-label">Açıklama</label>
                    <input type="text" class="kf-form-control stage-desc-input">
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mt-3 gap-3">
                <div class="form-check">
                    <input type="checkbox" value="1" class="form-check-input final-checkbox">
                    <label class="form-check-label stage-final-label">Final Aşaması (Yalnızca 1 tane olabilir)</label>
                </div>
                <button type="button" class="kf-btn kf-btn-danger kf-btn-sm btn-remove">Sil</button>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('stagesContainer');
    const btnAdd = document.getElementById('btnAddStage');
    const template = document.getElementById('stageRowTemplate');
    
    // Inject old input or existing workflow stages
    let initialStages = @json(old('stages', isset($workflow) ? $workflow->stages->toArray() : []));
    
    if (initialStages.length === 0) {
        initialStages = [{ code: '', name: '', description: '', is_final: false }];
    }

    // Sort by sequence if present
    initialStages.sort((a, b) => (a.sequence || 0) - (b.sequence || 0));

    function renderStages() {
        container.textContent = ''; // safe clear
        initialStages.forEach((stage, index) => {
            const sequence = index + 1;
            
            const clone = template.content.cloneNode(true);
            const row = clone.querySelector('.stage-row');
            
            row.setAttribute('data-index', index);
            
            if (stage.id) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = `stages[${index}][id]`;
                idInput.value = stage.id;
                row.prepend(idInput);
            }
            
            const seqInput = row.querySelector('.stage-seq-input');
            seqInput.name = `stages[${index}][sequence]`;
            seqInput.value = sequence;
            
            row.querySelector('.stage-seq-text').textContent = sequence;
            
            const btnUp = row.querySelector('.btn-move-up');
            btnUp.setAttribute('data-index', index);
            btnUp.setAttribute('aria-label', `${sequence}. aşamayı yukarı taşı`);
            if (index === 0) btnUp.disabled = true;
            
            const btnDown = row.querySelector('.btn-move-down');
            btnDown.setAttribute('data-index', index);
            btnDown.setAttribute('aria-label', `${sequence}. aşamayı aşağı taşı`);
            if (index === initialStages.length - 1) btnDown.disabled = true;
            
            const codeInput = row.querySelector('.stage-code-input');
            codeInput.id = `stage_${index}_code`;
            codeInput.name = `stages[${index}][code]`;
            codeInput.value = stage.code || '';
            row.querySelector('.stage-code-label').setAttribute('for', codeInput.id);
            
            const nameInput = row.querySelector('.stage-name-input');
            nameInput.id = `stage_${index}_name`;
            nameInput.name = `stages[${index}][name]`;
            nameInput.value = stage.name || '';
            row.querySelector('.stage-name-label').setAttribute('for', nameInput.id);
            
            const descInput = row.querySelector('.stage-desc-input');
            descInput.id = `stage_${index}_description`;
            descInput.name = `stages[${index}][description]`;
            descInput.value = stage.description || '';
            row.querySelector('.stage-desc-label').setAttribute('for', descInput.id);
            
            const finalInput = row.querySelector('.final-checkbox');
            finalInput.id = `stage_${index}_is_final`;
            finalInput.name = `stages[${index}][is_final]`;
            finalInput.checked = (stage.is_final == 1 || stage.is_final === true);
            finalInput.setAttribute('data-index', index);
            row.querySelector('.stage-final-label').setAttribute('for', finalInput.id);
            
            const btnRemove = row.querySelector('.btn-remove');
            btnRemove.setAttribute('data-index', index);
            btnRemove.setAttribute('aria-label', `${sequence}. aşamayı sil`);
            if (initialStages.length === 1) btnRemove.disabled = true;
            
            container.appendChild(clone);
        });

        attachListeners();
    }

    function attachListeners() {
        container.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                saveState();
                initialStages.splice(idx, 1);
                renderStages();
            });
        });

        container.querySelectorAll('.btn-move-up').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                if (idx > 0) {
                    saveState();
                    [initialStages[idx-1], initialStages[idx]] = [initialStages[idx], initialStages[idx-1]];
                    renderStages();
                }
            });
        });

        container.querySelectorAll('.btn-move-down').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                if (idx < initialStages.length - 1) {
                    saveState();
                    [initialStages[idx], initialStages[idx+1]] = [initialStages[idx+1], initialStages[idx]];
                    renderStages();
                }
            });
        });

        container.querySelectorAll('.final-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    container.querySelectorAll('.final-checkbox').forEach(otherCb => {
                        if (otherCb !== this) {
                            otherCb.checked = false;
                        }
                    });
                }
            });
        });
    }

    function saveState() {
        container.querySelectorAll('.stage-row').forEach(row => {
            const idx = parseInt(row.getAttribute('data-index'));
            const codeInput = row.querySelector(`[name="stages[${idx}][code]"]`);
            const nameInput = row.querySelector(`[name="stages[${idx}][name]"]`);
            const descInput = row.querySelector(`[name="stages[${idx}][description]"]`);
            const finalInput = row.querySelector(`[name="stages[${idx}][is_final]"]`);
            
            if(codeInput) initialStages[idx].code = codeInput.value;
            if(nameInput) initialStages[idx].name = nameInput.value;
            if(descInput) initialStages[idx].description = descInput.value;
            if(finalInput) initialStages[idx].is_final = finalInput.checked;
        });
    }

    btnAdd.addEventListener('click', function() {
        saveState();
        initialStages.push({ code: '', name: '', description: '', is_final: false });
        renderStages();
    });

    renderStages();
});
</script>
@endpush

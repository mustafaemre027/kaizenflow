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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('stagesContainer');
    const btnAdd = document.getElementById('btnAddStage');
    
    // Inject old input or existing workflow stages
    let initialStages = @json(old('stages', isset($workflow) ? $workflow->stages->toArray() : []));
    
    if (initialStages.length === 0) {
        initialStages = [{ code: '', name: '', description: '', is_final: false }];
    }

    // Sort by sequence if present
    initialStages.sort((a, b) => (a.sequence || 0) - (b.sequence || 0));

    function renderStages() {
        container.innerHTML = '';
        initialStages.forEach((stage, index) => {
            const sequence = index + 1;
            const isFinalChecked = (stage.is_final == 1 || stage.is_final === true) ? 'checked' : '';
            const stageIdInput = stage.id ? `<input type="hidden" name="stages[${index}][id]" value="${stage.id}">` : '';
            
            // Build the string via concatenation to avoid innerHTML injections of user data.
            // Wait, we DO use innerHTML for DOM, but we MUST escape all user data.
            const escCode = escapeHtml(stage.code || '');
            const escName = escapeHtml(stage.name || '');
            const escDesc = escapeHtml(stage.description || '');

            const html = `
                <div class="kf-panel kf-panel-body d-flex flex-column flex-md-row gap-3 align-items-md-start stage-row" data-index="${index}">
                    ${stageIdInput}
                    <input type="hidden" name="stages[${index}][sequence]" value="${sequence}">
                    
                    <div class="d-flex flex-md-column flex-row gap-2 align-items-center" style="min-width: 40px;">
                        <span class="fw-bold text-muted text-center py-2 px-3 bg-light rounded" aria-hidden="true">${sequence}</span>
                        <button type="button" class="kf-btn kf-btn-secondary kf-btn-sm btn-move-up" data-index="${index}" ${index === 0 ? 'disabled' : ''} aria-label="${sequence}. aşamayı yukarı taşı" style="padding: 0.25rem 0.5rem;">↑</button>
                        <button type="button" class="kf-btn kf-btn-secondary kf-btn-sm btn-move-down" data-index="${index}" ${index === initialStages.length - 1 ? 'disabled' : ''} aria-label="${sequence}. aşamayı aşağı taşı" style="padding: 0.25rem 0.5rem;">↓</button>
                    </div>

                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="stage_${index}_code" class="kf-form-label">Kod <span class="text-danger">*</span></label>
                                <input type="text" id="stage_${index}_code" name="stages[${index}][code]" class="kf-form-control" value="${escCode}" required aria-required="true">
                            </div>
                            <div class="col-md-8">
                                <label for="stage_${index}_name" class="kf-form-label">Ad <span class="text-danger">*</span></label>
                                <input type="text" id="stage_${index}_name" name="stages[${index}][name]" class="kf-form-control" value="${escName}" required aria-required="true">
                            </div>
                            <div class="col-md-12">
                                <label for="stage_${index}_description" class="kf-form-label">Açıklama</label>
                                <input type="text" id="stage_${index}_description" name="stages[${index}][description]" class="kf-form-control" value="${escDesc}">
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mt-3 gap-3">
                            <div class="form-check">
                                <input type="checkbox" id="stage_${index}_is_final" name="stages[${index}][is_final]" value="1" class="form-check-input final-checkbox" data-index="${index}" ${isFinalChecked}>
                                <label class="form-check-label" for="stage_${index}_is_final">Final Aşaması (Yalnızca 1 tane olabilir)</label>
                            </div>
                            <button type="button" class="kf-btn kf-btn-danger kf-btn-sm btn-remove" data-index="${index}" ${initialStages.length === 1 ? 'disabled' : ''} aria-label="${sequence}. aşamayı sil">Sil</button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });

        attachListeners();
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
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
        // Read current DOM values into initialStages array before re-rendering
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

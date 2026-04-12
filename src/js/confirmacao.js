/* confirmacao.js */

(function () {
    const form      = document.getElementById('form-confirmacao');
    const alertBox  = document.getElementById('conf-alert');
    const btnSubmit = document.getElementById('btn-confirmar');
    const BASE_URL  = document.querySelector('meta[name="base-url"]')?.content ?? '';

    // Força uppercase no campo de código enquanto digita
    document.getElementById('conf-codigo')?.addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });

    function showAlert(type, msg) {
        alertBox.innerHTML = `<div class="${type === 'success' ? 'alert-success-custom' : 'alert-error-custom'} mb-4">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${msg}
        </div>`;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function validate() {
        let ok = true;

        const nome   = document.getElementById('conf-nome');
        const codigo = document.getElementById('conf-codigo');
        const errNome   = document.getElementById('err-nome');
        const errCodigo = document.getElementById('err-codigo');

        if (!nome.value.trim()) {
            nome.classList.add('is-invalid');
            errNome.textContent = 'Nome é obrigatório.';
            ok = false;
        } else {
            nome.classList.remove('is-invalid');
            errNome.textContent = '';
        }

        if (!codigo.value.trim()) {
            codigo.classList.add('is-invalid');
            errCodigo.textContent = 'Código do convite é obrigatório.';
            ok = false;
        } else {
            codigo.classList.remove('is-invalid');
            errCodigo.textContent = '';
        }

        return ok;
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validate()) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';

        const payload = {
            nome:   document.getElementById('conf-nome').value.trim(),
            codigo: document.getElementById('conf-codigo').value.trim().toUpperCase(),
        };

        try {
            const resp = await fetch(BASE_URL + '/api/confirmar.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const data = await resp.json();

            if (data.success) {
                showAlert('success', data.message);
                form.style.display = 'none';
                // Atualiza contador se existir
                if (data.total_conf !== undefined) {
                    const numEl = document.querySelector('.conf-counter-num');
                    if (numEl) numEl.textContent = data.total_conf;
                    document.querySelector('.conf-counter')?.classList.remove('d-none', 'hidden');
                }
            } else {
                showAlert('error', data.message);
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><span>Confirmar minha presença</span>';
            }
        } catch {
            showAlert('error', 'Erro de conexão. Tente novamente.');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><span>Confirmar minha presença</span>';
        }
    });
})();

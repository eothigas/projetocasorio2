/* confirmacao.js */

(function () {
    const form      = document.getElementById('form-confirmacao');
    const alertBox  = document.getElementById('conf-alert');
    const btnSubmit = document.getElementById('btn-confirmar');
    const BASE_URL  = document.querySelector('meta[name="base-url"]')?.content ?? '';

    function showAlert(type, msg) {
        alertBox.innerHTML = `<div class="${type === 'success' ? 'alert-success-custom' : 'alert-error-custom'} mb-4">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${msg}
        </div>`;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function validate() {
        const nome    = document.getElementById('conf-nome');
        const errNome = document.getElementById('err-nome');

        if (!nome.value.trim()) {
            nome.classList.add('is-invalid');
            errNome.textContent = 'Nome é obrigatório.';
            return false;
        }

        nome.classList.remove('is-invalid');
        errNome.textContent = '';
        return true;
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validate()) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';

        const payload = {
            nome: document.getElementById('conf-nome').value.trim(),
        };

        try {
            const resp = await fetch(BASE_URL + '/api/confirmar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const data = await resp.json();

            if (data.success) {
                showAlert('success', data.message);
                form.style.display = 'none';
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

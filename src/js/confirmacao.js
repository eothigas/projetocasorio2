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
        let ok = true;
        const nome = document.getElementById('conf-nome');
        const err  = document.getElementById('err-nome');

        if (!nome.value.trim()) {
            nome.classList.add('is-invalid');
            err.textContent = 'Nome é obrigatório.';
            ok = false;
        } else {
            nome.classList.remove('is-invalid');
            err.textContent = '';
        }
        return ok;
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validate()) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        const payload = {
            nome:          document.getElementById('conf-nome').value.trim(),
            email:         document.getElementById('conf-email').value.trim(),
            telefone:      document.getElementById('conf-telefone').value.trim(),
            acompanhantes: document.getElementById('conf-acompanhantes').value,
            restricoes:    document.getElementById('conf-restricoes').value.trim(),
            mensagem:      document.getElementById('conf-mensagem').value.trim(),
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
                form.reset();
                // Atualiza o contador na página
                if (data.total_pax !== undefined) {
                    const numEl = document.querySelector('.conf-counter-num');
                    const lblEl = document.querySelector('.conf-counter-label');
                    if (numEl) numEl.textContent = data.total_pax;
                    if (lblEl) lblEl.textContent = `pessoa${data.total_pax !== 1 ? 's' : ''} confirmada${data.total_pax !== 1 ? 's' : ''} até agora`;
                    document.querySelector('.conf-counter')?.classList.remove('d-none');
                }
            } else {
                showAlert('error', data.message);
            }
        } catch {
            showAlert('error', 'Erro de conexão. Tente novamente.');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><span>Confirmar minha presença</span>';
        }
    });
})();

/* mensagens.js */

(function () {
    const form      = document.getElementById('form-mensagem');
    const alertBox  = document.getElementById('msg-alert');
    const btnSubmit = document.getElementById('btn-enviar');
    const charCount = document.getElementById('char-count');
    const textarea  = document.getElementById('msg-texto');
    const BASE_URL  = document.querySelector('meta[name="base-url"]')?.content ?? '';

    // Contador de caracteres
    textarea?.addEventListener('input', () => {
        if (charCount) charCount.textContent = textarea.value.length;
    });

    function showAlert(type, msg) {
        alertBox.innerHTML = `<div class="${type === 'success' ? 'alert-success-custom' : 'alert-error-custom'} mb-4 mt-0">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${msg}
        </div>`;
    }

    function validate() {
        let ok = true;

        const nome  = document.getElementById('msg-nome');
        const texto = document.getElementById('msg-texto');
        const errN  = document.getElementById('err-msg-nome');
        const errT  = document.getElementById('err-msg-texto');

        if (!nome.value.trim()) {
            nome.classList.add('is-invalid');
            errN.textContent = 'Nome é obrigatório.';
            ok = false;
        } else {
            nome.classList.remove('is-invalid');
            errN.textContent = '';
        }

        if (!texto.value.trim()) {
            texto.classList.add('is-invalid');
            errT.textContent = 'A mensagem não pode ser vazia.';
            ok = false;
        } else {
            texto.classList.remove('is-invalid');
            errT.textContent = '';
        }

        return ok;
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validate()) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        const payload = {
            nome:     document.getElementById('msg-nome').value.trim(),
            mensagem: document.getElementById('msg-texto').value.trim(),
        };

        try {
            const resp = await fetch(BASE_URL + '/api/mensagem', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const data = await resp.json();

            if (data.success) {
                showAlert('success', data.message);
                form.reset();
                if (charCount) charCount.textContent = '0';
            } else {
                showAlert('error', data.message);
            }
        } catch {
            showAlert('error', 'Erro de conexão. Tente novamente.');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-send-heart-fill me-1"></i><span>Enviar mensagem</span>';
        }
    });
})();

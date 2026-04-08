/* presentes.js */

(function () {
    const modal        = new bootstrap.Modal(document.getElementById('modalPresente'));
    const btnEscolher  = document.querySelectorAll('.btn-escolher');
    const presenteId   = document.getElementById('presente-id');
    const presenteNome = document.getElementById('presente-nome');
    const modalName    = document.getElementById('modal-present-name');
    const modalAlert   = document.getElementById('modal-alert');
    const btnConfirmar = document.getElementById('btn-confirmar-presente');

    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content ?? '';

    btnEscolher.forEach(btn => {
        btn.addEventListener('click', () => {
            presenteId.value        = btn.dataset.id;
            modalName.textContent   = btn.dataset.nome;
            presenteNome.value      = '';
            modalAlert.innerHTML    = '';
            modal.show();
        });
    });

    btnConfirmar?.addEventListener('click', async () => {
        const nome = presenteNome.value.trim();
        if (!nome) {
            presenteNome.classList.add('is-invalid');
            return;
        }
        presenteNome.classList.remove('is-invalid');

        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Aguarde...';

        try {
            const resp = await fetch(BASE_URL + '/api/presente.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ id: presenteId.value, nome }),
            });
            const data = await resp.json();

            if (data.success) {
                modalAlert.innerHTML = `<div class="alert-success-custom mt-2"><i class="bi bi-check-circle-fill me-2"></i>${data.message}</div>`;
                setTimeout(() => { modal.hide(); location.reload(); }, 1800);
            } else {
                modalAlert.innerHTML = `<div class="alert-error-custom mt-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>${data.message}</div>`;
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="bi bi-heart-fill me-1"></i>Confirmar';
            }
        } catch {
            modalAlert.innerHTML = '<div class="alert-error-custom mt-2">Erro de conexão. Tente novamente.</div>';
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="bi bi-heart-fill me-1"></i>Confirmar';
        }
    });
})();

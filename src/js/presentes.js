/* presentes.js */

(function () {
    const modal         = new bootstrap.Modal(document.getElementById('modalPresente'));
    const btnEscolher    = document.querySelectorAll('.btn-escolher');
    const btnVisitarLoja = document.querySelectorAll('.btn-visitar-loja');
    const btnConfirmarManualAbrir = document.querySelectorAll('.btn-confirmar-manual');
    const presenteId      = document.getElementById('presente-id');
    const presenteNome    = document.getElementById('presente-nome');
    const presenteEmail   = document.getElementById('presente-email');
    const presenteValor   = document.getElementById('presente-valor');
    const grupoValor      = document.getElementById('grupo-valor');
    const modalName       = document.getElementById('modal-present-name');
    const modalAlert       = document.getElementById('modal-alert');
    const btnConfirmar     = document.getElementById('btn-confirmar-presente');
    const formPresente     = document.getElementById('form-presente');
    const pixResultado     = document.getElementById('pix-resultado');
    const pixQrImg         = document.getElementById('pix-qr-img');
    const pixCopiaCola     = document.getElementById('pix-copia-cola');
    const btnCopiarPix     = document.getElementById('btn-copiar-pix');
    const pixStatusText    = document.getElementById('pix-status-text');

    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content ?? '';

    let pollTimer = null;

    function pararPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    function iniciarPolling(externalReference) {
        pararPolling();
        pollTimer = setInterval(async () => {
            try {
                const resp = await fetch(BASE_URL + '/api/status-reserva?ref=' + encodeURIComponent(externalReference));
                const data = await resp.json();
                if (data.success && data.status === 'pago') {
                    pararPolling();
                    pixStatusText.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>Pagamento confirmado! Obrigado 💙';
                    setTimeout(() => { modal.hide(); location.reload(); }, 2200);
                } else if (data.success && (data.status === 'expirado' || data.status === 'cancelado')) {
                    pararPolling();
                    pixStatusText.textContent = 'Este Pix expirou. Feche e tente novamente.';
                }
            } catch { /* silencioso, tenta de novo no próximo ciclo */ }
        }, 4000);
    }

    modal._element.addEventListener('hidden.bs.modal', pararPolling);

    btnEscolher.forEach(btn => {
        btn.addEventListener('click', () => {
            presenteId.value      = btn.dataset.id;
            modalName.textContent = btn.dataset.nome;
            presenteNome.value    = '';
            presenteEmail.value   = '';
            modalAlert.innerHTML  = '';

            if (btn.dataset.tipo === 'cota') {
                grupoValor.style.display = '';
                presenteValor.value = btn.dataset.restante ?? '';
            } else {
                grupoValor.style.display = 'none';
                presenteValor.value = '';
            }

            formPresente.style.display = '';
            pixResultado.style.display = 'none';
            btnConfirmar.style.display = '';
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="bi bi-heart-fill me-1"></i>Gerar Pix';

            modal.show();
        });
    });

    btnConfirmar?.addEventListener('click', async () => {
        const nome  = presenteNome.value.trim();
        const email = presenteEmail.value.trim();
        const valor = presenteValor.value ? parseFloat(presenteValor.value.replace(',', '.')) : undefined;

        if (!nome) {
            presenteNome.classList.add('is-invalid');
            return;
        }
        presenteNome.classList.remove('is-invalid');

        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando Pix...';

        try {
            const resp = await fetch(BASE_URL + '/api/gerar-pix', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    presente_id: presenteId.value,
                    nome,
                    email,
                    valor,
                }),
            });
            const data = await resp.json();

            if (data.success) {
                pixQrImg.src            = 'data:image/png;base64,' + data.pix_qr_code_base64;
                pixCopiaCola.value      = data.pix_qr_code || '';
                formPresente.style.display = 'none';
                pixResultado.style.display = 'block';
                btnConfirmar.style.display = 'none';
                pixStatusText.textContent  = 'Aguardando pagamento…';
                iniciarPolling(data.external_reference);
            } else {
                modalAlert.innerHTML = `<div class="alert-error-custom mt-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>${data.message}</div>`;
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="bi bi-heart-fill me-1"></i>Gerar Pix';
            }
        } catch {
            modalAlert.innerHTML = '<div class="alert-error-custom mt-2">Erro de conexão. Tente novamente.</div>';
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="bi bi-heart-fill me-1"></i>Gerar Pix';
        }
    });

    btnCopiarPix?.addEventListener('click', () => {
        pixCopiaCola.select();
        navigator.clipboard?.writeText(pixCopiaCola.value);
        btnCopiarPix.innerHTML = '<i class="bi bi-check2"></i> Copiado!';
        setTimeout(() => { btnCopiarPix.innerHTML = '<i class="bi bi-clipboard"></i> Copiar código'; }, 1800);
    });

    async function confirmarPresente(endpoint, presenteId, nome, email, metodo) {
        const resp = await fetch(BASE_URL + endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ presente_id: presenteId, nome, email, metodo }),
        });
        return resp.json();
    }

    // ---------- Guia: visitar loja ----------
    const modalGuiaEl      = document.getElementById('modalGuiaLoja');
    const modalGuia        = new bootstrap.Modal(modalGuiaEl);
    const guiaPresentName  = document.getElementById('guia-present-name');
    const guiaAlert        = document.getElementById('guia-alert');
    const guiaPassoLink    = document.getElementById('guia-passo-link');
    const formGuiaConfirmar = document.getElementById('form-guia-confirmar');
    const guiaPresenteId   = document.getElementById('guia-presente-id');
    const guiaNome         = document.getElementById('guia-nome');
    const guiaEmail        = document.getElementById('guia-email');
    const guiaLinkLoja     = document.getElementById('guia-link-loja');
    const btnGuiaIrConfirmar = document.getElementById('btn-guia-ir-confirmar');
    const btnGuiaConfirmar   = document.getElementById('btn-guia-confirmar');

    btnVisitarLoja.forEach(btn => {
        btn.addEventListener('click', () => {
            guiaPresenteId.value       = btn.dataset.id;
            guiaPresentName.textContent = btn.dataset.nome;
            guiaLinkLoja.href          = btn.dataset.link;
            guiaNome.value             = '';
            guiaEmail.value            = '';
            guiaAlert.innerHTML        = '';

            guiaPassoLink.style.display    = '';
            formGuiaConfirmar.style.display = 'none';
            btnGuiaIrConfirmar.style.display = '';
            btnGuiaConfirmar.style.display   = 'none';
            btnGuiaConfirmar.disabled = false;
            btnGuiaConfirmar.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar presente';

            modalGuia.show();
        });
    });

    btnGuiaIrConfirmar?.addEventListener('click', () => {
        guiaPassoLink.style.display     = 'none';
        formGuiaConfirmar.style.display = '';
        btnGuiaIrConfirmar.style.display = 'none';
        btnGuiaConfirmar.style.display   = '';
    });

    btnGuiaConfirmar?.addEventListener('click', async () => {
        const nome  = guiaNome.value.trim();
        const email = guiaEmail.value.trim();

        if (!nome) {
            guiaNome.classList.add('is-invalid');
            return;
        }
        guiaNome.classList.remove('is-invalid');

        btnGuiaConfirmar.disabled = true;
        btnGuiaConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Confirmando...';

        try {
            const data = await confirmarPresente('/api/confirmar-manual', guiaPresenteId.value, nome, email, 'loja');
            if (data.success) {
                btnGuiaConfirmar.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Confirmado! Obrigado 💙';
                setTimeout(() => { modalGuia.hide(); location.reload(); }, 1800);
            } else {
                guiaAlert.innerHTML = `<div class="alert-error-custom mt-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>${data.message}</div>`;
                btnGuiaConfirmar.disabled = false;
                btnGuiaConfirmar.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar presente';
            }
        } catch {
            guiaAlert.innerHTML = '<div class="alert-error-custom mt-2">Erro de conexão. Tente novamente.</div>';
            btnGuiaConfirmar.disabled = false;
            btnGuiaConfirmar.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar presente';
        }
    });

    // ---------- Confirmar presente sem Pix/loja ----------
    const modalManualEl     = document.getElementById('modalConfirmarManual');
    const modalManual       = new bootstrap.Modal(modalManualEl);
    const manualPresentName = document.getElementById('manual-present-name');
    const manualAlert       = document.getElementById('manual-alert');
    const manualPresenteId  = document.getElementById('manual-presente-id');
    const manualNome        = document.getElementById('manual-nome');
    const manualEmail       = document.getElementById('manual-email');
    const btnManualConfirmar = document.getElementById('btn-manual-confirmar');

    btnConfirmarManualAbrir.forEach(btn => {
        btn.addEventListener('click', () => {
            manualPresenteId.value       = btn.dataset.id;
            manualPresentName.textContent = btn.dataset.nome;
            manualNome.value  = '';
            manualEmail.value = '';
            manualAlert.innerHTML = '';
            btnManualConfirmar.disabled = false;
            btnManualConfirmar.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar presente';
            modalManual.show();
        });
    });

    btnManualConfirmar?.addEventListener('click', async () => {
        const nome  = manualNome.value.trim();
        const email = manualEmail.value.trim();

        if (!nome) {
            manualNome.classList.add('is-invalid');
            return;
        }
        manualNome.classList.remove('is-invalid');

        btnManualConfirmar.disabled = true;
        btnManualConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Confirmando...';

        try {
            const data = await confirmarPresente('/api/confirmar-manual', manualPresenteId.value, nome, email, 'manual');
            if (data.success) {
                btnManualConfirmar.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Confirmado! Obrigado 💙';
                setTimeout(() => { modalManual.hide(); location.reload(); }, 1800);
            } else {
                manualAlert.innerHTML = `<div class="alert-error-custom mt-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>${data.message}</div>`;
                btnManualConfirmar.disabled = false;
                btnManualConfirmar.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar presente';
            }
        } catch {
            manualAlert.innerHTML = '<div class="alert-error-custom mt-2">Erro de conexão. Tente novamente.</div>';
            btnManualConfirmar.disabled = false;
            btnManualConfirmar.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Confirmar presente';
        }
    });
})();

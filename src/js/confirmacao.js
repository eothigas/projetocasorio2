/* confirmacao.js */

(function () {
    const form      = document.getElementById('form-confirmacao');
    const alertBox  = document.getElementById('conf-alert');
    const btnSubmit = document.getElementById('btn-confirmar');
    const grupoBox  = document.getElementById('conf-grupo');
    const emailInput = document.getElementById('conf-email');
    const BASE_URL  = document.querySelector('meta[name="base-url"]')?.content ?? '';

    function showAlert(type, msg) {
        alertBox.innerHTML = `<div class="${type === 'success' ? 'alert-success-custom' : 'alert-error-custom'} mb-4">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} me-2"></i>${msg}
        </div>`;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function resetBtnBusca() {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><span>Confirmar minha presença</span>';
    }

    function validate() {
        const nome     = document.getElementById('conf-nome');
        const errNome  = document.getElementById('err-nome');
        const email    = document.getElementById('conf-email');
        const errEmail = document.getElementById('err-email');
        let valido = true;

        if (!nome.value.trim()) {
            nome.classList.add('is-invalid');
            errNome.textContent = 'Nome é obrigatório.';
            valido = false;
        } else {
            nome.classList.remove('is-invalid');
            errNome.textContent = '';
        }

        const emailValor = email.value.trim();
        if (!emailValor) {
            email.classList.add('is-invalid');
            errEmail.textContent = 'E-mail é obrigatório.';
            valido = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValor)) {
            email.classList.add('is-invalid');
            errEmail.textContent = 'Digite um e-mail válido.';
            valido = false;
        } else {
            email.classList.remove('is-invalid');
            errEmail.textContent = '';
        }

        return valido;
    }

    function renderGrupo(grupoId, membros) {
        const itens = membros.map((m) => `
            <div class="conf-membro-item" data-id="${m.id}" data-vai="${m.responsavel ? '1' : ''}">
                <span class="conf-membro-nome">${m.nome}${m.responsavel ? ' <em>(você)</em>' : ''}</span>
                <div class="conf-membro-opcoes">
                    <button type="button" class="conf-opt conf-opt-sim${m.responsavel ? ' active' : ''}" data-val="1">Sim</button>
                    <button type="button" class="conf-opt conf-opt-nao" data-val="0">Não</button>
                </div>
            </div>
        `).join('');

        grupoBox.innerHTML = `
            <p class="conf-grupo-hint">Marque quem vai comparecer:</p>
            <div class="conf-membros-lista">${itens}</div>
            <button type="button" class="btn-primary-custom conf-submit" id="btn-confirmar-grupo">
                <i class="bi bi-check-circle-fill"></i>
                <span>Confirmar presenças</span>
            </button>
        `;
        grupoBox.classList.remove('hidden', 'd-none');
        grupoBox.dataset.grupoId = grupoId;

        grupoBox.querySelectorAll('.conf-opt').forEach((btn) => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.conf-membro-item');
                item.dataset.vai = btn.dataset.val;
                item.querySelectorAll('.conf-opt').forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        document.getElementById('btn-confirmar-grupo').addEventListener('click', enviarConfirmacao);
    }

    async function enviarConfirmacao() {
        const btn    = document.getElementById('btn-confirmar-grupo');
        const grupoId = grupoBox.dataset.grupoId;
        const itens  = Array.from(grupoBox.querySelectorAll('.conf-membro-item'));

        const semResposta = itens.some((el) => el.dataset.vai === '');
        if (semResposta) {
            showAlert('error', 'Responda Sim ou Não para todos os integrantes do grupo.');
            return;
        }

        const respostas = itens.map((el) => ({
            id:  parseInt(el.dataset.id, 10),
            vai: el.dataset.vai === '1',
        }));

        const email = emailInput?.value.trim() ?? '';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        try {
            const resp = await fetch(BASE_URL + '/api/confirmar', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ acao: 'confirmar', grupo_id: parseInt(grupoId, 10), respostas, email }),
            });
            const data = await resp.json();

            if (data.success) {
                showAlert('success', `${data.message} (${data.conf_grupo} de ${data.total_grupo} confirmado(s) no seu grupo)`);
                grupoBox.style.display = 'none';
                if (data.total_conf !== undefined) {
                    const numEl = document.querySelector('.conf-counter-num');
                    if (numEl) numEl.textContent = data.total_conf;
                    document.querySelector('.conf-counter')?.classList.remove('d-none', 'hidden');
                }
            } else {
                showAlert('error', data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><span>Confirmar presenças</span>';
            }
        } catch {
            showAlert('error', 'Erro de conexão. Tente novamente.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i><span>Confirmar presenças</span>';
        }
    }

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validate()) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';

        const payload = {
            acao: 'buscar',
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
                form.style.display = 'none';
                renderGrupo(data.grupo_id, data.membros);
            } else {
                showAlert('error', data.message);
                resetBtnBusca();
            }
        } catch {
            showAlert('error', 'Erro de conexão. Tente novamente.');
            resetBtnBusca();
        }
    });
})();

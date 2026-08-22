<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$acao = $body['acao'] ?? 'buscar';

try {
    $db = getDB();

    // Verifica se as confirmações estão abertas
    $aberta = $db->query("SELECT valor FROM configuracoes WHERE chave = 'confirmacao_aberta'")->fetchColumn();
    if ($aberta !== '1') {
        echo json_encode(['success' => false, 'message' => 'As confirmações ainda não estão abertas. Aguarde a divulgação dos noivos.']);
        exit;
    }

    if ($acao === 'buscar') {
        $nome = trim(strip_tags($body['nome'] ?? ''));

        if (strlen($nome) < 2) {
            echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome.']);
            exit;
        }

        // Busca convidado pelo nome (tolerante a capitalização e espaços extras)
        $nomeNormalizado = mb_strtolower(preg_replace('/\s+/', ' ', $nome));

        $stmt = $db->query("SELECT id, nome, grupo_id FROM convidados");
        $convidado = null;
        foreach ($stmt->fetchAll() as $c) {
            $nomeDB = mb_strtolower(preg_replace('/\s+/', ' ', $c['nome']));
            if ($nomeDB === $nomeNormalizado) {
                $convidado = $c;
                break;
            }
        }

        if (!$convidado) {
            echo json_encode(['success' => false, 'message' => 'Nome não encontrado na lista de convidados. Verifique e tente novamente.']);
            exit;
        }

        $grupo = $db->prepare("SELECT id, nome_grupo, respondido FROM grupos WHERE id = ?");
        $grupo->execute([$convidado['grupo_id']]);
        $grupo = $grupo->fetch();

        if ($grupo['respondido']) {
            echo json_encode(['success' => false, 'message' => 'A presença do seu grupo já foi confirmada anteriormente. Até lá! 💙']);
            exit;
        }

        $membrosStmt = $db->prepare(
            "SELECT id, nome, responsavel FROM convidados WHERE grupo_id = ? ORDER BY responsavel DESC, id ASC"
        );
        $membrosStmt->execute([$grupo['id']]);

        echo json_encode([
            'success'    => true,
            'grupo_id'   => (int) $grupo['id'],
            'nome_grupo' => $grupo['nome_grupo'],
            'membros'    => array_map(fn($m) => [
                'id'          => (int) $m['id'],
                'nome'        => $m['nome'],
                'responsavel' => (bool) $m['responsavel'],
            ], $membrosStmt->fetchAll()),
        ]);
        exit;
    }

    if ($acao === 'confirmar') {
        $grupoId   = (int) ($body['grupo_id'] ?? 0);
        $respostas = $body['respostas'] ?? [];
        $email     = trim(strip_tags($body['email'] ?? ''));

        if ($grupoId <= 0 || !is_array($respostas) || empty($respostas)) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            exit;
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Informe um e-mail válido para confirmar.']);
            exit;
        }

        $grupoStmt = $db->prepare("SELECT id, respondido FROM grupos WHERE id = ?");
        $grupoStmt->execute([$grupoId]);
        $grupo = $grupoStmt->fetch();

        if (!$grupo) {
            echo json_encode(['success' => false, 'message' => 'Grupo não encontrado.']);
            exit;
        }

        if ($grupo['respondido']) {
            echo json_encode(['success' => false, 'message' => 'A presença do seu grupo já foi confirmada anteriormente. Até lá! 💙']);
            exit;
        }

        $membrosStmt = $db->prepare("SELECT id FROM convidados WHERE grupo_id = ?");
        $membrosStmt->execute([$grupoId]);
        $idsGrupo = array_map('intval', array_column($membrosStmt->fetchAll(), 'id'));

        $idsRespostas = array_map(fn($r) => (int) ($r['id'] ?? 0), $respostas);
        sort($idsGrupo);
        sort($idsRespostas);

        if ($idsGrupo !== $idsRespostas) {
            echo json_encode(['success' => false, 'message' => 'É necessário responder por todos os integrantes do grupo.']);
            exit;
        }

        $vaiMap = [];
        $update = $db->prepare("UPDATE convidados SET confirmado = ?, confirmado_em = ? WHERE id = ? AND grupo_id = ?");
        foreach ($respostas as $r) {
            $id  = (int) ($r['id'] ?? 0);
            $vai = !empty($r['vai']);
            $vaiMap[$id] = $vai;
            $update->execute([$vai ? 1 : 0, $vai ? date('Y-m-d H:i:s') : null, $id, $grupoId]);
        }

        $grupoNomeStmt = $db->prepare("SELECT nome_grupo FROM grupos WHERE id = ?");
        $grupoNomeStmt->execute([$grupoId]);
        $nomeGrupo = $grupoNomeStmt->fetchColumn();

        $db->prepare("UPDATE grupos SET respondido = 1, respondido_em = NOW(), email = ? WHERE id = ?")
           ->execute([$email !== '' ? $email : null, $grupoId]);

        $membrosNomes = $db->prepare("SELECT id, nome, responsavel FROM convidados WHERE grupo_id = ? ORDER BY responsavel DESC, id ASC");
        $membrosNomes->execute([$grupoId]);
        $membros = array_map(fn($m) => [
            'nome' => $m['nome'],
            'vai'  => $vaiMap[(int) $m['id']] ?? false,
        ], $membrosNomes->fetchAll());

        $nomeResponsavel = $membros[0]['nome'] ?? '';

        if ($email !== '') {
            enviarEmailConfirmacaoPresenca($email, $nomeResponsavel, $nomeGrupo, $membros);
        }
        enviarEmailNotificacaoConfirmacaoNoivos($nomeGrupo, $email, $membros);

        $totalConf = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
        $confGrupo = count(array_filter($respostas, fn($r) => !empty($r['vai'])));

        echo json_encode([
            'success'     => true,
            'message'     => 'Presença confirmada com sucesso! Mal podemos esperar para celebrar com você. 💙',
            'total_conf'  => $totalConf,
            'conf_grupo'  => $confGrupo,
            'total_grupo' => count($respostas),
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente em instantes.']);
}

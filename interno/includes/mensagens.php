<?php
/**
 * Mensagens entre contas: qualquer funcionário (data/admins.php) pode
 * escrever a outro funcionário ou a uma escola; uma escola só pode escrever
 * a funcionários. Espera db() (includes/db.php) e MENSAGEM_CATEGORIA_OPCOES
 * (includes/alunos-schema.php) já carregados pelo ficheiro que inclui este.
 */

/** Quem está autenticado nesta sessão, como {tipo, id, nome} — ou null. */
function mensagensIdentidadeAtual(): ?array
{
    if (!empty($_SESSION['admin_user'])) {
        $admins = require __DIR__ . '/../../data/admins.php';
        $chave = $_SESSION['admin_user'];
        return isset($admins[$chave])
            ? ['tipo' => 'staff', 'id' => $chave, 'nome' => $admins[$chave]['nome']]
            : null;
    }

    if (!empty($_SESSION['escola_user'])) {
        $escolas = require __DIR__ . '/../../data/escolas.php';
        $chave = $_SESSION['escola_user'];
        return isset($escolas[$chave])
            ? ['tipo' => 'escola', 'id' => $chave, 'nome' => $escolas[$chave]['nome']]
            : null;
    }

    return null;
}

/**
 * Contactos com quem $identidade pode falar: um funcionário pode escrever a
 * outros funcionários e a qualquer escola; uma escola só pode escrever a
 * funcionários (nunca a outra escola).
 */
function mensagensContactosPermitidos(array $identidade): array
{
    $admins = require __DIR__ . '/../../data/admins.php';
    $contactos = [];

    foreach ($admins as $chave => $dados) {
        if ($identidade['tipo'] === 'staff' && $chave === $identidade['id']) {
            continue;
        }
        $contactos[] = ['tipo' => 'staff', 'id' => $chave, 'nome' => $dados['nome'], 'localidade' => null];
    }

    if ($identidade['tipo'] === 'staff') {
        $escolas = require __DIR__ . '/../../data/escolas.php';
        foreach ($escolas as $slug => $dados) {
            $contactos[] = ['tipo' => 'escola', 'id' => $slug, 'nome' => $dados['nome'], 'localidade' => $dados['localidade']];
        }
    }

    usort($contactos, fn ($a, $b) => strcasecmp($a['nome'], $b['nome']));

    return $contactos;
}

/** Procura um contacto permitido por (tipo,id); null se não for permitido. */
function mensagensContacto(array $identidade, string $tipo, string $id): ?array
{
    foreach (mensagensContactosPermitidos($identidade) as $contacto) {
        if ($contacto['tipo'] === $tipo && $contacto['id'] === $id) {
            return $contacto;
        }
    }

    return null;
}

/**
 * Lista de contactos permitidos, com a última mensagem trocada e a contagem
 * de não lidas — para a caixa de entrada. Ordenada pela conversa mais
 * recente primeiro; contactos sem histórico ficam no fim, por nome.
 */
function mensagensListarContactosComPreview(array $identidade): array
{
    $porChave = [];
    foreach (mensagensContactosPermitidos($identidade) as $contacto) {
        $porChave[$contacto['tipo'] . ':' . $contacto['id']] = $contacto + [
            'ultima_corpo' => null,
            'ultima_data' => null,
            'ultima_de_mim' => null,
            'por_ler' => 0,
        ];
    }

    $stmt = db()->prepare('
        SELECT remetente_tipo, remetente_id, destinatario_tipo, destinatario_id, corpo, created_at, lida
        FROM mensagens
        WHERE (remetente_tipo = :t1 AND remetente_id = :i1)
           OR (destinatario_tipo = :t2 AND destinatario_id = :i2)
        ORDER BY id DESC
    ');
    $stmt->execute(['t1' => $identidade['tipo'], 'i1' => $identidade['id'], 't2' => $identidade['tipo'], 'i2' => $identidade['id']]);

    foreach ($stmt->fetchAll() as $linha) {
        $deMim = $linha['remetente_tipo'] === $identidade['tipo'] && $linha['remetente_id'] === $identidade['id'];
        $tipoContacto = $deMim ? $linha['destinatario_tipo'] : $linha['remetente_tipo'];
        $idContacto = $deMim ? $linha['destinatario_id'] : $linha['remetente_id'];
        $chave = $tipoContacto . ':' . $idContacto;

        if (!isset($porChave[$chave])) {
            continue;
        }

        if ($porChave[$chave]['ultima_corpo'] === null) {
            $porChave[$chave]['ultima_corpo'] = $linha['corpo'];
            $porChave[$chave]['ultima_data'] = $linha['created_at'];
            $porChave[$chave]['ultima_de_mim'] = $deMim;
        }

        if (!$deMim && !(int) $linha['lida']) {
            $porChave[$chave]['por_ler']++;
        }
    }

    $contactos = array_values($porChave);
    usort($contactos, function ($a, $b) {
        if ($a['ultima_data'] === null && $b['ultima_data'] === null) {
            return strcasecmp($a['nome'], $b['nome']);
        }
        if ($a['ultima_data'] === null) {
            return 1;
        }
        if ($b['ultima_data'] === null) {
            return -1;
        }
        return strcmp($b['ultima_data'], $a['ultima_data']);
    });

    return $contactos;
}

/**
 * Histórico completo com um contacto (null se o contacto não for permitido).
 * Como efeito secundário, marca como lidas as mensagens desse contacto
 * dirigidas a mim.
 */
function mensagensThread(array $identidade, string $tipo, string $id): ?array
{
    $contacto = mensagensContacto($identidade, $tipo, $id);

    if ($contacto === null) {
        return null;
    }

    $stmt = db()->prepare('
        SELECT id, remetente_tipo, remetente_id, categoria, autor, corpo, created_at
        FROM mensagens
        WHERE (remetente_tipo = :t1 AND remetente_id = :i1 AND destinatario_tipo = :t2 AND destinatario_id = :i2)
           OR (remetente_tipo = :t2b AND remetente_id = :i2b AND destinatario_tipo = :t1b AND destinatario_id = :i1b)
        ORDER BY id
    ');
    $stmt->execute([
        't1' => $identidade['tipo'], 'i1' => $identidade['id'], 't2' => $tipo, 'i2' => $id,
        't2b' => $tipo, 'i2b' => $id, 't1b' => $identidade['tipo'], 'i1b' => $identidade['id'],
    ]);

    $mensagens = array_map(fn ($linha) => [
        'id' => (int) $linha['id'],
        'de_mim' => $linha['remetente_tipo'] === $identidade['tipo'] && $linha['remetente_id'] === $identidade['id'],
        'autor' => $linha['autor'],
        'categoria' => $linha['categoria'],
        'corpo' => $linha['corpo'],
        'created_at' => $linha['created_at'],
    ], $stmt->fetchAll());

    $stmt = db()->prepare("
        UPDATE mensagens SET lida = 1, lida_em = datetime('now')
        WHERE destinatario_tipo = :meT AND destinatario_id = :meI
          AND remetente_tipo = :cT AND remetente_id = :cI
          AND lida = 0
    ");
    $stmt->execute(['meT' => $identidade['tipo'], 'meI' => $identidade['id'], 'cT' => $tipo, 'cI' => $id]);

    return ['contacto' => $contacto, 'mensagens' => $mensagens];
}

/** Envia uma mensagem de $identidade para (tipo,id), validando tudo no servidor. */
function mensagensEnviar(array $identidade, string $tipo, string $id, string $categoria, string $corpo): array
{
    $corpo = trim($corpo);

    if ($corpo === '') {
        return ['ok' => false, 'erro' => 'A mensagem não pode ficar vazia.'];
    }

    // Limite em bytes: evita depender da extensão mbstring, que não está garantida.
    if (strlen($corpo) > 4000) {
        return ['ok' => false, 'erro' => 'A mensagem é demasiado longa (máx. 2000 caracteres).'];
    }

    if (!in_array($categoria, MENSAGEM_CATEGORIA_OPCOES, true)) {
        $categoria = MENSAGEM_CATEGORIA_OPCOES[0];
    }

    $contacto = mensagensContacto($identidade, $tipo, $id);

    if ($contacto === null) {
        return ['ok' => false, 'erro' => 'Destinatário inválido.'];
    }

    if ($identidade['tipo'] === 'escola') {
        $escolaSlug = $identidade['id'];
        $origem = 'escola';
    } elseif ($tipo === 'escola') {
        $escolaSlug = $id;
        $origem = 'direcao';
    } else {
        // Funcionário a funcionário: não há nenhuma escola envolvida, mas a
        // coluna escola_slug é NOT NULL — fica só a satisfazer a constraint.
        $escolaSlug = '';
        $origem = 'direcao';
    }

    $stmt = db()->prepare('
        INSERT INTO mensagens
            (escola_slug, origem, categoria, remetente_tipo, remetente_id, destinatario_tipo, destinatario_id, autor, corpo)
        VALUES
            (:escola_slug, :origem, :categoria, :r_tipo, :r_id, :d_tipo, :d_id, :autor, :corpo)
    ');
    $stmt->execute([
        'escola_slug' => $escolaSlug,
        'origem' => $origem,
        'categoria' => $categoria,
        'r_tipo' => $identidade['tipo'],
        'r_id' => $identidade['id'],
        'd_tipo' => $tipo,
        'd_id' => $id,
        'autor' => $identidade['nome'],
        'corpo' => $corpo,
    ]);

    return [
        'ok' => true,
        'mensagem' => [
            'id' => (int) db()->lastInsertId(),
            'de_mim' => true,
            'autor' => $identidade['nome'],
            'categoria' => $categoria,
            'corpo' => $corpo,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ],
    ];
}

/** Total de mensagens não lidas dirigidas a $identidade (para o badge do sino). */
function mensagensContarNaoLidas(array $identidade): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM mensagens WHERE destinatario_tipo = :t AND destinatario_id = :i AND lida = 0');
    $stmt->execute(['t' => $identidade['tipo'], 'i' => $identidade['id']]);

    return (int) $stmt->fetchColumn();
}

<?php
/**
 * Recolha da atividade de um dia para o painel de direção.
 *
 * Os carimbos created_at/updated_at são gravados em UTC pelo SQLite
 * (datetime('now')), por isso a comparação com o dia usa o modificador
 * 'localtime' para bater certo com o dia civil em Portugal.
 *
 * Uma linha conta como atividade se foi mexida nesse dia. As linhas vazias
 * criadas automaticamente para encher a tabela até 10 são ignoradas
 * (nunca foram editadas nem têm conteúdo).
 */

const ADMIN_ATIVIDADE_TEM_CONTEUDO = 'COALESCE(numero, nome, escola, ano, nif, escalao, irmao) IS NOT NULL';

function adminAtividadeDoDia(string $escolaSlug, string $dia): array
{
    $filtro = "escola_slug = :s AND date(updated_at, 'localtime') = :d"
        . ' AND ' . ADMIN_ATIVIDADE_TEM_CONTEUDO;
    $params = ['s' => $escolaSlug, 'd' => $dia];

    $stmt = db()->prepare("SELECT numero, nome, ano, servico, updated_at FROM alunos_mensalidades WHERE {$filtro} ORDER BY updated_at DESC");
    $stmt->execute($params);
    $mensalidades = $stmt->fetchAll();

    $stmt = db()->prepare("SELECT periodo, numero, nome, ano, updated_at FROM alunos_interrupcoes WHERE {$filtro} ORDER BY updated_at DESC");
    $stmt->execute($params);
    $interrupcoes = $stmt->fetchAll();

    $stmt = db()->prepare("SELECT numero, nome, ano, servico, updated_at FROM alunos_presenca WHERE {$filtro} ORDER BY updated_at DESC");
    $stmt->execute($params);
    $presenca = $stmt->fetchAll();

    $stmt = db()->prepare("SELECT COUNT(*) FROM presenca_status WHERE escola_slug = :s AND date(updated_at, 'localtime') = :d");
    $stmt->execute($params);
    $marcacoes = (int) $stmt->fetchColumn();

    $stmt = db()->prepare("SELECT data FROM presenca_dias WHERE escola_slug = :s AND date(created_at, 'localtime') = :d ORDER BY data");
    $stmt->execute($params);
    $diasNovos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return [
        'mensalidades' => $mensalidades,
        'interrupcoes' => $interrupcoes,
        'presenca' => $presenca,
        'marcacoes_presenca' => $marcacoes,
        'dias_novos' => $diasNovos,
        'total' => count($mensalidades) + count($interrupcoes) + count($presenca),
    ];
}

/** Converte o carimbo UTC da base de dados para a hora local (HH:MM). */
function adminHoraLocal(?string $carimboUtc): string
{
    if (!$carimboUtc) {
        return '';
    }
    $ts = strtotime($carimboUtc . ' UTC');
    return $ts ? date('H:i', $ts) : '';
}

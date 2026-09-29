<?php
/**
 * Constantes partilhadas pela funcionalidade "Alunos" (partials + endpoints da API)
 * e pela categorização das mensagens entre escolas e direção.
 */

const SERVICO_OPCOES = ['CAF M', 'CAF T', 'CAF M e T', 'AAAFE', 'CAF M e AAAFE'];

const IRMAO_OPCOES = ['Sim', 'Não'];

const ESCALAO_OPCOES = ['Escalão A', 'Escalão B', 'Sem escalão'];

const MENSAGEM_CATEGORIA_OPCOES = ['Geral', 'Financeiro', 'Pedagógico', 'Alunos'];

const PERIODOS_INTERRUPCAO = ['IL Novembro', 'IL Natal', 'IL Fim Semestre', 'IL Páscoa', 'F Verão'];

const MESES = [
    'setembro' => 'Setembro',
    'outubro' => 'Outubro',
    'novembro' => 'Novembro',
    'dezembro' => 'Dezembro',
    'janeiro' => 'Janeiro',
    'fevereiro' => 'Fevereiro',
    'marco' => 'Março',
    'abril' => 'Abril',
    'maio' => 'Maio',
    'junho' => 'Junho',
    'julho' => 'Julho',
    'agosto' => 'Agosto',
];

const IDENTIDADE_CAMPOS = ['numero', 'nome', 'escola', 'ano', 'nif', 'escalao', 'irmao'];

/**
 * Whitelist de colunas editáveis por tabela lógica, usada pelo endpoint de
 * gravação para nunca aceitar um nome de coluna arbitrário num UPDATE.
 */
function alunosCamposPermitidos(string $tabela): ?array
{
    $mensalidadesMeses = [];
    foreach (array_keys(MESES) as $mes) {
        $mensalidadesMeses[] = 'mes_' . $mes;
        $mensalidadesMeses[] = 'desc_' . $mes;
    }

    $mapa = [
        'mensalidades' => array_merge(IDENTIDADE_CAMPOS, [
            'servico', 'seguro', 'mes_seg',
        ], $mensalidadesMeses, [
            'il_novembro', 'il_natal', 'il_fim_semestre', 'il_pascoa', 'f_verao',
        ]),
        'interrupcoes' => IDENTIDADE_CAMPOS,
        'presenca' => array_merge(IDENTIDADE_CAMPOS, ['servico']),
    ];

    return $mapa[$tabela] ?? null;
}

function alunosNomeTabela(string $tabela): ?string
{
    $mapa = [
        'mensalidades' => 'alunos_mensalidades',
        'interrupcoes' => 'alunos_interrupcoes',
        'presenca' => 'alunos_presenca',
    ];

    return $mapa[$tabela] ?? null;
}

function alunosCampoTexto(string $campo, ?string $valor, string $extra = ''): string
{
    return '<input type="text" data-campo="' . htmlspecialchars($campo) . '" value="'
        . htmlspecialchars($valor ?? '') . '" ' . $extra . '>';
}

function alunosCampoNif(?string $valor): string
{
    return alunosCampoTexto('nif', $valor, 'maxlength="9" inputmode="numeric" pattern="[0-9]{0,9}"');
}

function alunosCampoSelect(string $campo, array $opcoes, ?string $valor): string
{
    $html = '<select data-campo="' . htmlspecialchars($campo) . '"><option value=""></option>';
    foreach ($opcoes as $opcao) {
        $selecionado = ($valor === $opcao) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($opcao) . '"' . $selecionado . '>'
            . htmlspecialchars($opcao) . '</option>';
    }
    return $html . '</select>';
}

/** Renderiza as 7 células de identidade comuns às 3 tabelas (N.º…Irmão). */
function alunosCelulasIdentidade(array $linha): string
{
    global $escolaAtual;

    $html = '';
    $html .= '<td>' . alunosCampoTexto('numero', $linha['numero'] ?? null) . '</td>';
    $html .= '<td>' . alunosCampoTexto('nome', $linha['nome'] ?? null) . '</td>';
    $html .= '<td>' . alunosCampoTexto('escola', $linha['escola'] ?? ($escolaAtual['nome'] ?? null)) . '</td>';
    $html .= '<td>' . alunosCampoTexto('ano', $linha['ano'] ?? null) . '</td>';
    $html .= '<td>' . alunosCampoNif($linha['nif'] ?? null) . '</td>';
    $html .= '<td>' . alunosCampoSelect('escalao', ESCALAO_OPCOES, $linha['escalao'] ?? null) . '</td>';
    $html .= '<td>' . alunosCampoSelect('irmao', IRMAO_OPCOES, $linha['irmao'] ?? null) . '</td>';
    return $html;
}

/** Botão de remover linha + indicador de gravação, para a última coluna de cada linha. */
function alunosCelulaAcoes(string $tabela, int $id): string
{
    return '<td class="col-acoes">'
        . '<button type="button" class="btn-delete-row" data-tabela="' . htmlspecialchars($tabela) . '" data-id="' . $id . '">×</button>'
        . '<span class="save-indicator"></span>'
        . '</td>';
}

/**
 * Garante que a tabela indicada tem pelo menos $minimo linhas para a escola
 * (e período, quando aplicável), inserindo linhas vazias em falta. Chamada no
 * carregamento da página para que as tabelas nunca apareçam vazias.
 */
function alunosGarantirLinhasMinimas(string $nomeTabela, string $escola, int $minimo, ?string $periodo = null): void
{
    $ondePeriodo = $periodo !== null ? ' AND periodo = :p' : '';
    $params = ['s' => $escola];
    if ($periodo !== null) {
        $params['p'] = $periodo;
    }

    $stmt = db()->prepare("SELECT COUNT(*) FROM {$nomeTabela} WHERE escola_slug = :s{$ondePeriodo}");
    $stmt->execute($params);
    $existentes = (int) $stmt->fetchColumn();

    if ($existentes >= $minimo) {
        return;
    }

    $stmt = db()->prepare("SELECT COALESCE(MAX(row_order), 0) FROM {$nomeTabela} WHERE escola_slug = :s{$ondePeriodo}");
    $stmt->execute($params);
    $rowOrder = (int) $stmt->fetchColumn();

    $colunas = $periodo !== null ? 'escola_slug, periodo, row_order' : 'escola_slug, row_order';
    $valores = $periodo !== null ? ':s, :p, :o' : ':s, :o';
    $insert = db()->prepare("INSERT INTO {$nomeTabela} ({$colunas}) VALUES ({$valores})");

    for ($i = $existentes; $i < $minimo; $i++) {
        $rowOrder++;
        $paramsInsert = ['s' => $escola, 'o' => $rowOrder];
        if ($periodo !== null) {
            $paramsInsert['p'] = $periodo;
        }
        $insert->execute($paramsInsert);
    }
}

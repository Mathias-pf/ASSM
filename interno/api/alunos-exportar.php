<?php
/**
 * Exporta os dados de uma das tabelas de alunos para um ficheiro .csv
 * (delimitador ";", com BOM UTF-8) que o Excel abre diretamente.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['escola_user']) && empty($_SESSION['admin_user'])) {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/alunos-schema.php';

$escolas = require __DIR__ . '/../../data/escolas.php';

// A direção exporta qualquer escola (?escola=slug); uma escola exporta só a sua.
$escola = !empty($_SESSION['admin_user'])
    ? (string) ($_GET['escola'] ?? '')
    : $_SESSION['escola_user'];

$escolaAtual = $escolas[$escola] ?? null;

if (!$escolaAtual) {
    if (!empty($_SESSION['admin_user'])) {
        http_response_code(400);
        exit('Escola inválida.');
    }
    session_destroy();
    header('Location: ../login.php');
    exit;
}
$tabela = $_GET['tabela'] ?? '';

function csvLinha(array $campos): string
{
    $escapados = array_map(function ($valor) {
        $valor = str_replace('"', '""', (string) ($valor ?? ''));
        return '"' . $valor . '"';
    }, $campos);
    return implode(';', $escapados) . "\r\n";
}

$cabecalho = [];
$linhas = [];
$nomeFicheiro = 'export';

switch ($tabela) {
    case 'mensalidades':
        $cabecalho = ['N.º', 'Nome', 'Escola', 'Ano', 'NIF', 'Escalão', 'Irmão', 'Serviço', 'Seguro', 'Mês seg'];
        foreach (MESES as $rotulo) {
            $cabecalho[] = $rotulo;
            $cabecalho[] = 'Desc';
        }
        $cabecalho = array_merge($cabecalho, ['IL Novembro', 'IL Natal', 'IL Fim Semestre', 'IL Páscoa', 'F Verão']);

        $stmt = db()->prepare('SELECT * FROM alunos_mensalidades WHERE escola_slug = :s ORDER BY row_order, id');
        $stmt->execute(['s' => $escola]);
        foreach ($stmt->fetchAll() as $linha) {
            $linhaExp = [
                $linha['numero'], $linha['nome'], $linha['escola'] ?: ($escolaAtual['nome'] ?? ''), $linha['ano'], $linha['nif'],
                $linha['escalao'], $linha['irmao'], $linha['servico'], $linha['seguro'], $linha['mes_seg'],
            ];
            foreach (array_keys(MESES) as $mes) {
                $linhaExp[] = $linha['mes_' . $mes];
                $linhaExp[] = $linha['desc_' . $mes];
            }
            $linhaExp = array_merge($linhaExp, [
                $linha['il_novembro'], $linha['il_natal'], $linha['il_fim_semestre'], $linha['il_pascoa'], $linha['f_verao'],
            ]);
            $linhas[] = $linhaExp;
        }
        $nomeFicheiro = 'mensalidades';
        break;

    case 'interrupcoes':
        $cabecalho = ['Período', 'N.º', 'Nome', 'Escola', 'Ano', 'NIF', 'Escalão', 'Irmão'];

        $stmt = db()->prepare('SELECT * FROM alunos_interrupcoes WHERE escola_slug = :s AND periodo = :p ORDER BY row_order, id');
        foreach (PERIODOS_INTERRUPCAO as $periodo) {
            $stmt->execute(['s' => $escola, 'p' => $periodo]);
            foreach ($stmt->fetchAll() as $linha) {
                $linhas[] = [
                    $periodo, $linha['numero'], $linha['nome'], $linha['escola'] ?: ($escolaAtual['nome'] ?? ''), $linha['ano'],
                    $linha['nif'], $linha['escalao'], $linha['irmao'],
                ];
            }
        }
        $nomeFicheiro = 'interrupcoes-ferias';
        break;

    case 'presenca':
        $stmt = db()->prepare('SELECT * FROM presenca_dias WHERE escola_slug = :s ORDER BY row_order, id');
        $stmt->execute(['s' => $escola]);
        $dias = $stmt->fetchAll();

        $cabecalho = ['N.º', 'Nome', 'Escola', 'Ano', 'NIF', 'Escalão', 'Irmão', 'Serviço'];
        foreach ($dias as $dia) {
            $ts = strtotime($dia['data']);
            $rotuloDia = $ts ? date('d-m-Y', $ts) : $dia['data'];
            $cabecalho[] = $rotuloDia . ' M';
            $cabecalho[] = $rotuloDia . ' A';
            $cabecalho[] = $rotuloDia . ' T';
        }

        $stmt = db()->prepare('SELECT * FROM alunos_presenca WHERE escola_slug = :s ORDER BY row_order, id');
        $stmt->execute(['s' => $escola]);
        $alunos = $stmt->fetchAll();

        $stmtStatus = db()->prepare('SELECT aluno_id, dia_id, periodo, status FROM presenca_status WHERE escola_slug = :s');
        $stmtStatus->execute(['s' => $escola]);
        $statusMap = [];
        foreach ($stmtStatus->fetchAll() as $s) {
            $statusMap[$s['aluno_id'] . ':' . $s['dia_id'] . ':' . $s['periodo']] = $s['status'];
        }

        foreach ($alunos as $aluno) {
            $linhaExp = [
                $aluno['numero'], $aluno['nome'], $aluno['escola'] ?: ($escolaAtual['nome'] ?? ''), $aluno['ano'],
                $aluno['nif'], $aluno['escalao'], $aluno['irmao'], $aluno['servico'],
            ];
            foreach ($dias as $dia) {
                foreach (['M', 'A', 'T'] as $periodo) {
                    $status = $statusMap[$aluno['id'] . ':' . $dia['id'] . ':' . $periodo] ?? '';
                    $linhaExp[] = $status === 'presente' ? 'Presente' : ($status === 'ausente' ? 'Ausente' : '');
                }
            }
            $linhas[] = $linhaExp;
        }
        $nomeFicheiro = 'presenca-diaria';
        break;

    default:
        http_response_code(400);
        exit('Tabela inválida.');
}

$slugEscola = preg_replace('/[^a-z0-9]+/i', '-', $escolaAtual['nome'] ?? $escola);
$nomeCompleto = $nomeFicheiro . '-' . strtolower(trim($slugEscola, '-')) . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nomeCompleto . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
echo csvLinha($cabecalho);
foreach ($linhas as $linha) {
    echo csvLinha($linha);
}
exit;

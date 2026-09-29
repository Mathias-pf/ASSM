<?php
/**
 * Ligação PDO à base de dados SQLite usada pela funcionalidade "Alunos".
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $caminho = __DIR__ . '/../../data/alunos.sqlite';

    $pdo = new PDO('sqlite:' . $caminho);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alunos_mensalidades (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          escola_slug TEXT NOT NULL,
          row_order INTEGER NOT NULL DEFAULT 0,
          numero TEXT, nome TEXT, escola TEXT, ano TEXT, nif TEXT, escalao TEXT,
          irmao TEXT CHECK (irmao IS NULL OR irmao IN ('Sim','Não')),
          servico TEXT CHECK (servico IS NULL OR servico IN ('CAF M','CAF T','CAF M e T','AAAFE','CAF M e AAAFE')),
          seguro TEXT, mes_seg TEXT,
          mes_setembro TEXT, desc_setembro TEXT, mes_outubro TEXT, desc_outubro TEXT,
          mes_novembro TEXT, desc_novembro TEXT, mes_dezembro TEXT, desc_dezembro TEXT,
          mes_janeiro TEXT, desc_janeiro TEXT, mes_fevereiro TEXT, desc_fevereiro TEXT,
          mes_marco TEXT, desc_marco TEXT, mes_abril TEXT, desc_abril TEXT,
          mes_maio TEXT, desc_maio TEXT, mes_junho TEXT, desc_junho TEXT,
          mes_julho TEXT, desc_julho TEXT, mes_agosto TEXT, desc_agosto TEXT,
          il_novembro TEXT, il_natal TEXT, il_fim_semestre TEXT, il_pascoa TEXT, f_verao TEXT,
          created_at TEXT NOT NULL DEFAULT (datetime('now')),
          updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_mensalidades_escola ON alunos_mensalidades(escola_slug);

        CREATE TABLE IF NOT EXISTS alunos_interrupcoes (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          escola_slug TEXT NOT NULL,
          periodo TEXT NOT NULL CHECK (periodo IN ('IL Novembro','IL Natal','IL Fim Semestre','IL Páscoa','F Verão')),
          row_order INTEGER NOT NULL DEFAULT 0,
          numero TEXT, nome TEXT, escola TEXT, ano TEXT, nif TEXT, escalao TEXT,
          irmao TEXT CHECK (irmao IS NULL OR irmao IN ('Sim','Não')),
          created_at TEXT NOT NULL DEFAULT (datetime('now')),
          updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_interrupcoes_escola ON alunos_interrupcoes(escola_slug, periodo);

        CREATE TABLE IF NOT EXISTS alunos_presenca (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          escola_slug TEXT NOT NULL,
          row_order INTEGER NOT NULL DEFAULT 0,
          numero TEXT, nome TEXT, escola TEXT, ano TEXT, nif TEXT, escalao TEXT,
          irmao TEXT CHECK (irmao IS NULL OR irmao IN ('Sim','Não')),
          servico TEXT CHECK (servico IS NULL OR servico IN ('CAF M','CAF T','CAF M e T','AAAFE','CAF M e AAAFE')),
          created_at TEXT NOT NULL DEFAULT (datetime('now')),
          updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_presenca_escola ON alunos_presenca(escola_slug);

        CREATE TABLE IF NOT EXISTS presenca_dias (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          escola_slug TEXT NOT NULL,
          data TEXT NOT NULL,
          row_order INTEGER NOT NULL DEFAULT 0,
          created_at TEXT NOT NULL DEFAULT (datetime('now')),
          UNIQUE(escola_slug, data)
        );

        CREATE TABLE IF NOT EXISTS presenca_status (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          escola_slug TEXT NOT NULL,
          aluno_id INTEGER NOT NULL REFERENCES alunos_presenca(id) ON DELETE CASCADE,
          dia_id INTEGER NOT NULL REFERENCES presenca_dias(id) ON DELETE CASCADE,
          periodo TEXT NOT NULL CHECK (periodo IN ('M','A','T')),
          status TEXT CHECK (status IS NULL OR status IN ('presente','ausente')),
          updated_at TEXT NOT NULL DEFAULT (datetime('now')),
          UNIQUE(aluno_id, dia_id, periodo)
        );
        CREATE INDEX IF NOT EXISTS idx_status_dia ON presenca_status(dia_id);
        CREATE INDEX IF NOT EXISTS idx_status_aluno ON presenca_status(aluno_id);

        CREATE TABLE IF NOT EXISTS mensagens (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          escola_slug TEXT NOT NULL,
          origem TEXT NOT NULL DEFAULT 'direcao' CHECK (origem IN ('direcao','escola')),
          resposta_a INTEGER,
          categoria TEXT NOT NULL DEFAULT 'Geral',
          autor TEXT NOT NULL,
          corpo TEXT NOT NULL,
          lida INTEGER NOT NULL DEFAULT 0,
          created_at TEXT NOT NULL DEFAULT (datetime('now')),
          lida_em TEXT
        );
        CREATE INDEX IF NOT EXISTS idx_mensagens_escola ON mensagens(escola_slug, lida);
    ");

    // Bases de dados criadas antes das respostas das escolas não têm estas
    // colunas; ALTER TABLE não é idempotente, daí a verificação prévia.
    $colunasMensagens = $pdo->query('PRAGMA table_info(mensagens)')->fetchAll(PDO::FETCH_COLUMN, 1);

    if (!in_array('origem', $colunasMensagens, true)) {
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN origem TEXT NOT NULL DEFAULT 'direcao'");
    }

    if (!in_array('resposta_a', $colunasMensagens, true)) {
        $pdo->exec('ALTER TABLE mensagens ADD COLUMN resposta_a INTEGER');
    }

    if (!in_array('categoria', $colunasMensagens, true)) {
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN categoria TEXT NOT NULL DEFAULT 'Geral'");
    }

    // Generaliza as mensagens de "escola <-> direção" para conversas entre
    // qualquer par de contas (staff ou escola). remetente/destinatario
    // identificam os dois lados; escola_slug/origem mantêm-se só para as
    // linhas antigas (e para satisfazer o NOT NULL de escola_slug quando não
    // há escola nenhuma envolvida, caso staff-a-staff).
    if (!in_array('remetente_id', $colunasMensagens, true)) {
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN remetente_tipo TEXT NOT NULL DEFAULT 'staff'");
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN remetente_id TEXT NOT NULL DEFAULT ''");
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN destinatario_tipo TEXT NOT NULL DEFAULT 'escola'");
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN destinatario_id TEXT NOT NULL DEFAULT ''");

        // Migração única: as linhas antigas só podiam ser entre uma escola e
        // a única conta de direção que existia, 'diretor'.
        $pdo->exec("
            UPDATE mensagens SET
              remetente_tipo    = CASE WHEN origem = 'escola' THEN 'escola' ELSE 'staff' END,
              remetente_id      = CASE WHEN origem = 'escola' THEN escola_slug ELSE 'diretor' END,
              destinatario_tipo = CASE WHEN origem = 'escola' THEN 'staff' ELSE 'escola' END,
              destinatario_id   = CASE WHEN origem = 'escola' THEN 'diretor' ELSE escola_slug END
            WHERE remetente_id = ''
        ");
    }

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mensagens_remetente ON mensagens(remetente_tipo, remetente_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mensagens_destinatario ON mensagens(destinatario_tipo, destinatario_id)');

    return $pdo;
}

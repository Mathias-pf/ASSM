<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!empty($_SESSION['admin_user'])) {
    header('Location: admin.php');
    exit;
}

if (!empty($_SESSION['escola_user'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $escolas = require __DIR__ . '/../data/escolas.php';
    $admins = require __DIR__ . '/../data/admins.php';

    if (isset($admins[$usuario]) && password_verify($senha, $admins[$usuario]['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $usuario;
        header('Location: admin.php');
        exit;
    }

    if (isset($escolas[$usuario]) && password_verify($senha, $escolas[$usuario]['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['escola_user'] = $usuario;
        header('Location: index.php');
        exit;
    }

    $erro = 'Utilizador ou senha incorretos.';
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<main>
  <div class="container">
    <div class="login-box">
      <h1>Área de Funcionários</h1>
      <?php if ($erro !== ''): ?>
        <p class="login-error"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <form method="post" action="login.php">
        <label for="usuario">Utilizador</label>
        <input type="text" id="usuario" name="usuario" autocomplete="username" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" autocomplete="current-password" required>

        <button type="submit" class="btn btn-primary">Entrar</button>
      </form>
    </div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

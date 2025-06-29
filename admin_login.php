<?php
// Inicia a sessão para que possamos usar a variável $_SESSION
session_start();

// Se o administrador já estiver logado, redireciona para o painel admin
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

require_once 'db.php';
$erros = [];
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erros[] = "Todos os campos são obrigatórios.";
    } else {
        // Busca o administrador pelo e-mail na tabela 'administradores'
        try {
            $stmt = $pdo->prepare("SELECT id, nome_admin, senha_hash, nivel_acesso FROM administradores WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica se o administrador existe E se a senha está correta
            if ($admin && password_verify($senha, $admin['senha_hash'])) {
                // Credenciais corretas! Armazena os dados na sessão.
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nome'] = $admin['nome_admin'];
                $_SESSION['admin_nivel'] = $admin['nivel_acesso'];
                
                // Redireciona para o painel administrativo
                header("Location: admin_dashboard.php");
                exit();
            } else {
                // Credenciais incorretas
                $erros[] = "E-mail ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao tentar fazer login. Por favor, tente novamente mais tarde.";
            // error_log("Erro de login admin: " . $e->getMessage());
        }
    }
}

$page_title = "Login Administrativo";
include 'templates/header.php'; // Reutiliza o header existente
?>

<main>
    <div class="form-container">
        <h2>Acesso Administrativo</h2>
        <p>Acesse o painel para gerenciar o guia comercial.</p>

        <?php if (!empty($erros)): ?>
            <div class="mensagem erro">
                <?php foreach ($erros as $erro): ?>
                    <p><?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST">
            <div class="form-grupo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-grupo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn btn-login-form">Entrar como Admin</button>
        </form>
    </div>
</main>

<?php include 'templates/footer.php'; // Reutiliza o footer existente ?>
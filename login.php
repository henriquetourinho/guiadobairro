<?php
// Inicia a sessão. Isso deve ser a primeira coisa no script PHP que usa sessões.
// Permite o uso da superglobal $_SESSION para armazenar e acessar dados entre requisições.
session_start();

// Se o usuário já estiver logado (ou seja, se 'comerciante_id' existir na sessão),
// redireciona-o para o painel para evitar que acesse a página de login novamente.
if (isset($_SESSION['comerciante_id'])) {
    header("Location: dashboard.php");
    exit(); // Termina a execução do script após o redirecionamento.
}

// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// Inicializa variáveis para armazenar mensagens de erro e o e-mail digitado no formulário.
$erros = [];
$email = '';

// Verifica se o formulário de login foi enviado via método POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpa os dados do formulário.
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // Validação básica: verifica se ambos os campos foram preenchidos.
    if (empty($email) || empty($senha)) {
        $erros[] = "Todos os campos são obrigatórios.";
    } else {
        try {
            // Prepara uma consulta para buscar o comerciante pelo e-mail.
            // Seleciona id, nome_responsavel, senha_hash e status_conta.
            $stmt = $pdo->prepare("SELECT id, nome_responsavel, senha_hash, status_conta FROM comerciantes WHERE email = ?");
            $stmt->execute([$email]);
            $comerciante = $stmt->fetch(PDO::FETCH_ASSOC); // Obtém a linha como um array associativo.

            // Verifica se o comerciante foi encontrado E se a senha fornecida corresponde ao hash armazenado.
            // password_verify() é a função segura para comparar uma senha com um hash.
            if ($comerciante && password_verify($senha, $comerciante['senha_hash'])) {

                // Se as credenciais estiverem corretas, verifica o status da conta.
                if ($comerciante['status_conta'] == 'ativo') {
                    // Login bem-sucedido! Armazena informações importantes na sessão.
                    $_SESSION['comerciante_id'] = $comerciante['id'];
                    $_SESSION['comerciante_nome'] = $comerciante['nome_responsavel'];
                    $_SESSION['comerciante_email'] = $comerciante['email']; // Opcional, mas útil

                    // Redireciona o usuário para o painel de controle.
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Conta inativa ou pendente de aprovação.
                    $erros[] = "Sua conta está inativa ou pendente de aprovação. Entre em contato com o suporte.";
                }

            } else {
                // E-mail não encontrado ou senha incorreta.
                $erros[] = "E-mail ou senha inválidos.";
            }
        } catch (PDOException $e) {
            // Erro no banco de dados durante o processo de login.
            $erros[] = "Erro ao tentar fazer login. Por favor, tente novamente mais tarde.";
            // Em um sistema real, você logaria o erro: error_log($e->getMessage());
        }
    }
}

// Define o título da página.
$page_title = "Login do Comerciante";
// Inclui o cabeçalho da página.
include 'templates/header.php';
?>

<div class="form-container">
    <h2>Acesse seu Painel</h2>
    <p>Faça o login para gerenciar suas lojas.</p>

    <?php
    // Exibe mensagem de sucesso se o usuário acabou de se cadastrar.
    if (isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso'):
    ?>
        <div class="mensagem sucesso">
            <p>Cadastro realizado com sucesso! Faça o login para continuar.</p>
        </div>
    <?php
    endif;
    ?>

    <?php
    // Exibe mensagens de erro, se existirem.
    if (!empty($erros)):
    ?>
        <div class="mensagem erro">
            <?php foreach ($erros as $erro): ?>
                <p><?php echo htmlspecialchars($erro); ?></p>
            <?php endforeach; ?>
        </div>
    <?php
    endif;
    ?>

    <form action="login.php" method="POST">
        <div class="form-grupo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-grupo">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn btn-login-form">Entrar</button>
    </form>
    <div class="form-link">
        <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui.</a></p>
    </div>
</div>

<?php
// Inclui o rodapé da página.
include 'templates/footer.php';
?>
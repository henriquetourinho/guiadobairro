<?php
// Inicia a sessão para acessar o ID e nome do comerciante logado.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// 1. PROTEÇÃO DE ACESSO
// Verifica se o usuário (comerciante) está logado.
if (!isset($_SESSION['comerciante_id'])) {
    header("Location: login.php");
    exit();
}
$comerciante_id = $_SESSION['comerciante_id'];

// Inicializa variáveis para mensagens e dados do formulário
$erros = [];
$sucesso = '';
$nome_responsavel = '';
$email = '';
$cnpj_cpf = ''; // Campo somente leitura

// 2. CARREGAR DADOS ATUAIS DO PERFIL (GET request)
// Se a página for carregada pela primeira vez (GET), busca os dados do comerciante.
// Se for um POST, as variáveis serão preenchidas pelos dados do formulário (POST).
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        $stmt_comerciante = $pdo->prepare(
            "SELECT nome_responsavel, email, cnpj_cpf, senha_hash
             FROM comerciantes
             WHERE id = ?"
        );
        $stmt_comerciante->execute([$comerciante_id]);
        $comerciante_data = $stmt_comerciante->fetch(PDO::FETCH_ASSOC);

        if (!$comerciante_data) {
            // Isso não deveria acontecer se o ID da sessão for válido
            $_SESSION['mensagem_erro'] = "Seu perfil não foi encontrado. Por favor, tente novamente.";
            header("Location: dashboard.php");
            exit();
        }

        // Preenche as variáveis com os dados atuais do banco de dados
        $nome_responsavel = $comerciante_data['nome_responsavel'];
        $email = $comerciante_data['email'];
        $cnpj_cpf = $comerciante_data['cnpj_cpf'];

    } catch (PDOException $e) {
        $_SESSION['mensagem_erro'] = "Erro ao carregar dados do perfil. Por favor, tente novamente.";
        header("Location: dashboard.php");
        exit();
        // error_log("Erro ao carregar perfil em editar_perfil.php (GET): " . $e->getMessage());
    }
}


// 3. PROCESSAMENTO DO FORMULÁRIO (POST request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpeza dos dados do formulário
    $nome_responsavel = trim($_POST['nome_responsavel']);
    $email = trim($_POST['email']);
    // O CNPJ/CPF não vem do POST para edição aqui, ele é somente leitura
    // Para alteração de senha:
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_nova_senha = $_POST['confirma_nova_senha'] ?? '';

    // Validação dos campos obrigatórios e formato
    if (empty($nome_responsavel)) {
        $erros[] = "O campo 'Nome Completo' é obrigatório.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O formato do e-mail é inválido.";
    }

    // Validação de e-mail duplicado (se o e-mail foi alterado)
    try {
        $stmt_check_email = $pdo->prepare("SELECT id FROM comerciantes WHERE email = ? AND id != ?");
        $stmt_check_email->execute([$email, $comerciante_id]);
        if ($stmt_check_email->fetch()) {
            $erros[] = "Este e-mail já está em uso por outra conta.";
        }
    } catch (PDOException $e) {
        $erros[] = "Erro ao verificar e-mail. Por favor, tente novamente.";
        // error_log("Erro ao verificar email em editar_perfil.php: " . $e->getMessage());
    }

    // Lógica de alteração de senha (se algum campo de senha foi preenchido)
    if (!empty($nova_senha) || !empty($confirma_nova_senha) || !empty($senha_atual)) {
        if (empty($senha_atual)) {
            $erros[] = "Para alterar a senha, você deve informar sua senha atual.";
        }
        if (empty($nova_senha)) {
            $erros[] = "Você deve informar a nova senha.";
        }
        if (strlen($nova_senha) < 8) {
            $erros[] = "A nova senha deve ter no mínimo 8 caracteres.";
        }
        if ($nova_senha !== $confirma_nova_senha) {
            $erros[] = "A nova senha e a confirmação não coincidem.";
        }

        // Se não houver erros nas senhas até agora, verifica a senha atual com o hash salvo
        if (empty($erros)) {
            try {
                $stmt_get_hash = $pdo->prepare("SELECT senha_hash FROM comerciantes WHERE id = ?");
                $stmt_get_hash->execute([$comerciante_id]);
                $current_hash = $stmt_get_hash->fetchColumn(); // Pega apenas o valor da coluna senha_hash

                if (!password_verify($senha_atual, $current_hash)) {
                    $erros[] = "Sua senha atual está incorreta.";
                }
            } catch (PDOException $e) {
                $erros[] = "Erro ao verificar senha atual. Tente novamente.";
                // error_log("Erro ao verificar senha atual em editar_perfil.php: " . $e->getMessage());
            }
        }
    }

    // Se não houver erros, procede com a atualização no banco de dados
    if (empty($erros)) {
        try {
            $update_fields = [];
            $update_params = [];

            // Adiciona nome e email para atualização
            $update_fields[] = "nome_responsavel = ?";
            $update_params[] = $nome_responsavel;
            $update_fields[] = "email = ?";
            $update_params[] = $email;

            // Se a senha foi alterada, adicione-a à atualização
            if (!empty($nova_senha) && empty($erros)) { // Verifica empty($erros) novamente para garantir que a senha_atual foi validada
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $update_fields[] = "senha_hash = ?";
                $update_params[] = $nova_senha_hash;
            }

            // Apenas atualiza se houver campos para atualizar
            if (!empty($update_fields)) {
                $query = "UPDATE comerciantes SET " . implode(", ", $update_fields) . " WHERE id = ?";
                $update_params[] = $comerciante_id;

                $stmt_update = $pdo->prepare($query);
                $stmt_update->execute($update_params);

                if ($stmt_update->rowCount() > 0) {
                    $sucesso = "Seu perfil foi atualizado com sucesso!";
                    // Atualiza o nome na sessão caso tenha sido alterado
                    $_SESSION['comerciante_nome'] = $nome_responsavel;
                } else {
                    $sucesso = "Nenhuma alteração foi feita no seu perfil."; // Sem erro, mas sem mudança
                }
            } else {
                $sucesso = "Nenhuma alteração foi solicitada."; // Caso o formulário seja submetido vazio
            }

        } catch (PDOException $e) {
            $erros[] = "Erro ao atualizar seu perfil. Por favor, tente novamente mais tarde.";
            // error_log("Erro ao atualizar perfil em editar_perfil.php: " . $e->getMessage());
        }
    }
}


// Define o título da página.
$page_title = "Editar Perfil";
// Inclui o cabeçalho da página.
include 'templates/header.php';
?>

<main>
    <div class="form-container">
        <h2>Editar Meu Perfil</h2>
        <p>Atualize suas informações de cadastro.</p>

        <?php
        // Exibe mensagens de sucesso
        if (!empty($sucesso)):
        ?>
            <div class="mensagem sucesso">
                <p><?php echo htmlspecialchars($sucesso); ?></p>
            </div>
        <?php
        endif;

        // Exibe mensagens de erro
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

        <form action="editar_perfil.php" method="POST">
            <div class="form-grupo">
                <label for="nome_responsavel">Nome Completo</label>
                <input type="text" id="nome_responsavel" name="nome_responsavel" value="<?php echo htmlspecialchars($nome_responsavel); ?>" required>
            </div>
            <div class="form-grupo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-grupo">
                <label for="cnpj_cpf">CNPJ/CPF</label>
                <input type="text" id="cnpj_cpf" value="<?php echo htmlspecialchars($cnpj_cpf); ?>" readonly>
                <small class="form-text-muted">Seu CNPJ/CPF não pode ser alterado aqui.</small>
            </div>

            <hr class="form-divider"> <h3>Alterar Senha (Opcional)</h3>
            <p class="form-text-muted">Preencha os campos abaixo apenas se desejar alterar sua senha.</p>
            <div class="form-grupo">
                <label for="senha_atual">Senha Atual</label>
                <input type="password" id="senha_atual" name="senha_atual">
            </div>
            <div class="form-grupo">
                <label for="nova_senha">Nova Senha</label>
                <input type="password" id="nova_senha" name="nova_senha">
            </div>
            <div class="form-grupo">
                <label for="confirma_nova_senha">Confirme Nova Senha</label>
                <input type="password" id="confirma_nova_senha" name="confirma_nova_senha">
            </div>

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="dashboard.php" class="btn btn-secondary mt-1">Voltar ao Painel</a>
        </form>
    </div>
</main>

<?php
// Inclui o rodapé da página.
include 'templates/footer.php';
?>
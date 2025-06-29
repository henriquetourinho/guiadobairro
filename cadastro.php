<?php
// Inclui o arquivo de conexão com o banco de dados
require_once 'db.php';

// Inicializa variáveis para mensagens e dados do formulário
$erros = [];
$nome_responsavel = '';
$email = '';
$cnpj_cpf = ''; // Variável para o CNPJ

// Verifica se o formulário foi enviado (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. COLETA E LIMPEZA DOS DADOS
    $nome_responsavel = trim($_POST['nome_responsavel']);
    $email = trim($_POST['email']);
    $cnpj_cpf = trim($_POST['cnpj_cpf']); // Coleta o CNPJ COM formatação
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];

    // Limpa o CNPJ para validação e armazenamento (APENAS NÚMEROS)
    $cnpj_limpo = preg_replace('/[^0-9]/', '', $cnpj_cpf);

    // 2. VALIDAÇÃO DOS DADOS
    if (empty($nome_responsavel)) {
        $erros[] = "O campo 'Nome do Responsável' é obrigatório.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O formato do e-mail é inválido.";
    }
    if (empty($cnpj_limpo)) { // Validação de campo obrigatório para CNPJ
        $erros[] = "O campo 'CNPJ' é obrigatório.";
    } else {
        // Validação: deve ter EXATAMENTE 14 dígitos para CNPJ
        if (strlen($cnpj_limpo) != 14) {
            $erros[] = "CNPJ inválido. Deve ter 14 dígitos numéricos.";
        }
        // Opcional: Adicionar validação de dígito verificador de CNPJ aqui, se desejar uma validação mais robusta.
    }
    if (strlen($senha) < 8) {
        $erros[] = "A senha deve ter no mínimo 8 caracteres.";
    }
    if ($senha !== $senha_confirma) {
        $erros[] = "As senhas não coincidem.";
    }

    // 3. VERIFICAÇÃO DE E-MAIL E CNPJ DUPLICADO NO BANCO
    if (empty($erros)) {
        try {
            // Verifica e-mail duplicado
            $stmt = $pdo->prepare("SELECT id FROM comerciantes WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erros[] = "Este e-mail já está cadastrado em nosso sistema.";
            }

            // VERIFICAÇÃO: CNPJ duplicado (usando o valor LIMPO)
            $stmt = $pdo->prepare("SELECT id FROM comerciantes WHERE cnpj_cpf = ?");
            $stmt->execute([$cnpj_limpo]);
            if ($stmt->fetch()) {
                $erros[] = "Este CNPJ já está cadastrado em nosso sistema.";
            }

        } catch (PDOException $e) {
            $erros[] = "Erro ao verificar dados. Por favor, tente novamente mais tarde.";
            // error_log($e->getMessage()); // Logar o erro real em produção
        }
    }

    // 4. INSERÇÃO NO BANCO DE DADOS (se não houver erros)
    if (empty($erros)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            // INSERT: Inclui o campo `cnpj_cpf` (usando o valor LIMPO)
            $stmt = $pdo->prepare("INSERT INTO comerciantes (nome_responsavel, email, cnpj_cpf, senha_hash, status_conta) VALUES (?, ?, ?, ?, 'pendente')");
            $stmt->execute([$nome_responsavel, $email, $cnpj_limpo, $senha_hash]);

            header("Location: login.php?cadastro=sucesso");
            exit();

        } catch (PDOException $e) {
            $erros[] = "Erro ao cadastrar. Por favor, tente novamente mais tarde.";
            // error_log($e->getMessage()); // Logar o erro real em produção
        }
    }
}

// Inclui o cabeçalho
$page_title = "Cadastro de Comerciante";
include 'templates/header.php';
?>

<main>
    <div class="form-container">
        <h2>Cadastre-se para Divulgar sua Loja</h2>
        <p>Crie sua conta para começar a gerenciar seus estabelecimentos no guia.</p>

        <?php if (!empty($erros)): ?>
            <div class="mensagem erro">
                <?php foreach ($erros as $erro): ?>
                    <p><?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <div class="form-grupo">
                <label for="nome_responsavel">Seu Nome Completo (Pessoa Jurídica ou Física)</label>
                <input type="text" id="nome_responsavel" name="nome_responsavel" value="<?php echo htmlspecialchars($nome_responsavel); ?>" required>
            </div>
            <div class="form-grupo">
                <label for="email">Seu Melhor E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-grupo">
                <label for="cnpj_cpf">CNPJ</label>
                <input type="text" id="cnpj_cpf" name="cnpj_cpf" value="<?php echo htmlspecialchars($cnpj_cpf); ?>" placeholder="XX.XXX.XXX/XXXX-XX" maxlength="18" required>
            </div>
            <div class="form-grupo">
                <label for="senha">Crie uma Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <div class="form-grupo">
                <label for="senha_confirma">Confirme sua Senha</label>
                <input type="password" id="senha_confirma" name="senha_confirma" required>
            </div>
            <button type="submit" class="btn btn-cadastro-form">Criar Minha Conta</button>
        </form>
        <div class="form-link">
            <p>Já tem uma conta? <a href="login.php">Faça o login aqui.</a></p>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>
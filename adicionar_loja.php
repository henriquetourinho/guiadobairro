<?php
// Inicia a sessão para acessar o ID do comerciante logado e para gerenciar mensagens de feedback.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// 1. PROTEÇÃO DE ACESSO
// Verifica se o usuário (comerciante) está logado.
// Se não estiver, redireciona para a página de login.
if (!isset($_SESSION['comerciante_id'])) {
    header("Location: login.php");
    exit();
}

// Obtém o ID do comerciante logado da sessão.
$comerciante_id = $_SESSION['comerciante_id'];

// Inicializa variáveis para mensagens de erro/sucesso e dados do formulário.
$erros = [];
$sucesso = '';
$nome_loja = '';
$descricao = '';
$endereco = '';
$Maps_link = ''; // NOVA VARIÁVEL: Para o link do Google Maps
$categoria_id = '';
$bairro_id = '';
$foto_capa = ''; // Variável para o caminho da foto de capa da loja

// Define o diretório de upload das fotos de capa das lojas
$upload_dir = 'uploads/lojas_capas/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 2. BUSCAR CATEGORIAS E BAIRROS DO BANCO DE DADOS
$categorias = [];
$bairros_permitidos = [];
try {
    // Buscar Categorias
    $stmt_categorias = $pdo->query("SELECT id, nome_categoria FROM categorias ORDER BY nome_categoria ASC");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    // Buscar Bairros Permitidos
    $stmt_bairros = $pdo->query("SELECT id, nome_bairro FROM bairros_permitidos ORDER BY nome_bairro ASC");
    $bairros_permitidos = $stmt_bairros->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erros[] = "Erro ao carregar categorias ou bairros. Por favor, tente novamente mais tarde.";
    // error_log("Erro ao carregar categorias/bairros em adicionar_loja.php: " . $e->getMessage());
}

// Verifica se o formulário foi enviado via método POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. COLETA E LIMPEZA DOS DADOS DO FORMULÁRIO
    $nome_loja = trim($_POST['nome_loja']);
    $descricao = trim($_POST['descricao']);
    $endereco = trim($_POST['endereco']);
    $Maps_link = trim($_POST['Maps_link']); // COLETAR O LINK
    $categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
    $bairro_id = filter_input(INPUT_POST, 'bairro_id', FILTER_VALIDATE_INT);

    // 4. VALIDAÇÃO DOS DADOS
    if (empty($nome_loja)) {
        $erros[] = "O campo 'Nome da Loja' é obrigatório.";
    }
    if (empty($descricao)) {
        $erros[] = "O campo 'Descrição' é obrigatório.";
    }
    if (empty($endereco)) {
        $erros[] = "O campo 'Endereço Completo' é obrigatório.";
    }
    // Opcional: Validação de URL para o link do Google Maps
    if (!empty($Maps_link) && !filter_var($Maps_link, FILTER_VALIDATE_URL)) {
        $erros[] = "O link do Google Maps fornecido é inválido.";
    }
    
    $categoria_valida = false;
    if ($categoria_id !== false && $categoria_id !== null) {
        foreach ($categorias as $cat) {
            if ($cat['id'] == $categoria_id) {
                $categoria_valida = true;
                break;
            }
        }
    }
    if (!$categoria_valida) {
        $erros[] = "Por favor, selecione uma categoria válida.";
    }
    $bairro_valido = false;
    if ($bairro_id !== false && $bairro_id !== null) {
        foreach ($bairros_permitidos as $bairro_data) {
            if ($bairro_data['id'] == $bairro_id) {
                $bairro_valido = true;
                break;
            }
        }
    }
    if (!$bairro_valido) {
        $erros[] = "Por favor, selecione um bairro válido da lista.";
    }

    // 5. Lógica de upload de foto de capa da LOJA
    $caminho_foto_loja = NULL;

    if (isset($_FILES['foto_capa_loja']) && $_FILES['foto_capa_loja']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['foto_capa_loja']['tmp_name'];
        $file_name = $_FILES['foto_capa_loja']['name'];
        $file_size = $_FILES['foto_capa_loja']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_ext)) {
            $erros[] = "Formato de arquivo não permitido para a foto de capa da loja. Use JPG, JPEG, PNG ou GIF.";
        }
        if ($file_size > $max_size) {
            $erros[] = "A foto de capa da loja não pode ter mais de 5MB.";
        }

        if (empty($erros)) {
            // Gera um nome de arquivo único para evitar colisões
            $new_file_name = uniqid('loja_capa_') . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                $caminho_foto_loja = $destination_path; // Salva o caminho para o DB
            } else {
                $erros[] = "Erro ao mover o arquivo de foto de capa da loja para o diretório de destino.";
            }
        }
    } // Se nenhum arquivo foi enviado, $caminho_foto_loja permanece NULL

    // 6. INSERÇÃO NO BANCO DE DADOS (se não houver erros de validação e upload)
    if (empty($erros)) {
        try {
            // Prepara a query de inserção. AGORA INCLUI `Maps_link`
            $stmt = $pdo->prepare(
                "INSERT INTO lojas (comerciante_id, nome_loja, descricao, endereco, Maps_link, bairro_id, categoria_id, foto_capa, status_publicacao)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')"
            );
            $stmt->execute([$comerciante_id, $nome_loja, $descricao, $endereco, $Maps_link, $bairro_id, $categoria_id, $caminho_foto_loja]);

            $_SESSION['mensagem_sucesso'] = "Loja '<strong>" . htmlspecialchars($nome_loja) . "</strong>' cadastrada com sucesso! Ela está aguardando aprovação.";

            header("Location: dashboard.php");
            exit();

        } catch (PDOException $e) {
            $erros[] = "Erro ao cadastrar a loja. Por favor, tente novamente mais tarde.";
            // error_log("Erro ao adicionar loja: " . $e->getMessage());
        }
    }
}

// Define o título da página.
$page_title = "Adicionar Nova Loja";
// Inclui o cabeçalho da página.
include 'templates/header.php';
?>

<main>
    <div class="form-container">
        <h2>Adicionar Nova Loja</h2>
        <p>Preencha os dados abaixo para cadastrar seu estabelecimento no guia.</p>

        <?php
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

        <form action="adicionar_loja.php" method="POST" enctype="multipart/form-data">
            <div class="form-grupo">
                <label for="foto_capa_loja">Foto de Capa da Loja (Opcional)</label>
                <input type="file" id="foto_capa_loja" name="foto_capa_loja" accept="image/jpeg, image/png, image/gif">
                <small class="form-text-muted">Adicione uma imagem que represente sua loja. Formatos permitidos: JPG, PNG, GIF. Máximo: 5MB.</small>
            </div>

            <hr class="form-divider">

            <div class="form-grupo">
                <label for="nome_loja">Nome da Loja</label>
                <input type="text" id="nome_loja" name="nome_loja" value="<?php echo htmlspecialchars($nome_loja); ?>" required>
            </div>

            <div class="form-grupo">
                <label for="descricao">Descrição da Loja</label>
                <textarea id="descricao" name="descricao" rows="6" required><?php echo htmlspecialchars($descricao); ?></textarea>
            </div>

            <div class="form-grupo">
                <label for="endereco">Endereço Completo (Rua, Número, Complemento)</label>
                <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($endereco); ?>" required>
            </div>

            <div class="form-grupo">
                <label for="Maps_link">Link do Google Maps (Opcional)</label>
                <input type="url" id="Maps_link" name="Maps_link" value="<?php echo htmlspecialchars($Maps_link); ?>" placeholder="Cole o link completo do Google Maps aqui">
                <small class="form-text-muted">Exemplo: http://maps.google.com/...</small>
            </div>


            <div class="form-grupo">
                <label for="bairro_id">Bairro</label>
                <select id="bairro_id" name="bairro_id" required>
                    <option value="">Selecione um bairro</option>
                    <?php
                    foreach ($bairros_permitidos as $bairro_data):
                        $selected = ($bairro_data['id'] == $bairro_id) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($bairro_data['id']); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($bairro_data['nome_bairro']); ?>
                        </option>
                    <?php
                    endforeach;
                    ?>
                </select>
            </div>

            <div class="form-grupo">
                <label for="categoria_id">Categoria</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">Selecione uma categoria</option>
                    <?php
                    foreach ($categorias as $cat):
                        $selected = ($cat['id'] == $categoria_id) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($cat['nome_categoria']); ?>
                        </option>
                    <?php
                    endforeach;
                    ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Cadastrar Loja</button>
            <a href="dashboard.php" class="btn btn-secondary mt-1">Voltar ao Painel</a>
        </form>
    </div>
</main>

<?php
// Inclui o rodapé da página.
include 'templates/footer.php';
?>
<?php
// Inicia a sessão para acessar o ID do comerciante logado.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// 1. PROTEÇÃO DE ACESSO E OBTENÇÃO DO ID DO COMERCIANTE
if (!isset($_SESSION['comerciante_id'])) {
    header("Location: login.php");
    exit();
}
$comerciante_id = $_SESSION['comerciante_id'];

// 2. OBTER E VALIDAR O ID DA LOJA DA URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    $_SESSION['mensagem_erro'] = "ID da loja inválido ou não fornecido para edição.";
    header("Location: dashboard.php");
    exit();
}
$loja_id = (int)$_GET['id'];

// Inicializa variáveis para mensagens e dados do formulário
$erros = [];
$sucesso = '';
$nome_loja = '';
$descricao = '';
$endereco = '';
$Maps_link = ''; // NOVA VARIÁVEL: Para o link do Google Maps
$categoria_id = '';
$bairro_id = '';
$foto_capa_loja_atual = ''; // Variável para o caminho da foto de capa da loja atual

// Define o diretório de upload das fotos de capa das lojas
$upload_dir = 'uploads/lojas_capas/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}


// 3. BUSCAR CATEGORIAS E BAIRROS DO BANCO DE DADOS
$categorias = [];
$bairros_permitidos = [];
try {
    $stmt_categorias = $pdo->query("SELECT id, nome_categoria FROM categorias ORDER BY nome_categoria ASC");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    $stmt_bairros = $pdo->query("SELECT id, nome_bairro FROM bairros_permitidos ORDER BY nome_bairro ASC");
    $bairros_permitidos = $stmt_bairros->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erros[] = "Erro ao carregar categorias ou bairros. Por favor, tente novamente mais tarde.";
    // error_log("Erro ao carregar categorias/bairros em editar_loja.php: " . $e->getMessage());
}

// 4. CARREGAR DADOS DA LOJA EXISTENTE (GET request)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        // Busca os dados da loja, incluindo bairro_id, foto_capa e o Maps_link
        $stmt_loja = $pdo->prepare(
            "SELECT id, nome_loja, descricao, endereco, Maps_link, bairro_id, categoria_id, comerciante_id, foto_capa
             FROM lojas
             WHERE id = ? AND comerciante_id = ?"
        );
        $stmt_loja->execute([$loja_id, $comerciante_id]);
        $loja = $stmt_loja->fetch(PDO::FETCH_ASSOC);

        if (!$loja) {
            $_SESSION['mensagem_erro'] = "Loja não encontrada ou você não tem permissão para editá-la.";
            header("Location: dashboard.php");
            exit();
        }

        // Preenche as variáveis do formulário com os dados atuais da loja
        $nome_loja = $loja['nome_loja'];
        $descricao = $loja['descricao'];
        $endereco = $loja['endereco'];
        $Maps_link = $loja['Maps_link']; // PREENCHE O LINK DO GOOGLE MAPS
        $bairro_id = $loja['bairro_id'];
        $categoria_id = $loja['categoria_id'];
        $foto_capa_loja_atual = $loja['foto_capa'];

    } catch (PDOException $e) {
        $_SESSION['mensagem_erro'] = "Erro ao carregar dados da loja para edição. Por favor, tente novamente.";
        header("Location: dashboard.php");
        exit();
        // error_log("Erro ao carregar dados da loja em editar_loja.php (GET): " . $e->getMessage());
    }
}


// 5. PROCESSAMENTO DO FORMULÁRIO DE EDIÇÃO (POST request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpeza dos dados do formulário
    $nome_loja = trim($_POST['nome_loja']);
    $descricao = trim($_POST['descricao']);
    $endereco = trim($_POST['endereco']);
    $Maps_link = trim($_POST['Maps_link']); // COLETAR O LINK
    $bairro_id = filter_input(INPUT_POST, 'bairro_id', FILTER_VALIDATE_INT);
    $categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
    $remover_foto_loja = isset($_POST['remover_foto_loja']) && $_POST['remover_foto_loja'] === 'on';

    // Carrega o caminho da foto atual do DB para lógica de exclusão/substituição
    try {
        $stmt_get_foto = $pdo->prepare("SELECT foto_capa FROM lojas WHERE id = ? AND comerciante_id = ?");
        $stmt_get_foto->execute([$loja_id, $comerciante_id]);
        $loja_db_data = $stmt_get_foto->fetch(PDO::FETCH_ASSOC);
        $foto_capa_loja_atual = $loja_db_data['foto_capa']; // Valor do DB
    } catch (PDOException $e) {
        $erros[] = "Erro ao verificar dados da foto de capa da loja.";
        // error_log("Erro ao obter foto_capa da loja DB: " . $e->getMessage());
    }

    // Validação dos dados
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

    // Lógica de upload de foto de capa da LOJA (para o UPDATE)
    $caminho_foto_loja = $foto_capa_loja_atual; // Começa com o caminho atual

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
            $new_file_name = uniqid('loja_capa_') . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                $caminho_foto_loja = $destination_path;

                if ($foto_capa_loja_atual && file_exists($foto_capa_loja_atual)) {
                    unlink($foto_capa_loja_atual);
                }
            } else {
                $erros[] = "Erro ao mover o arquivo de foto para o diretório de destino.";
            }
        }
    } elseif ($foto_capa_loja_atual && $remover_foto_loja) {
        if (file_exists($foto_capa_loja_atual)) {
            unlink($foto_capa_loja_atual);
        }
        $caminho_foto_loja = NULL;
    } else {
        $caminho_foto_loja = $foto_capa_loja_atual;
    }


    // Se não houver erros de validação e upload, procede com a atualização
    if (empty($erros)) {
        try {
            // Atualiza os dados da loja no banco de dados, incluindo `Maps_link`
            $stmt = $pdo->prepare(
                "UPDATE lojas
                 SET nome_loja = ?, descricao = ?, endereco = ?, Maps_link = ?, bairro_id = ?, categoria_id = ?, foto_capa = ?, data_atualizacao = NOW()
                 WHERE id = ? AND comerciante_id = ?"
            );
            $stmt->execute([$nome_loja, $descricao, $endereco, $Maps_link, $bairro_id, $categoria_id, $caminho_foto_loja, $loja_id, $comerciante_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['mensagem_sucesso'] = "Loja '{$nome_loja}' atualizada com sucesso!";
            } else {
                $_SESSION['mensagem_aviso'] = "Nenhuma alteração foi feita na loja '{$nome_loja}'.";
            }

            header("Location: dashboard.php");
            exit();

        } catch (PDOException $e) {
            $erros[] = "Erro ao atualizar a loja. Por favor, tente novamente mais tarde.";
            // error_log("Erro ao atualizar loja: " . $e->getMessage());
        }
    }
}

// Define o título da página.
$page_title = "Editar Loja: " . htmlspecialchars($nome_loja);
// Inclui o cabeçalho da página.
include 'templates/header.php';
?>

<main>
    <div class="form-container">
        <h2>Editar Loja: <?php echo htmlspecialchars($nome_loja); ?></h2>
        <p>Altere os dados da sua loja abaixo e salve as modificações.</p>

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

        <form action="editar_loja.php?id=<?php echo htmlspecialchars($loja_id); ?>" method="POST" enctype="multipart/form-data">
            <div class="form-grupo">
                <label for="foto_capa_loja">Foto de Capa da Loja (Opcional)</label>
                <?php if ($foto_capa_loja_atual && file_exists($foto_capa_loja_atual)): ?>
                    <div class="current-cover-photo">
                        <img src="<?php echo htmlspecialchars($foto_capa_loja_atual); ?>" alt="Foto de Capa da Loja Atual" class="img-preview">
                        <label>
                            <input type="checkbox" name="remover_foto_loja" value="on" id="remover_foto_loja"> Remover foto atual
                        </label>
                    </div>
                    <small class="form-text-muted">Envie uma nova foto para substituir a atual.</small>
                <?php else: ?>
                    <small class="form-text-muted">Ainda não há foto de capa para esta loja. Envie uma para destacá-la.</small>
                <?php endif; ?>
                <input type="file" id="foto_capa_loja" name="foto_capa_loja" accept="image/jpeg, image/png, image/gif">
                <small class="form-text-muted">Formatos permitidos: JPG, PNG, GIF. Máximo: 5MB.</small>
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

            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="dashboard.php" class="btn btn-secondary mt-1">Voltar ao Painel</a>
        </form>
    </div>
</main>

<?php
// Inclui o rodapé da página.
include 'templates/footer.php';
?>
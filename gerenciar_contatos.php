<?php
// Inicia a sessão para acessar o ID do comerciante logado e mensagens de feedback.
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

// Inicializa variáveis para mensagens e dados
$erros = [];
$sucesso = '';
$loja_nome = ''; // Para exibir o nome da loja no título

// Variáveis para o formulário de adicionar/editar
$contato_id_editar = null; // ID do contato sendo editado
$tipo_contato = '';
$valor_contato = '';

// Tipos de contato permitidos (deve refletir o ENUM da sua tabela `contatos`)
$tipos_permitidos = ['telefone', 'whatsapp', 'email', 'instagram', 'telegram'];

// 2. OBTER E VALIDAR O ID DA LOJA DA URL
if (!isset($_GET['loja_id']) || !filter_var($_GET['loja_id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    $_SESSION['mensagem_erro'] = "ID da loja inválido ou não fornecido para gerenciar contatos.";
    header("Location: dashboard.php");
    exit();
}
$loja_id = (int)$_GET['loja_id'];

// 3. VERIFICAR PROPRIEDADE DA LOJA E OBTER NOME DA LOJA
// Garante que a loja pertence ao comerciante logado.
try {
    $stmt_loja = $pdo->prepare("SELECT nome_loja, comerciante_id FROM lojas WHERE id = ?");
    $stmt_loja->execute([$loja_id]);
    $loja_info = $stmt_loja->fetch(PDO::FETCH_ASSOC);

    if (!$loja_info || $loja_info['comerciante_id'] != $comerciante_id) {
        $_SESSION['mensagem_erro'] = "Loja não encontrada ou você não tem permissão para gerenciar contatos para esta loja.";
        header("Location: dashboard.php");
        exit();
    }
    $loja_nome = $loja_info['nome_loja']; // Nome da loja para o título da página

} catch (PDOException $e) {
    $_SESSION['mensagem_erro'] = "Erro ao carregar informações da loja. Por favor, tente novamente.";
    header("Location: dashboard.php");
    exit();
    // error_log("Erro ao carregar loja em gerenciar_contatos.php: " . $e->getMessage());
}

// 4. PROCESSAMENTO DE AÇÕES (ADICIONAR/EDITAR/DELETAR CONTATO)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];

        // Ação de Adicionar/Editar Contato
        if ($acao == 'adicionar' || $acao == 'editar') {
            $tipo_contato = trim($_POST['tipo_contato']);
            $valor_contato = trim($_POST['valor_contato']);
            $contato_id_editar_post = filter_input(INPUT_POST, 'contato_id', FILTER_VALIDATE_INT); // Para edição

            // Validação dos dados do contato
            if (empty($tipo_contato) || !in_array($tipo_contato, $tipos_permitidos)) {
                $erros[] = "Tipo de contato inválido.";
            }
            if (empty($valor_contato)) {
                $erros[] = "O valor do contato é obrigatório.";
            }

            if (empty($erros)) {
                try {
                    if ($acao == 'adicionar') {
                        $stmt = $pdo->prepare(
                            "INSERT INTO contatos (loja_id, tipo_contato, valor_contato)
                             VALUES (?, ?, ?)"
                        );
                        $stmt->execute([$loja_id, $tipo_contato, $valor_contato]);
                        $sucesso = "Contato adicionado com sucesso!";
                    } elseif ($acao == 'editar') {
                        if ($contato_id_editar_post === false || $contato_id_editar_post === null) {
                            $erros[] = "ID do contato para edição inválido.";
                        } else {
                            // Validação extra: verifica se o contato realmente pertence à loja e ao comerciante
                            $stmt_check = $pdo->prepare(
                                "SELECT c.id FROM contatos c
                                 JOIN lojas l ON c.loja_id = l.id
                                 WHERE c.id = ? AND c.loja_id = ? AND l.comerciante_id = ?"
                            );
                            $stmt_check->execute([$contato_id_editar_post, $loja_id, $comerciante_id]);

                            if (!$stmt_check->fetch()) {
                                $erros[] = "Contato não encontrado ou você não tem permissão para editá-lo.";
                            } else {
                                $stmt = $pdo->prepare(
                                    "UPDATE contatos
                                     SET tipo_contato = ?, valor_contato = ?
                                     WHERE id = ? AND loja_id = ?"
                                );
                                $stmt->execute([$tipo_contato, $valor_contato, $contato_id_editar_post, $loja_id]);
                                if ($stmt->rowCount() > 0) {
                                    $sucesso = "Contato atualizado com sucesso!";
                                } else {
                                    $erros[] = "Nenhuma alteração feita no contato.";
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $erros[] = "Erro ao processar o contato. Por favor, tente novamente.";
                    // error_log("Erro ao gerenciar contato (Add/Edit): " . $e->getMessage());
                }
            }
        }
        // Ação de Deletar Contato
        elseif ($acao == 'deletar') {
            $contato_id_deletar = filter_input(INPUT_POST, 'contato_id_deletar', FILTER_VALIDATE_INT);

            if ($contato_id_deletar === false || $contato_id_deletar === null) {
                $erros[] = "ID do contato para exclusão inválido.";
            } else {
                try {
                    // Validação extra: verifica se o contato realmente pertence à loja e ao comerciante
                    $stmt_check = $pdo->prepare(
                        "SELECT c.id FROM contatos c
                         JOIN lojas l ON c.loja_id = l.id
                         WHERE c.id = ? AND c.loja_id = ? AND l.comerciante_id = ?"
                    );
                    $stmt_check->execute([$contato_id_deletar, $loja_id, $comerciante_id]);

                    if (!$stmt_check->fetch()) {
                        $erros[] = "Contato não encontrado ou você não tem permissão para deletá-lo.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM contatos WHERE id = ? AND loja_id = ?");
                        $stmt->execute([$contato_id_deletar, $loja_id]);
                        if ($stmt->rowCount() > 0) {
                            $sucesso = "Contato excluído com sucesso!";
                        } else {
                            $erros[] = "Erro ao excluir contato ou contato não encontrado.";
                        }
                    }
                } catch (PDOException $e) {
                    $erros[] = "Erro ao excluir o contato. Por favor, tente novamente.";
                    // error_log("Erro ao gerenciar contato (Delete): " . $e->getMessage());
                }
            }
        }
    }
    // Após qualquer POST, redireciona para limpar os dados do POST e evitar reenvio
    // E permite exibir a mensagem de sucesso/erro na próxima carga da página
    header("Location: gerenciar_contatos.php?loja_id=" . $loja_id . "&sucesso=" . urlencode($sucesso) . "&erros=" . urlencode(json_encode($erros)));
    exit();
}

// 5. LÓGICA PARA PRÉ-PREENCHER FORMULÁRIO EM CASO DE EDIÇÃO (GET)
// Se houver um 'edit_id' na URL, carrega os dados do contato para edição.
if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    $contato_id_editar = (int)$_GET['edit_id'];
    try {
        // Busca o contato E verifica se ele pertence à loja correta E ao comerciante logado.
        $stmt_contato_edit = $pdo->prepare(
            "SELECT c.id, c.tipo_contato, c.valor_contato FROM contatos c
             JOIN lojas l ON c.loja_id = l.id
             WHERE c.id = ? AND c.loja_id = ? AND l.comerciante_id = ?"
        );
        $stmt_contato_edit->execute([$contato_id_editar, $loja_id, $comerciante_id]);
        $contato_para_editar = $stmt_contato_edit->fetch(PDO::FETCH_ASSOC);

        if ($contato_para_editar) {
            $tipo_contato = $contato_para_editar['tipo_contato'];
            $valor_contato = $contato_para_editar['valor_contato'];
        } else {
            $_SESSION['mensagem_erro'] = "Contato para edição não encontrado ou você não tem permissão.";
            header("Location: gerenciar_contatos.php?loja_id=" . $loja_id);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem_erro'] = "Erro ao carregar contato para edição. Tente novamente.";
        header("Location: gerenciar_contatos.php?loja_id=" . $loja_id);
        exit();
    }
}

// Lógica para exibir mensagens passadas via GET (após redirecionamento POST)
if (isset($_GET['sucesso']) && !empty($_GET['sucesso'])) {
    $sucesso = urldecode($_GET['sucesso']);
}
if (isset($_GET['erros']) && !empty($_GET['erros'])) {
    $erros_json = urldecode($_GET['erros']);
    $erros_array = json_decode($erros_json, true);
    if (is_array($erros_array)) {
        $erros = array_merge($erros, $erros_array); // Junta com possíveis erros pré-existentes
    }
}


// 6. BUSCAR TODOS OS CONTATOS DA LOJA
$contatos = [];
try {
    $stmt_contatos = $pdo->prepare(
        "SELECT id, tipo_contato, valor_contato
         FROM contatos
         WHERE loja_id = ?
         ORDER BY tipo_contato ASC"
    );
    $stmt_contatos->execute([$loja_id]);
    $contatos = $stmt_contatos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erros[] = "Erro ao buscar contatos da loja para exibição. Por favor, tente novamente.";
    // error_log("Erro ao buscar contatos para exibição: " . $e->getMessage());
}

// Define o título da página.
$page_title = "Gerenciar Contatos - " . htmlspecialchars($loja_nome);
// Inclui o cabeçalho da página.
include 'templates/header.php';
?>

<main>
    <div class="dashboard-container">
        <h2>Gerenciar Contatos de: <?php echo htmlspecialchars($loja_nome); ?></h2>
        <p>Adicione, edite ou remova as informações de contato da sua loja.</p>

        <div class="dashboard-actions">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar ao Painel</a>
        </div>

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

        <h3><?php echo ($contato_id_editar ? 'Editar Contato' : 'Adicionar Novo Contato'); ?></h3>
        <div class="form-container form-contato-pequeno">
            <form action="gerenciar_contatos.php?loja_id=<?php echo htmlspecialchars($loja_id); ?>" method="POST">
                <?php if ($contato_id_editar): ?>
                    <input type="hidden" name="contato_id" value="<?php echo htmlspecialchars($contato_id_editar); ?>">
                <?php endif; ?>

                <div class="form-grupo">
                    <label for="tipo_contato">Tipo de Contato</label>
                    <select id="tipo_contato" name="tipo_contato" required>
                        <option value="">Selecione o Tipo</option>
                        <?php foreach ($tipos_permitidos as $tipo):
                            $selected = ($tipo == $tipo_contato) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars(ucfirst($tipo)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grupo">
                    <label for="valor_contato">Valor do Contato</label>
                    <input type="text" id="valor_contato" name="valor_contato" value="<?php echo htmlspecialchars($valor_contato); ?>" placeholder="Ex: (XX) XXXX-XXXX, @seuperfil, seu@email.com" required>
                </div>

                <button type="submit" name="acao" value="<?php echo ($contato_id_editar ? 'editar' : 'adicionar'); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-<?php echo ($contato_id_editar ? 'floppy-disk' : 'plus'); ?>"></i> <?php echo ($contato_id_editar ? 'Salvar Contato' : 'Adicionar Contato'); ?>
                </button>
                <?php if ($contato_id_editar): ?>
                    <a href="gerenciar_contatos.php?loja_id=<?php echo htmlspecialchars($loja_id); ?>" class="btn btn-secondary mt-1">
                        <i class="fa-solid fa-ban"></i> Cancelar Edição
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <h3>Contatos Cadastrados</h3>

        <?php if (!empty($contatos)): ?>
            <div class="contatos-listagem">
                <?php foreach ($contatos as $contato): ?>
                    <div class="contato-item">
                        <div class="contato-info">
                            <i class="fa-solid <?php
                                // Ícones baseados no tipo de contato
                                switch ($contato['tipo_contato']) {
                                    case 'telefone': echo 'fa-phone'; break;
                                    case 'whatsapp': echo 'fa-whatsapp'; break;
                                    case 'email': echo 'fa-envelope'; break;
                                    case 'instagram': echo 'fa-instagram'; break;
                                    case 'telegram': echo 'fa-telegram-plane'; break;
                                    default: echo 'fa-info-circle'; break; // Ícone padrão
                                }
                            ?>"></i>
                            <strong><?php echo htmlspecialchars(ucfirst($contato['tipo_contato'])); ?>:</strong>
                            <span><?php echo htmlspecialchars($contato['valor_contato']); ?></span>
                        </div>
                        <div class="contato-acoes">
                            <a href="gerenciar_contatos.php?loja_id=<?php echo htmlspecialchars($loja_id); ?>&edit_id=<?php echo htmlspecialchars($contato['id']); ?>" class="btn-acao editar" title="Editar Contato">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="gerenciar_contatos.php?loja_id=<?php echo htmlspecialchars($loja_id); ?>" method="POST" style="display:inline-block;">
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="contato_id_deletar" value="<?php echo htmlspecialchars($contato['id']); ?>">
                                <button type="submit" class="btn-acao deletar" title="Deletar Contato" onclick="return confirm('Tem certeza que deseja deletar este contato?');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="aviso-geral">
                <p>Nenhum contato cadastrado para esta loja ainda.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Inclui o rodapé da página.
include 'templates/footer.php';
?>
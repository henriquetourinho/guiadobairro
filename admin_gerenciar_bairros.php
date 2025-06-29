<?php
// Inicia a sessão.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// 1. PROTEÇÃO DE ACESSO
// Verifica se o administrador está logado.
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_nome = $_SESSION['admin_nome'];

// Inicializa variáveis para mensagens de feedback
$mensagem_sucesso = '';
$mensagem_erro = '';
$mensagem_aviso = '';

// Lógica para exibir mensagens passadas via sessão (após ações de POST)
if (isset($_SESSION['mensagem_sucesso_admin'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso_admin'];
    unset($_SESSION['mensagem_sucesso_admin']);
}
if (isset($_SESSION['mensagem_erro_admin'])) {
    $mensagem_erro = $_SESSION['mensagem_erro_admin'];
    unset($_SESSION['mensagem_erro_admin']);
}
if (isset($_SESSION['mensagem_aviso_admin'])) {
    $mensagem_aviso = $_SESSION['mensagem_aviso_admin'];
    unset($_SESSION['mensagem_aviso_admin']);
}

// Variáveis para o formulário de adicionar/editar
$bairro_id_editar = null;
$nome_bairro = '';

// 2. PROCESSAMENTO DE AÇÕES (ADICIONAR/EDITAR/DELETAR BAIRRO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // Ação de Adicionar/Editar Bairro
    if ($acao == 'adicionar' || $acao == 'editar') {
        $nome_bairro = trim($_POST['nome_bairro']);
        $bairro_id_editar_post = filter_input(INPUT_POST, 'bairro_id', FILTER_VALIDATE_INT); // Para edição

        if (empty($nome_bairro)) {
            $mensagem_erro = "O nome do bairro é obrigatório.";
        } else {
            try {
                // Verifica se o nome do bairro já existe (para evitar duplicatas, exceto na edição do próprio item)
                $stmt_check = $pdo->prepare("SELECT id FROM bairros_permitidos WHERE nome_bairro = ? AND id != ?");
                $stmt_check->execute([$nome_bairro, ($acao == 'editar' ? $bairro_id_editar_post : 0)]);
                if ($stmt_check->fetch()) {
                    $mensagem_erro = "Já existe um bairro com este nome.";
                } elseif ($acao == 'adicionar') {
                    $stmt = $pdo->prepare("INSERT INTO bairros_permitidos (nome_bairro) VALUES (?)");
                    $stmt->execute([$nome_bairro]);
                    $_SESSION['mensagem_sucesso_admin'] = "Bairro '{$nome_bairro}' adicionado com sucesso!";
                } elseif ($acao == 'editar') {
                    if ($bairro_id_editar_post === false || $bairro_id_editar_post === null) {
                        $mensagem_erro = "ID do bairro para edição inválido.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE bairros_permitidos SET nome_bairro = ? WHERE id = ?");
                        $stmt->execute([$nome_bairro, $bairro_id_editar_post]);
                        if ($stmt->rowCount() > 0) {
                            $_SESSION['mensagem_sucesso_admin'] = "Bairro '{$nome_bairro}' atualizado com sucesso!";
                        } else {
                            $_SESSION['mensagem_aviso_admin'] = "Nenhuma alteração feita no bairro.";
                        }
                    }
                }
            } catch (PDOException $e) {
                $mensagem_erro = "Erro ao processar o bairro: " . $e->getMessage();
                // error_log("Erro em admin_gerenciar_bairros (Add/Edit): " . $e->getMessage());
            }
        }
    }
    // Ação de Deletar Bairro
    elseif ($acao == 'deletar') {
        $bairro_id_deletar = filter_input(INPUT_POST, 'bairro_id_deletar', FILTER_VALIDATE_INT);

        if ($bairro_id_deletar === false || $bairro_id_deletar === null) {
            $mensagem_erro = "ID do bairro para exclusão inválido.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM bairros_permitidos WHERE id = ?");
                $stmt->execute([$bairro_id_deletar]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['mensagem_sucesso_admin'] = "Bairro excluído com sucesso!";
                } else {
                    $_SESSION['mensagem_aviso_admin'] = "Erro ao excluir bairro ou ID não encontrado.";
                }
            } catch (PDOException $e) {
                // Erro 23000 (SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION) indica FK constraint
                if ($e->getCode() == '23000') {
                    $_SESSION['mensagem_erro_admin'] = "Não foi possível excluir o bairro. Existem lojas vinculadas a ele.";
                } else {
                    $_SESSION['mensagem_erro_admin'] = "Erro ao excluir o bairro: " . $e->getMessage();
                }
                // error_log("Erro em admin_gerenciar_bairros (Delete): " . $e->getMessage());
            }
        }
    }
    // Redireciona para evitar reenvio de formulário e mostrar a mensagem
    header("Location: admin_gerenciar_bairros.php");
    exit();
}

// 4. LÓGICA PARA PRÉ-PREENCHER FORMULÁRIO EM CASO DE EDIÇÃO (GET)
if (isset($_GET['edit_id']) && filter_var($_GET['edit_id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    $bairro_id_editar = (int)$_GET['edit_id'];
    try {
        $stmt_bairro_edit = $pdo->prepare("SELECT nome_bairro FROM bairros_permitidos WHERE id = ?");
        $stmt_bairro_edit->execute([$bairro_id_editar]);
        $bairro_para_editar = $stmt_bairro_edit->fetch(PDO::FETCH_ASSOC);

        if ($bairro_para_editar) {
            $nome_bairro = $bairro_para_editar['nome_bairro'];
        } else {
            $_SESSION['mensagem_erro_admin'] = "Bairro para edição não encontrado.";
            header("Location: admin_gerenciar_bairros.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem_erro_admin'] = "Erro ao carregar bairro para edição. Tente novamente.";
        header("Location: admin_gerenciar_bairros.php");
        exit();
        // error_log("Erro ao carregar bairro em admin_gerenciar_bairros (GET): " . $e->getMessage());
    }
}

// 5. BUSCA DE TODOS OS BAIRROS PARA EXIBIÇÃO
$bairros = [];
try {
    $stmt_bairros_list = $pdo->query(
        "SELECT id, nome_bairro FROM bairros_permitidos ORDER BY nome_bairro ASC"
    );
    $bairros = $stmt_bairros_list->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar lista de bairros: " . $e->getMessage();
    // error_log("Erro em admin_gerenciar_bairros (listagem): " . $e->getMessage());
}

$page_title = "Gerenciar Bairros - Admin";
include 'templates/header.php';
?>

<main>
    <div class="dashboard-container">
        <h2>Gerenciar Bairros Permitidos</h2>
        <p>Adicione, edite ou remova os bairros em que as lojas podem ser cadastradas.</p>

        <div class="dashboard-actions">
            <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar ao Painel Admin</a>
        </div>

        <?php
        // Exibe mensagens de feedback
        if (!empty($mensagem_sucesso)): ?> <div class="mensagem sucesso"><p><?php echo htmlspecialchars($mensagem_sucesso); ?></p></div> <?php endif;
        if (!empty($mensagem_erro)):    ?> <div class="mensagem erro"><p><?php echo htmlspecialchars($mensagem_erro); ?></p></div>       <?php endif;
        if (!empty($mensagem_aviso)):   ?> <div class="mensagem aviso"><p><?php echo htmlspecialchars($mensagem_aviso); ?></p></div>      <?php endif;
        ?>

        <h3><?php echo ($bairro_id_editar ? 'Editar Bairro' : 'Adicionar Novo Bairro'); ?></h3>
        <div class="form-container form-contato-pequeno"> <form action="admin_gerenciar_bairros.php" method="POST">
                <?php if ($bairro_id_editar): ?>
                    <input type="hidden" name="bairro_id" value="<?php echo htmlspecialchars($bairro_id_editar); ?>">
                <?php endif; ?>

                <div class="form-grupo">
                    <label for="nome_bairro">Nome do Bairro</label>
                    <input type="text" id="nome_bairro" name="nome_bairro" value="<?php echo htmlspecialchars($nome_bairro); ?>" required>
                </div>

                <button type="submit" name="acao" value="<?php echo ($bairro_id_editar ? 'editar' : 'adicionar'); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-<?php echo ($bairro_id_editar ? 'floppy-disk' : 'plus'); ?>"></i> <?php echo ($bairro_id_editar ? 'Salvar Bairro' : 'Adicionar Bairro'); ?>
                </button>
                <?php if ($bairro_id_editar): ?>
                    <a href="admin_gerenciar_bairros.php" class="btn btn-secondary mt-1">
                        <i class="fa-solid fa-ban"></i> Cancelar Edição
                    </a>
                <?php endif; ?>
            </form>
        </div>


        <h3>Bairros Cadastrados</h3>

        <?php if (!empty($bairros)): ?>
            <div class="tabela-responsiva">
                <table class="tabela-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome do Bairro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bairros as $bairro): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bairro['id']); ?></td>
                                <td><?php echo htmlspecialchars($bairro['nome_bairro']); ?></td>
                                <td class="admin-acoes-coluna">
                                    <a href="admin_gerenciar_bairros.php?edit_id=<?php echo htmlspecialchars($bairro['id']); ?>" class="btn-acao editar" title="Editar Bairro">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="admin_gerenciar_bairros.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="acao" value="deletar">
                                        <input type="hidden" name="bairro_id_deletar" value="<?php echo htmlspecialchars($bairro['id']); ?>">
                                        <button type="submit" class="btn-acao deletar" title="Remover Bairro" onclick="return confirm('Tem certeza que deseja remover o bairro \'<?php echo htmlspecialchars($bairro['nome_bairro']); ?>\'? Isso falhará se houver lojas vinculadas a ele.');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="aviso-geral">
                <p>Nenhum bairro cadastrado no sistema. Por favor, adicione alguns.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'templates/footer.php'; ?>
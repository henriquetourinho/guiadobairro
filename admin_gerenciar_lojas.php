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

// 2. PROCESSAMENTO DE AÇÕES (ALTERAR STATUS DA LOJA)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && isset($_POST['loja_id'])) {
    $loja_id_acao = filter_input(INPUT_POST, 'loja_id', FILTER_VALIDATE_INT);
    $acao = $_POST['acao']; // Ex: 'publicar', 'pendenciar', 'arquivar', 'remover' (exclusão permanente)
    $novo_status = null;

    // Define o novo status baseado na ação
    switch ($acao) {
        case 'publicar':
            $novo_status = 'publicado';
            break;
        case 'pendenciar':
            $novo_status = 'pendente';
            break;
        case 'revisao':
            $novo_status = 'revisao';
            break;
        case 'arquivar':
            $novo_status = 'arquivado';
            break;
        case 'remover': // Ação de exclusão permanente
            break;
        default:
            $mensagem_erro = "Ação inválida para a loja.";
            break;
    }

    if ($loja_id_acao === false || $loja_id_acao === null) {
        $mensagem_erro = "ID da loja inválido para a ação.";
    } elseif (!empty($mensagem_erro)) {
        // Erro já definido pelo switch/validação inicial
    } else {
        try {
            // Se a ação for REMOVER, executa DELETE
            if ($acao == 'remover') {
                $stmt_delete = $pdo->prepare("DELETE FROM lojas WHERE id = ?");
                $stmt_delete->execute([$loja_id_acao]);
                if ($stmt_delete->rowCount() > 0) {
                    $_SESSION['mensagem_sucesso_admin'] = "Loja removida permanentemente com sucesso!";
                } else {
                    $_SESSION['mensagem_aviso_admin'] = "Nenhuma loja foi removida. ID não encontrado?";
                }
            } else { // Se a ação for de alteração de status
                $stmt_update = $pdo->prepare(
                    "UPDATE lojas
                     SET status_publicacao = ?, data_atualizacao = NOW()
                     WHERE id = ?"
                );
                $stmt_update->execute([$novo_status, $loja_id_acao]);
                if ($stmt_update->rowCount() > 0) {
                    $_SESSION['mensagem_sucesso_admin'] = "Status da loja atualizado para '" . htmlspecialchars($novo_status) . "' com sucesso!";
                } else {
                    $_SESSION['mensagem_aviso_admin'] = "Status da loja não foi alterado (talvez já estivesse neste status).";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem_erro_admin'] = "Erro ao processar ação para a loja: " . $e->getMessage();
            // error_log("Erro em admin_gerenciar_lojas: " . $e->getMessage());
        }
    }
    // Redireciona para evitar reenvio de formulário e mostrar a mensagem
    header("Location: admin_gerenciar_lojas.php");
    exit();
}

// 3. BUSCA DE TODAS AS LOJAS PARA EXIBIÇÃO
$lojas = [];
try {
    $stmt_lojas = $pdo->query(
        "SELECT
            l.id,
            l.nome_loja,
            l.descricao,
            l.status_publicacao,
            l.data_criacao,
            l.data_atualizacao,
            c.nome_categoria,
            bp.nome_bairro,
            co.nome_responsavel,
            co.email,
            co.cnpj_cpf
        FROM lojas l
        JOIN categorias c ON l.categoria_id = c.id
        LEFT JOIN bairros_permitidos bp ON l.bairro_id = bp.id
        JOIN comerciantes co ON l.comerciante_id = co.id
        ORDER BY l.data_criacao DESC" // Ordena pelas mais recentes primeiro
    );
    $lojas = $stmt_lojas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar lista de lojas: " . $e->getMessage();
    // error_log("Erro em admin_gerenciar_lojas (listagem): " . $e->getMessage());
}

$page_title = "Gerenciar Lojas - Admin";
include 'templates/header.php';
?>

<main>
    <div class="dashboard-container">
        <h2>Gerenciar Lojas</h2>
        <p>Ajuste o status de publicação e gerencie os detalhes de todas as lojas.</p>

        <div class="dashboard-actions">
            <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar ao Painel Admin</a>
        </div>

        <?php
        // Exibe mensagens de feedback
        if (!empty($mensagem_sucesso)): ?> <div class="mensagem sucesso"><p><?php echo htmlspecialchars($mensagem_sucesso); ?></p></div> <?php endif;
        if (!empty($mensagem_erro)):    ?> <div class="mensagem erro"><p><?php echo htmlspecialchars($mensagem_erro); ?></p></div>       <?php endif;
        if (!empty($mensagem_aviso)):   ?> <div class="mensagem aviso"><p><?php echo htmlspecialchars($mensagem_aviso); ?></p></div>      <?php endif;
        ?>

        <h3>Todas as Lojas Cadastradas</h3>

        <?php if (!empty($lojas)): ?>
            <div class="tabela-responsiva">
                <table class="tabela-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Loja</th>
                            <th>Categoria</th>
                            <th>Bairro</th>
                            <th>Comerciante</th>
                            <th>CNPJ/CPF</th>
                            <th>Status</th>
                            <th>Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lojas as $loja): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($loja['id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($loja['nome_loja']); ?></strong><br>
                                    <small><?php echo htmlspecialchars(substr($loja['descricao'], 0, 50)) . '...'; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($loja['nome_categoria']); ?></td>
                                <td><?php echo htmlspecialchars($loja['nome_bairro'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($loja['nome_responsavel']); ?><br>
                                    <small><?php echo htmlspecialchars($loja['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($loja['cnpj_cpf']); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($loja['status_publicacao']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($loja['status_publicacao'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($loja['data_criacao'])); ?></td>
                                <td class="admin-acoes-coluna">
                                    <a href="loja.php?id=<?php echo htmlspecialchars($loja['id']); ?>" target="_blank" class="btn-acao visualizar" title="Ver Loja no Guia">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="editar_loja.php?id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn-acao editar" title="Editar Detalhes">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    <form action="admin_gerenciar_lojas.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="loja_id" value="<?php echo htmlspecialchars($loja['id']); ?>">
                                        <?php if ($loja['status_publicacao'] !== 'publicado'): ?>
                                            <button type="submit" name="acao" value="publicar" class="btn-acao btn-success" title="Publicar Loja">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($loja['status_publicacao'] !== 'pendente'): ?>
                                            <button type="submit" name="acao" value="pendenciar" class="btn-acao btn-warning" title="Marcar como Pendente">
                                                <i class="fa-solid fa-hourglass-half"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($loja['status_publicacao'] !== 'revisao'): ?>
                                            <button type="submit" name="acao" value="revisao" class="btn-acao btn-info" title="Marcar para Revisão">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($loja['status_publicacao'] !== 'arquivado'): ?>
                                            <button type="submit" name="acao" value="arquivar" class="btn-acao btn-secondary" title="Arquivar Loja">
                                                <i class="fa-solid fa-box-archive"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="submit" name="acao" value="remover" class="btn-acao btn-danger" title="Remover Loja Permanentemente" onclick="return confirm('ATENÇÃO: Tem certeza que deseja REMOVER esta loja permanentemente? Esta ação é irreversível e removerá todos os contatos e avaliações associadas!');">
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
                <p>Nenhuma loja encontrada no sistema.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'templates/footer.php'; ?>
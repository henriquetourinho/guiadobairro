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

// 2. PROCESSAMENTO DE AÇÕES (ALTERAR STATUS DA AVALIAÇÃO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && isset($_POST['avaliacao_id'])) {
    $avaliacao_id_acao = filter_input(INPUT_POST, 'avaliacao_id', FILTER_VALIDATE_INT);
    $acao = $_POST['acao']; // Ex: 'aprovar', 'rejeitar'

    $novo_status = null;
    switch ($acao) {
        case 'aprovar':
            $novo_status = 'aprovada';
            break;
        case 'rejeitar':
            $novo_status = 'rejeitada';
            break;
        default:
            $mensagem_erro = "Ação inválida para a avaliação.";
            break;
    }

    if ($avaliacao_id_acao === false || $avaliacao_id_acao === null) {
        $mensagem_erro = "ID da avaliação inválido para a ação.";
    } elseif (!empty($mensagem_erro)) {
        // Erro já definido pelo switch/validação inicial
    } else {
        try {
            $stmt_update = $pdo->prepare(
                "UPDATE avaliacoes
                 SET status_avaliacao = ?
                 WHERE id = ?"
            );
            $stmt_update->execute([$novo_status, $avaliacao_id_acao]);
            if ($stmt_update->rowCount() > 0) {
                $_SESSION['mensagem_sucesso_admin'] = "Status da avaliação atualizado para '" . htmlspecialchars($novo_status) . "' com sucesso!";
            } else {
                $_SESSION['mensagem_aviso_admin'] = "Status da avaliação não foi alterado (talvez já estivesse neste status).";
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem_erro_admin'] = "Erro ao processar ação para a avaliação: " . $e->getMessage();
            // error_log("Erro em admin_gerenciar_avaliacoes: " . $e->getMessage());
        }
    }
    // Redireciona para evitar reenvio de formulário e mostrar a mensagem
    header("Location: admin_gerenciar_avaliacoes.php");
    exit();
}

// 3. BUSCA DE TODAS AS AVALIAÇÕES PARA EXIBIÇÃO
$avaliacoes = [];
try {
    $stmt_avaliacoes = $pdo->query(
        "SELECT
            a.id,
            a.nome_avaliador,
            a.nota,
            a.comentario,
            a.data_avaliacao,
            a.status_avaliacao,
            l.nome_loja,
            l.id as loja_id_fk
        FROM avaliacoes a
        JOIN lojas l ON a.loja_id = l.id
        ORDER BY a.data_avaliacao DESC" // Ordena pelas mais recentes primeiro
    );
    $avaliacoes = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar lista de avaliações: " . $e->getMessage();
    // error_log("Erro em admin_gerenciar_avaliacoes (listagem): " . $e->getMessage());
}

$page_title = "Gerenciar Avaliações - Admin";
include 'templates/header.php';
?>

<main>
    <div class="dashboard-container">
        <h2>Gerenciar Avaliações</h2>
        <p>Aprove ou rejeite as avaliações enviadas pelos clientes.</p>

        <div class="dashboard-actions">
            <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar ao Painel Admin</a>
        </div>

        <?php
        // Exibe mensagens de feedback
        if (!empty($mensagem_sucesso)): ?> <div class="mensagem sucesso"><p><?php echo htmlspecialchars($mensagem_sucesso); ?></p></div> <?php endif;
        if (!empty($mensagem_erro)):    ?> <div class="mensagem erro"><p><?php echo htmlspecialchars($mensagem_erro); ?></p></div>       <?php endif;
        if (!empty($mensagem_aviso)):   ?> <div class="mensagem aviso"><p><?php echo htmlspecialchars($mensagem_aviso); ?></p></div>      <?php endif;
        ?>

        <h3>Todas as Avaliações</h3>

        <?php if (!empty($avaliacoes)): ?>
            <div class="tabela-responsiva">
                <table class="tabela-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Avaliador</th>
                            <th>Nota</th>
                            <th>Comentário</th>
                            <th>Loja</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avaliacoes as $avaliacao): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($avaliacao['id']); ?></td>
                                <td><?php echo htmlspecialchars($avaliacao['nome_avaliador']); ?></td>
                                <td>
                                    <span class="nota-tabela">
                                        <?php echo htmlspecialchars($avaliacao['nota']); ?> <i class="fa-solid fa-star"></i>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(substr($avaliacao['comentario'], 0, 70)); ?>...
                                    </td>
                                <td>
                                    <a href="loja.php?id=<?php echo htmlspecialchars($avaliacao['loja_id_fk']); ?>" target="_blank" title="Ver Loja">
                                        <?php echo htmlspecialchars($avaliacao['nome_loja']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($avaliacao['data_avaliacao'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($avaliacao['status_avaliacao']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($avaliacao['status_avaliacao'])); ?>
                                    </span>
                                </td>
                                <td class="admin-acoes-coluna">
                                    <form action="admin_gerenciar_avaliacoes.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="avaliacao_id" value="<?php echo htmlspecialchars($avaliacao['id']); ?>">
                                        <?php if ($avaliacao['status_avaliacao'] === 'pendente'): ?>
                                            <button type="submit" name="acao" value="aprovar" class="btn-acao btn-success" title="Aprovar Avaliação">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button type="submit" name="acao" value="rejeitar" class="btn-acao btn-danger" title="Rejeitar Avaliação">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        <?php elseif ($avaliacao['status_avaliacao'] === 'rejeitada'): ?>
                                            <button type="submit" name="acao" value="aprovar" class="btn-acao btn-success" title="Aprovar Avaliação">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        <?php elseif ($avaliacao['status_avaliacao'] === 'aprovada'): ?>
                                            <button type="submit" name="acao" value="rejeitar" class="btn-acao btn-danger" title="Rejeitar Avaliação">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="aviso-geral">
                <p>Nenhuma avaliação encontrada no sistema.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'templates/footer.php'; ?>
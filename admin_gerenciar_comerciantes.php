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

// 2. PROCESSAMENTO DE AÇÕES (ALTERAR STATUS DO COMERCIANTE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && isset($_POST['comerciante_id'])) {
    $comerciante_id_acao = filter_input(INPUT_POST, 'comerciante_id', FILTER_VALIDATE_INT);
    $acao = $_POST['acao']; // Ex: 'ativar', 'pendenciar', 'inativar', 'remover' (exclusão permanente)
    $novo_status = null;

    // Define o novo status baseado na ação
    switch ($acao) {
        case 'ativar':
            $novo_status = 'ativo';
            break;
        case 'pendenciar':
            $novo_status = 'pendente';
            break;
        case 'inativar':
            $novo_status = 'inativo';
            break;
        case 'remover': // Ação de exclusão permanente
            break;
        default:
            $mensagem_erro = "Ação inválida para o comerciante.";
            break;
    }

    if ($comerciante_id_acao === false || $comerciante_id_acao === null) {
        $mensagem_erro = "ID do comerciante inválido para a ação.";
    } elseif (!empty($mensagem_erro)) {
        // Erro já definido pelo switch/validação inicial
    } else {
        try {
            // Se a ação for REMOVER, executa DELETE
            if ($acao == 'remover') {
                // ATENÇÃO: Se `lojas` tem FOREIGN KEY `ON DELETE CASCADE` para `comerciantes`,
                // todas as lojas e seus dados (contatos, avaliações) desse comerciante serão apagados.
                $stmt_delete = $pdo->prepare("DELETE FROM comerciantes WHERE id = ?");
                $stmt_delete->execute([$comerciante_id_acao]);
                if ($stmt_delete->rowCount() > 0) {
                    $_SESSION['mensagem_sucesso_admin'] = "Comerciante removido permanentemente com sucesso!";
                } else {
                    $_SESSION['mensagem_aviso_admin'] = "Nenhum comerciante foi removido. ID não encontrado?";
                }
            } else { // Se a ação for de alteração de status
                $stmt_update = $pdo->prepare(
                    "UPDATE comerciantes
                     SET status_conta = ?
                     WHERE id = ?"
                );
                $stmt_update->execute([$novo_status, $comerciante_id_acao]);
                if ($stmt_update->rowCount() > 0) {
                    $_SESSION['mensagem_sucesso_admin'] = "Status do comerciante atualizado para '" . htmlspecialchars($novo_status) . "' com sucesso!";
                } else {
                    $_SESSION['mensagem_aviso_admin'] = "Status do comerciante não foi alterado (talvez já estivesse neste status).";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem_erro_admin'] = "Erro ao processar ação para o comerciante: " . $e->getMessage();
            // error_log("Erro em admin_gerenciar_comerciantes: " . $e->getMessage());
        }
    }
    // Redireciona para evitar reenvio de formulário e mostrar a mensagem
    header("Location: admin_gerenciar_comerciantes.php");
    exit();
}

// 3. BUSCA DE TODOS OS COMERCIANTES PARA EXIBIÇÃO
$comerciantes = [];
try {
    $stmt_comerciantes = $pdo->query(
        "SELECT
            id,
            nome_responsavel,
            email,
            cnpj_cpf,
            data_cadastro,
            status_conta
        FROM comerciantes
        ORDER BY data_cadastro DESC" // Ordena pelos mais recentes primeiro
    );
    $comerciantes = $stmt_comerciantes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar lista de comerciantes: " . $e->getMessage();
    // error_log("Erro em admin_gerenciar_comerciantes (listagem): " . $e->getMessage());
}

$page_title = "Gerenciar Comerciantes - Admin";
include 'templates/header.php';
?>

<main>
    <div class="dashboard-container">
        <h2>Gerenciar Comerciantes</h2>
        <p>Ajuste o status das contas de comerciantes e visualize seus dados.</p>

        <div class="dashboard-actions">
            <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar ao Painel Admin</a>
        </div>

        <?php
        // Exibe mensagens de feedback
        if (!empty($mensagem_sucesso)): ?> <div class="mensagem sucesso"><p><?php echo htmlspecialchars($mensagem_sucesso); ?></p></div> <?php endif;
        if (!empty($mensagem_erro)):    ?> <div class="mensagem erro"><p><?php echo htmlspecialchars($mensagem_erro); ?></p></div>       <?php endif;
        if (!empty($mensagem_aviso)):   ?> <div class="mensagem aviso"><p><?php echo htmlspecialchars($mensagem_aviso); ?></p></div>      <?php endif;
        ?>

        <h3>Todas as Contas de Comerciantes</h3>

        <?php if (!empty($comerciantes)): ?>
            <div class="tabela-responsiva">
                <table class="tabela-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>CNPJ/CPF</th>
                            <th>Cadastro</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comerciantes as $comerciante): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($comerciante['id']); ?></td>
                                <td><?php echo htmlspecialchars($comerciante['nome_responsavel']); ?></td>
                                <td><?php echo htmlspecialchars($comerciante['email']); ?></td>
                                <td><?php echo htmlspecialchars($comerciante['cnpj_cpf']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($comerciante['data_cadastro'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($comerciante['status_conta']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($comerciante['status_conta'])); ?>
                                    </span>
                                </td>
                                <td class="admin-acoes-coluna">
                                    <form action="admin_gerenciar_comerciantes.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="comerciante_id" value="<?php echo htmlspecialchars($comerciante['id']); ?>">
                                        <?php if ($comerciante['status_conta'] !== 'ativo'): ?>
                                            <button type="submit" name="acao" value="ativar" class="btn-acao btn-success" title="Ativar Conta">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($comerciante['status_conta'] !== 'pendente'): ?>
                                            <button type="submit" name="acao" value="pendenciar" class="btn-acao btn-warning" title="Marcar como Pendente">
                                                <i class="fa-solid fa-hourglass-half"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($comerciante['status_conta'] !== 'inativo'): ?>
                                            <button type="submit" name="acao" value="inativar" class="btn-acao btn-danger" title="Inativar Conta">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="submit" name="acao" value="remover" class="btn-acao btn-dark" title="Remover Comerciante Permanentemente" onclick="return confirm('ATENÇÃO: Tem certeza que deseja REMOVER este comerciante e TODAS as suas lojas, contatos e avaliações associadas? Esta ação é irreversível!');">
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
                <p>Nenhum comerciante encontrado no sistema.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'templates/footer.php'; ?>
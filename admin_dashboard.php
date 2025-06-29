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
$admin_nivel = $_SESSION['admin_nivel']; // Pode ser usado para controlar acesso a certas funcionalidades

// Inicializa variáveis para mensagens de feedback
$mensagem_sucesso = '';
$mensagem_erro = '';
$mensagem_aviso = '';

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

// 2. BUSCA DE ESTATÍSTICAS/SOLICITAÇÕES PENDENTES
$comerciantes_pendentes = 0;
$lojas_pendentes = 0;
$avaliacoes_pendentes = 0;

try {
    $stmt_cp = $pdo->query("SELECT COUNT(id) FROM comerciantes WHERE status_conta = 'pendente'");
    $comerciantes_pendentes = $stmt_cp->fetchColumn();

    $stmt_lp = $pdo->query("SELECT COUNT(id) FROM lojas WHERE status_publicacao = 'pendente'");
    $lojas_pendentes = $stmt_lp->fetchColumn();

    $stmt_ap = $pdo->query("SELECT COUNT(id) FROM avaliacoes WHERE status_avaliacao = 'pendente'");
    $avaliacoes_pendentes = $stmt_ap->fetchColumn();

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar estatísticas do painel. Tente novamente.";
    // error_log("Erro em admin_dashboard stats: " . $e->getMessage());
}


$page_title = "Painel Administrativo";
include 'templates/header.php'; // Reutiliza o header existente
?>

<main>
    <div class="dashboard-container">
        <h2>Bem-vindo(a), <?php echo htmlspecialchars($admin_nome); ?> (Administrador)!</h2>
        <p>Aqui você pode gerenciar os cadastros, lojas e avaliações do Guia do Bairro.</p>

        <div class="dashboard-actions">
            <a href="admin_logout.php" class="btn btn-secondary"><i class="fa-solid fa-right-from-bracket"></i> Sair do Admin</a>
        </div>

        <?php
        if (!empty($mensagem_sucesso)): ?> <div class="mensagem sucesso"><p><?php echo htmlspecialchars($mensagem_sucesso); ?></p></div> <?php endif;
        if (!empty($mensagem_erro)):    ?> <div class="mensagem erro"><p><?php echo htmlspecialchars($mensagem_erro); ?></p></div>       <?php endif;
        if (!empty($mensagem_aviso)):   ?> <div class="mensagem aviso"><p><?php echo htmlspecialchars($mensagem_aviso); ?></p></div>      <?php endif;
        ?>

        <h3>Resumo das Solicitações Pendentes</h3>
        <div class="summary-cards-container">
            <div class="summary-card">
                <h4>Comerciantes Pendentes</h4>
                <p class="count"><?php echo $comerciantes_pendentes; ?></p>
                <a href="admin_gerenciar_comerciantes.php" class="btn btn-primary btn-sm">Gerenciar <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="summary-card">
                <h4>Lojas Pendentes</h4>
                <p class="count"><?php echo $lojas_pendentes; ?></p>
                <a href="admin_gerenciar_lojas.php" class="btn btn-primary btn-sm">Gerenciar <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="summary-card">
                <h4>Avaliações Pendentes</h4>
                <p class="count"><?php echo $avaliacoes_pendentes; ?></p>
                <a href="admin_gerenciar_avaliacoes.php" class="btn btn-primary btn-sm">Gerenciar <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>

        <h3>Ações Administrativas</h3>
        <div class="admin-actions-grid">
            <a href="admin_gerenciar_comerciantes.php" class="admin-action-item">
                <i class="fa-solid fa-users"></i>
                <span>Gerenciar Comerciantes</span>
            </a>
            <a href="admin_gerenciar_lojas.php" class="admin-action-item">
                <i class="fa-solid fa-store-alt"></i>
                <span>Gerenciar Lojas</span>
            </a>
            <a href="admin_gerenciar_avaliacoes.php" class="admin-action-item">
                <i class="fa-solid fa-comments"></i>
                <span>Gerenciar Avaliações</span>
            </a>
            <a href="admin_gerenciar_categorias.php" class="admin-action-item">
                <i class="fa-solid fa-folder-open"></i>
                <span>Gerenciar Categorias</span>
            </a>
            <a href="admin_gerenciar_bairros.php" class="admin-action-item">
                <i class="fa-solid fa-location-dot"></i>
                <span>Gerenciar Bairros</span>
            </a>
        </div>

    </div>
</main>

<?php include 'templates/footer.php'; // Reutiliza o footer existente ?>
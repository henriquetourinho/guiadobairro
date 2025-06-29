<?php
// Inicia a sessão. É crucial para acessar as variáveis de sessão como comerciante_id.
session_start();

// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// 1. PROTEÇÃO DE ACESSO
// Verifica se o usuário (comerciante) está logado.
// Se 'comerciante_id' não estiver definido na sessão, redireciona para a página de login.
if (!isset($_SESSION['comerciante_id'])) {
    header("Location: login.php");
    exit(); // Encerra o script para garantir que o redirecionamento ocorra.
}

// Obtém o ID e o nome do comerciante da sessão para uso na página.
$comerciante_id = $_SESSION['comerciante_id'];
$comerciante_nome = $_SESSION['comerciante_nome'];

// 2. BUSCA DAS LOJAS DO COMERCIANTE LOGADO
// Prepara a consulta para selecionar todas as lojas que pertencem a este comerciante.
// Também traz o nome da categoria para exibição.
try {
    $stmt_lojas = $pdo->prepare(
        "SELECT l.id, l.nome_loja, l.status_publicacao, c.nome_categoria
         FROM lojas l
         JOIN categorias c ON l.categoria_id = c.id
         WHERE l.comerciante_id = ?
         ORDER BY l.nome_loja ASC"
    );
    $stmt_lojas->execute([$comerciante_id]); // Executa a consulta com o ID do comerciante.
    $lojas_do_comerciante = $stmt_lojas->fetchAll(PDO::FETCH_ASSOC); // Obtém todas as lojas.
} catch (PDOException $e) {
    // Em caso de erro na consulta, exibe uma mensagem amigável.
    // Em produção, você logaria este erro: error_log($e->getMessage());
    die("Erro ao buscar suas lojas: " . $e->getMessage());
}

// Define o título da página para ser usado no header.
$page_title = "Painel do Comerciante";
// Inclui o cabeçalho da página.
include 'templates/header.php';
?>

<main>
    <div class="dashboard-container">
        <h2>Bem-vindo(a), <?php echo htmlspecialchars($comerciante_nome); ?>!</h2>
        <p>Aqui você pode gerenciar suas lojas e adicionar novos estabelecimentos ao guia.</p>

        <div class="dashboard-actions">
            <a href="adicionar_loja.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Adicionar Nova Loja
            </a>
            <a href="logout.php" class="btn btn-secondary">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a>
        </div>

        <h3>Minhas Lojas Cadastradas</h3>

        <?php
        // Verifica se o comerciante possui lojas cadastradas.
        if (!empty($lojas_do_comerciante)):
        ?>
            <div class="lojas-listagem">
                <?php foreach ($lojas_do_comerciante as $loja): ?>
                    <div class="loja-item">
                        <div class="loja-info">
                            <h4><?php echo htmlspecialchars($loja['nome_loja']); ?></h4>
                            <p>Categoria: <span><?php echo htmlspecialchars($loja['nome_categoria']); ?></span></p>
                            <p>Status: <span class="status-<?php echo strtolower($loja['status_publicacao']); ?>"><?php echo htmlspecialchars(ucfirst($loja['status_publicacao'])); ?></span></p>
                        </div>
                        <div class="loja-acoes">
                            <a href="editar_loja.php?id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn-acao editar" title="Editar Loja">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="loja.php?id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn-acao visualizar" title="Visualizar no Guia" target="_blank">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="gerenciar_contatos.php?loja_id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn-acao contatos" title="Gerenciar Contatos">
                                <i class="fa-solid fa-phone"></i>
                            </a>
                             <a href="gerenciar_avaliacoes.php?loja_id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn-acao avaliacoes" title="Gerenciar Avaliações">
                                <i class="fa-solid fa-comment-dots"></i>
                            </a>
                            <a href="deletar_loja.php?id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn-acao deletar" title="Deletar Loja" onclick="return confirm('Tem certeza que deseja deletar esta loja? Esta ação é irreversível.');">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="aviso-geral">
                <p>Você ainda não cadastrou nenhuma loja. <a href="adicionar_loja.php">Clique aqui para adicionar sua primeira loja!</a></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Inclui o rodapé da página.
include 'templates/footer.php';
?>
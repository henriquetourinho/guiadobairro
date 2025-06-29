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

// 2. OBTER E VALIDAR O ID DA LOJA DA URL
if (!isset($_GET['loja_id']) || !filter_var($_GET['loja_id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    $_SESSION['mensagem_erro'] = "ID da loja inválido ou não fornecido para gerenciar avaliações.";
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
        $_SESSION['mensagem_erro'] = "Loja não encontrada ou você não tem permissão para gerenciar avaliações para esta loja.";
        header("Location: dashboard.php");
        exit();
    }
    $loja_nome = $loja_info['nome_loja']; // Nome da loja para o título da página

} catch (PDOException $e) {
    $_SESSION['mensagem_erro'] = "Erro ao carregar informações da loja. Por favor, tente novamente.";
    header("Location: dashboard.php");
    exit();
    // error_log("Erro ao carregar loja em gerenciar_avaliacoes.php: " . $e->getMessage());
}

// 4. PROCESSAMENTO DE AÇÕES (APROVAR/REJEITAR AVALIAÇÃO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && isset($_POST['avaliacao_id'])) {
    $avaliacao_id = filter_input(INPUT_POST, 'avaliacao_id', FILTER_VALIDATE_INT);
    $acao = $_POST['acao']; // 'aprovar' ou 'rejeitar'

    if ($avaliacao_id === false || $avaliacao_id === null) {
        $erros[] = "ID da avaliação inválido.";
    } elseif (!in_array($acao, ['aprovar', 'rejeitar'])) {
        $erros[] = "Ação inválida.";
    } else {
        try {
            // Primeiro, verifica se a avaliação existe E pertence à loja correta
            // E se a loja pertence ao comerciante logado.
            $stmt_check = $pdo->prepare(
                "SELECT a.id FROM avaliacoes a
                 JOIN lojas l ON a.loja_id = l.id
                 WHERE a.id = ? AND a.loja_id = ? AND l.comerciante_id = ?"
            );
            $stmt_check->execute([$avaliacao_id, $loja_id, $comerciante_id]);

            if (!$stmt_check->fetch()) {
                $erros[] = "Avaliação não encontrada ou você não tem permissão para modificá-la.";
            } else {
                // Prepara a atualização do status da avaliação
                $novo_status = ($acao == 'aprovar') ? 'aprovada' : 'rejeitada';
                $stmt_update = $pdo->prepare(
                    "UPDATE avaliacoes
                     SET status_avaliacao = ?
                     WHERE id = ?"
                );
                $stmt_update->execute([$novo_status, $avaliacao_id]);

                if ($stmt_update->rowCount() > 0) {
                    $sucesso = "Avaliação atualizada para status: " . htmlspecialchars($novo_status) . ".";
                } else {
                    $erros[] = "Nenhuma alteração feita na avaliação.";
                }
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao processar a avaliação. Por favor, tente novamente.";
            // error_log("Erro ao gerenciar avaliacao: " . $e->getMessage());
        }
    }
}

// 5. BUSCAR TODAS AS AVALIAÇÕES DA LOJA
$avaliacoes = [];
try {
    $stmt_avaliacoes = $pdo->prepare(
        "SELECT id, nome_avaliador, nota, comentario, data_avaliacao, status_avaliacao
         FROM avaliacoes
         WHERE loja_id = ?
         ORDER BY data_avaliacao DESC"
    );
    $stmt_avaliacoes->execute([$loja_id]);
    $avaliacoes = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erros[] = "Erro ao buscar avaliações da loja. Por favor, tente novamente.";
    // error_log("Erro ao buscar avaliações para exibição: " . $e->getMessage());
}

// Define o título da página.
$page_title = "Gerenciar Avaliações - " . htmlspecialchars($loja_nome);
// Inclui o cabeçalho da página.
include 'templates/header.php';
?>

<main>
    <div class="dashboard-container">
        <h2>Gerenciar Avaliações de: <?php echo htmlspecialchars($loja_nome); ?></h2>
        <p>Visualize e modere as avaliações recebidas para sua loja.</p>

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

        <h3>Todas as Avaliações</h3>

        <?php if (!empty($avaliacoes)): ?>
            <div class="avaliacoes-listagem">
                <?php foreach ($avaliacoes as $avaliacao): ?>
                    <div class="avaliacao-gerenciar-item">
                        <div class="avaliacao-header-gerenciar">
                            <span class="nome"><?php echo htmlspecialchars($avaliacao['nome_avaliador']); ?></span>
                            <span class="nota">Nota: <?php echo htmlspecialchars($avaliacao['nota']); ?></span>
                            <span class="data"><?php echo date('d/m/Y H:i', strtotime($avaliacao['data_avaliacao'])); ?></span>
                            <span class="status status-<?php echo strtolower($avaliacao['status_avaliacao']); ?>">
                                <?php echo htmlspecialchars(ucfirst($avaliacao['status_avaliacao'])); ?>
                            </span>
                        </div>
                        <p class="comentario"><?php echo nl2br(htmlspecialchars($avaliacao['comentario'])); ?></p>

                        <?php
                        // Ações visíveis apenas se a avaliação estiver pendente
                        if ($avaliacao['status_avaliacao'] == 'pendente'):
                        ?>
                            <div class="avaliacao-acoes">
                                <form action="gerenciar_avaliacoes.php?loja_id=<?php echo htmlspecialchars($loja_id); ?>" method="POST" style="display:inline-block; margin-right: 5px;">
                                    <input type="hidden" name="avaliacao_id" value="<?php echo htmlspecialchars($avaliacao['id']); ?>">
                                    <button type="submit" name="acao" value="aprovar" class="btn btn-sm btn-success">
                                        <i class="fa-solid fa-check"></i> Aprovar
                                    </button>
                                </form>
                                <form action="gerenciar_avaliacoes.php?loja_id=<?php echo htmlspecialchars($loja_id); ?>" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="avaliacao_id" value="<?php echo htmlspecialchars($avaliacao['id']); ?>">
                                    <button type="submit" name="acao" value="rejeitar" class="btn btn-sm btn-danger">
                                        <i class="fa-solid fa-xmark"></i> Rejeitar
                                    </button>
                                </form>
                            </div>
                        <?php
                        endif;
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="aviso-geral">
                <p>Nenhuma avaliação encontrada para esta loja.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Inclui o rodapé da página.
include 'templates/footer.php';
?>
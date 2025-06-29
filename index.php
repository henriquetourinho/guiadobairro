<?php
// O session_start() agora é chamado no header.php
require_once 'db.php';

// A lógica para buscar as lojas continua a mesma
// Esta é a query para a CARGA INICIAL da página
$sql = "SELECT
            lojas.id,
            lojas.nome_loja,
            lojas.descricao,
            lojas.foto_capa,
            categorias.nome_categoria
        FROM lojas
        JOIN categorias ON lojas.categoria_id = categorias.id
        WHERE lojas.status_publicacao = 'publicado'
        ORDER BY lojas.nome_loja ASC";

try {
    $stmt = $pdo->query($sql);
    $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar lojas: " . $e->getMessage());
}

// Define o título da página para o header
$page_title = "Página Inicial";
include 'templates/header.php';
?>

<section class="hero-section">
    <div class="hero-content">
        <h1>Guia Comercial do Nosso Bairro</h1>
        <p>Apoie o comércio local! Encontre tudo o que você precisa aqui, na palma da sua mão.</p>
    </div>
</section>

<section class="search-section container-guias">
    <div class="search-input-container">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="search-input" class="search-input" placeholder="Buscar lojas por nome, descrição, categoria, bairro...">
        <button id="clear-search-btn" class="clear-search-btn" title="Limpar pesquisa" style="display: none;">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</section>

<section class="categories-filter-section">
    <h3>Filtrar por Categoria</h3>
    <div class="categories-list-container" id="categories-filter-list">
        <button class="category-btn active" data-category-id="all">
            Todas <span class="category-count"> (0)</span>
        </button>
        </div>
</section>


<section class="container-guias" id="lojas-results">
    <?php if (count($lojas) > 0): ?>
        <?php foreach ($lojas as $loja): ?>
            <article class="cartao">
                <?php
                if (!empty($loja['foto_capa']) && file_exists($loja['foto_capa'])):
                ?>
                    <div class="cartao-img-container">
                        <img src="<?php echo htmlspecialchars($loja['foto_capa']); ?>" alt="Foto de Capa da Loja <?php echo htmlspecialchars($loja['nome_loja']); ?>" class="cartao-img">
                    </div>
                <?php else: ?>
                    <div class="cartao-img-container placeholder-img">
                        <i class="fa-solid fa-store"></i>
                        <span>Sem foto de capa</span>
                    </div>
                <?php endif; ?>

                <div class="cartao-body">
                    <span class="cartao-categoria">
                        <i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($loja['nome_categoria']); ?>
                    </span>
                    <h2 class="cartao-titulo"><?php echo htmlspecialchars($loja['nome_loja']); ?></h2>
                    <p class="cartao-descricao"><?php echo htmlspecialchars(substr($loja['descricao'], 0, 110)) . '...'; ?></p>
                </div>
                <div class="cartao-footer">
                    <a href="loja.php?id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn-detalhes">
                        Ver Mais Detalhes <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="aviso-geral" style="grid-column: 1 / -1;">
            <p>Nenhum estabelecimento encontrado no momento. Estamos trabalhando para popular nosso guia!</p>
        </div>
    <?php endif; ?>
</section>

<?php
include 'templates/footer.php';
?>
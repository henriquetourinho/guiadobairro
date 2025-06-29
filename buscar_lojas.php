<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// Define o cabeçalho para indicar que a resposta é JSON.
header('Content-Type: application/json');

// Obtém o termo de pesquisa da requisição GET
// Usa trim() para remover espaços em branco no início e no fim.
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

// Inicializa o array de resultados
$results = [];

if (!empty($search_term)) {
    // Adiciona curingas para a pesquisa LIKE
    $search_param = '%' . $search_term . '%';

    // Query SQL para buscar lojas.
    // Inclui busca por nome da loja, descrição, nome da categoria e nome do bairro.
    // Filtra por lojas publicadas e por termos de pesquisa nos campos relevantes.
    try {
        $stmt = $pdo->prepare(
            "SELECT
                l.id,
                l.nome_loja,
                l.descricao,
                l.foto_capa,
                c.nome_categoria,
                bp.nome_bairro
            FROM lojas l
            JOIN categorias c ON l.categoria_id = c.id
            LEFT JOIN bairros_permitidos bp ON l.bairro_id = bp.id
            WHERE l.status_publicacao = 'publicado'
            AND (
                l.nome_loja LIKE ? OR
                l.descricao LIKE ? OR
                c.nome_categoria LIKE ? OR
                bp.nome_bairro LIKE ?
            )
            ORDER BY l.nome_loja ASC"
        );
        // Os parâmetros são passados na ordem da query
        $stmt->execute([$search_param, $search_param, $search_param, $search_param]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Em caso de erro no banco de dados, retorna uma mensagem de erro em formato JSON.
        // Em um ambiente de produção real, o $e->getMessage() não deveria ser exposto diretamente.
        echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
        exit();
    }
} else {
    // Se o termo de pesquisa estiver vazio, retorna todas as lojas publicadas.
    // Isso é útil para a carga inicial da página ou quando o campo de busca é limpo.
    try {
        $stmt = $pdo->query(
            "SELECT
                l.id,
                l.nome_loja,
                l.descricao,
                l.foto_capa,
                c.nome_categoria,
                bp.nome_bairro
            FROM lojas l
            JOIN categorias c ON l.categoria_id = c.id
            LEFT JOIN bairros_permitidos bp ON l.bairro_id = bp.id
            WHERE l.status_publicacao = 'publicado'
            ORDER BY l.nome_loja ASC"
        );
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar lojas iniciais: ' . $e->getMessage()]);
        exit();
    }
}

// Retorna os resultados (sejam as lojas filtradas ou todas as publicadas) em formato JSON.
echo json_encode($results);
?>
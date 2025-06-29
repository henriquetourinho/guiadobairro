<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'db.php';

// Define o cabeçalho para indicar que a resposta é JSON.
header('Content-Type: application/json');

// Obtém o termo de pesquisa e o ID da categoria da requisição GET (para contagem filtrada)
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

$categories_with_count = [];

try {
    // Primeiro, busca a contagem total de lojas publicadas
    $sql_total = "SELECT COUNT(id) FROM lojas WHERE status_publicacao = 'publicado'";
    $params_total = [];

    // Se houver termo de pesquisa, filtra a contagem total também
    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        $sql_total = "SELECT COUNT(l.id) FROM lojas l
                      JOIN categorias c ON l.categoria_id = c.id
                      LEFT JOIN bairros_permitidos bp ON l.bairro_id = bp.id
                      WHERE l.status_publicacao = 'publicado'
                      AND (l.nome_loja LIKE ? OR l.descricao LIKE ? OR c.nome_categoria LIKE ? OR bp.nome_bairro LIKE ?)";
        $params_total = [$search_param, $search_param, $search_param, $search_param];
    }
    
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params_total);
    $total_lojas = $stmt_total->fetchColumn();


    // Busca todas as categorias e a contagem de lojas publicadas em cada uma,
    // opcionalmente filtrado pelo termo de pesquisa.
    $sql_categories = "SELECT
                        c.id,
                        c.nome_categoria,
                        COUNT(l.id) AS loja_count
                       FROM categorias c
                       LEFT JOIN lojas l ON c.id = l.categoria_id AND l.status_publicacao = 'publicado'";
    
    $join_needed = false;
    $category_params = [];
    $category_conditions = [];

    if (!empty($search_term)) {
        $join_needed = true; // Precisa do JOIN de lojas para filtrar por termo
        $search_param = '%' . $search_term . '%';
        $category_conditions[] = "(l.nome_loja LIKE ? OR l.descricao LIKE ? OR c.nome_categoria LIKE ? OR bp.nome_bairro LIKE ?)";
        $category_params = array_merge($category_params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    // Se o termo de busca for aplicado a lojas, precisamos do JOIN com bairros_permitidos também
    if ($join_needed) {
        $sql_categories .= " LEFT JOIN bairros_permitidos bp ON l.bairro_id = bp.id";
    }

    if (!empty($category_conditions)) {
        $sql_categories .= " WHERE " . implode(" AND ", $category_conditions);
    }
    
    $sql_categories .= " GROUP BY c.id, c.nome_categoria ORDER BY c.nome_categoria ASC";


    $stmt_categories = $pdo->prepare($sql_categories);
    $stmt_categories->execute($category_params);
    $categories_data = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    $categories_with_count['total_lojas'] = $total_lojas;
    $categories_with_count['categories'] = $categories_data;


} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar categorias: ' . $e->getMessage()]);
    exit();
}

echo json_encode($categories_with_count);
?>
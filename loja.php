<?php
// Inicia a sessão (necessário para o botão de edição)
session_start();

// 1. OBTER E VALIDAR O ID DA LOJA DA URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    die("ID da loja inválido ou não fornecido.");
}
$loja_id = (int)$_GET['id'];


// 2. CONECTAR AO BANCO DE DADOS
require_once 'db.php';


// 3. BUSCAR OS DADOS PRINCIPAIS DA LOJA, BAIRRO, CNPJ/CPF, FOTO DE CAPA E O LINK DO GOOGLE MAPS
try {
    $stmt_loja = $pdo->prepare(
        "SELECT l.*, c.nome_categoria, bp.nome_bairro, co.cnpj_cpf
         FROM lojas l
         JOIN categorias c ON l.categoria_id = c.id
         LEFT JOIN bairros_permitidos bp ON l.bairro_id = bp.id
         JOIN comerciantes co ON l.comerciante_id = co.id
         WHERE l.id = ?"
    );
    $stmt_loja->execute([$loja_id]);
    $loja = $stmt_loja->fetch(PDO::FETCH_ASSOC);

    if (!$loja) {
        die("Loja não encontrada.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar detalhes da loja: " . $e->getMessage());
}

// 4. BUSCAR OS CONTATOS DA LOJA
try {
    $stmt_contatos = $pdo->prepare("SELECT tipo_contato, valor_contato FROM contatos WHERE loja_id = ?");
    $stmt_contatos->execute([$loja_id]);
    $contatos = $stmt_contatos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar contatos da loja: " . $e->getMessage());
}


// 5. BUSCAR AS AVALIAÇÕES APROVADAS DA LOJA
try {
    $stmt_avaliacoes = $pdo->prepare(
        "SELECT nome_avaliador, nota, comentario, data_avaliacao
         FROM avaliacoes
         WHERE loja_id = ? AND status_avaliacao = 'aprovada'
         ORDER BY data_avaliacao DESC"
    );
    $stmt_avaliacoes->execute([$loja_id]);
    $avaliacoes = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar avaliações da loja: " . $e->getMessage());
}


// 6. LÓGICA PARA EXIBIR BOTÃO DE EDIÇÃO
$show_edit_button = false;
if (isset($_SESSION['comerciante_id']) && $_SESSION['comerciante_id'] == $loja['comerciante_id']) {
    $show_edit_button = true;
}


// 7. INCLUIR O CABEÇALHO DA PÁGINA
$page_title = htmlspecialchars($loja['nome_loja']);
include 'templates/header.php';

?>

<main>
    <div class="loja-container">
        <div class="loja-header">
            <h1><?php echo htmlspecialchars($loja['nome_loja']); ?></h1>
            <p class="categoria"><?php echo htmlspecialchars($loja['nome_categoria']); ?></p>

            <?php
            // Exibe o botão de edição se as condições forem atendidas
            if ($show_edit_button):
            ?>
                <div class="loja-actions-header">
                    <a href="editar_loja.php?id=<?php echo htmlspecialchars($loja['id']); ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-pen-to-square"></i> Editar Esta Loja
                    </a>
                </div>
            <?php
            endif;
            ?>
        </div>

        <?php
        // Exibe a foto de capa da LOJA, se existir
        if (!empty($loja['foto_capa']) && file_exists($loja['foto_capa'])):
        ?>
            <section class="loja-section loja-capa-section">
                <img src="<?php echo htmlspecialchars($loja['foto_capa']); ?>" alt="Capa da Loja" class="loja-capa-img">
            </section>
        <?php endif; ?>

        <section class="loja-section">
            <h2>Descrição</h2>
            <p><?php echo nl2br(htmlspecialchars($loja['descricao'])); ?></p>
        </section>

        <section class="loja-section">
            <h2>Endereço</h2>
            <p><?php echo htmlspecialchars($loja['endereco']); ?></p>
            <?php if (!empty($loja['nome_bairro'])): ?>
                <p>Bairro: <strong><?php echo htmlspecialchars($loja['nome_bairro']); ?></strong></p>
            <?php endif; ?>
            <?php if (!empty($loja['cnpj_cpf'])): ?>
                <p>CNPJ/CPF do Comerciante: <strong><?php echo htmlspecialchars($loja['cnpj_cpf']); ?></strong></p>
            <?php endif; ?>

            <?php
            // NOVO: Exibe o Google Maps incorporado se o link existir
            if (!empty($loja['Maps_link'])):
                // Valida o URL do Google Maps antes de usar no iframe src
                $maps_link_sanitized = filter_var($loja['Maps_link'], FILTER_SANITIZE_URL);
                
                // **** SUA CHAVE DE API DO GOOGLE MAPS. SUBSTITUA PELA SUA CHAVE REAL AQUI ****
                // É uma boa prática armazenar isso em um arquivo de configuração (db.php ou config.php)
                // para não expor a chave diretamente no código, mas para o propósito de teste, pode ser aqui.
                $Maps_api_key = "SUA_API_KEY_AQUI"; // <--- SUBSTITUA ESTE VALOR PELA SUA CHAVE REAL!
            ?>
                <div class="google-maps-container">
                    <h3>Localização no Mapa</h3>
                    <iframe
                        src="<?php echo htmlspecialchars($maps_link_sanitized); ?>&key=<?php echo htmlspecialchars($Maps_api_key); ?>"
                        width="100%"
                        height="450"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        class="google-maps-iframe"
                    ></iframe>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($contatos)): ?>
        <section class="loja-section">
            <h2>Contatos</h2>
            <ul class="contatos-lista">
                <?php foreach ($contatos as $contato):
                    $link_href = '';
                    $link_target = '_self';
                    $link_icon = 'fa-info-circle';
                    $display_value = htmlspecialchars($contato['valor_contato']);

                    switch ($contato['tipo_contato']) {
                        case 'telefone':
                            $link_href = 'tel:' . preg_replace('/[^0-9+]/', '', $contato['valor_contato']);
                            $link_icon = 'fa-phone';
                            break;
                        case 'whatsapp':
                            $numero_whatsapp = preg_replace('/[^0-9]/', '', $contato['valor_contato']);
                            if (substr($numero_whatsapp, 0, 2) != '55') { // Código do Brasil
                                $numero_whatsapp = '55' . $numero_whatsapp;
                            }
                            $link_href = 'https://wa.me/' . $numero_whatsapp;
                            $link_target = '_blank';
                            $link_icon = 'fa-whatsapp';
                            break;
                        case 'email':
                            $link_href = 'mailto:' . htmlspecialchars($contato['valor_contato']);
                            $link_icon = 'fa-envelope';
                            break;
                        case 'instagram':
                            $perfil_instagram = ltrim(htmlspecialchars($contato['valor_contato']), '@');
                            $link_href = 'https://instagram.com/' . $perfil_instagram;
                            $link_target = '_blank';
                            $link_icon = 'fa-instagram';
                            break;
                        case 'telegram':
                            $link_href = 'https://t.me/' . htmlspecialchars($contato['valor_contato']);
                            $link_target = '_blank';
                            $link_icon = 'fa-telegram-plane';
                            break;
                        case 'facebook':
                            $link_href = 'https://facebook.com/' . htmlspecialchars($contato['valor_contato']);
                            $link_target = '_blank';
                            $link_icon = 'fa-facebook-f';
                            break;
                        case 'site':
                            $url = htmlspecialchars($contato['valor_contato']);
                            if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
                                $url = "http://" . $url;
                            }
                            $link_href = $url;
                            $link_target = '_blank';
                            $link_icon = 'fa-globe';
                            break;
                        default:
                            $link_href = '#';
                            $link_target = '_self';
                            $link_icon = 'fa-info-circle';
                            break;
                    }
                ?>
                    <li>
                        <i class="fa-solid <?php echo $link_icon; ?>"></i>
                        <strong><?php echo htmlspecialchars(ucfirst($contato['tipo_contato'])); ?>:</strong>
                        <?php if ($link_href && $link_href != '#'): ?>
                            <a href="<?php echo $link_href; ?>" target="<?php echo $link_target; ?>" class="contact-link">
                                <?php echo $display_value; ?> <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i>
                            </a>
                        <?php else: ?>
                            <span><?php echo $display_value; ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!empty($avaliacoes)): ?>
        <section class="loja-section">
            <h2>Avaliações de Clientes</h2>
            <?php foreach ($avaliacoes as $avaliacao): ?>
                <div class="avaliacao-item">
                    <div class="avaliacao-header">
                        <span class="nome"><?php echo htmlspecialchars($avaliacao['nome_avaliador']); ?></span>
                        <span class="nota">Nota: <?php echo htmlspecialchars($avaliacao['nota']); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($avaliacao['comentario']); ?></p>
                </div>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="loja-section">
            <h2>Avaliações de Clientes</h2>
            <p>Este estabelecimento ainda não possui avaliações aprovadas.</p>
        </section>
    <?php endif; ?>

        <section class="loja-section">
            <h2>Deixe sua Avaliação</h2>
            <p>Em breve, você poderá enviar sua própria avaliação para esta loja!</p>
        </section>

    </div>
</main>

<?php include 'templates/footer.php'; ?>
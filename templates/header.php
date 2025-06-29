<?php
// Inicia a sessão. É crucial para que a variável $_SESSION esteja disponível.
// Colocar aqui garante que a sessão é iniciada em todas as páginas que incluem o header.
if (session_status() == PHP_SESSION_NONE) { // Verifica se a sessão já não foi iniciada
    session_start();
}

// Verifica se a variável $page_title está definida. Se não, define um padrão.
if (!isset($page_title)) {
    $page_title = "Guia do Bairro";
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-logo">
                <i class="fa-solid fa-map-location-dot"></i> GuiaDoBairro
            </a>
            <div class="navbar-menu">
                <?php
                // Verifica se o comerciante está logado
                if (isset($_SESSION['comerciante_id'])):
                ?>
                    <span class="navbar-welcome">Olá, <?php echo htmlspecialchars($_SESSION['comerciante_nome']); ?>!</span>
                    <a href="dashboard.php" class="btn btn-primary"><i class="fa-solid fa-table-columns"></i> Meu Painel</a>
                    <a href="editar_perfil.php" class="btn btn-secondary"><i class="fa-solid fa-user-gear"></i> Editar Perfil</a>
                    <a href="logout.php" class="btn btn-secondary"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                    <a href="cadastro.php" class="btn btn-secondary"><i class="fa-solid fa-user-plus"></i> Cadastro</a>
                <?php endif; ?>
                
                <button id="theme-toggle" class="theme-toggle-btn" aria-label="Alternar tema">
                    <i class="fa-solid fa-moon icon-dark"></i>
                    <i class="fa-solid fa-sun icon-light"></i>
                </button>
            </div>
        </div>
    </nav>

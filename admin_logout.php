<?php
// Inicia a sessão.
session_start();

// Destrói todas as variáveis de sessão para o administrador.
if (isset($_SESSION['admin_id'])) {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_nome']);
    unset($_SESSION['admin_nivel']);
}

// Se for usado cookies de sessão, também destrói o cookie de sessão do admin.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão (o arquivo de sessão no servidor).
session_destroy();

// Redireciona o administrador para a página de login administrativo.
header("Location: admin_login.php");
exit();
?>
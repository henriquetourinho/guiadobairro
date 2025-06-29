<?php
// Inicia a sessão. É crucial para que o PHP saiba qual sessão ele precisa destruir.
session_start();

// Destrói todas as variáveis de sessão.
$_SESSION = array();

// Se for usado cookies de sessão, também destrói o cookie.
// Nota: Isso irá destruir o cookie de sessão e não apenas os dados da sessão!
// É importante definir o caminho do cookie e o domínio para garantir que o cookie correto seja excluído.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão.
session_destroy();

// Redireciona o usuário para a página de login.
header("Location: login.php");
exit(); // Encerra o script para garantir que o redirecionamento ocorra imediatamente.
?>
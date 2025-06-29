<?php
// Arquivo de Conexão com o Banco de Dados (db.php)

// Definição das credenciais do banco de dados
$db_host = 'localhost';    // O servidor do banco de dados (localhost)
$db_name = 'BANCODEDADOS';          // O nome do banco de dados que você criou
$db_user = 'USUARIO';          // O usuário do banco de dados
$db_pass = 'SENHA';   // A sua senha
$db_charset = 'utf8mb4';    // O charset para garantir a correta exibição de caracteres especiais

// DSN (Data Source Name) - String de conexão
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

// Opções do PDO para a conexão
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna os dados como arrays associativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa "prepared statements" nativos do MySQL
];

// Tenta estabelecer a conexão com o banco de dados
try {
    // Cria uma nova instância do PDO (a conexão) e a armazena na variável $pdo
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    // Em caso de falha na conexão, exibe uma mensagem de erro e encerra o script.
    // Em um site real (em produção), o ideal seria registrar este erro em um arquivo de log
    // em vez de exibi-lo na tela, para não expor detalhes do sistema.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Se o script chegou até aqui, significa que a conexão foi bem-sucedida.
// A variável $pdo está agora pronta para ser usada em qualquer outro arquivo
// que inclua o db.php.
?>
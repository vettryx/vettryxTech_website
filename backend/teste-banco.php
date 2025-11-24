<?php
// Carrega suas configurações
require 'config.php';

header('Content-Type: application/json');

try {
    // Tenta listar as tabelas para ver se foram criadas
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "status" => "SUCESSO",
        "mensagem" => "Conexão com a Hostinger realizada!",
        "banco" => DB_NAME,
        "tabelas_encontradas" => $tabelas
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "ERRO",
        "mensagem" => "Não conectou nem a pau.",
        "detalhe" => $e->getMessage()
    ]);
}
?>
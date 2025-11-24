<?php
// backend/api_projects.php
require 'config.php';

// 1. Headers para permitir acesso do Next.js e definir JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

try {
    // 2. Busca os projetos
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Devolve em JSON puro
    echo json_encode($projects);

} catch (PDOException $e) {
    // Em caso de erro, devolve JSON de erro
    http_response_code(500);
    echo json_encode(["error" => "Erro ao buscar dados: " . $e->getMessage()]);
}
?>
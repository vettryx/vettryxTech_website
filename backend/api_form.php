<?php
require 'config.php';

// 1. Permitir acesso do Next.js (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 2. Pegar o Slug da URL (ex: api_form.php?slug=contact-main)
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(["error" => "Slug não informado. Use ?slug=seu-id"]);
    exit;
}

try {
    // 3. Buscar o Formulário pelo Slug
    $stmt = $pdo->prepare("SELECT id, title, recipient_email FROM forms WHERE slug = ?");
    $stmt->execute([$slug]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        http_response_code(404);
        echo json_encode(["error" => "Formulário '$slug' não encontrado."]);
        exit;
    }

    // 4. Buscar os Campos desse Formulário
    $stmtFields = $pdo->prepare("SELECT id, label, name, type, options, is_required FROM form_fields WHERE form_id = ? ORDER BY id ASC");
    $stmtFields->execute([$form['id']]);
    $fields = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

    // 5. Retornar tudo bonitinho em JSON
    echo json_encode([
        "success" => true,
        "form" => [
            "id" => $form['id'],
            "title" => $form['title']
        ],
        "fields" => $fields
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro interno: " . $e->getMessage()]);
}
?>
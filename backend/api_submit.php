<?php
require 'config.php';

// CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents("php://input"), true);
$form_id = $input['form_id'] ?? null;
$formData = $input['data'] ?? [];

try {
    // Busca Formulário
    $stmtForm = $pdo->prepare("SELECT title, recipient_email FROM forms WHERE id = ?");
    $stmtForm->execute([$form_id]);
    $formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$formInfo) throw new Exception("Formulário não encontrado.");

    // Salva no Banco
    $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, email_status) VALUES (?, ?, ?)");
    $stmt->execute([$form_id, $jsonData, 'Pendente']);
    $submission_id = $pdo->lastInsertId();

    // --- ENVIO BLINDADO PARA OUTLOOK ---
    
    $para = $formInfo['recipient_email'];
    $assunto = "Novo Lead: " . $formInfo['title'];
    
    // E-mail de sistema (validado no SPF)
    $emailFrom = "no-reply@asventura.com.br";
    $nomeFrom = "Site Andre Ventura";

    // Corpo
    $mensagem = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
      <div style='border-left: 4px solid #2ECC40; padding-left: 15px;'>
        <h3 style='color: #023047;'>Novo contato recebido</h3>
    ";
    foreach ($formData as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        $val = htmlspecialchars($value);
        $mensagem .= "<p><strong>$label:</strong> $val</p>";
    }
    $mensagem .= "</div></body></html>";

    // --- CABEÇALHOS (A CORREÇÃO ESTÁ AQUI) ---
    // O segredo é alinhar From, Reply-To, Sender e Return-Path
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    
    // 1. Quem está mandando (Visual)
    $headers .= "From: $nomeFrom <$emailFrom>" . "\r\n";
    
    // 2. Quem mandou tecnicamente (Para tirar o 'em nome de')
    $headers .= "Sender: $emailFrom" . "\r\n";
    $headers .= "Return-Path: $emailFrom" . "\r\n";
    
    // 3. Para onde vai a resposta (Cliente)
    if (!empty($formData['email'])) {
        $headers .= "Reply-To: " . $formData['email'] . "\r\n";
    }

    // Parâmetro do envelope (Técnico)
    $params = "-f" . $emailFrom;

    // Envia
    $enviou = mail($para, $assunto, $mensagem, $headers, $params);

    // Atualiza status
    $statusFinal = $enviou ? 'Enviado' : 'Falha Local';
    $pdo->prepare("UPDATE form_submissions SET email_status = ? WHERE id = ?")->execute([$statusFinal, $submission_id]);

    echo json_encode(["success" => true, "message" => "Recebido com sucesso!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>
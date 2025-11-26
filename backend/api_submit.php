<?php
require 'config.php';

// --- CORS (Obrigatório) ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

// Recebe dados
$input = json_decode(file_get_contents("php://input"), true);
$form_id = $input['form_id'] ?? null;
$formData = $input['data'] ?? [];

try {
    // Busca destino no banco
    $stmtForm = $pdo->prepare("SELECT title, recipient_email FROM forms WHERE id = ?");
    $stmtForm->execute([$form_id]);
    $formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$formInfo) throw new Exception("Formulário não encontrado.");

    // Salva no Banco
    $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, email_status) VALUES (?, ?, ?)");
    $stmt->execute([$form_id, $jsonData, 'Pendente']);
    $submission_id = $pdo->lastInsertId();

    // --- ENVIO DE E-MAIL NATIVO (SEM SENHA) ---
    
    $para = $formInfo['recipient_email'];
    $assunto = "Novo Contato: " . $formInfo['title'];
    
    // Corpo da mensagem
    $mensagem = "<html><body>";
    $mensagem .= "<h2>Novo contato recebido</h2><hr>";
    foreach ($formData as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        $mensagem .= "<strong>$label:</strong> " . htmlspecialchars($value) . "<br>";
    }
    $mensagem .= "</body></html>";

    // CABEÇALHOS (O Segredo para parecer profissional)
    // Define o nome "Site André Ventura" no remetente
    $nomeRemetente = "Site André Ventura";
    $emailRemetente = "no-reply@asventura.com.br"; // Não precisa existir, é só um rótulo
    
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: $nomeRemetente <$emailRemetente>" . "\r\n";
    
    // Se o usuário preencheu um e-mail, o "Responder" vai pra ele
    if (!empty($formData['email'])) {
        $headers .= "Reply-To: " . $formData['email'] . "\r\n";
    }

    // Dispara
    $enviou = mail($para, $assunto, $mensagem, $headers);

    // Atualiza status
    $statusFinal = $enviou ? 'Enviado' : 'Falha Local';
    $pdo->prepare("UPDATE form_submissions SET email_status = ? WHERE id = ?")->execute([$statusFinal, $submission_id]);

    echo json_encode(["success" => true, "message" => "Recebido com sucesso!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>
<?php
require 'config.php';

// --- CORS ---
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

$input = json_decode(file_get_contents("php://input"), true);
$form_id = $input['form_id'] ?? null;
$formData = $input['data'] ?? [];

try {
    // 1. Busca dados do formulário
    $stmtForm = $pdo->prepare("SELECT title, recipient_email FROM forms WHERE id = ?");
    $stmtForm->execute([$form_id]);
    $formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$formInfo) throw new Exception("Formulário não encontrado.");

    // 2. Salva no Banco
    $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, email_status) VALUES (?, ?, ?)");
    $stmt->execute([$form_id, $jsonData, 'Pendente']);
    $submission_id = $pdo->lastInsertId();

    // --- 3. ENVIO ESTILO ELEMENTOR / WORDPRESS ---
    
    $para = $formInfo['recipient_email'];
    $assunto = "Novo Contato: " . $formInfo['title'];
    
    // Remetente "Fictício" (Igual o WP faz: wordpress@dominio.com)
    // Isso valida o SPF que você configurou no Cloudflare
    $emailSistema = "no-reply@asventura.com.br"; 
    
    // Corpo da mensagem limpo
    $mensagem = "
    <html>
    <head>
      <title>$assunto</title>
    </head>
    <body style='font-family: Arial, sans-serif;'>
      <div style='background-color: #f4f4f4; padding: 20px;'>
        <div style='background-color: #fff; padding: 20px; border-radius: 5px; border-left: 5px solid #2ECC40;'>
          <h2 style='color: #023047; margin-top: 0;'>Novo Lead Recebido</h2>
          <hr style='border: 0; border-top: 1px solid #eee;'>
    ";
    
    foreach ($formData as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        $val = htmlspecialchars($value);
        $mensagem .= "<p><strong>$label:</strong><br>$val</p>";
    }
    
    $mensagem .= "
        </div>
        <p style='font-size: 12px; color: #999; text-align: center; margin-top: 20px;'>
          Enviado via Site André Ventura
        </p>
      </div>
    </body>
    </html>
    ";

    // CABEÇALHOS CORRETOS (MIME + FROM + REPLY-TO)
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: Site André Ventura <$emailSistema>" . "\r\n";
    
    if (!empty($formData['email'])) {
        $headers .= "Reply-To: " . $formData['email'] . "\r\n";
    }
    
    // Adiciona X-Mailer para parecer legítimo
    $headers .= "X-Mailer: PHP/" . phpversion();

    // --- O PULO DO GATO: O PARÂMETRO -f ---
    // Isso força o servidor a usar esse e-mail no envelope técnico
    // É isso que faz o SPF funcionar sem senha.
    $parametros = "-f$emailSistema";

    // Envia
    $enviou = mail($para, $assunto, $mensagem, $headers, $parametros);

    // Atualiza status
    $statusFinal = $enviou ? 'Enviado' : 'Falha Local';
    $pdo->prepare("UPDATE form_submissions SET email_status = ? WHERE id = ?")->execute([$statusFinal, $submission_id]);

    echo json_encode(["success" => true, "message" => "Recebido com sucesso!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>
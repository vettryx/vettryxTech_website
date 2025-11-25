<?php
require 'config.php';

// 1. Configuração CORS/JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// 2. Recebe os dados
$input = json_decode(file_get_contents("php://input"), true);

// Verifica se veio o ID do formulário e os dados
$form_id = $input['form_id'] ?? null;
$formData = $input['data'] ?? [];

if (!$form_id || empty($formData)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Dados incompletos."]);
    exit;
}

try {
    // 3. Busca o e-mail de destino deste formulário
    $stmtForm = $pdo->prepare("SELECT title, recipient_email FROM forms WHERE id = ?");
    $stmtForm->execute([$form_id]);
    $formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$formInfo) {
        throw new Exception("Formulário original não encontrado.");
    }

    // 4. Salva no Banco (Tabela NOVA de JSON)
    $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
    
    $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, email_status) VALUES (?, ?, ?)");
    // Vamos tentar enviar o email abaixo, por enquanto status é Pendente
    $stmt->execute([$form_id, $jsonData, 'Pendente']);
    $submission_id = $pdo->lastInsertId();

    // 5. Envia o E-mail (Lógica robusta)
    $emailSent = false;
    
    // Monta o corpo do e-mail dinamicamente
    $mailBody = "<h2>Novo contato: " . htmlspecialchars($formInfo['title']) . "</h2><hr>";
    foreach ($formData as $key => $value) {
        // Tenta deixar a chave bonita (ex: 'seu_nome' -> 'Seu Nome')
        $label = ucwords(str_replace('_', ' ', $key));
        $mailBody .= "<strong>$label:</strong> " . htmlspecialchars($value) . "<br>";
    }

    // Usa PHPMailer se disponível (Recomendado) ou mail() nativo
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = 'tls'; 
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_USER, 'Site Notification');
            $mail->addAddress($formInfo['recipient_email']); // Destino dinâmico
            
            // Tenta achar um campo de email na resposta para setar o ReplyTo
            if (!empty($formData['email'])) {
                $mail->addReplyTo($formData['email']);
            }

            $mail->isHTML(true);
            $mail->Subject = "Contato Site: " . $formInfo['title'];
            $mail->Body    = $mailBody;

            $mail->send();
            $emailSent = true;
        } catch (Exception $e) {
            // Falha silenciosa no email, mas salva no banco
        }
    } else {
        // Fallback simples
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_USER;
        $emailSent = mail($formInfo['recipient_email'], "Novo Contato", $mailBody, $headers);
    }

    // Atualiza status do envio no banco
    if ($emailSent) {
        $pdo->prepare("UPDATE form_submissions SET email_status = 'Enviado' WHERE id = ?")->execute([$submission_id]);
    } else {
        $pdo->prepare("UPDATE form_submissions SET email_status = 'Falha Envio' WHERE id = ?")->execute([$submission_id]);
    }

    echo json_encode(["success" => true, "message" => "Enviado com sucesso!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>
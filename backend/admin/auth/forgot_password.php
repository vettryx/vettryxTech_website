<?php
// backend/admin/auth/forgot_password.php
session_start();
require_once __DIR__ . '/../../config.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$message = '';
$msgType = ''; // success ou error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if ($email) {
        // 1. Verifica se o admin existe
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Gera Token Seguro
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hora

            // 3. Salva no Banco
            $update = $pdo->prepare("UPDATE admins SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
            $update->execute([$tokenHash, $expiry, $user['id']]);

            // 4. Prepara o E-mail (Nativo PHP mail)
            $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/backend/admin/reset_password.php?token=" . $token;
            
            $subject = 'Recuperar Senha - Painel Admin';
            
            // Cabeçalhos essenciais para HTML e UTF-8
            $headers  = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
            
            // Importante: O "From" deve ser um e-mail válido do seu domínio na Hostinger para não cair no spam
            // Se não tiver um definido, tente admin@seu-dominio ou noreply@
            $fromEmail = 'no-reply@' . $_SERVER['HTTP_HOST']; 
            $headers .= "From: Admin System <$fromEmail>" . "\r\n";

            $body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2 style='color: #023047;'>Recuperação de Senha</h2>
                    <p>Você solicitou a redefinição de sua senha.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p>
                        <a href='$resetLink' style='background-color: #2ECC40; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Redefinir Senha</a>
                    </p>
                    <p style='font-size: 12px; color: #666;'>Se o botão não funcionar, copie este link:<br>$resetLink</p>
                    <p style='font-size: 12px; color: #999;'>Este link expira em 1 hora.</p>
                </div>
            ";

            // Envia usando o servidor
            if (mail($email, $subject, $body, $headers)) {
                $message = "Link de recuperação enviado para seu e-mail!";
                $msgType = "success";
            } else {
                $message = "O servidor tentou enviar, mas falhou. Verifique se o PHP mail() está ativo.";
                $msgType = "error";
            }

        } else {
            // Segurança: mensagem genérica
            $message = "Se o e-mail existir, você receberá um link.";
            $msgType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - André Ventura</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { dark: "#023047", green: "#2ECC40" } } } } }
    </script>
</head>
<body class="bg-brand-dark h-screen w-full flex items-center justify-center font-sans text-white">
    <div class="w-full max-w-sm bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/10 shadow-2xl">
        <h2 class="text-2xl font-bold text-center mb-6">Recuperar Senha</h2>
        
        <?php if($message): ?>
            <div class="p-3 mb-4 rounded text-center text-sm <?php echo $msgType == 'success' ? 'bg-green-500/20 text-green-200' : 'bg-red-500/20 text-red-200'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">E-mail</label>
                <input type="email" name="email" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg focus:border-brand-green focus:outline-none transition placeholder-slate-500" placeholder="admin@asventura.com.br">
            </div>
            <button type="submit" class="w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 rounded-lg transition">Enviar Link</button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-400">
            <a href="login.php" class="hover:text-white transition">Voltar para o Login</a>
        </p>
    </div>
</body>
</html>
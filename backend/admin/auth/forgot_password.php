<?php
// backend/admin/auth/forgot_password.php

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/Layout.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../index.php');
    exit;
}

$message = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    if ($email) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiry = date('Y-m-d H:i:s', time() + 3600);
            
            $pdo->prepare("UPDATE admins SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?")->execute([$tokenHash, $expiry, $user['id']]);

            // Link aponta para a pasta correta agora
            $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/backend/admin/auth/reset_password.php?token=" . $token;
            
            $headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Admin System <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
            $body = "<h2>Recuperar Senha</h2><p>Clique aqui para redefinir: <a href='$resetLink'>$resetLink</a></p>";

            if (mail($email, 'Recuperar Senha', $body, $headers)) {
                $message = "Link enviado! Verifique seu e-mail."; $msgType = "success";
            } else {
                $message = "Erro ao enviar e-mail."; $msgType = "error";
            }
        } else {
            $message = "Se o e-mail existir, você receberá um link."; $msgType = "success";
        }
    }
}

// Renderiza o cabeçalho padrão
Layout::authHeader('Recuperar Senha');
?>

<div class="text-center mb-6">
    <h2 class="text-2xl font-bold text-white">Recuperar Acesso</h2>
    <p class="text-slate-400 text-sm mt-2">Informe seu e-mail para receber o link de redefinição de senha.</p>
</div>

<?php if($message): ?>
    <div class="p-3 mb-4 rounded text-center text-sm <?php echo $msgType == 'success' ? 'bg-green-500/20 text-green-200' : 'bg-red-500/20 text-red-200'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-4">
    <div>
        <label class="block text-xs font-bold uppercase text-slate-300 mb-1 ml-1">E-mail</label>
        <input type="email" name="email" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition" placeholder="admin@exemplo.com">
    </div>
    
    <button type="submit" class="w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-green-500/20 transition transform hover:-translate-y-0.5">
        Enviar Link
    </button>
</form>

<div class="mt-6 text-center">
    <a href="login.php" class="text-sm text-slate-400 hover:text-white underline transition">Voltar para o Login</a>
</div>

<?php Layout::authFooter(); ?>
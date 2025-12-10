<?php
// backend/admin/auth/reset_password.php

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/Layout.php';

$token = $_GET['token'] ?? '';
$validToken = false;
$message = '';

if ($token) {
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();
    if ($user) $validToken = true;
    else $message = "Link inválido ou expirado.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $message = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($password !== $confirm) {
        $message = "As senhas não coincidem.";
    } else {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admins SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?")->execute([$newHash, $user['id']]);
        $message = "Senha alterada! <a href='login.php' class='underline text-white font-bold'>Faça login.</a>";
        $validToken = false;
    }
}

Layout::authHeader('Nova Senha');
?>

<div class="text-center mb-6">
    <h2 class="text-2xl font-bold text-white">Nova Senha</h2>
</div>

<?php if($message): ?>
    <div class="p-3 mb-4 rounded text-center text-sm bg-blue-500/20 text-blue-100 border border-blue-500/30">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($validToken): ?>
<form method="POST" class="space-y-4">
    <div>
        <label class="block text-xs font-bold uppercase text-slate-300 mb-1 ml-1">Nova Senha</label>
        <input type="password" name="password" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition">
    </div>
    <div>
        <label class="block text-xs font-bold uppercase text-slate-300 mb-1 ml-1">Confirmar Senha</label>
        <input type="password" name="confirm_password" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition">
    </div>
    <button type="submit" class="w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-green-500/20 transition transform hover:-translate-y-0.5">
        Alterar Senha
    </button>
</form>
<?php endif; ?>

<?php if (!$validToken && empty($message)): ?>
    <div class="text-center">
        <p class="text-red-400 mb-4">Token não fornecido ou inválido.</p>
        <a href="login.php" class="text-white underline">Voltar</a>
    </div>
<?php endif; ?>

<?php Layout::authFooter(); ?>
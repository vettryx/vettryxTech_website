<?php
// backend/admin/reset_password.php
session_start();
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$validToken = false;
$message = '';

// Verifica se o token é válido
if ($token) {
    $tokenHash = hash('sha256', $token);
    
    // Busca usuário com token válido e não expirado
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if ($user) {
        $validToken = true;
    } else {
        $message = "Link inválido ou expirado.";
    }
}

// Processa a nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $message = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($password !== $confirm) {
        $message = "As senhas não coincidem.";
    } else {
        // Atualiza a senha e limpa o token
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        
        $update = $pdo->prepare("UPDATE admins SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $update->execute([$newHash, $user['id']]);
        
        $message = "Senha alterada com sucesso! <a href='login.php' class='underline font-bold'>Faça login agora.</a>";
        $validToken = false; // Esconde o formulário
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - André Ventura</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { dark: "#023047", green: "#2ECC40" } } } } }
    </script>
</head>
<body class="bg-brand-dark h-screen w-full flex items-center justify-center font-sans text-white">
    <div class="w-full max-w-sm bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/10 shadow-2xl">
        <h2 class="text-2xl font-bold text-center mb-6">Nova Senha</h2>

        <?php if($message): ?>
            <div class="p-3 mb-4 rounded text-center text-sm bg-blue-500/20 text-blue-200">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nova Senha</label>
                <input type="password" name="password" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg focus:border-brand-green focus:outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Confirmar Senha</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg focus:border-brand-green focus:outline-none transition">
            </div>
            <button type="submit" class="w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 rounded-lg transition">Alterar Senha</button>
        </form>
        <?php endif; ?>

        <?php if (!$validToken && empty($message)): ?>
            <p class="text-center text-red-400">Token não fornecido.</p>
        <?php endif; ?>
    </div>
</body>
</html>
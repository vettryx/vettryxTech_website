<?php
// backend/admin/auth/security.php

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/Layout.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

// Segurança: Apenas logados
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$tfa = new TwoFactorAuth(new QRServerProvider());
$userId = $_SESSION['admin_id'];
$message = ''; $msgType = '';

// Processa formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'enable') {
        $secret = $_POST['secret'] ?? '';
        $code   = $_POST['code'] ?? '';
        if ($tfa->verifyCode($secret, $code)) {
            $pdo->prepare("UPDATE admins SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?")->execute([$secret, $userId]);
            $message = "2FA Ativado com Sucesso!"; $msgType = 'success';
        } else {
            $message = "Código incorreto."; $msgType = 'error';
        }
    } elseif ($action === 'disable') {
        $pdo->prepare("UPDATE admins SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?")->execute([$userId]);
        $message = "2FA Desativado."; $msgType = 'warning';
    }
}

// Busca status atual
$stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_secret, email FROM admins WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$isEnabled = (bool)$user['two_factor_enabled'];
$secret = $user['two_factor_secret'];
$qrCodeUrl = '';

if (!$isEnabled) {
    $secret = $tfa->createSecret();
    $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($user['email'], $secret);
}

// Usa layout focado (sem menu lateral para evitar links quebrados na subpasta)
Layout::authHeader('Segurança 2FA');
?>

<div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
    </div>
    <h2 class="text-2xl font-bold text-white">Configurar 2FA</h2>
    <div class="mt-2">
        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?php echo $isEnabled ? 'bg-green-500 text-white' : 'bg-slate-700 text-slate-300'; ?>">
            <?php echo $isEnabled ? 'ATIVADO' : 'DESATIVADO'; ?>
        </span>
    </div>
</div>

<?php if($message): ?>
    <div class="p-3 mb-4 rounded text-center text-sm font-bold <?php echo $msgType === 'success' ? 'bg-green-500/20 text-green-200' : 'bg-red-500/20 text-red-200'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="space-y-6" x-data="{ code: '' }">
    <?php if ($isEnabled): ?>
        <div class="text-center py-4">
            <p class="text-slate-300 text-sm mb-6">Sua conta está protegida. O login exige o código do app.</p>
            <form method="POST" onsubmit="return confirm('Tem certeza? Isso reduzirá a segurança.');">
                <input type="hidden" name="action" value="disable">
                <button class="px-6 py-2 border border-red-500/50 text-red-400 rounded-lg hover:bg-red-500/10 transition text-sm">
                    Desativar Proteção
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="flex flex-col items-center gap-4">
            <div class="bg-white p-2 rounded-lg">
                <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="w-40 h-40">
            </div>
            <div class="text-center">
                <p class="text-xs text-slate-400 mb-1">Ou digite manualmente:</p>
                <code class="bg-slate-900 px-2 py-1 rounded text-brand-green font-mono text-xs select-all"><?php echo $secret; ?></code>
            </div>
        </div>

        <form method="POST" class="mt-4">
            <input type="hidden" name="action" value="enable">
            <input type="hidden" name="secret" value="<?php echo $secret; ?>">
            
            <label class="block text-xs font-bold uppercase text-slate-300 mb-2 text-center">Código do App</label>
            <input type="text" name="code" x-model="code" maxlength="6" class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-600 focus:outline-none focus:border-brand-green transition text-center text-2xl tracking-widest font-mono" placeholder="000000">
            
            <button type="submit" :disabled="code.length < 6" class="mt-4 w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-green-500/20 transition disabled:opacity-50">
                Ativar e Salvar
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="mt-8 pt-6 border-t border-white/10 text-center">
    <a href="../index.php" class="text-sm text-slate-400 hover:text-white transition flex items-center justify-center gap-2">
        <span>←</span> Voltar ao Dashboard
    </a>
</div>

<?php Layout::authFooter(); ?>
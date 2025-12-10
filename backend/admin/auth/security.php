<?php
// backend/admin/auth/security.php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

// 1. Segurança: Apenas logados
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$tfa = new TwoFactorAuth(new QRServerProvider());
$userId = $_SESSION['admin_id'];
$message = '';
$msgType = '';

// --- LÓGICA DE POST (Ativar/Desativar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'enable') {
        $secret = $_POST['secret'] ?? '';
        $code   = $_POST['code'] ?? '';

        if ($tfa->verifyCode($secret, $code)) {
            // Código correto! Salva no banco
            $stmt = $pdo->prepare("UPDATE admins SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
            $stmt->execute([$secret, $userId]);
            $message = "Autenticação em Dois Fatores (2FA) ATIVADA com sucesso!";
            $msgType = 'success';
        } else {
            $message = "Código incorreto. Tente novamente.";
            $msgType = 'error';
        }
    } elseif ($action === 'disable') {
        // Desativa
        $stmt = $pdo->prepare("UPDATE admins SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        $message = "2FA Desativado.";
        $msgType = 'warning';
    }
}

// --- LÓGICA DE LEITURA (Carregar dados atuais) ---
$stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_secret, email FROM admins WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$isEnabled = (bool)$user['two_factor_enabled'];

// Se não tiver segredo gerado ainda, gera um novo para o setup
$secret = $user['two_factor_secret'];
$qrCodeUrl = '';

if (!$isEnabled) {
    // Gera um segredo temporário para a tela de setup
    $secret = $tfa->createSecret();
    // Gera a imagem do QR Code
    $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($user['email'], $secret);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Segurança 2FA - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { dark: "#023047", green: "#2ECC40" } } } } }
    </script>
</head>
<body class="bg-slate-100 font-sans min-h-screen">
    
    <nav class="bg-brand-dark text-white p-4 mb-8">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="font-bold text-xl">Segurança da Conta</h1>
            <a href="index.php" class="text-sm hover:text-brand-green">← Voltar ao Painel</a>
        </div>
    </nav>

    <div class="container mx-auto px-4 max-w-2xl">
        
        <?php if($message): ?>
            <div class="p-4 mb-6 rounded-lg text-center font-bold <?php echo $msgType === 'success' ? 'bg-green-100 text-green-800' : ($msgType === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-brand-dark">Autenticação de Dois Fatores</h2>
                        <p class="text-gray-500 text-sm mt-1">Proteja sua conta exigindo um código extra ao entrar.</p>
                    </div>
                    <div class="px-3 py-1 rounded-full text-xs font-bold uppercase <?php echo $isEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>">
                        <?php echo $isEnabled ? 'ATIVO' : 'INATIVO'; ?>
                    </div>
                </div>

                <hr class="border-gray-100 mb-8">

                <?php if ($isEnabled): ?>
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Sua conta está segura!</h3>
                        <p class="text-gray-500 mt-2 mb-6">O 2FA está ativado. Você precisará do código gerado pelo seu aplicativo sempre que fizer login.</p>
                        
                        <form method="POST" onsubmit="return confirm('Tem certeza? Sua conta ficará menos segura.');">
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" class="px-6 py-2 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition font-semibold text-sm">
                                Desativar 2FA
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <div x-data="{ code: '' }" class="space-y-6">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <p class="text-sm text-blue-800"><strong>Passo 1:</strong> Baixe o Google Authenticator ou Authy no seu celular.</p>
                        </div>

                        <div class="flex flex-col md:flex-row items-center gap-8 justify-center py-4">
                            <div class="bg-white p-2 border rounded-lg shadow-sm">
                                <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code 2FA" class="w-48 h-48">
                            </div>
                            <div class="text-center md:text-left">
                                <p class="text-sm text-gray-500 mb-2">Não consegue ler o QR?</p>
                                <p class="font-mono bg-gray-100 p-2 rounded text-xs select-all text-gray-700 break-all border">
                                    <?php echo $secret; ?>
                                </p>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                            <p class="text-sm text-blue-800"><strong>Passo 2:</strong> Digite o código de 6 dígitos que apareceu no app.</p>
                        </div>

                        <form method="POST" class="max-w-xs mx-auto">
                            <input type="hidden" name="action" value="enable">
                            <input type="hidden" name="secret" value="<?php echo $secret; ?>">
                            
                            <div class="mb-4">
                                <input type="text" name="code" x-model="code" maxlength="6" class="w-full text-center text-2xl tracking-widest font-mono p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-green focus:border-transparent outline-none uppercase" placeholder="000000" required>
                            </div>

                            <button type="submit" :disabled="code.length < 6" class="w-full bg-brand-dark hover:bg-slate-800 text-white font-bold py-3 rounded-lg transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                Verificar e Ativar
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>
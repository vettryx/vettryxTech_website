<?php
// backend/admin/auth/login.php

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/Layout.php';
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

// Redireciona se já logado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../index.php');
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// --- LÓGICA DE LOGIN (Mantida) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    $email = trim($input['email'] ?? '');
    $pass  = $input['password'] ?? '';
    $code  = $input['code'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            if (!empty($user['two_factor_enabled']) && $user['two_factor_enabled'] == 1) {
                if (empty($code)) {
                    echo json_encode(['success' => false, 'require_2fa' => true]);
                    exit;
                }
                $tfa = new TwoFactorAuth(new QRServerProvider());
                if (!$tfa->verifyCode($user['two_factor_secret'], $code)) {
                    echo json_encode(['success' => false, 'message' => 'Código 2FA incorreto.']);
                    exit;
                }
            }
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        }
    } catch (Exception $e) { echo json_encode(['success' => false, 'message' => 'Erro interno.']); }
    exit;
}

// --- AQUI ENTRA O NOVO LAYOUT ---
// Carrega o topo padrão para telas de auth (já traz cores, blobs, fonts)
Layout::authHeader('Acesso Admin'); 
?>

<div x-data="loginApp()">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-brand-green/20 mb-4 shadow-inner ring-1 ring-white/20">
            <span class="text-3xl" x-text="step === '2fa' ? '🔒' : '🚀'"></span>
        </div>
        <h2 class="text-2xl font-bold text-white tracking-wide" x-text="step === '2fa' ? 'Verificação 2FA' : 'Bem-vindo'"></h2>
    </div>

    <form @submit.prevent="submitLogin" class="space-y-5">
        <div x-show="step === 'login'" x-transition>
            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1">E-mail</label>
                    <input type="email" x-model="form.email" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1">Senha</label>
                    <input type="password" x-model="form.password" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition">
                </div>
            </div>
            <div class="text-right mt-2">
                <a href="forgot_password.php" class="text-xs text-brand-green hover:text-white transition">Esqueceu a senha?</a>
            </div>
        </div>

        <div x-show="step === '2fa'" x-cloak x-transition>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1 text-center">Código de 6 Dígitos</label>
            <input type="text" x-model="form.code" maxlength="6" class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-600 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition text-center text-2xl tracking-[0.5em] font-mono" x-ref="codeinput">
            <div class="mt-4 text-center">
                <button type="button" @click="step = 'login'; errorMessage = ''" class="text-xs text-slate-400 hover:text-white underline">Voltar</button>
            </div>
        </div>

        <div x-show="errorMessage" x-cloak class="text-red-300 text-sm bg-red-900/30 p-2 rounded border border-red-500/30 text-center">
            <span x-text="errorMessage"></span>
        </div>

        <button type="submit" :disabled="loading" class="w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg shadow-lg hover:shadow-green-500/20 transition transform hover:-translate-y-0.5 disabled:opacity-50 flex justify-center">
            <span x-text="loading ? 'Validando...' : (step === '2fa' ? 'Verificar' : 'Entrar')"></span>
        </button>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loginApp', () => ({
            form: { email: '', password: '', code: '' },
            loading: false, errorMessage: '', step: 'login',
            async submitLogin() {
                this.loading = true; this.errorMessage = '';
                try {
                    const res = await fetch('login.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.form) });
                    const data = await res.json();
                    if (data.success) window.location.href = '../index.php';
                    else if (data.require_2fa) { this.step = '2fa'; this.$nextTick(() => this.$refs.codeinput.focus()); }
                    else this.errorMessage = data.message || 'Erro.';
                } catch (e) { this.errorMessage = 'Erro de conexão.'; } finally { this.loading = false; }
            }
        }));
    });
</script>

<?php Layout::authFooter(); ?>
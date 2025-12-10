<?php
// backend/admin/auth/login.php

session_start();
require_once __DIR__ . '/../../config.php';
// Carrega a biblioteca de 2FA se existir
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input) {
    header('Content-Type: application/json');
    $email = trim($input['email'] ?? '');
    $pass  = $input['password'] ?? '';
    $code  = $input['code'] ?? ''; // Código 2FA opcional

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1. Valida Senha Primeiro
        if ($user && password_verify($pass, $user['password'])) {
            
            // 2. Verifica se tem 2FA Ativo
            if (!empty($user['two_factor_enabled']) && $user['two_factor_enabled'] == 1) {
                
                // Se não enviou o código ainda, avisa o front para pedir
                if (empty($code)) {
                    echo json_encode(['success' => false, 'require_2fa' => true]);
                    exit;
                }

                // Se enviou o código, valida
                $tfa = new TwoFactorAuth(new QRServerProvider());
                if (!$tfa->verifyCode($user['two_factor_secret'], $code)) {
                    echo json_encode(['success' => false, 'message' => 'Código 2FA incorreto.']);
                    exit;
                }
            }

            // 3. Login Sucesso (Se não tiver 2FA ou se passou pelo código)
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        }
    } catch (Exception $e) { 
        echo json_encode(['success' => false, 'message' => 'Erro interno.']); 
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Admin - André Ventura</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand: { dark: "#023047", green: "#2ECC40" } } } }
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-brand-dark h-screen w-full flex items-center justify-center relative overflow-hidden font-sans">

    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-brand-green/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[10%] -right-[10%] w-[40%] h-[50%] bg-blue-500/10 rounded-full blur-[120px]"></div>
    </div>

    <div x-data="loginApp()" class="relative z-10 bg-white/10 backdrop-blur-md border border-white/10 p-8 rounded-2xl shadow-2xl w-full max-w-sm mx-4 transition-all duration-300">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-brand-green/20 mb-4 shadow-inner ring-1 ring-white/20">
                <span class="text-3xl" x-text="step === '2fa' ? '🔒' : '🚀'"></span>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-wide" x-text="step === '2fa' ? 'Verificação 2FA' : 'Bem-vindo'"></h2>
            <p class="text-slate-300 text-sm mt-2" x-text="step === '2fa' ? 'Digite o código do seu app autenticador.' : 'Painel de Controle'"></p>
        </div>

        <form @submit.prevent="submitLogin" class="space-y-5">
            
            <div x-show="step === 'login'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-10" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1">E-mail</label>
                        <input type="email" x-model="form.email" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition" placeholder="admin@asventura.com.br">
                    </div>

                    <div x-data="{ show: false }">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1">Senha</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" x-model="form.password" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition" placeholder="••••••••">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition">
                                <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="text-right mt-2">
                    <a href="forgot_password.php" class="text-xs text-brand-green hover:text-white transition">Esqueceu a senha?</a>
                </div>
            </div>

            <div x-show="step === '2fa'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-10" x-transition:enter-end="opacity-100 translate-x-0">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1 text-center">Código de 6 Dígitos</label>
                    <input type="text" x-model="form.code" maxlength="6" class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-600 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition text-center text-2xl tracking-[0.5em] font-mono" placeholder="000000" x-ref="codeinput">
                </div>
                <div class="mt-4 text-center">
                    <button type="button" @click="step = 'login'; errorMessage = ''" class="text-xs text-slate-400 hover:text-white underline">Voltar para login</button>
                </div>
            </div>

            <div x-show="errorMessage" x-cloak class="text-red-300 text-sm bg-red-900/30 p-2 rounded border border-red-500/30 text-center animate-pulse">
                <span x-text="errorMessage"></span>
            </div>

            <button type="submit" :disabled="loading" class="w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg shadow-lg hover:shadow-green-500/20 transition transform hover:-translate-y-0.5 disabled:opacity-50 flex justify-center">
                <span x-text="loading ? 'Validando...' : (step === '2fa' ? 'Verificar Código' : 'Entrar')"></span>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('loginApp', () => ({
                form: { email: '', password: '', code: '' },
                loading: false,
                errorMessage: '',
                step: 'login', // 'login' ou '2fa'
                
                async submitLogin() {
                    this.loading = true; 
                    this.errorMessage = '';
                    
                    try {
                        const res = await fetch('login.php', { 
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/json' }, 
                            body: JSON.stringify(this.form) 
                        });
                        const data = await res.json();
                        
                        if (data.success) {
                            // Login OK
                            window.location.href = 'index.php';
                        } else if (data.require_2fa) {
                            // Senha correta, mas precisa de 2FA
                            this.step = '2fa';
                            // Foca no input do código
                            this.$nextTick(() => this.$refs.codeinput.focus());
                        } else {
                            // Erro (senha errada ou código 2FA errado)
                            this.errorMessage = data.message || 'Erro desconhecido.';
                        }
                    } catch (e) { 
                        this.errorMessage = 'Erro de conexão.'; 
                    } finally { 
                        this.loading = false; 
                    }
                }
            }));
        });
    </script>
</body>
</html>
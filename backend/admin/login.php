<?php
// backend/admin/login.php

session_start();
require_once __DIR__ . '/../config.php';

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

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
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

    <div x-data="loginApp()" class="relative z-10 bg-white/10 backdrop-blur-md border border-white/10 p-8 rounded-2xl shadow-2xl w-full max-w-sm mx-4">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-brand-green/20 mb-4 shadow-inner ring-1 ring-white/20">
                <span class="text-3xl">🚀</span>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-wide">Bem-vindo</h2>
            <p class="text-slate-300 text-sm mt-2">Painel de Controle</p>
        </div>

        <form @submit.prevent="submitLogin" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1">E-mail</label>
                <input type="email" x-model="form.email" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition" placeholder="admin@asventura.com.br">
            </div>

            <div x-data="{ show: false }">
                <label class="block text-xs font-bold text-slate-300 uppercase mb-2 ml-1">Senha</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" x-model="form.password" required class="block w-full px-4 py-3 bg-slate-900/50 border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition" placeholder="••••••••">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition">
                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.523 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                        <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.477 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" /><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.742L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z" /></svg>
                    </button>
                </div>
            </div>

            <div x-show="errorMessage" x-cloak class="text-red-300 text-sm bg-red-900/30 p-2 rounded border border-red-500/30 text-center">
                <span x-text="errorMessage"></span>
            </div>

            <button type="submit" :disabled="loading" class="w-full bg-brand-green hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg shadow-lg hover:shadow-green-500/20 transition transform hover:-translate-y-0.5 disabled:opacity-50 flex justify-center">
                <span x-text="loading ? 'Validando...' : 'Entrar'"></span>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('loginApp', () => ({
                form: { email: '', password: '' }, loading: false, errorMessage: '',
                async submitLogin() {
                    this.loading = true; this.errorMessage = '';
                    try {
                        const res = await fetch('login.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(this.form) });
                        const data = await res.json();
                        if (data.success) window.location.href = 'index.php';
                        else this.errorMessage = data.message || 'Erro desconhecido.';
                    } catch (e) { this.errorMessage = 'Erro de conexão.'; } 
                    finally { this.loading = false; }
                }
            }));
        });
    </script>
</body>
</html>
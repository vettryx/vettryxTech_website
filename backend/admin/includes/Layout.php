<?php
// backend/admin/includes/Layout.php

class Layout {
    // 1. CENTRAL DE DESIGN (Onde você define as cores para o site TODO)
    private static function getThemeConfig() {
        return "
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            brand: {
                                dark:   '#023047', // Azul Fundo
                                green:  '#2ECC40', // Verde Principal
                                purple: '#5D3FD3', 
                                orange: '#FF8D37',
                                blue:   '#89D6FB',
                                light:  '#D4F0FC',
                                white:  '#ffffff'
                            }
                        },
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                            rajdhani: ['Rajdhani', 'sans-serif']
                        }
                    }
                }
            }
        ";
    }

    // 2. CABEÇALHO PARA LOGIN/SENHA (Sem menu, centralizado)
    public static function authHeader($title = 'Acesso Admin') {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?></title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
            <script><?php echo self::getThemeConfig(); ?></script>
            <style>[x-cloak] { display: none !important; }</style>
        </head>
        <body class="bg-brand-dark h-screen w-full flex items-center justify-center relative overflow-hidden font-sans text-white">
            
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
                <div class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] bg-brand-green/10 rounded-full blur-[120px]"></div>
                <div class="absolute bottom-[10%] -right-[10%] w-[40%] h-[50%] bg-blue-500/10 rounded-full blur-[120px]"></div>
            </div>

            <div class="relative z-10 w-full max-w-sm mx-4 bg-white/10 backdrop-blur-md border border-white/10 p-8 rounded-2xl shadow-2xl transition-all duration-300">
        <?php
    }

    public static function authFooter() {
        ?>
                <div class="mt-6 text-center">
                    <p class="text-xs text-slate-400">&copy; <?php echo date('Y'); ?> VettryxTech</p>
                </div>
            </div> </body>
        </html>
        <?php
    }

    // 3. CABEÇALHO DO DASHBOARD (Com Menu e Navbar)
    public static function header($title = 'Painel Admin') {
        global $pdo;
        
        // Configurações e Dados do Usuário
        $site_title = 'André Ventura';
        $favicon = ''; 
        $logo = '';
        
        // Tenta buscar do banco (fallback seguro)
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if(isset($settings['site_title'])) $site_title = $settings['site_title'];
            if(isset($settings['site_favicon'])) $favicon = $settings['site_favicon'];
            if(isset($settings['site_logo'])) $logo = $settings['site_logo'];
        } catch (Exception $e) {}

        $user_email = $_SESSION['admin_email'] ?? 'Admin';

        echo '<!DOCTYPE html>
        <html lang="pt-BR" x-data="{ darkMode: localStorage.getItem(\'theme\') === \'dark\' }" 
              x-init="$watch(\'darkMode\', val => localStorage.setItem(\'theme\', val ? \'dark\' : \'light\')); if(darkMode) document.documentElement.classList.add(\'dark\');"
              :class="{ \'dark\': darkMode }">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($title) . ' - ' . htmlspecialchars($site_title) . '</title>
            ' . ($favicon ? '<link rel="icon" href="'.$favicon.'">' : '') . '
            
            <script src="https://cdn.tailwindcss.com"></script>
            <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
            <script>' . self::getThemeConfig() . '</script>
            
            <style>
                [x-cloak] { display: none !important; }
                body, div, nav, button, input, textarea { transition: background-color 0.3s, border-color 0.3s, color 0.3s; }
            </style>
        </head>
        
        <body class="bg-brand-light dark:bg-brand-dark text-slate-800 dark:text-brand-light min-h-screen font-sans flex flex-col transition-colors duration-300">';
        
        self::navbar($user_email, $logo);
        
        echo '<main class="flex-grow max-w-7xl mx-auto w-full p-6">';
    }

    public static function footer() {
        echo '</main>
        <footer class="bg-white dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700 mt-auto backdrop-blur-md">
            <div class="max-w-7xl mx-auto py-6 px-4 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">&copy; ' . date('Y') . ' Painel Administrativo</p>
            </div>
        </footer>
        </body>
        </html>';
    }

    private static function navbar($email, $logoUrl) {
        $avatar = "https://ui-avatars.com/api/?name=$email&background=2ECC40&color=fff&bold=true";
        
        echo '
        <nav class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-20 items-center">
                    
                    <div class="flex items-center gap-3">';
                        if ($logoUrl) {
                            echo '<img src="'.$logoUrl.'" class="h-10 w-auto object-contain">';
                        } else {
                            echo '<div class="flex flex-col leading-none">
                                    <span class="text-xl font-bold tracking-widest text-brand-dark dark:text-white uppercase">André</span>
                                    <span class="text-sm font-bold tracking-widest text-brand-green uppercase">Ventura</span>
                                  </div>';
                        }
        echo '      </div>
                    
                    <div class="flex items-center gap-4 text-sm font-medium">
                        <div class="hidden md:flex gap-6 mr-4">
                            <a href="index.php" class="text-slate-600 dark:text-slate-300 hover:text-brand-green dark:hover:text-brand-green transition">Dashboard</a>
                            <a href="clients.php" class="text-slate-600 dark:text-slate-300 hover:text-brand-green dark:hover:text-brand-green transition">Clientes</a>
                            <a href="contracts.php" class="text-slate-600 dark:text-slate-300 hover:text-brand-green dark:hover:text-brand-green transition">Contratos</a>
                            <a href="projects.php" class="text-slate-600 dark:text-slate-300 hover:text-brand-green dark:hover:text-brand-green transition">Projetos</a>
                            <a href="forms.php" class="text-slate-600 dark:text-slate-300 hover:text-brand-green dark:hover:text-brand-green transition">Formulários</a>
                        </div>

                        <button @click="darkMode = !darkMode" class="p-2 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-brand-green hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                            <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            <svg x-show="darkMode" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        </button>
                        
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 focus:outline-none transition transform active:scale-95">
                                <img src="'.$avatar.'" class="h-9 w-9 rounded-full border-2 border-slate-200 dark:border-brand-green/50">
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak 
                                 class="absolute right-0 mt-3 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-100 dark:border-slate-700 py-2 z-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase">Logado como</p>
                                    <p class="text-sm font-bold text-brand-dark dark:text-white truncate">'.$email.'</p>
                                </div>
                                <a href="users.php" class="block px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-brand-light dark:hover:bg-slate-700 hover:text-brand-dark dark:hover:text-white transition">Gerenciar Equipe</a>
                                <a href="settings.php" class="block px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-brand-light dark:hover:bg-slate-700 hover:text-brand-dark dark:hover:text-white transition">Configurações</a>
                                <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                                <a href="auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 font-bold transition">Sair do Sistema</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>';
    }
}
?>
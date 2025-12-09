<?php
// backend/install.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. CORREÇÃO DE CAMINHOS ---
if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
} else {
    die("❌ Erro Crítico: O arquivo <strong>config.php</strong> não foi encontrado.<br>Caminho atual: " . __DIR__);
}

echo "<h1>🛠️ Iniciando Instalação e Limpeza...</h1>";

try {
    // --- CRIAR TABELAS PRINCIPAIS ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, image_url VARCHAR(255), link VARCHAR(255), created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS forms (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, recipient_email VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS form_fields (id INT AUTO_INCREMENT PRIMARY KEY, form_id INT NOT NULL, label VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, options TEXT NULL, placeholder VARCHAR(255) NULL, is_required BOOLEAN DEFAULT 0, order_index INT DEFAULT 999, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS form_submissions (id INT AUTO_INCREMENT PRIMARY KEY, form_id INT NOT NULL, data JSON NOT NULL, email_status VARCHAR(50) DEFAULT 'Pendente', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE)");
    
    // TABELA SETTINGS
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");
    echo "✅ Tabelas do banco de dados verificadas.<br>";

    // --- ATUALIZAÇÃO AUTOMÁTICA DA TABELA ADMINS (2FA e Recuperação) ---
    // Função auxiliar local para adicionar colunas se não existirem
    if (!function_exists('addColumnIfNotExists')) {
        function addColumnIfNotExists($pdo, $table, $column, $definition) {
            try {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
                $stmt->execute([$column]);
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $column $definition");
                    echo "✅ Coluna <strong>$column</strong> adicionada em $table.<br>";
                }
            } catch (PDOException $e) {
                echo "⚠️ Erro ao tentar adicionar $column: " . $e->getMessage() . "<br>";
            }
        }
    }

    echo "<hr><h3>🔄 Verificando estrutura para 2FA e Recuperação...</h3>";
    
    // 1. Token de recuperação de senha
    addColumnIfNotExists($pdo, 'admins', 'reset_token_hash', 'VARCHAR(64) NULL DEFAULT NULL AFTER password');
    
    // 2. Expiração do token
    addColumnIfNotExists($pdo, 'admins', 'reset_token_expires_at', 'DATETIME NULL DEFAULT NULL AFTER reset_token_hash');
    
    // 3. Segredo do 2FA (Google Authenticator)
    addColumnIfNotExists($pdo, 'admins', 'two_factor_secret', 'VARCHAR(255) NULL DEFAULT NULL AFTER reset_token_expires_at');
    
    // 4. Status do 2FA (Ligado/Desligado)
    addColumnIfNotExists($pdo, 'admins', 'two_factor_enabled', 'TINYINT(1) DEFAULT 0 AFTER two_factor_secret');
    
    // 5. Códigos de recuperação (Backup codes)
    addColumnIfNotExists($pdo, 'admins', 'two_factor_recovery_codes', 'TEXT NULL DEFAULT NULL AFTER two_factor_enabled');
    
    echo "✅ Estrutura de segurança atualizada.<br>";

    // --- CONFIGURAÇÕES PADRÃO (LIMPAS) ---
    $defaults = [
        // Identidade
        'site_title' => 'Meu Site',
        'site_description' => '',
        
        // Contatos
        'contact_email' => '',
        'contact_phone' => '',
        'contact_address' => '',
        
        // Redes Sociais
        'social_links' => '[]',
        
        // Integrações
        'recaptcha_site_key' => '',
        'recaptcha_secret' => ''
    ];
    
    // Removemos configurações antigas
    $keysToRemove = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'social_linkedin', 'social_instagram', 'social_github', 'social_whatsapp'];
    foreach ($keysToRemove as $key) {
        $pdo->prepare("DELETE FROM settings WHERE setting_key = ?")->execute([$key]);
    }
    echo "🧹 Configurações antigas limpas.<br>";
    
    // Insere os novos defaults
    $stmtCheck = $pdo->prepare("SELECT count(*) FROM settings WHERE setting_key = ?");
    $stmtInsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");

    foreach ($defaults as $key => $val) {
        $stmtCheck->execute([$key]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmtInsert->execute([$key, $val]);
            echo "➕ Configuração adicionada: $key<br>";
        }
    }
    echo "✅ Configurações padrão atualizadas.<br>";

    // --- ADMIN INICIAL ---
    $email = getenv('DEFAULT_ADMIN_EMAIL') ?: 'admin@admin.com';
    $pass  = getenv('DEFAULT_ADMIN_PASS')  ?: 'admin';
    $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() == 0) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)")->execute([$email, $hash]);
        echo "<hr>👤 Admin criado: $email / $pass<br>";
    }
    
    echo "<h2>🏁 Instalação Atualizada com Sucesso!</h2>";

} catch (PDOException $e) {
    echo "<hr>❌ <strong>ERRO:</strong> " . $e->getMessage();
}
?>
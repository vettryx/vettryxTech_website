<?php
// backend/install.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. CARREGA CONFIGURAÇÕES ---
if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
} else {
    die("❌ Erro Crítico: O arquivo <strong>config.php</strong> não foi encontrado.<br>Caminho atual: " . __DIR__);
}

echo "<h1>🛠️ Atualizando e Corrigindo Banco de Dados...</h1>";

try {
    // FUNÇÃO PARA ADICIONAR COLUNAS QUE FALTAM (ESSENCIAL)
    if (!function_exists('addColumnIfNotExists')) {
        function addColumnIfNotExists($pdo, $table, $column, $definition) {
            try {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
                $stmt->execute([$column]);
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $column $definition");
                    echo "✅ Coluna <strong>$column</strong> adicionada em $table.<br>";
                }
            } catch (PDOException $e) { /* Tabela pode não existir ainda, ignora */ }
        }
    }

    // =================================================================================
    // PARTE 1: TABELAS ORIGINAIS
    // =================================================================================
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, image_url VARCHAR(255), link VARCHAR(255), created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS forms (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, recipient_email VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS form_fields (id INT AUTO_INCREMENT PRIMARY KEY, form_id INT NOT NULL, label VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, options TEXT NULL, placeholder VARCHAR(255) NULL, is_required BOOLEAN DEFAULT 0, order_index INT DEFAULT 999, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS form_submissions (id INT AUTO_INCREMENT PRIMARY KEY, form_id INT NOT NULL, data JSON NOT NULL, email_status VARCHAR(50) DEFAULT 'Pendente', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");
    
    // --- CORREÇÕES DA TABELA ADMINS (2FA) ---
    addColumnIfNotExists($pdo, 'admins', 'reset_token_hash', 'VARCHAR(64) NULL DEFAULT NULL AFTER password');
    addColumnIfNotExists($pdo, 'admins', 'reset_token_expires_at', 'DATETIME NULL DEFAULT NULL AFTER reset_token_hash');
    addColumnIfNotExists($pdo, 'admins', 'two_factor_secret', 'VARCHAR(255) NULL DEFAULT NULL AFTER reset_token_expires_at');
    addColumnIfNotExists($pdo, 'admins', 'two_factor_enabled', 'TINYINT(1) DEFAULT 0 AFTER two_factor_secret');
    addColumnIfNotExists($pdo, 'admins', 'two_factor_recovery_codes', 'TEXT NULL DEFAULT NULL AFTER two_factor_enabled');

    echo "✅ Tabelas base verificadas.<br>";

    // =================================================================================
    // PARTE 2: GESTÃO DE CLIENTES E CONTRATOS
    // =================================================================================

    // 1. Planos
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    // Seed Planos
    if ($pdo->query("SELECT count(*) FROM plans")->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO plans (name, price, description) VALUES 
        ('Plano Blindagem Essencial', 89.90, 'Atualização Segura, Monitoramento Uptime, Backup Semanal'),
        ('Plano Gestão Completa', 149.90, 'Tudo do Essencial + 2h suporte + Pequenas alterações')");
    }

    // 2. Formas de Pagamento
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        details TEXT,
        active TINYINT(1) DEFAULT 1
    )");
    // Seed Pagamentos
    if ($pdo->query("SELECT count(*) FROM payment_methods")->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO payment_methods (name, details) VALUES 
        ('PIX', 'Chave CNPJ: 63.641.188/0001-77'),
        ('Cartão de Crédito', 'Link de pagamento enviado mensalmente')");
    }

    // 3. Clientes
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('PF', 'PJ') DEFAULT 'PJ',
        name VARCHAR(255) NOT NULL,
        document VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL,
        billing_email VARCHAR(255),
        phone VARCHAR(50),
        website VARCHAR(255),
        zip_code VARCHAR(10),
        address VARCHAR(255),
        number VARCHAR(20),
        complement VARCHAR(100),
        neighborhood VARCHAR(100),
        city VARCHAR(100),
        state VARCHAR(2),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Contratos
    $pdo->exec("CREATE TABLE IF NOT EXISTS contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contract_number VARCHAR(20) NOT NULL UNIQUE,
        client_id INT NOT NULL,
        plan_id INT NOT NULL,
        payment_method_id INT NOT NULL,
        domain_url VARCHAR(255) NOT NULL,
        monthly_price DECIMAL(10, 2) NOT NULL,
        due_day INT NOT NULL,
        issue_date DATE NOT NULL,
        start_date DATE NOT NULL,
        status ENUM('draft', 'active', 'suspended', 'cancelled') DEFAULT 'draft',
        token VARCHAR(64) UNIQUE, -- Coluna Essencial
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id),
        FOREIGN KEY (plan_id) REFERENCES plans(id),
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
    )");

    // --- CORREÇÃO CRÍTICA DO ERRO DE TOKEN ---
    // Se a tabela já existia sem o token, isso vai consertar agora:
    addColumnIfNotExists($pdo, 'contracts', 'token', 'VARCHAR(64) UNIQUE AFTER status');

    echo "✅ Estrutura de Gestão (Clientes/Contratos) verificada e corrigida.<br>";

    // --- Configurações Finais ---
    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
        ('contract_fine_percent', '2'),
        ('contract_interest_percent', '1'),
        ('tech_hour_value', '150.00')
    ");

    // Admin Inicial (Mantido)
    $email = getenv('DEFAULT_ADMIN_EMAIL') ?: 'admin@admin.com';
    $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() == 0) {
        $pass = getenv('DEFAULT_ADMIN_PASS') ?: 'admin';
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)")->execute([$email, $hash]);
    }
    
    echo "<h2>🏁 Instalação/Correção Concluída!</h2>";

} catch (PDOException $e) {
    echo "<hr>❌ <strong>ERRO:</strong> " . $e->getMessage();
}
?>
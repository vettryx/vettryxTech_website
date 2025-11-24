<?php
// backend/install.php

// Carrega as configurações (que já leem o .env)
require 'config.php';

echo "<h1>Iniciando Instalação do Banco de Dados...</h1>";

try {
    // --- 1. CRIAR TABELA DE ADMINS ---
    $sqlAdmins = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlAdmins);
    echo "✅ Tabela 'admins' verificada.<br>";

    // --- 2. CRIAR TABELA DE PROJETOS ---
    $sqlProjects = "CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        link VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlProjects);
    echo "✅ Tabela 'projects' verificada.<br>";

    // --- 3. CRIAR TABELA DE CONTATOS ---
    $sqlContacts = "CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'Novo',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlContacts);
    echo "✅ Tabela 'contacts' verificada.<br>";

    // --- 4. CRIAR O ADMIN INICIAL (Baseado no .env) ---
    // Pega as variáveis do ambiente (ou usa um padrão se esquecerem de por no .env)
    $email = getenv('DEFAULT_ADMIN_EMAIL') ?: 'admin@admin.com';
    $pass  = getenv('DEFAULT_ADMIN_PASS')  ?: 'admin';

    // Verifica se já existe algum admin para não duplicar
    $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() == 0) {
        // Criptografa a senha (NUNCA salve senha pura)
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $insert = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
        $insert->execute([$email, $hash]);
        
        echo "<hr>✅ <strong>SUCESSO:</strong> Usuário Admin criado!<br>";
        echo "Email: $email<br>";
        echo "Senha: (A que está no seu arquivo .env)<br>";
    } else {
        echo "<hr>ℹ️ O usuário admin '$email' já existe. Nenhuma alteração feita.<br>";
    }

} catch (PDOException $e) {
    echo "<hr>❌ <strong>ERRO CRÍTICO:</strong> " . $e->getMessage();
}
?>
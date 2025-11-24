<?php
// backend/config.php

// 1. TENTA LER VARIÁVEIS DE AMBIENTE (Docker já entrega isso pronto)
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$smtp_host = getenv('SMTP_HOST'); // Pega valores opcionais também

// 2. SE NÃO ACHOU AS VARIÁVEIS (Cenário Hostinger), TENTA LER O ARQUIVO .ENV
if (!$db_user) {
    $envPath = __DIR__ . '/.env';

    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $value = trim($value, '"\'');

                // Popula as variáveis locais
                switch ($name) {
                    case 'DB_HOST': $db_host = $value; break;
                    case 'DB_NAME': $db_name = $value; break;
                    case 'DB_USER': $db_user = $value; break;
                    case 'DB_PASS': $db_pass = $value; break;
                    case 'SMTP_HOST': $smtp_host = $value; define('SMTP_HOST', $value); break;
                    case 'SMTP_PORT': define('SMTP_PORT', $value); break;
                    case 'SMTP_USER': define('SMTP_USER', $value); break;
                    case 'SMTP_PASS': define('SMTP_PASS', $value); break;
                }
                
                // Popula o getenv para o resto do sistema
                putenv("$name=$value");
            }
        }
    }
}

// 3. DEFINE CONSTANTES GERAIS (Garante que existam mesmo se vierem do Docker)
if (!defined('SMTP_HOST') && $smtp_host) {
    define('SMTP_HOST', getenv('SMTP_HOST'));
    define('SMTP_PORT', getenv('SMTP_PORT'));
    define('SMTP_USER', getenv('SMTP_USER'));
    define('SMTP_PASS', getenv('SMTP_PASS'));
}
define('CORS_ORIGIN', '*');

// 4. VERIFICAÇÃO FINAL (Só morre se realmente não tiver dados nem na memória nem no arquivo)
if (empty($db_user)) {
    header('Content-Type: application/json');
    // Em produção, isso ajuda a debugar se o .env está sendo lido
    die(json_encode([
        "erro" => "Credenciais de banco não encontradas.", 
        "debug" => "Não achou nem via getenv (Docker) nem via arquivo .env (Hostinger)."
    ]));
}

// --- INICIALIZAÇÃO DO BANCO ---
function init_db() {
    global $db_host, $db_name, $db_user, $db_pass;

    // Fallback para localhost se o host estiver vazio
    $host = $db_host ?: 'localhost';

    try {
        $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;

    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(["erro" => "Falha na conexão com o Banco: " . $e->getMessage()]);
        exit;
    }
}

// Inicializa a conexão
$pdo = init_db();

// --- FUNÇÕES AUXILIARES ---

function send_cors_headers() {
    header("Access-Control-Allow-Origin: " . CORS_ORIGIN);
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function authenticate() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(["erro" => "Não autorizado."]);
        exit;
    }
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
<?php
// backend/debug_form.php
require 'config.php';

echo "<h1>🔍 Diagnóstico do Banco de Dados</h1>";

try {
    $stmt = $pdo->query("SELECT id, title, slug, recipient_email FROM forms");
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Slug</th><th>E-mail de Destino (O que está no banco)</th></tr>";

    foreach ($forms as $f) {
        echo "<tr>";
        echo "<td>" . $f['id'] . "</td>";
        echo "<td>" . $f['title'] . "</td>";
        echo "<td>" . $f['slug'] . "</td>";
        // AQUI ESTÁ A PROVA REAL:
        echo "<td style='color:red; font-weight:bold;'>" . $f['recipient_email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
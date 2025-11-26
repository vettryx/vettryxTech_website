<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'auth.php';
require '../config.php';

$form_id = $_GET['form_id'] ?? null;

if (!$form_id) {
    header("Location: forms.php");
    exit;
}

// Busca Formulário
$stmt = $pdo->prepare("SELECT title FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) die("Formulário não encontrado.");

// Busca Mensagens
$stmtSub = $pdo->prepare("SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC");
$stmtSub->execute([$form_id]);
$submissions = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

// Define colunas dinâmicas
$dynamic_columns = [];
if (count($submissions) > 0) {
    $first_data = json_decode($submissions[0]['data'], true);
    if (is_array($first_data)) {
        $dynamic_columns = array_keys($first_data);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório - <?php echo htmlspecialchars($form['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">

    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center sticky top-0 z-10">
        <div class="flex flex-col">
            <span class="text-xs text-gray-500 uppercase">Relatório de Envios</span>
            <h1 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($form['title']); ?></h1>
        </div>
        <a href="forms.php" class="text-blue-600 hover:underline text-sm font-bold">← Voltar</a>
    </nav>

    <div class="container mx-auto p-4 max-w-7xl">
        
        <div class="mb-4 flex justify-between items-center">
            <span class="text-sm text-gray-600">Total: <strong><?php echo count($submissions); ?></strong> registros</span>
        </div>

        <?php if(empty($submissions)): ?>
            <div class="bg-white p-12 rounded shadow text-center border border-dashed border-gray-300">
                <p class="text-gray-400 text-lg">📭 Nenhuma mensagem recebida.</p>
            </div>
        <?php else: ?>
            
            <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
                <table class="min-w-full text-sm text-left text-gray-600">
                    <thead class="bg-gray-800 text-white uppercase font-bold text-xs">
                        <tr>
                            <th class="px-4 py-3 w-10 text-center">Ver</th>
                            <th class="px-4 py-3 w-32">Data</th>
                            <th class="px-4 py-3 w-24 text-center">Status</th>
                            
                            <?php foreach($dynamic_columns as $col): ?>
                                <th class="px-4 py-3 max-w-xs truncate">
                                    <?php echo str_replace('_', ' ', $col); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach($submissions as $sub): 
                            $data = json_decode($sub['data'], true);
                            $jsonDataSafe = htmlspecialchars($sub['data'], ENT_QUOTES, 'UTF-8'); // Prepara dados para o JS
                            $statusColor = ($sub['email_status'] === 'Enviado') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                        ?>
                        <tr class="hover:bg-blue-50 transition">
                            
                            <td class="px-4 py-3 text-center">
                                <button onclick='openModal(<?php echo $jsonDataSafe; ?>, "<?php echo $sub['created_at']; ?>", "<?php echo $sub['email_status']; ?>")' 
                                        class="bg-blue-500 hover:bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center shadow transition" title="Ver Detalhes Completo">
                                    👁️
                                </button>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap font-mono text-gray-500">
                                <?php echo date('d/m/y H:i', strtotime($sub['created_at'])); ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $statusColor; ?>">
                                    <?php echo $sub['email_status']; ?>
                                </span>
                            </td>

                            <?php foreach($dynamic_columns as $col): ?>
                                <td class="px-4 py-3 max-w-[150px] truncate text-gray-700">
                                    <?php echo htmlspecialchars($data[$col] ?? '-'); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl transform transition-all scale-100">
            
            <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold">Detalhes da Mensagem</h3>
                    <span id="modalDate" class="text-xs text-gray-400 font-mono"></span>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
            </div>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div id="modalStatus" class="mb-4"></div>
                <div id="modalContent" class="space-y-4">
                    </div>
            </div>

            <div class="bg-gray-50 px-6 py-3 rounded-b-lg text-right border-t">
                <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('detailsModal');
        const modalContent = document.getElementById('modalContent');
        const modalDate = document.getElementById('modalDate');
        const modalStatus = document.getElementById('modalStatus');

        function openModal(data, date, status) {
            // Limpa conteúdo anterior
            modalContent.innerHTML = '';
            
            // Define data e status
            modalDate.innerText = new Date(date).toLocaleString('pt-BR');
            const statusColor = status === 'Enviado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            modalStatus.innerHTML = `<span class="px-3 py-1 rounded text-xs font-bold uppercase ${statusColor}">E-mail: ${status}</span>`;

            // Gera os campos dinamicamente
            for (const [key, value] of Object.entries(data)) {
                const label = key.replace(/_/g, ' ').toUpperCase();
                const safeValue = value ? value.toString() : '-'; // Garante string
                
                // Cria o HTML para cada campo
                const div = document.createElement('div');
                div.className = 'border-b border-gray-100 pb-2';
                div.innerHTML = `
                    <label class="block text-xs text-gray-400 font-bold mb-1">${label}</label>
                    <div class="text-gray-800 text-sm whitespace-pre-wrap leading-relaxed">${safeValue}</div>
                `;
                modalContent.appendChild(div);
            }

            // Mostra o modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Fecha ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        // Fecha com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    </script>

</body>
</html>
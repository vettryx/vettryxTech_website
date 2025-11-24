<?php
require 'auth.php';
require '../config.php';

$msg = "";
$projectToEdit = null; // Variável para guardar os dados quando for edição

// --- 1. LÓGICA DE CADASTRO E EDIÇÃO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $link  = $_POST['link'];
    $id    = $_POST['id'] ?? null; // ID só existe na edição
    
    // --- TRATAMENTO DA IMAGEM ---
    $final_image_url = "";

    // A. Usuário fez upload de NOVA imagem?
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $upload_dir = '../uploads/';
        $filename = time() . '_' . basename($_FILES['image_file']['name']);
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $filename)) {
            $final_image_url = '/uploads/' . $filename;
        }
    } 
    // B. Usuário colou um NOVO link?
    elseif (!empty($_POST['image_url'])) {
        $final_image_url = $_POST['image_url'];
    }
    // C. (Só Edição) Nenhuma nova imagem? Mantém a antiga (hidden field)
    elseif ($action === 'update' && !empty($_POST['existing_image'])) {
        $final_image_url = $_POST['existing_image'];
    }

    // --- EXECUÇÃO NO BANCO ---
    if ($action === 'create') {
        // INSERIR NOVO
        if ($final_image_url) {
            $sql = "INSERT INTO projects (title, description, link, image_url) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$title, $desc, $link, $final_image_url])) {
                $msg = "✅ Project created successfully!";
            }
        } else {
            $msg = "⚠️ Image is required for new projects.";
        }
    } 
    elseif ($action === 'update' && $id) {
        // ATUALIZAR EXISTENTE
        $sql = "UPDATE projects SET title=?, description=?, link=?, image_url=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$title, $desc, $link, $final_image_url, $id])) {
            $msg = "✅ Project updated successfully!";
            // Limpa a variável de edição para voltar ao modo "Novo"
            $projectToEdit = null; 
        } else {
            $msg = "❌ Error updating project.";
        }
    }
}

// --- 2. LÓGICA DE EXCLUSÃO ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: projects.php");
    exit;
}

// --- 3. LÓGICA DE CARREGAR PARA EDITAR ---
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $projectToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- 4. BUSCAR TODOS (PARA A LISTA) ---
$stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Projects</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen pb-10">

    <nav class="bg-white shadow p-4 mb-8 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">📂 Manage Projects</h1>
        <div>
            <a href="projects.php" class="text-gray-600 hover:text-blue-600 mr-4 text-sm font-bold">New Project</a>
            <a href="index.php" class="text-blue-600 hover:underline text-sm">← Dashboard</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 max-w-4xl">
        
        <?php if($msg): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8 border-t-4 <?php echo $projectToEdit ? 'border-yellow-400' : 'border-blue-500'; ?>">
            <h2 class="text-lg font-bold mb-4 border-b pb-2">
                <?php echo $projectToEdit ? '✏️ Edit Project' : '✨ New Project'; ?>
            </h2>
            
            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-4">
                <input type="hidden" name="action" value="<?php echo $projectToEdit ? 'update' : 'create'; ?>">
                
                <?php if ($projectToEdit): ?>
                    <input type="hidden" name="id" value="<?php echo $projectToEdit['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo $projectToEdit['image_url']; ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-bold text-gray-700">Project Name / Client</label>
                    <input type="text" name="title" required 
                           value="<?php echo $projectToEdit['title'] ?? ''; ?>"
                           class="w-full p-2 border rounded mt-1 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700">Description</label>
                    <textarea name="description" rows="3" class="w-full p-2 border rounded mt-1"><?php echo $projectToEdit['description'] ?? ''; ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700">Project Link</label>
                    <input type="text" name="link" 
                           value="<?php echo $projectToEdit['link'] ?? ''; ?>"
                           class="w-full p-2 border rounded mt-1">
                </div>

                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Project Image</label>
                    
                    <?php if ($projectToEdit): ?>
                        <div class="mb-4 flex items-center gap-4">
                            <span class="text-xs text-gray-500">Current Image:</span>
                            <img src="<?php echo strpos($projectToEdit['image_url'], 'http') === 0 ? $projectToEdit['image_url'] : 'http://localhost:8000'.$projectToEdit['image_url']; ?>" class="h-12 w-12 object-cover rounded border">
                            <span class="text-xs text-yellow-600 font-bold">(Leave blank below to keep this image)</span>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Option A: Upload New File</span>
                        <input type="file" name="image_file" accept="image/*" class="block w-full text-sm mt-1"/>
                    </div>
                    <div class="text-center text-gray-400 text-xs my-2">- OR -</div>
                    <div>
                        <span class="text-xs font-semibold text-gray-500 uppercase">Option B: New External URL</span>
                        <input type="text" name="image_url" placeholder="https://..." class="w-full p-2 border rounded mt-1 text-sm">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 <?php echo $projectToEdit ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-blue-600 hover:bg-blue-700'; ?> text-white font-bold py-3 px-4 rounded transition">
                        <?php echo $projectToEdit ? 'Save Changes' : '+ Create Project'; ?>
                    </button>
                    
                    <?php if ($projectToEdit): ?>
                        <a href="projects.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-4 rounded text-center">
                            Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <h2 class="text-xl font-bold mb-4">Existing Projects</h2>
        <div class="grid gap-4">
            <?php foreach($projects as $proj): ?>
                <div class="bg-white p-4 rounded shadow flex flex-col md:flex-row gap-4 items-center">
                    
                    <div class="w-full md:w-24 h-24 bg-gray-200 rounded overflow-hidden flex-shrink-0">
                        <?php 
                            $imgSrc = strpos($proj['image_url'], 'http') === 0 
                                      ? $proj['image_url'] 
                                      : 'http://localhost:8000' . $proj['image_url'];
                        ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Project" class="w-full h-full object-cover">
                    </div>

                    <div class="flex-grow">
                        <h3 class="font-bold text-lg"><?php echo htmlspecialchars($proj['title']); ?></h3>
                        <p class="text-gray-600 text-sm mb-1"><?php echo htmlspecialchars($proj['description']); ?></p>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="?edit=<?php echo $proj['id']; ?>" 
                           class="bg-yellow-100 text-yellow-700 px-3 py-2 rounded hover:bg-yellow-200 text-sm font-bold border border-yellow-200">
                           ✏️ Edit
                        </a>

                        <a href="?delete=<?php echo $proj['id']; ?>" 
                           onclick="return confirm('Are you sure?')"
                           class="bg-red-100 text-red-600 px-3 py-2 rounded hover:bg-red-200 text-sm font-bold border border-red-200">
                           🗑️ Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</body>
</html>
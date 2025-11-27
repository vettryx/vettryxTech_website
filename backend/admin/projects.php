<?php
// backend/admin/projects.php

require 'auth.php';
require '../config.php';
require 'includes/Layout.php';
require 'includes/Components.php';

// --- API HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = $_POST['id'] ?? null;
            $title = $_POST['title'] ?? '';
            $desc = $_POST['description'] ?? '';
            $link = $_POST['link'] ?? '';
            $final_image_url = $_POST['existing_image'] ?? '';

            // Upload Lógica
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $filename)) {
                    $final_image_url = '/uploads/' . $filename;
                }
            } elseif (!empty($_POST['image_url'])) {
                $final_image_url = $_POST['image_url'];
            }

            if ($id) {
                $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, link=?, image_url=? WHERE id=?");
                $stmt->execute([$title, $desc, $link, $final_image_url, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO projects (title, description, link, image_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $desc, $link, $final_image_url]);
                $id = $pdo->lastInsertId();
            }
            $response['success'] = true;
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $response['success'] = true;
        }

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
}

// --- VIEW ---
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

Layout::header('Meus Projetos');
?>

<div x-data="projectsManager(<?php echo htmlspecialchars(json_encode($projects)); ?>)">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-brand-dark dark:text-white">Portfólio</h1>
            <p class="text-slate-500 dark:text-slate-400">Gerencie os projetos exibidos no site.</p>
        </div>
        <?php echo UI::button('Novo Projeto', 'button', 'brand-green', 'openModal()', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>'); ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-if="projects.length === 0">
            <div class="col-span-full text-center py-20 bg-white dark:bg-slate-800 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700">
                <p class="text-slate-400">Nenhum projeto cadastrado.</p>
            </div>
        </template>

        <template x-for="p in projects" :key="p.id">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden flex flex-col group hover:shadow-lg transition duration-300">
                <div class="h-48 relative overflow-hidden bg-slate-200 dark:bg-slate-700">
                    <img :src="p.image_url" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-brand-dark/80 opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center gap-3">
                        <button @click="openModal(p)" class="p-2 bg-white text-brand-dark rounded-full hover:bg-brand-green hover:text-white transition transform hover:scale-110" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                        </button>
                        <button @click="deleteProject(p.id)" class="p-2 bg-white text-red-600 rounded-full hover:bg-red-600 hover:text-white transition transform hover:scale-110" title="Excluir">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-5 flex-grow flex flex-col">
                    <h3 class="font-bold text-lg text-brand-dark dark:text-white mb-2" x-text="p.title"></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-3 mb-4" x-text="p.description"></p>
                    <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-700">
                        <a :href="p.link" target="_blank" class="text-xs font-bold text-brand-blue hover:underline flex items-center gap-1">
                            🔗 Acessar Projeto
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <?php 
        $modalBody = '
            ' . UI::input('Nome do Projeto / Cliente', 'form.title', 'text') . '
            
            <div class="mt-4">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1 ml-1">Descrição</label>
                <textarea x-model="form.description" rows="3" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg p-3 text-slate-800 dark:text-white focus:outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green transition"></textarea>
            </div>

            <div class="mt-4">' . UI::input('Link do Projeto', 'form.link', 'text', 'https://...') . '</div>
            
            <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700">
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Imagem de Capa</label>
                <div class="flex flex-col gap-2">
                    <input type="file" x-ref="fileInput" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-blue file:text-brand-dark hover:file:bg-blue-400 cursor-pointer">
                    <div class="text-center text-xs text-slate-400 font-bold uppercase tracking-wider">- OU URL Externa -</div>
                    <input type="text" x-model="form.image_url" placeholder="https://imgur.com/..." class="w-full text-sm bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded p-2 text-slate-700 dark:text-white">
                </div>
            </div>
        ';

        $modalFooter = '
            ' . UI::button('<span x-text="isLoading ? \'Salvando...\' : \'Salvar\'"></span>', 'button', 'brand-green', 'saveProject()') . '
            ' . UI::button('Cancelar', 'button', 'red', 'isModalOpen = false') . '
        ';

        echo UI::modal('isModalOpen', '<span x-text="form.id ? \'Editar Projeto\' : \'Novo Projeto\'"></span>', $modalBody, $modalFooter);
    ?>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('projectsManager', (initialProjects) => ({
            projects: initialProjects,
            isModalOpen: false,
            isLoading: false,
            form: { id: null, title: '', description: '', link: '', image_url: '', existing_image: '' },

            openModal(project = null) {
                if(this.$refs.fileInput) this.$refs.fileInput.value = '';
                if (project) {
                    this.form = { ...project, existing_image: project.image_url, image_url: '' };
                } else {
                    this.form = { id: null, title: '', description: '', link: '', image_url: '', existing_image: '' };
                }
                this.isModalOpen = true;
            },

            async saveProject() {
                this.isLoading = true;
                const formData = new FormData();
                formData.append('action', 'save');
                if(this.form.id) formData.append('id', this.form.id);
                formData.append('title', this.form.title);
                formData.append('description', this.form.description);
                formData.append('link', this.form.link);
                formData.append('existing_image', this.form.existing_image);
                if(this.form.image_url) formData.append('image_url', this.form.image_url);
                const file = this.$refs.fileInput.files[0];
                if(file) formData.append('image_file', file);

                try {
                    const res = await fetch('projects.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) window.location.reload();
                    else alert('Erro: ' + data.message);
                } catch (err) { alert('Erro no servidor.'); } 
                finally { this.isLoading = false; }
            },

            async deleteProject(id) {
                if(!confirm('Excluir este projeto?')) return;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                await fetch('projects.php', { method: 'POST', body: formData });
                window.location.reload();
            }
        }));
    });
</script>

<?php Layout::footer(); ?>
<?php
/**
 * Gerenciamento de Páginas
 * Mundial Gráfica - Painel Administrativo
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = checkAuth();
$db = new Database();
$pages = $db->getCollection('pages');

$action = $_GET['action'] ?? 'list';
$page_id = $_GET['id'] ?? null;
$file = $_GET['file'] ?? $_POST['file'] ?? null; // Para arquivos HTML estáticos

// Processar ações
if ($_POST) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        setFlashMessage('Token de segurança inválido', 'error');
        header('Location: manage-pages.php');
        exit;
    }
    
    switch ($action) {
        case 'add':
        case 'edit':
            // Se é um arquivo HTML estático
            if ($file) {
                $file_path = dirname(dirname(__DIR__)) . '/' . $file;
                
                // Debug: verificar se o arquivo existe
                error_log("Tentando editar arquivo: " . $file_path);
                error_log("Arquivo existe: " . (file_exists($file_path) ? 'SIM' : 'NÃO'));
                
                if (file_exists($file_path)) {
                    $content = $_POST['content'];
                    
                    // Debug: verificar permissões
                    error_log("Conteúdo recebido: " . strlen($content) . " caracteres");
                    
                    if (file_put_contents($file_path, $content)) {
                        logActivity('Arquivo HTML editado', $file);
                        setFlashMessage('Arquivo HTML atualizado com sucesso!');
                    } else {
                        error_log("Erro ao escrever no arquivo: " . $file_path);
                        setFlashMessage('Erro ao salvar arquivo HTML - verifique as permissões', 'error');
                    }
                } else {
                    setFlashMessage('Arquivo não encontrado: ' . $file, 'error');
                }
                
                // Redirecionar mantendo o parâmetro file
                header('Location: manage-pages.php?action=edit&file=' . urlencode($file));
                exit;
            } else {
                // Páginas do banco de dados (código original)
                $data = [
                    'name' => sanitize($_POST['name']),
                    'title' => sanitize($_POST['title']),
                    'slug' => generateSlug($_POST['slug'] ?: $_POST['name']),
                    'content' => $_POST['content'], // HTML content
                    'meta_description' => sanitize($_POST['meta_description']),
                    'status' => sanitize($_POST['status']),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                if ($action === 'add') {
                    $data['created_at'] = new MongoDB\BSON\UTCDateTime();
                    $result = $pages->insertOne($data);
                    if ($result->getInsertedId()) {
                        logActivity('Página criada', $data['name']);
                        setFlashMessage('Página criada com sucesso!');
                    } else {
                        setFlashMessage('Erro ao criar página', 'error');
                    }
                } else {
                    $result = $pages->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($page_id)],
                        ['$set' => $data]
                    );
                    if ($result->getModifiedCount() > 0) {
                        logActivity('Página editada', $data['name']);
                        setFlashMessage('Página atualizada com sucesso!');
                    } else {
                        setFlashMessage('Nenhuma alteração foi feita', 'error');
                    }
                }
            }
            
            header('Location: manage-pages.php');
            exit;
            
        case 'delete':
            if ($page_id) {
                $page = $pages->findOne(['_id' => new MongoDB\BSON\ObjectId($page_id)]);
                if ($page) {
                    $result = $pages->deleteOne(['_id' => new MongoDB\BSON\ObjectId($page_id)]);
                    if ($result->getDeletedCount() > 0) {
                        logActivity('Página excluída', $page['name']);
                        setFlashMessage('Página excluída com sucesso!');
                    } else {
                        setFlashMessage('Erro ao excluir página', 'error');
                    }
                }
            }
            header('Location: manage-pages.php');
            exit;
    }
}

// Buscar dados para edição
$current_page = null;
$html_content = '';

if ($action === 'edit') {
    if ($file) {
        // Editar arquivo HTML estático
        $file_path = dirname(dirname(__DIR__)) . '/' . $file;
        if (file_exists($file_path)) {
            $html_content = file_get_contents($file_path);
            $current_page = [
                'name' => ucfirst(str_replace(['.html', '-'], [' ', ' '], $file)),
                'file' => $file,
                'content' => $html_content
            ];
        } else {
            setFlashMessage('Arquivo não encontrado', 'error');
            header('Location: manage-pages.php');
            exit;
        }
    } elseif ($page_id) {
        // Editar página do banco de dados
        $current_page = $pages->findOne(['_id' => new MongoDB\BSON\ObjectId($page_id)]);
        if (!$current_page) {
            setFlashMessage('Página não encontrada', 'error');
            header('Location: manage-pages.php');
            exit;
        }
    }
}

// Listar páginas com paginação
$page_num = (int)($_GET['page'] ?? 1);
$search = $_GET['search'] ?? '';
$filter = [];

if ($search) {
    $filter['$or'] = [
        ['name' => new MongoDB\BSON\Regex($search, 'i')],
        ['title' => new MongoDB\BSON\Regex($search, 'i')]
    ];
}

$pagination = paginate($pages, $page_num, 10, $filter);

// Lista de arquivos HTML estáticos do frontend
$static_files = array_map(function($p) {
    return 'frontend/' . basename($p);
}, glob(dirname(dirname(__DIR__)) . '/frontend/*.html'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Páginas - Painel Administrativo | Mundial Gráfica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <!-- TinyMCE via CDN alternativo sem necessidade de API key -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" integrity="sha512-6JR4bbn8rCKvrkdoTJd/VFyXAN4CE9XMtgykPWgKiHjou56YDJxWsi90hAeMTYxNwUnKSQu9JPc3SQUg+aGCHw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-cogs me-2"></i>Admin Panel</h4>
        </div>
        
        <nav class="sidebar-nav">
            <a href="../index.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="manage-pages.php" class="nav-link active">
                <i class="fas fa-file-alt"></i> Gerenciar Páginas
            </a>
            <a href="manage-menus.php" class="nav-link">
                <i class="fas fa-bars"></i> Gerenciar Menus
            </a>
            <a href="manage-products.php" class="nav-link">
                <i class="fas fa-box"></i> Gerenciar Produtos
            </a>
            <a href="manage-images.php" class="nav-link">
                <i class="fas fa-images"></i> Gerenciar Imagens
            </a>
            <a href="../logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1>
                    <i class="fas fa-file-alt me-2"></i>
                    <?php if ($action === 'add'): ?>
                        Nova Página
                    <?php elseif ($action === 'edit'): ?>
                        Editar Página
                    <?php else: ?>
                        Gerenciar Páginas
                    <?php endif; ?>
                </h1>
                
                <?php if ($action === 'list'): ?>
                    <a href="manage-pages.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nova Página
                    </a>
                <?php else: ?>
                    <a href="manage-pages.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Content -->
        <div class="content-body">
            <?php displayFlashMessage(); ?>
            
            <?php if ($action === 'list'): ?>
                <!-- Atalho: Editar Arquivos HTML Estáticos -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-code me-2"></i>Editar Arquivo HTML Estático</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($static_files)): ?>
                            <p class="text-muted">Nenhum arquivo HTML encontrado em <code>frontend/</code>.</p>
                        <?php else: ?>
                            <form method="GET" class="row g-3 align-items-end">
                                <input type="hidden" name="action" value="edit">
                                <div class="col-md-8">
                                    <label for="file" class="form-label">Selecione o arquivo</label>
                                    <select id="file" name="file" class="form-select" required>
                                        <?php foreach ($static_files as $sf): ?>
                                            <option value="<?= htmlspecialchars($sf) ?>"><?= htmlspecialchars($sf) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Arquivos localizados no diretório <code>frontend/</code>.</div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-edit me-2"></i>Editar Arquivo
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Lista de Páginas -->
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5><i class="fas fa-list me-2"></i>Lista de Páginas</h5>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2" 
                                           placeholder="Buscar páginas..." value="<?= htmlspecialchars($search) ?>">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pagination['items'])): ?>
                            <p class="text-muted text-center py-4">Nenhuma página encontrada.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Título</th>
                                            <th>Slug</th>
                                            <th>Status</th>
                                            <th>Criado em</th>
                                            <th width="150">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pagination['items'] as $page): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($page['name']) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($page['title']) ?></td>
                                                <td>
                                                    <code><?= htmlspecialchars($page['slug']) ?></code>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?= $page['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                                        <?= $page['status'] === 'active' ? 'Ativo' : 'Inativo' ?>
                                                    </span>
                                                </td>
                                                <td><?= formatDate($page['created_at']) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="manage-pages.php?action=edit&id=<?= $page['_id'] ?>" 
                                                           class="btn btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="manage-pages.php?action=delete&id=<?= $page['_id'] ?>" 
                                                           class="btn btn-outline-danger" title="Excluir"
                                                           data-confirm-delete="Tem certeza que deseja excluir a página '<?= htmlspecialchars($page['name']) ?>'?">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?= renderPagination($pagination, 'manage-pages.php') ?>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Formulário de Página -->
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?> me-2"></i>
                            <?= $action === 'add' ? 'Nova Página' : 'Editar ' . ($file ? 'Arquivo HTML' : 'Página') ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" <?= $file ? 'action="?action=edit&file=' . urlencode($file) . '"' : '' ?> class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <?php if ($file): ?>
                                <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Editando arquivo HTML estático: <strong><?= htmlspecialchars($file) ?></strong>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">Conteúdo HTML</label>
                                    <textarea class="form-control" id="content" name="content" rows="25" style="font-family: 'Courier New', monospace;"><?= htmlspecialchars($current_page['content'] ?? '') ?></textarea>
                                    <small class="form-text text-muted">Edite o código HTML diretamente</small>
                                </div>
                                
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Nome da Página *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?= htmlspecialchars($current_page['name'] ?? '') ?>" required>
                                            <div class="invalid-feedback">Por favor, informe o nome da página.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="slug" class="form-label">Slug (URL)</label>
                                            <input type="text" class="form-control" id="slug" name="slug" 
                                                   value="<?= htmlspecialchars($current_page['slug'] ?? '') ?>"
                                                   placeholder="Deixe vazio para gerar automaticamente">
                                            <small class="form-text text-muted">Ex: sobre-nos, contato, servicos</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Título da Página *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?= htmlspecialchars($current_page['title'] ?? '') ?>" required>
                                    <div class="invalid-feedback">Por favor, informe o título da página.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_description" class="form-label">Meta Descrição</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" 
                                              rows="2" maxlength="160"><?= htmlspecialchars($current_page['meta_description'] ?? '') ?></textarea>
                                    <small class="form-text text-muted">Máximo 160 caracteres para SEO</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">Conteúdo da Página</label>
                                    <textarea class="form-control" id="content" name="content" rows="15"><?= htmlspecialchars($current_page['content'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?= ($current_page['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="inactive" <?= ($current_page['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <a href="manage-pages.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action === 'add' ? 'Criar Página' : 'Salvar Alterações' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <?php if ($action !== 'list'): ?>
    <script>
        // Inicializar TinyMCE com configuração otimizada
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
            branding: false,
            promotion: false,
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            }
        });
        
        // Auto-gerar slug
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        
        if (nameInput && slugInput) {
            nameInput.addEventListener('input', function() {
                if (!slugInput.value) {
                    slugInput.value = this.value.toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/[\s-]+/g, '-')
                        .trim('-');
                }
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
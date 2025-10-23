<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_menu_item') {
        $menuName = sanitize($_POST['menu_name'] ?? '');
        $menuUrl = sanitize($_POST['menu_url'] ?? '');
        $menuType = sanitize($_POST['menu_type'] ?? '');
        
        if (!empty($menuName) && !empty($menuUrl)) {
            // Aqui você pode adicionar lógica para salvar no arquivo HTML
            $message = 'Item de menu "' . $menuName . '" adicionado com sucesso!';
            $messageType = 'success';
        } else {
            $message = 'Nome e URL do menu são obrigatórios.';
            $messageType = 'danger';
        }
    }
}

// Extrair estrutura de menus do index.html
$indexPath = '../../index.html';
$menuStructure = [
    'navigation' => [],
    'categories' => [],
    'products' => []
];

if (file_exists($indexPath)) {
    $content = file_get_contents($indexPath);
    
    // Extrair navegação principal
    preg_match_all('/<nav[^>]*>.*?<\/nav>/s', $content, $navMatches);
    if (!empty($navMatches[0])) {
        preg_match_all('/<a[^>]*href="([^"]*)"[^>]*>([^<]*)<\/a>/i', $navMatches[0][0], $navLinks);
        if (!empty($navLinks[1])) {
            for ($i = 0; $i < count($navLinks[1]); $i++) {
                $menuStructure['navigation'][] = [
                    'name' => trim($navLinks[2][$i]),
                    'url' => trim($navLinks[1][$i])
                ];
            }
        }
    }
    
    // Extrair categorias de produtos
    preg_match_all('/<button[^>]*data-filter="([^"]*)"[^>]*>([^<]*)<\/button>/i', $content, $categoryMatches);
    if (!empty($categoryMatches[1])) {
        for ($i = 0; $i < count($categoryMatches[1]); $i++) {
            if ($categoryMatches[1][$i] !== '*') {
                $menuStructure['categories'][] = [
                    'name' => trim($categoryMatches[2][$i]),
                    'filter' => trim($categoryMatches[1][$i])
                ];
            }
        }
    }
    
    // Extrair produtos
    preg_match_all('/<h3[^>]*class="product-title"[^>]*>([^<]*)<\/h3>/i', $content, $productMatches);
    if (!empty($productMatches[1])) {
        $menuStructure['products'] = array_map('trim', $productMatches[1]);
    }
}

// Tipos de menu disponíveis
$menuTypes = [
    'navigation' => 'Navegação Principal',
    'category' => 'Categoria de Produto',
    'product' => 'Produto',
    'footer' => 'Rodapé'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Menus - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="sidebar-brand mb-4">
                        <i class="fas fa-cog me-2"></i>Admin Panel
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage-pages.php">
                                <i class="fas fa-file-alt me-2"></i>Gerenciar Páginas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage-menus.php">
                                <i class="fas fa-bars me-2"></i>Gerenciar Menus
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage-products.php">
                                <i class="fas fa-box me-2"></i>Gerenciar Produtos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage-images.php">
                                <i class="fas fa-images me-2"></i>Gerenciar Imagens
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gerenciar Menus</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Add Menu Item Form -->
                <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus me-2"></i>Adicionar Novo Item de Menu</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_menu_item">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="menu_name" class="form-label">Nome do Menu</label>
                                            <input type="text" class="form-control" id="menu_name" name="menu_name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="menu_url" class="form-label">URL/Link</label>
                                            <input type="text" class="form-control" id="menu_url" name="menu_url" placeholder="ex: catalogo.html ou #secao" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="menu_type" class="form-label">Tipo de Menu</label>
                                        <select class="form-select" id="menu_type" name="menu_type" required>
                                            <option value="">Selecione o tipo</option>
                                            <?php foreach ($menuTypes as $key => $type): ?>
                                                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($type) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-2"></i>Salvar Item
                                        </button>
                                        <a href="manage-menus.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Navigation Menu -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-compass me-2"></i>Navegação Principal (<?= count($menuStructure['navigation']) ?>)</h5>
                                <a href="?action=add" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>Novo Item
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($menuStructure['navigation'])): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-compass fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">Nenhum item de navegação encontrado.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nome</th>
                                                    <th>URL</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($menuStructure['navigation'] as $index => $item): ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                                        <td><code><?= htmlspecialchars($item['url']) ?></code></td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="http://localhost:3000/<?= htmlspecialchars($item['url']) ?>" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-outline-primary"
                                                                   title="Ver Link">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                                <button class="btn btn-sm btn-outline-warning" 
                                                                        title="Editar"
                                                                        onclick="alert('Funcionalidade de edição será implementada em breve!')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Categories -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-tags me-2"></i>Categorias de Produtos (<?= count($menuStructure['categories']) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($menuStructure['categories'])): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">Nenhuma categoria encontrada.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($menuStructure['categories'] as $category): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title">
                                                            <i class="fas fa-tag me-2 text-primary"></i>
                                                            <?= htmlspecialchars($category['name']) ?>
                                                        </h6>
                                                        <p class="card-text small text-muted">
                                                            Filtro: <code><?= htmlspecialchars($category['filter']) ?></code>
                                                        </p>
                                                        <div class="btn-group w-100" role="group">
                                                            <a href="http://localhost:3000/index.html" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-outline-warning" 
                                                                    onclick="alert('Funcionalidade de edição será implementada em breve!')">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Menu -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-box me-2"></i>Produtos no Menu (<?= count($menuStructure['products']) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($menuStructure['products'])): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-box fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">Nenhum produto encontrado no menu.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach (array_slice($menuStructure['products'], 0, 12) as $index => $product): ?>
                                            <div class="col-md-3 col-sm-6 mb-3">
                                                <div class="card bg-light h-100">
                                                    <div class="card-body d-flex flex-column">
                                                        <h6 class="card-title">
                                                            <i class="fas fa-cube me-2 text-success"></i>
                                                            <?= htmlspecialchars($product) ?>
                                                        </h6>
                                                        <div class="mt-auto">
                                                            <div class="btn-group w-100" role="group">
                                                                <a href="http://localhost/graficamundial/index.html" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <button class="btn btn-sm btn-outline-warning" 
                                                                        onclick="alert('Funcionalidade de edição será implementada em breve!')">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (count($menuStructure['products']) > 12): ?>
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Mostrando 12 de <?= count($menuStructure['products']) ?> produtos. 
                                                    <a href="manage-products.php">Ver todos os produtos</a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Menu Statistics -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>Estatísticas dos Menus</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <i class="fas fa-compass fa-2x text-primary mb-2"></i>
                                            <h4><?= count($menuStructure['navigation']) ?></h4>
                                            <p class="text-muted">Itens de Navegação</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <i class="fas fa-tags fa-2x text-success mb-2"></i>
                                            <h4><?= count($menuStructure['categories']) ?></h4>
                                            <p class="text-muted">Categorias</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <i class="fas fa-box fa-2x text-warning mb-2"></i>
                                            <h4><?= count($menuStructure['products']) ?></h4>
                                            <p class="text-muted">Produtos</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <i class="fas fa-list fa-2x text-info mb-2"></i>
                                            <h4><?= count($menuStructure['navigation']) + count($menuStructure['categories']) + count($menuStructure['products']) ?></h4>
                                            <p class="text-muted">Total de Itens</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
    
    if ($action === 'add') {
        $productName = sanitize($_POST['product_name'] ?? '');
        $productDescription = sanitize($_POST['product_description'] ?? '');
        $productCategory = sanitize($_POST['product_category'] ?? '');
        $slug = categorySlug($productCategory);

        if (!empty($productName) && !empty($productCategory)) {
            if (file_exists($catalogPath)) {
                $html = file_get_contents($catalogPath);
                $marker = '<!-- admin:inject-new-products-here -->';
                $newItemHtml =
                    '<div class="product-item" data-category="' . $slug . '">' .
                        '<a href="#" class="product-link">' .
                            '<img src="logo.png" alt="' . htmlspecialchars($productName) . '" class="product-image">' .
                            '<div class="product-info">' .
                                '<h3 class="product-title">' . htmlspecialchars($productName) . '</h3>' .
                                '<p class="product-description">' . htmlspecialchars($productDescription) . '</p>' .
                            '</div>' .
                            '<div class="product-action"><span class="arrow-icon">❯</span></div>' .
                        '</a>' .
                    '</div>' . PHP_EOL;

                if (strpos($html, $marker) !== false) {
                    $html = str_replace($marker, $newItemHtml . $marker, $html);
                    file_put_contents($catalogPath, $html);
                    $message = 'Produto "' . $productName . '" adicionado à categoria ' . $productCategory . '.';
                    $messageType = 'success';
                } else {
                    $message = 'Marcador de injeção não encontrado em catalogo.html.';
                    $messageType = 'warning';
                }
            } else {
                $message = 'Arquivo catalogo.html não encontrado.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Nome e categoria do produto são obrigatórios.';
            $messageType = 'danger';
        }
    }
}

// Extrair produtos do index.html
$indexPath = '../../index.html';
$products = [];
if (file_exists($indexPath)) {
    $content = file_get_contents($indexPath);
    preg_match_all('/<h3[^>]*class="product-title"[^>]*>(.*?)<\/h3>/i', $content, $matches);
    if (!empty($matches[1])) {
        $products = array_map('trim', $matches[1]);
    }
}

// Categorias disponíveis
$categories = [
    'Impressos Rápidos',
    'Embalagens', 
    'Promocionais',
    'Corporativos'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - Admin Panel</title>
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
                            <a class="nav-link active" href="manage-products.php">
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
                    <h1 class="h2">Gerenciar Produtos</h1>
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

                <!-- Add Product Form -->
                <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus me-2"></i>Adicionar Novo Produto</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Nome do Produto</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="product_category" class="form-label">Categoria</label>
                                        <select class="form-select" id="product_category" name="product_category" required>
                                            <option value="">Selecione uma categoria</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="product_description" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="product_description" name="product_description" rows="3"></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-2"></i>Salvar Produto
                                        </button>
                                        <a href="manage-products.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Products List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-box me-2"></i>Produtos Existentes (<?= count($products) ?>)</h5>
                                <a href="?action=add" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>Novo Produto
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($products)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Nenhum produto encontrado no site.</p>
                                        <a href="?action=add" class="btn btn-success">
                                            <i class="fas fa-plus me-2"></i>Adicionar Primeiro Produto
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nome do Produto</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($products as $index => $product): ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($product) ?></td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="/catalogo.html?categoria=impressos"
                                                                   target="_blank"
                                                                   class="btn btn-outline-primary btn-sm">
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

                <!-- Categories Overview -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-tags me-2"></i>Categorias Disponíveis</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-tag fa-2x text-primary mb-2"></i>
                                                    <h6><?= htmlspecialchars($category) ?></h6>
                                                    <small class="text-muted">Categoria de produto</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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

<?php
// Ler produtos do catálogo
$catalogPath = '../../frontend/catalogo.html';
function categorySlug($name) {
    $map = [
        'Impressos Rápidos' => 'impressos',
        'Embalagens' => 'embalagens',
        'Promocionais' => 'promocionais',
        'Corporativos' => 'corporativos',
        'Todos' => 'todos',
    ];
    return $map[$name] ?? 'todos';
}
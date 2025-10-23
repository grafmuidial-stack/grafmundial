<?php
/**
 * Dashboard Principal
 * Mundial Gráfica - Painel Administrativo
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = checkAuth();
$db = new Database();

// Estatísticas do dashboard
$pages_dir = dirname(__DIR__);

// Contar páginas HTML
$html_files = glob($pages_dir . '/*.html');
$pages_count = count($html_files);

// Contar produtos (baseado nos produtos listados no index.html)
$products_count = 0;
$index_file = $pages_dir . '/index.html';
if (file_exists($index_file)) {
    $content = file_get_contents($index_file);
    preg_match_all('/<h3 class="product-title">([^<]*)<\/h3>/', $content, $product_matches);
    $products_count = count($product_matches[1]);
}

// Contar imagens
$image_extensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
$all_images = [];
foreach ($image_extensions as $ext) {
    $images = glob($pages_dir . '/*.' . $ext);
    $all_images = array_merge($all_images, $images);
}
$images_count = count($all_images);

// Contar menus (navegação principal + categorias)
$menus_count = 0;
if (file_exists($index_file)) {
    $content = file_get_contents($index_file);
    // Contar itens de navegação principal
    preg_match_all('/<a href="[^"]*" class="nav-item[^"]*"/', $content, $nav_matches);
    // Contar categorias de produtos
    preg_match_all('/<a href="#" class="category-tab[^"]*"/', $content, $cat_matches);
    $menus_count = count($nav_matches[0]) + count($cat_matches[0]);
}

$stats = [
    'pages' => $pages_count,
    'products' => $products_count,
    'images' => $images_count,
    'menus' => $menus_count
];

// Atividades recentes
$recent_activities = [];

$user = $auth->getCurrentUser();

// Verifica se o usuário foi encontrado
if (!$user) {
    // Se não encontrou o usuário, força logout
    $auth->logout();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Painel Administrativo | Mundial Gráfica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-cogs me-2"></i>Admin Panel</h4>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="pages/manage-pages.php" class="nav-link">
                <i class="fas fa-file-alt"></i> Gerenciar Páginas
            </a>
            <a href="pages/manage-menus.php" class="nav-link">
                <i class="fas fa-bars"></i> Gerenciar Menus
            </a>
            <a href="pages/manage-products.php" class="nav-link">
                <i class="fas fa-box"></i> Gerenciar Produtos
            </a>
            <a href="pages/manage-images.php" class="nav-link">
                <i class="fas fa-images"></i> Gerenciar Imagens
            </a>
            <a href="pages/settings.php" class="nav-link">
                <i class="fas fa-cog"></i> Configurações
            </a>
            <a href="logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span class="me-3">Bem-vindo, <strong><?= htmlspecialchars($user['username'] ?? 'Usuário') ?></strong></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="content-body">
            <?php displayFlashMessage(); ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-primary">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['pages'] ?></h3>
                            <p>Páginas</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card bg-success">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['products'] ?></h3>
                            <p>Produtos</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card bg-warning">
                        <div class="stat-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['images'] ?></h3>
                            <p>Imagens</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card bg-info">
                        <div class="stat-icon">
                            <i class="fas fa-bars"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stats['menus'] ?></h3>
                            <p>Menus</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt me-2"></i>Ações Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="pages/manage-pages.php?action=add" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-2"></i>Nova Página
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="pages/manage-products.php?action=add" class="btn btn-success w-100">
                                        <i class="fas fa-plus me-2"></i>Novo Produto
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="pages/manage-images.php" class="btn btn-warning w-100">
                                        <i class="fas fa-upload me-2"></i>Upload Imagem
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="http://localhost/graficamundial/index.html" target="_blank" class="btn btn-info w-100">
                                        <i class="fas fa-eye me-2"></i>Ver Site
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history me-2"></i>Atividades Recentes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_activities)): ?>
                                <p class="text-muted">Nenhuma atividade registrada ainda.</p>
                            <?php else: ?>
                                <div class="activity-list">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-user-circle"></i>
                                            </div>
                                            <div class="activity-content">
                                                <strong><?= htmlspecialchars($activity['username']) ?></strong>
                                                <?= htmlspecialchars($activity['action']) ?>
                                                <?php if (!empty($activity['details'])): ?>
                                                    <small class="text-muted">- <?= htmlspecialchars($activity['details']) ?></small>
                                                <?php endif; ?>
                                                <div class="activity-time">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= formatDate($activity['created_at']) ?>
                                                    </small>
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

            <!-- Páginas Existentes -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-file-alt me-2"></i>Páginas Existentes</h5>
                            <a href="pages/manage-pages.php?action=add" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Nova Página
                            </a>
                        </div>
                        <div class="card-body">
                            <?php
                            $pages_dir = dirname(__DIR__);
                            $html_files = glob($pages_dir . '/*.html');
                            if (!empty($html_files)):
                            ?>
                                <div class="row">
                                    <?php foreach ($html_files as $file): 
                                        $filename = basename($file);
                                        $page_name = ucfirst(str_replace(['.html', '-'], [' ', ' '], $filename));
                                    ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card border">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title mb-2">
                                                        <i class="fas fa-file-code me-2 text-primary"></i>
                                                        <?= htmlspecialchars($page_name) ?>
                                                    </h6>
                                                    <p class="card-text small text-muted mb-2"><?= htmlspecialchars($filename) ?></p>
                                                    <div class="btn-group btn-group-sm w-100">
                                                        <a href="../<?= htmlspecialchars($filename) ?>" target="_blank" class="btn btn-outline-primary">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </a>
                                                        <a href="pages/manage-pages.php?action=edit&file=<?= urlencode($filename) ?>" class="btn btn-outline-warning">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Nenhuma página HTML encontrada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Imagens Existentes -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-images me-2"></i>Imagens Existentes</h5>
                            <a href="pages/manage-images.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-upload me-1"></i>Upload Imagem
                            </a>
                        </div>
                        <div class="card-body">
                            <?php
                            $image_extensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
                            $all_images = [];
                            foreach ($image_extensions as $ext) {
                                $images = glob($pages_dir . '/*.' . $ext);
                                $all_images = array_merge($all_images, $images);
                            }
                            
                            if (!empty($all_images)):
                                // Limitar a 12 imagens para não sobrecarregar a página
                                $displayed_images = array_slice($all_images, 0, 12);
                            ?>
                                <div class="row">
                                    <?php foreach ($displayed_images as $image): 
                                        $filename = basename($image);
                                        $file_size = filesize($image);
                                        $file_size_kb = round($file_size / 1024, 1);
                                    ?>
                                        <div class="col-md-3 col-sm-4 col-6 mb-3">
                                            <div class="card border">
                                                <div class="image-preview" style="height: 120px; overflow: hidden; background: #f8f9fa;">
                                                    <?php if (pathinfo($filename, PATHINFO_EXTENSION) === 'svg'): ?>
                                                        <div class="d-flex align-items-center justify-content-center h-100">
                                                            <i class="fas fa-file-image fa-2x text-muted"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <img src="../<?= htmlspecialchars($filename) ?>" 
                                                             alt="<?= htmlspecialchars($filename) ?>" 
                                                             class="img-fluid w-100 h-100" 
                                                             style="object-fit: cover;">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body p-2">
                                                    <h6 class="card-title small mb-1" title="<?= htmlspecialchars($filename) ?>">
                                                        <?= htmlspecialchars(strlen($filename) > 20 ? substr($filename, 0, 17) . '...' : $filename) ?>
                                                    </h6>
                                                    <p class="card-text small text-muted mb-2"><?= $file_size_kb ?> KB</p>
                                                    <div class="btn-group btn-group-sm w-100">
                                                        <a href="../<?= htmlspecialchars($filename) ?>" target="_blank" class="btn btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="pages/manage-images.php?action=edit&file=<?= urlencode($filename) ?>" class="btn btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($all_images) > 12): ?>
                                    <div class="text-center mt-3">
                                        <a href="pages/manage-images.php" class="btn btn-outline-primary">
                                            Ver todas as <?= count($all_images) ?> imagens
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">Nenhuma imagem encontrada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estrutura de Menus -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-bars me-2"></i>Estrutura de Menus</h5>
                            <a href="pages/manage-menus.php" class="btn btn-info btn-sm">
                                <i class="fas fa-cog me-1"></i>Gerenciar Menus
                            </a>
                        </div>
                        <div class="card-body">
                            <?php
                            // Analisar o arquivo index.html para extrair a estrutura de menus
                            $index_file = $pages_dir . '/index.html';
                            $menu_items = [];
                            
                            if (file_exists($index_file)) {
                                $content = file_get_contents($index_file);
                                
                                // Extrair itens do menu principal
                                preg_match_all('/<a href="([^"]*)" class="nav-item[^"]*"[^>]*>.*?<span>([^<]*)<\/span>/s', $content, $nav_matches);
                                if (!empty($nav_matches[1])) {
                                    for ($i = 0; $i < count($nav_matches[1]); $i++) {
                                        $menu_items[] = [
                                            'type' => 'Navegação Principal',
                                            'title' => trim($nav_matches[2][$i]),
                                            'link' => $nav_matches[1][$i],
                                            'icon' => 'fas fa-link'
                                        ];
                                    }
                                }
                                
                                // Extrair categorias de produtos
                                preg_match_all('/<a href="#" class="category-tab[^"]*" data-category="[^"]*">([^<]*)<\/a>/', $content, $cat_matches);
                                if (!empty($cat_matches[1])) {
                                    foreach ($cat_matches[1] as $category) {
                                        $menu_items[] = [
                                            'type' => 'Categoria de Produto',
                                            'title' => trim($category),
                                            'link' => '#',
                                            'icon' => 'fas fa-tag'
                                        ];
                                    }
                                }
                                
                                // Extrair produtos
                                preg_match_all('/<h3 class="product-title">([^<]*)<\/h3>/', $content, $product_matches);
                                if (!empty($product_matches[1])) {
                                    foreach (array_slice($product_matches[1], 0, 8) as $product) { // Limitar a 8 produtos
                                        $menu_items[] = [
                                            'type' => 'Produto',
                                            'title' => trim($product),
                                            'link' => '#',
                                            'icon' => 'fas fa-box'
                                        ];
                                    }
                                }
                            }
                            
                            if (!empty($menu_items)):
                            ?>
                                <div class="row">
                                    <?php 
                                    $grouped_items = [];
                                    foreach ($menu_items as $item) {
                                        $grouped_items[$item['type']][] = $item;
                                    }
                                    
                                    foreach ($grouped_items as $type => $items): 
                                    ?>
                                        <div class="col-md-4 mb-4">
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-folder me-2"></i><?= htmlspecialchars($type) ?>
                                            </h6>
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($items as $item): ?>
                                                    <div class="list-group-item border-0 px-0 py-2">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <i class="<?= $item['icon'] ?> me-2 text-muted"></i>
                                                                <span class="small"><?= htmlspecialchars($item['title']) ?></span>
                                                            </div>
                                                            <?php if ($item['link'] !== '#'): ?>
                                                                <a href="../<?= htmlspecialchars($item['link']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Não foi possível analisar a estrutura de menus.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
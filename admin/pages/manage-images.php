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

// Processar upload de imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = '../../';
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
    
    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileType = $file['type'];
    $fileError = $file['error'];
    $fileSize = $file['size'];
    
    if ($fileError === 0) {
        if (in_array($fileType, $allowedTypes)) {
            if ($fileSize < 5000000) { // 5MB limit
                $fileDestination = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $message = 'Imagem enviada com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = 'Erro ao enviar a imagem.';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Arquivo muito grande. Máximo 5MB.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou SVG.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Erro no upload do arquivo.';
        $messageType = 'danger';
    }
}

// Listar imagens existentes
$imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
$images = [];
foreach ($imageExtensions as $ext) {
    $files = glob('../../*.' . $ext);
    $images = array_merge($images, $files);
}

// Remover o prefixo do caminho para exibição
$images = array_map(function($path) {
    return str_replace('../../', '', $path);
}, $images);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Imagens - Admin Panel</title>
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
                            <a class="nav-link active" href="manage-images.php">
                                <i class="fas fa-images me-2"></i>Gerenciar Imagens
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gerenciar Imagens</h1>
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

                <!-- Upload Form -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-upload me-2"></i>Upload de Nova Imagem</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Selecionar Imagem</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF, SVG. Tamanho máximo: 5MB</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Enviar Imagem
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-images me-2"></i>Imagens Existentes (<?= count($images) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($images)): ?>
                                    <p class="text-muted">Nenhuma imagem encontrada.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($images as $image): ?>
                                            <div class="col-md-3 col-sm-6 mb-4">
                                                <div class="card h-100">
                                                    <div class="image-preview" style="height: 200px; overflow: hidden;">
                                                        <img src="../../<?= htmlspecialchars($image) ?>" 
                                                             class="card-img-top" 
                                                             style="width: 100%; height: 100%; object-fit: cover;"
                                                             alt="<?= htmlspecialchars($image) ?>">
                                                    </div>
                                                    <div class="card-body">
                                                        <h6 class="card-title text-truncate" title="<?= htmlspecialchars($image) ?>">
                                                            <?= htmlspecialchars($image) ?>
                                                        </h6>
                                                        <p class="card-text small text-muted">
                                                            <?php
                                                            $filePath = '../../' . $image;
                                                            if (file_exists($filePath)) {
                                                                $fileSize = filesize($filePath);
                                                                echo 'Tamanho: ' . formatBytes($fileSize);
                                                            }
                                                            ?>
                                                        </p>
                                                        <div class="btn-group w-100" role="group">
                                                            <a href="../../<?= htmlspecialchars($image) ?>" 
                                                               target="_blank" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="http://localhost/graficamundial/<?= htmlspecialchars($image) ?>" 
                                                               target="_blank" 
                                                               class="btn btn-sm btn-outline-success">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
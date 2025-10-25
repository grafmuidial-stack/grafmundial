<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';
$db = new Database();
$imagesCollection = $db->getCollection('images');

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// Processar upload de imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $replaceTarget = isset($_POST['replace_target']) ? trim($_POST['replace_target']) : null;

    $upload = uploadFile($_FILES['image'], ['jpg','jpeg','png','gif','svg'], MAX_FILE_SIZE);
    if ($upload['success']) {
        if ($replaceTarget && file_exists('../../' . $replaceTarget)) {
            $targetPath = '../../' . $replaceTarget;

            // backup da imagem antiga
            $backupDir = '../../frontend/uploads/backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }
            $backupPath = $backupDir . basename($replaceTarget) . '.' . time() . '.bak';
            @copy($targetPath, $backupPath);

            // sobrescreve preservando o nome original
            if (@rename($upload['filepath'], $targetPath)) {
                $message = 'Imagem substituída com sucesso (nome preservado).';
                $messageType = 'success';

                $imagesCollection->insertOne([
                    'action' => 'replace',
                    'target' => $replaceTarget,
                    'backup' => str_replace('../../', '', $backupPath),
                    'filename' => basename($replaceTarget),
                    'size' => filesize($targetPath),
                    'mime' => mime_content_type($targetPath),
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'user' => $_SESSION['admin_user'] ?? 'admin'
                ]);
            } else {
                $message = 'Falha ao substituir a imagem.';
                $messageType = 'danger';
                @unlink($upload['filepath']);
            }
        } else {
            $message = 'Imagem enviada com sucesso!';
            $messageType = 'success';
            $imagesCollection->insertOne([
                'action' => 'upload',
                'filename' => $upload['filename'],
                'path' => str_replace('../../', '', $upload['filepath']),
                'size' => $_FILES['image']['size'],
                'mime' => $_FILES['image']['type'],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'user' => $_SESSION['admin_user'] ?? 'admin'
            ]);
        }
    } else {
        $message = $upload['message'];
        $messageType = 'danger';
    }
}

// Listar imagens (raiz e frontend/)
$imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
$images = [];
foreach ($imageExtensions as $ext) {
    $images = array_merge($images, glob('../../*.' . $ext));
    $images = array_merge($images, glob('../../frontend/*.' . $ext));
}
$images = array_map(function($path) { return str_replace('../../', '', $path); }, $images);
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
                                    <div class="mb-3">
                                        <label for="replace_target" class="form-label">Substituir arquivo existente (opcional)</label>
                                        <select id="replace_target" name="replace_target" class="form-select">
                                            <option value="">— não substituir —</option>
                                            <?php foreach ($images as $img): ?>
                                                <option value="<?= htmlspecialchars($img) ?>"><?= htmlspecialchars($img) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Se escolher um arquivo, o novo upload irá sobrescrever mantendo o mesmo nome.</div>
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
                                                            <a href="/<?= htmlspecialchars($image) ?>"
                                                               target="_blank"
                                                               class="btn btn-outline-primary btn-sm">
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
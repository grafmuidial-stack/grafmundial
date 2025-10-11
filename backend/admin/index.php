<?php
// Admin simples: login básico e upload de imagens para frontend/uploads
session_start();

$USER = getenv('ADMIN_USER') ?: 'admin';
$PASS = getenv('ADMIN_PASS') ?: 'changeme';
$uploadsDir = ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html') . '/frontend/uploads';
if (!is_dir($uploadsDir)) { mkdir($uploadsDir, 0777, true); }

function is_logged_in() {
    return isset($_SESSION['logged']) && $_SESSION['logged'] === true;
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $u = $_POST['user'] ?? '';
    $p = $_POST['pass'] ?? '';
    if ($u === $GLOBALS['USER'] && $p === $GLOBALS['PASS']) {
        $_SESSION['logged'] = true;
        header('Location: /admin');
        exit;
    } else {
        $error = 'Usuário ou senha inválidos';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin');
    exit;
}

if (!is_logged_in()) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Login Admin</title><style>body{font-family:sans-serif;max-width:480px;margin:40px auto;padding:20px}input{display:block;margin:8px 0;padding:8px;width:100%}button{padding:8px 12px}</style></head><body>';
    if (!empty($error)) echo '<p style="color:red">'.$error.'</p>';
    echo '<form method="post"><input type="hidden" name="action" value="login"><label>Usuário</label><input name="user"><label>Senha</label><input name="pass" type="password"><button type="submit">Entrar</button></form>';
    echo '</body></html>';
    exit;
}

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $targetName = $_POST['target'] ?? '';
    if ($targetName === '') { $targetName = basename($_FILES['file']['name']); }
    $targetPath = $uploadsDir . '/' . $targetName;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $msg = 'Falha ao salvar arquivo';
    } else {
        $msg = 'Arquivo enviado: ' . htmlspecialchars($targetName);
    }
}

$files = array_values(array_filter(scandir($uploadsDir), function($f){ return !in_array($f, ['.','..']); }));

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin - Uploads</title>
  <style>
    body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:20px}
    header{display:flex;justify-content:space-between;align-items:center}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-top:20px}
    .card{border:1px solid #ddd;border-radius:8px;padding:8px}
    img{max-width:100%;height:auto}
    form{margin-top:12px}
    input,button{padding:8px}
    label{display:block;margin-top:8px}
  </style>
</head>
<body>
<header>
  <h1>Admin - Upload de Imagens</h1>
  <a href="/admin?logout=1">Sair</a>
</header>
<?php if (!empty($msg)) echo '<p style="color:green">'.htmlspecialchars($msg).'</p>'; ?>
<section>
  <h2>Enviar nova imagem</h2>
  <form method="post" enctype="multipart/form-data">
    <label>Arquivo</label>
    <input type="file" name="file" accept="image/*" required>
    <label>Salvar como (opcional, ex: logo.png, banner.png)</label>
    <input type="text" name="target" placeholder="logo.png">
    <button type="submit">Enviar</button>
  </form>
</section>
<section class="grid">
  <?php foreach ($files as $f): $p = '/frontend/uploads/' . rawurlencode($f); ?>
    <div class="card">
      <img src="<?php echo $p; ?>" alt="<?php echo htmlspecialchars($f); ?>">
      <div><?php echo htmlspecialchars($f); ?></div>
      <form method="post" onsubmit="return confirm('Deseja substituir este arquivo?');" enctype="multipart/form-data">
        <input type="hidden" name="target" value="<?php echo htmlspecialchars($f); ?>">
        <input type="file" name="file" accept="image/*" required>
        <button type="submit">Substituir</button>
      </form>
    </div>
  <?php endforeach; ?>
</section>
</body>
</html>
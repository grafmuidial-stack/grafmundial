<?php
// Admin simples: login básico, upload de imagens e gestão de páginas (ver/editar)
session_start();

$USER = getenv('ADMIN_USER') ?: 'admin';
$PASS = getenv('ADMIN_PASS') ?: 'changeme';
$DOCROOT = ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html');
$uploadsDir = $DOCROOT . '/uploads';
if (!is_dir($uploadsDir)) { mkdir($uploadsDir, 0777, true); }

function is_logged_in() {
    return isset($_SESSION['logged']) && $_SESSION['logged'] === true;
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $u = $_POST['user'] ?? '';
    $p = $_POST['pass'] ?? '';
    if ($u === getenv('ADMIN_USER') ?: 'admin' && $p === getenv('ADMIN_PASS') ?: 'changeme') {
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

// ====== Gestão de Páginas ======
function list_pages($docroot) {
    $files = scandir($docroot);
    return array_values(array_filter($files, function($f){
        if (in_array($f, ['.','..'])) return false;
        // Apenas .html na raiz
        return preg_match('/\.html$/i', $f) === 1;
    }));
}
function sanitize_page_name($name) {
    $base = basename($name);
    if (preg_match('/^[A-Za-z0-9._-]+\.html$/', $base) !== 1) return false;
    return $base;
}
$page_msg = '';
if (isset($_POST['action']) && $_POST['action'] === 'save_page') {
    $page = sanitize_page_name($_POST['page'] ?? '');
    if (!$page) {
        $page_msg = 'Nome de página inválido.';
    } else {
        $content = $_POST['content'] ?? '';
        $target = $DOCROOT . '/' . $page;
        if (file_put_contents($target, $content) === false) {
            $page_msg = 'Falha ao salvar página.';
        } else {
            $page_msg = 'Página salva: ' . htmlspecialchars($page);
        }
    }
}
$editing = null;
$edit_content = '';
if (isset($_GET['edit'])) {
    $editing = sanitize_page_name($_GET['edit']);
    if ($editing) {
        $path = $DOCROOT . '/' . $editing;
        if (is_file($path)) {
            $edit_content = file_get_contents($path);
        } else {
            $page_msg = 'Arquivo não encontrado: ' . htmlspecialchars($editing);
        }
    } else {
        $page_msg = 'Nome de página inválido para edição.';
    }
}
$pages = list_pages($DOCROOT);

// ====== Upload ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && (!isset($_POST['action']) || $_POST['action'] !== 'save_page')) {
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
  <title>Admin - Gestão</title>
  <style>
    body{font-family:sans-serif;max-width:1000px;margin:40px auto;padding:20px}
    header{display:flex;justify-content:space-between;align-items:center}
    nav a{margin-right:12px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-top:20px}
    .card{border:1px solid #ddd;border-radius:8px;padding:8px}
    img{max-width:100%;height:auto}
    form{margin-top:12px}
    input,button,textarea{padding:8px}
    label{display:block;margin-top:8px}
    textarea{width:100%;height:400px;font-family:monospace}
    .msg{padding:8px;border-radius:6px;background:#f5f5f5;margin:8px 0}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
  </style>
</head>
<body>
<header>
  <h1>Admin - Gestão de Conteúdo</h1>
  <a href="/admin?logout=1">Sair</a>
</header>
<nav>
  <a href="/admin">Uploads</a>
  <a href="/admin?tab=pages">Páginas</a>
</nav>
<?php if (!empty($msg)) echo '<div class="msg">'.htmlspecialchars($msg).'</div>'; ?>
<?php if (!empty($page_msg)) echo '<div class="msg">'.htmlspecialchars($page_msg).'</div>'; ?>
<?php if (($_GET['tab'] ?? '') === 'pages') { ?>
  <section>
    <h2>Páginas (.html)</h2>
    <?php if ($editing) { ?>
      <h3>Editando: <?php echo htmlspecialchars($editing); ?></h3>
      <form method="post">
        <input type="hidden" name="action" value="save_page">
        <input type="hidden" name="page" value="<?php echo htmlspecialchars($editing); ?>">
        <label>Conteúdo HTML</label>
        <textarea name="content"><?php echo htmlspecialchars($edit_content); ?></textarea>
        <button type="submit">Salvar Página</button>
        <a href="/admin?tab=pages" style="margin-left:8px">Cancelar</a>
      </form>
    <?php } else { ?>
      <table>
        <thead><tr><th>Arquivo</th><th>Ações</th></tr></thead>
        <tbody>
          <?php foreach ($pages as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p); ?></td>
              <td>
                <a href="/<?php echo rawurlencode($p); ?>" target="_blank">Ver</a>
                <a href="/admin?tab=pages&edit=<?php echo rawurlencode($p); ?>" style="margin-left:8px">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php } ?>
  </section>
<?php } else { ?>
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
    <?php foreach ($files as $f): $p = '/uploads/' . rawurlencode($f); ?>
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
<?php } ?>
</body>
</html>
<?php
// Admin simples: login básico, upload de imagens e gestão de páginas (ver/editar)
session_start();

$USER = getenv('ADMIN_USER') ?: 'admin';
$PASS = getenv('ADMIN_PASS') ?: 'changeme';
$DOCROOT = ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html');
$uploadsDir = $DOCROOT . '/uploads';
if (!is_dir($uploadsDir)) { mkdir($uploadsDir, 0777, true); }
$backupDir = $uploadsDir . '/backups';
if (!is_dir($backupDir)) { mkdir($backupDir, 0777, true); }

function backup_file($path, $backupDir) {
    if (is_file($path)) {
        $base = basename($path);
        $ts = date('Ymd-His');
        @copy($path, $backupDir . '/' . $base . '.' . $ts . '.bak');
    }
}

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
// Helpers para criação e menu
function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-_. ]+/', '', $text);
    $text = preg_replace('/[ ]+/', '-', $text);
    if ($text === '') return false;
    return $text;
}
function page_default_content($title, $docroot) {
    $index = $docroot . '/index.html';
    $content = '<!doctype html><html><head><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title><link rel="stylesheet" href="/styles.css"></head><body><header><img src="/logo.png" alt="Grafica Mundial Logo" style="max-height:60px"></header><nav class="main-nav"><a class="nav-item" href="/index.html"> <span class="nav-icon">🏠</span> Início </a> <a class="nav-item" href="/portfolio.html"> <span class="nav-icon">📸</span> Portfólio </a> <a class="nav-item" href="/catalogo.html"> <span class="nav-icon">📋</span> Catálogo </a> <a class="nav-item" href="/avaliacoes.html"> <span class="nav-icon">⭐</span> Avaliações </a> <a class="nav-item" href="/contatos.html"> <span class="nav-icon">📞</span> Contatos </a></nav><main class="content"><h1>'.htmlspecialchars($title).'</h1><p>Nova página criada pelo admin.</p></main></body></html>';
    // Se houver index.html, tentar usar seu nav
    if (is_file($index)) {
        $idx = file_get_contents($index);
        if (preg_match('/<nav class="main-nav">.*?<\/nav>/is', $idx, $m)) {
            $menu = $m[0];
            $content = '<!doctype html><html><head><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title><link rel="stylesheet" href="/styles.css"></head><body><header><img src="/logo.png" alt="Grafica Mundial Logo" style="max-height:60px"></header>'.$menu.'<main class="content"><h1>'.htmlspecialchars($title).'</h1><p>Nova página criada pelo admin.</p></main></body></html>';
        }
    }
    return $content;
}
function extract_menu_from_file($path) {
    if (!is_file($path)) return '';
    $html = file_get_contents($path);
    if (preg_match('/<nav class="main-nav">.*?<\/nav>/is', $html, $m)) {
        return $m[0];
    }
    return '';
}
function apply_menu_to_all_pages($docroot, $menuHtml) {
    $pages = list_pages($docroot);
    $applied = 0;
    foreach ($pages as $p) {
        $path = $docroot . '/' . $p;
        $html = file_get_contents($path);
        if (preg_match('/<nav class="main-nav">.*?<\/nav>/is', $html)) {
            $new = preg_replace('/<nav class="main-nav">.*?<\/nav>/is', $menuHtml, $html, 1);
        } else {
            // Se não existe, insere após <header>
            $new = preg_replace('/<header[^>]*>.*?<\/header>/is', '$0' . $menuHtml, $html, 1);
        }
        if ($new && $new !== $html) {
            backup_file($path, $GLOBALS['backupDir']);
            file_put_contents($path, $new);
            $applied++;
        }
    }
    return $applied;
}
// ===== Utilitários para edição segura do conteúdo principal =====
function extract_main_content($html) {
    if (preg_match('/<main[^>]*>([\s\S]*?)<\/main>/i', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $html, $m)) {
        return $m[1];
    }
    return $html; // fallback
}
function replace_main_content($html, $newContent) {
    // Caso já exista <main>, substitui apenas o conteúdo de <main>
    if (preg_match('/<main[^>]*>[\s\S]*?<\/main>/i', $html)) {
        return preg_replace('/<main[^>]*>[\s\S]*?<\/main>/i', '<main class="content">' . $newContent . '</main>', $html, 1);
    }
    // Se não houver <main> mas houver <body>, preserva header/nav/footer e injeta um <main>
    if (preg_match('/<body[^>]*>[\s\S]*?<\/body>/i', $html)) {
        $openBodyTag = '<body>';
        if (preg_match('/<body[^>]*>/i', $html, $mOpen)) { $openBodyTag = $mOpen[0]; }
        $header = '';
        if (preg_match('/<header[^>]*>[\s\S]*?<\/header>/i', $html, $mHeader)) { $header = $mHeader[0]; }
        $nav = '';
        if (preg_match('/<nav[^>]*class="[^"]*main-nav[^"]*"[^>]*>[\s\S]*?<\/nav>/i', $html, $mNav)) { $nav = $mNav[0]; }
        $footer = '';
        if (preg_match('/<footer[^>]*>[\s\S]*?<\/footer>/i', $html, $mFooter)) { $footer = $mFooter[0]; }
        $newInner = $header . $nav . '<main class="content">' . $newContent . '</main>' . $footer;
        return preg_replace('/<body[^>]*>[\s\S]*?<\/body>/i', $openBodyTag . $newInner . '</body>', $html, 1);
    }
    // Fallback: se não houver nem <main> nem <body>, retorna só o fragmento (quem salvou em modo avançado sem estrutura)
    return $newContent;
}

$page_msg = '';
// Criar página
if (isset($_POST['action']) && $_POST['action'] === 'create_page') {
    $name = trim($_POST['new_page_name'] ?? '');
    $slug = slugify($name);
    if (!$slug) {
        $page_msg = 'Nome inválido para página.';
    } else {
        $file = sanitize_page_name($slug . '.html');
        if (!$file) {
            $page_msg = 'Nome inválido após sanitização.';
        } else {
            $target = $DOCROOT . '/' . $file;
            if (file_exists($target)) {
                $page_msg = 'Já existe uma página com este nome.';
            } else {
                $content = page_default_content($name, $DOCROOT);
                if (file_put_contents($target, $content) === false) {
                    $page_msg = 'Falha ao criar página.';
                } else {
                    $page_msg = 'Página criada: ' . htmlspecialchars($file);
                }
            }
        }
    }
}
// Excluir página
if (isset($_POST['action']) && $_POST['action'] === 'delete_page') {
    $page = sanitize_page_name($_POST['page'] ?? '');
    if (!$page) {
        $page_msg = 'Nome de página inválido para exclusão.';
    } else {
        if ($page === 'index.html') {
            $page_msg = 'Não é permitido excluir index.html.';
        } else {
            $path = $DOCROOT . '/' . $page;
            if (is_file($path)) {
                if (unlink($path)) {
                    $page_msg = 'Página excluída: ' . htmlspecialchars($page);
                } else {
                    $page_msg = 'Falha ao excluir página.';
                }
            } else {
                $page_msg = 'Arquivo não encontrado para exclusão.';
            }
        }
    }
}
// Salvar página (edição)
if (isset($_POST['action']) && $_POST['action'] === 'save_page') {
    $page = sanitize_page_name($_POST['page'] ?? '');
    if (!$page) {
        $page_msg = 'Nome de página inválido.';
    } else {
        $target = $DOCROOT . '/' . $page;
        if (!is_writable(dirname($target))) {
            $page_msg = 'Destino não gravável. Verifique permissões.';
        } else {
            $editorMode = $_POST['editor_mode'] ?? 'fragment';
            if ($editorMode === 'fragment') {
                $fragment = $_POST['content_fragment'] ?? '';
                if (trim($fragment) === '') {
                    $page_msg = 'Conteúdo vazio — nada foi salvo.';
                } else {
                    $original = is_file($target) ? file_get_contents($target) : '';
                    $newHtml = replace_main_content($original, $fragment);
                    backup_file($target, $backupDir);
                    if (file_put_contents($target, $newHtml) === false) {
                        $page_msg = 'Falha ao salvar conteúdo principal.';
                    } else {
                        $page_msg = 'Conteúdo principal da página atualizado.';
                    }
                }
            } else {
                $content = $_POST['content'] ?? '';
                if (trim($content) === '') {
                    $page_msg = 'Página completa vazia — nada foi salvo.';
                } else {
                    $original = is_file($target) ? file_get_contents($target) : '';
                    // Se o conteúdo não contém estrutura HTML completa, aplicar apenas no <main> como proteção
                    if (stripos($content, '<html') === false || stripos($content, '<body') === false) {
                        $safeHtml = replace_main_content($original, $content);
                        backup_file($target, $backupDir);
                        if (file_put_contents($target, $safeHtml) === false) {
                            $page_msg = 'Falha ao salvar (proteção de estrutura HTML).';
                        } else {
                            $page_msg = 'Conteúdo salvo no <main> (estrutura HTML ausente).';
                        }
                    } else {
                        backup_file($target, $backupDir);
                        if (file_put_contents($target, $content) === false) {
                            $page_msg = 'Falha ao salvar página.';
                        } else {
                            $page_msg = 'Página salva: ' . htmlspecialchars($page);
                        }
                    }
                }
            }
        }
    }
}
// Menu centralizado
if (isset($_POST['action']) && $_POST['action'] === 'save_menu') {
    $menuHtml = $_POST['menu_html'] ?? '';
    if ($menuHtml === '' || stripos($menuHtml, '<nav') === false) {
        $page_msg = 'HTML de menu inválido.';
    } else {
        $applied = apply_menu_to_all_pages($DOCROOT, $menuHtml);
        $page_msg = 'Menu atualizado em ' . $applied . ' página(s).';
    }
}

$editing = null;
$edit_content = '';
$edit_fragment = '';
if (isset($_GET['edit'])) {
    $editing = sanitize_page_name($_GET['edit']);
    if ($editing) {
        $path = $DOCROOT . '/' . $editing;
        if (is_file($path)) {
            $edit_content = file_get_contents($path);
            $edit_fragment = extract_main_content($edit_content);
        } else {
            $page_msg = 'Arquivo não encontrado: ' . htmlspecialchars($editing);
        }
    } else {
        $page_msg = 'Nome de página inválido para edição.';
    }
}
$pages = list_pages($DOCROOT);

// ====== Upload ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && (!isset($_POST['action']) || !in_array($_POST['action'], ['save_page','create_page','delete_page','save_menu']))) {
    $targetName = $_POST['target'] ?? '';
    if ($targetName === '') { $targetName = basename($_FILES['file']['name']); }
    $targetPath = $uploadsDir . '/' . $targetName;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        if (isset($_POST['quillImage'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'upload_failed']);
            exit;
        }
        $msg = 'Falha ao salvar arquivo';
    } else {
        if (isset($_POST['quillImage'])) {
            header('Content-Type: application/json');
            $url = '/uploads/' . rawurlencode($targetName);
            echo json_encode(['ok' => true, 'url' => $url, 'name' => $targetName]);
            exit;
        }
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
    body{font-family:sans-serif;max-width:1100px;margin:40px auto;padding:20px}
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
    .actions a,.actions button{margin-right:8px}
    .toolbar{margin:8px 0}
    #editor{height:420px;border:1px solid #ddd}
  </style>
  <!-- Quill WYSIWYG -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
</head>
<body>
<header>
  <h1>Admin - Gestão de Conteúdo</h1>
  <a href="/admin?logout=1">Sair</a>
</header>
<nav>
  <a href="/admin">Uploads</a>
  <a href="/admin?tab=pages">Páginas</a>
  <a href="/admin?tab=menu">Menu</a>
</nav>
<?php if (!empty($msg)) echo '<div class="msg">'.htmlspecialchars($msg).'</div>'; ?>
<?php if (!empty($page_msg)) echo '<div class="msg">'.htmlspecialchars($page_msg).'</div>'; ?>
<?php if (($_GET['tab'] ?? '') === 'pages') { ?>
  <section>
    <h2>Páginas (.html)</h2>
    <form method="post" style="margin-bottom:16px">
      <input type="hidden" name="action" value="create_page">
      <label>Nova Página (nome):</label>
      <input type="text" name="new_page_name" placeholder="ex: servicos premium" required>
      <button type="submit">Criar Página</button>
    </form>
    <?php if ($editing) { ?>
      <h3>Editando: <?php echo htmlspecialchars($editing); ?></h3>
      <form method="post" onsubmit="document.querySelector('input[name=editor_mode]').value='fragment';document.querySelector('textarea[name=content_fragment]').value = quill.root.innerHTML;">
        <input type="hidden" name="action" value="save_page">
        <input type="hidden" name="page" value="<?php echo htmlspecialchars($editing); ?>">
        <input type="hidden" name="editor_mode" value="fragment">
        <label>Editor (WYSIWYG) - edita apenas o conteúdo dentro de <main></label>
        <div id="toolbar" class="toolbar">
          <span class="ql-formats">
            <select class="ql-header"></select>
            <button class="ql-bold"></button>
            <button class="ql-italic"></button>
            <button class="ql-underline"></button>
            <button class="ql-link"></button>
            <button class="ql-list" value="ordered"></button>
            <button class="ql-list" value="bullet"></button>
            <select class="ql-align"></select>
            <select class="ql-color"></select>
            <select class="ql-background"></select>
            <button class="ql-clean"></button>
            <button class="ql-image"></button>
            <button type="button" id="btn-insert-iframe" title="Inserir Iframe">Iframe</button>
          </span>
        </div>
        <div id="editor"></div>
         <input type="file" id="image-upload-input" accept="image/*" style="display:none">
         <textarea name="content_fragment" style="display:none"></textarea>
         <details style="margin:8px 0">
           <summary>Inserir imagens</summary>
           <p>Você pode enviar uma imagem e inseri-la no cursor do editor, ou escolher da galeria abaixo.</p>
           <button type="button" id="btn-upload-image">Enviar e Inserir Imagem</button>
           <div style="margin-top:12px" class="grid">
             <?php foreach ($files as $f): $p = '/uploads/' . rawurlencode($f); ?>
               <div class="card">
                 <img src="<?php echo $p; ?>" alt="<?php echo htmlspecialchars($f); ?>">
                 <div><?php echo htmlspecialchars($f); ?></div>
                 <button type="button" onclick="insertImageFromGallery('<?php echo $p; ?>')">Inserir no Editor</button>
               </div>
             <?php endforeach; ?>
           </div>
         </details>
         <details style="margin:8px 0">
           <summary>Editar HTML bruto da página (avançado)</summary>
          <p>Use apenas se precisar alterar estruturas (head, body, header, nav). Isso pode quebrar o layout.</p>
          <textarea name="content" style="height:240px"><?php echo htmlspecialchars($edit_content); ?></textarea>
          <label><input type="checkbox" onchange="document.querySelector('input[name=editor_mode]').value=this.checked?'full':'fragment'"> Salvar como página completa</label>
        </details>
        <button type="submit">Salvar</button>
        <a href="/admin?tab=pages" style="margin-left:8px">Cancelar</a>
      </form>
      <script>
        var quill = new Quill('#editor', { theme: 'snow', modules: { toolbar: '#toolbar' } });
        quill.root.innerHTML = <?php echo json_encode($edit_fragment); ?>;
        // Handler customizado para upload via Quill
        var toolbar = quill.getModule('toolbar');
        toolbar.addHandler('image', function() {
          var input = document.getElementById('image-upload-input');
          input.value = '';
          input.click();
        });
        document.getElementById('image-upload-input').addEventListener('change', async function() {
          var file = this.files && this.files[0];
          if (!file) return;
          var fd = new FormData();
          fd.append('file', file);
          fd.append('target', file.name);
          fd.append('quillImage', '1');
          try {
            var res = await fetch('/admin', { method: 'POST', body: fd });
            var json = await res.json();
            if (json && json.ok && json.url) {
              var range = quill.getSelection(true);
              quill.insertEmbed(range.index, 'image', json.url, Quill.sources.USER);
              quill.setSelection(range.index + 1);
            } else {
              alert('Falha ao enviar imagem.');
            }
          } catch (e) {
            alert('Erro no envio: ' + e);
          }
        });
        // Botão auxiliar de upload
        var btnUpload = document.getElementById('btn-upload-image');
        if (btnUpload) {
          btnUpload.addEventListener('click', function() {
            var input = document.getElementById('image-upload-input');
            input.value = '';
            input.click();
          });
        }
        // Inserir da galeria existente
        function insertImageFromGallery(url) {
          var range = quill.getSelection(true) || { index: quill.getLength() };
          quill.insertEmbed(range.index, 'image', url, Quill.sources.USER);
          quill.setSelection(range.index + 1);
        }
        window.insertImageFromGallery = insertImageFromGallery;
        // Inserção de iframe (frame HTML)
        var btnIframe = document.getElementById('btn-insert-iframe');
        if (btnIframe) {
          btnIframe.addEventListener('click', function() {
            var url = prompt('URL do conteúdo a incorporar (http/https):');
            if (!url) return;
            url = url.trim();
            var isValid = /^(https?:\/\/)/i.test(url) && !/["'<>]/.test(url);
            if (!isValid) {
              alert('URL inválida. Use http(s) e sem caracteres especiais.');
              return;
            }
            var range = quill.getSelection(true) || { index: quill.getLength() };
            var html = '<div class="responsive-iframe" style="position:relative;width:100%;padding-top:56.25%">' +
                       '<iframe src="' + url + '" title="Conteúdo incorporado" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0" allowfullscreen loading="lazy" referrerpolicy="no-referrer"></iframe>' +
                       '</div>';
            quill.clipboard.dangerouslyPasteHTML(range.index, html, 'user');
            quill.setSelection(range.index + 1);
          });
        }
     </script>
    <?php } else { ?>
      <table>
        <thead><tr><th>Arquivo</th><th class="actions">Ações</th></tr></thead>
        <tbody>
          <?php foreach ($pages as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p); ?></td>
              <td class="actions">
                <a href="/<?php echo rawurlencode($p); ?>" target="_blank">Ver</a>
                <a href="/admin?tab=pages&edit=<?php echo rawurlencode($p); ?>" style="margin-left:8px">Editar</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Excluir página?');">
                  <input type="hidden" name="action" value="delete_page">
                  <input type="hidden" name="page" value="<?php echo htmlspecialchars($p); ?>">
                  <button type="submit">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php } ?>
  </section>
<?php } elseif (($_GET['tab'] ?? '') === 'menu') { ?>
  <section>
    <h2>Menu (Navbar)</h2>
    <?php $currentMenu = extract_menu_from_file($DOCROOT . '/index.html'); ?>
    <form method="post">
      <input type="hidden" name="action" value="save_menu">
      <label>HTML do Menu (inclua a tag <nav class="main-nav"> ... </nav>)</label>
      <textarea name="menu_html" required><?php echo htmlspecialchars($currentMenu ?: '<nav class="main-nav"></nav>'); ?></textarea>
      <button type="submit">Salvar Menu</button>
    </form>
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
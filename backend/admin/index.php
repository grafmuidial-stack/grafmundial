<?php
// Admin simples: login básico, upload de imagens e gestão de páginas (ver/editar)
session_start();

$USER = getenv('ADMIN_USER') ?: 'admin';
$PASS = getenv('ADMIN_PASS') ?: 'changeme';
$DOCROOT = realpath(__DIR__ . '/../../frontend');
// Fallback para ambientes onde o caminho relativo não existe
if (!$DOCROOT || !is_dir($DOCROOT)) {
    $DOCROOT = ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html');
    if (is_dir($DOCROOT . '/frontend')) { $DOCROOT = $DOCROOT . '/frontend'; }
}
$uploadsDir = $DOCROOT . '/uploads';
if (!is_dir($uploadsDir)) { mkdir($uploadsDir, 0777, true); }
$backupDir = $uploadsDir . '/backups';
if (!is_dir($backupDir)) { mkdir($backupDir, 0777, true); }
// Caminho do meta (páginas ocultas e outros dados do admin)
$metaPath = $DOCROOT . '/uploads/admin_meta.json';
if (!is_file($metaPath)) {
    @file_put_contents($metaPath, json_encode(['hidden' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function get_meta($metaPath) {
    $raw = @file_get_contents($metaPath);
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = ['hidden' => []]; }
    if (!isset($data['hidden']) || !is_array($data['hidden'])) { $data['hidden'] = []; }
    return $data;
}
function save_meta($metaPath, $meta) {
    @file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function hide_page_meta(&$meta, $page) {
    if (!in_array($page, $meta['hidden'])) { $meta['hidden'][] = $page; }
}
function show_page_meta(&$meta, $page) {
    $meta['hidden'] = array_values(array_filter($meta['hidden'], function($x) use ($page) { return $x !== $page; }));
}

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
    $content = '<!doctype html><html><head><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title><link rel="stylesheet" href="/styles.css"></head><body><header class="site-header"><img src="/logo.png" alt="Grafica Mundial Logo" style="max-height:60px"></header><nav class="main-nav"><a class="nav-item" href="/index.html"> <span class="nav-icon">🏠</span> Início </a> <a class="nav-item" href="/portfolio.html"> <span class="nav-icon">📸</span> Portfólio </a> <a class="nav-item" href="/catalogo.html"> <span class="nav-icon">📋</span> Catálogo </a> <a class="nav-item" href="/avaliacoes.html"> <span class="nav-icon">⭐</span> Avaliações </a> <a class="nav-item" href="/contatos.html"> <span class="nav-icon">📞</span> Contatos </a></nav><main class="content"><h1>'.htmlspecialchars($title).'</h1><p>Nova página criada pelo admin.</p></main></body></html>';
    // Se houver index.html, tentar usar seu nav
    if (is_file($index)) {
        $idx = file_get_contents($index);
        if (preg_match('/<nav class="main-nav">.*?<\/nav>/is', $idx, $m)) {
            $menu = $m[0];
            $content = '<!doctype html><html><head><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title><link rel="stylesheet" href="/styles.css"></head><body><header class="site-header"><img src="/logo.png" alt="Grafica Mundial Logo" style="max-height:60px"></header>'.$menu.'<main class="content"><h1>'.htmlspecialchars($title).'</h1><p>Nova página criada pelo admin.</p></main></body></html>';
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
        // Garantir que o <header> possua class="site-header" para manter o background
        if ($new && preg_match('/<header[^>]*>/i', $new, $hm)) {
          $headerTag = $hm[0];
          if (stripos($headerTag, 'site-header') === false) {
            if (stripos($headerTag, 'class=') === false) {
              $newHeaderTag = preg_replace('/<header/i', '<header class="site-header"', $headerTag);
            } else {
              $newHeaderTag = preg_replace('/class=("|\')([^"\']*)(\1)/i', 'class=$1$2 site-header$1', $headerTag);
            }
            $new = str_replace($headerTag, $newHeaderTag, $new);
          }
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
    // Caso já exista <main>, substitui apenas o conteúdo de <main> usando callback
    if (preg_match('/(<main[^>]*>)[\s\S]*?(<\/main>)/i', $html)) {
        return preg_replace_callback(
            '/(<main[^>]*>)[\s\S]*?(<\/main>)/i',
            function($m) use ($newContent) { return $m[1] . $newContent . $m[2]; },
            $html,
            1
        );
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
        $newInner = $header . $nav . '<main>' . $newContent . '</main>' . $footer;
        return preg_replace_callback(
            '/<body[^>]*>[\s\S]*?<\/body>/i',
            function($m) use ($openBodyTag, $newInner) { return $openBodyTag . $newInner . '</body>'; },
            $html,
            1
        );
    }
    // Fallback: se não houver nem <main> nem <body>, retorna só o fragmento (quem salvou em modo avançado sem estrutura)
    return $newContent;
}

// ===== Upload e substituição de imagem =====
function detect_image_extension($filePath, $fallbackMime) {
    $mime = $fallbackMime;
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) { $m = finfo_file($f, $filePath); if ($m) $mime = $m; finfo_close($f); }
    }
    $map = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg'
    ];
    return $map[strtolower($mime)] ?? null;
}
function safe_image_filename($ext) {
    $ts = date('Ymd-His');
    $rand = mt_rand(1000,9999);
    return sprintf('img-%s-%s.%s', $ts, $rand, $ext);
}
function replace_first_img_src_in_main($html, $newSrc) {
    $main = extract_main_content($html);
    $updatedMain = $main;
    // Encontrar primeira imagem e substituir seu src manualmente
    if (preg_match('/<img[^>]*\bsrc=("|\')(.*?)\1/i', $main, $m, PREG_OFFSET_CAPTURE)) {
        $oldSrc = $m[2][0];
        $pos = $m[2][1];
        $updatedMain = substr($main, 0, $pos) . $newSrc . substr($main, $pos + strlen($oldSrc));
    } else {
        // Se não há <img>, insere no topo do <main>
        $updatedMain = '<img src="' . htmlspecialchars($newSrc, ENT_QUOTES) . '" alt="">' . $main;
    }
    return replace_main_content($html, $updatedMain);
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
// Ocultar página
if (isset($_POST['action']) && $_POST['action'] === 'hide_page') {
    $page = sanitize_page_name($_POST['page'] ?? '');
    if (!$page) {
        $page_msg = 'Nome de página inválido para ocultar.';
    } else {
        $meta = get_meta($metaPath);
        hide_page_meta($meta, $page);
        save_meta($metaPath, $meta);
        $page_msg = 'Página ocultada: ' . htmlspecialchars($page);
    }
}
// Exibir página
if (isset($_POST['action']) && $_POST['action'] === 'show_page') {
    $page = sanitize_page_name($_POST['page'] ?? '');
    if (!$page) {
        $page_msg = 'Nome de página inválido para exibir.';
    } else {
        $meta = get_meta($metaPath);
        show_page_meta($meta, $page);
        save_meta($metaPath, $meta);
        $page_msg = 'Página marcada como visível: ' . htmlspecialchars($page);
    }
}
// Duplicar página
if (isset($_POST['action']) && $_POST['action'] === 'duplicate_page') {
    $src = sanitize_page_name($_POST['page'] ?? '');
    $newName = trim($_POST['new_page_name'] ?? '');
    $slug = slugify($newName);
    if (!$src || !$slug) {
        $page_msg = 'Parâmetros inválidos para duplicação.';
    } else {
        $destFile = sanitize_page_name($slug . '.html');
        if (!$destFile) {
            $page_msg = 'Nome de destino inválido após sanitização.';
        } else {
            $srcPath = $DOCROOT . '/' . $src;
            $destPath = $DOCROOT . '/' . $destFile;
            if (!is_file($srcPath)) {
                $page_msg = 'Arquivo de origem não encontrado.';
            } elseif (file_exists($destPath)) {
                $page_msg = 'Já existe uma página com este nome.';
            } else {
                $content = file_get_contents($srcPath);
                if ($content === false) {
                    $page_msg = 'Falha ao ler página de origem.';
                } else {
                    if (file_put_contents($destPath, $content) === false) {
                        $page_msg = 'Falha ao criar página duplicada.';
                    } else {
                        $page_msg = 'Página duplicada: ' . htmlspecialchars($destFile);
                    }
                }
            }
        }
    }
}

// Aplicar menu do index.html a todas as páginas
if (isset($_POST['action']) && $_POST['action'] === 'apply_menu_all') {
    $indexPath = $DOCROOT . '/index.html';
    $menu = extract_menu_from_file($indexPath);
    if ($menu === '') {
        $page_msg = 'Não foi possível extrair o menu do index.html';
    } else {
        $count = apply_menu_to_all_pages($DOCROOT, $menu);
        $page_msg = 'Menu aplicado em ' . $count . ' página(s).';
    }
}

// Salvar conteúdo da página (substitui conteúdo dentro de <main>)
if (isset($_POST['action']) && $_POST['action'] === 'save_page') {
    $page = sanitize_page_name($_POST['page'] ?? '');
    $content = $_POST['content'] ?? '';
    if (!$page) {
        $page_msg = 'Página inválida para salvar.';
    } else {
        $path = $DOCROOT . '/' . $page;
        if (!is_file($path)) {
            $page_msg = 'Arquivo não encontrado para salvar.';
        } else {
            $html = file_get_contents($path);
            if ($html === false) {
                $page_msg = 'Falha ao ler arquivo para salvar.';
            } else {
                $newHtml = replace_main_content($html, $content);
                if ($newHtml === null) { $newHtml = $content; }
                backup_file($path, $backupDir);
                if (file_put_contents($path, $newHtml) === false) {
                    $page_msg = 'Falha ao salvar página.';
                } else {
                    $page_msg = 'Página salva: ' . htmlspecialchars($page);
                }
            }
        }
    }
}

// Upload de imagem e substituição opcional da primeira imagem do conteúdo
if (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
    $page = sanitize_page_name($_POST['page'] ?? '');
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $page_msg = 'Falha no upload: arquivo inválido.';
    } else {
        $tmp = $_FILES['image']['tmp_name'];
        $mime = $_FILES['image']['type'] ?? '';
        $ext = detect_image_extension($tmp, $mime);
        if (!$ext) {
            $page_msg = 'Formato de imagem não suportado (use PNG, JPG, WEBP, SVG).';
        } else {
            $fname = safe_image_filename($ext);
            $dest = $uploadsDir . '/' . $fname;
            if (!@move_uploaded_file($tmp, $dest)) {
                $page_msg = 'Falha ao salvar a imagem.';
            } else {
                $url = '/uploads/' . $fname;
                // Substituir primeira imagem se solicitado e página válida
                if (!empty($_POST['replace_first']) && $page) {
                    $path = $DOCROOT . '/' . $page;
                    if (is_file($path)) {
                        $html = file_get_contents($path);
                        if ($html !== false) {
                            $newHtml = replace_first_img_src_in_main($html, $url);
                            backup_file($path, $backupDir);
                            if (file_put_contents($path, $newHtml) === false) {
                                $page_msg = 'Imagem enviada, mas falhou ao substituir na página.';
                            } else {
                                $page_msg = 'Imagem enviada e substituída: ' . htmlspecialchars($url);
                            }
                        } else {
                            $page_msg = 'Imagem enviada, mas falhou ao ler a página.';
                        }
                    } else {
                        $page_msg = 'Imagem enviada: ' . htmlspecialchars($url) . ' (página não encontrada para substituir).';
                    }
                } else {
                    $page_msg = 'Imagem enviada: ' . htmlspecialchars($url);
                }
            }
        }
    }
}

// ===== Renderização do painel admin =====
echo '<!doctype html><html><head><meta charset="utf-8"><title>Admin - Grafica Mundial</title><style>
  body{font-family:sans-serif;max-width:1000px;margin:24px auto;padding:16px;background:#f7f7f7;color:#222}
  header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
  .box{background:#fff;border:1px solid #ddd;border-radius:8px;margin:12px 0;padding:12px}
  .list{display:grid;grid-template-columns:1fr auto auto auto;gap:8px;align-items:center}
  .list .item{display:contents}
  input,select,button{padding:8px;margin:4px}
  .msg{padding:10px;border-radius:6px;background:#e9f6ff;border:1px solid #b6e1ff;margin-bottom:12px}
  .hidden{opacity:0.6}
</style></head><body>';

echo '<header><h1>Admin</h1><a href="/admin?logout=1">Sair</a></header>';

// Mostrar mensagem após processar ações
if (!empty($page_msg)) { echo '<div class="msg">' . htmlspecialchars($page_msg) . '</div>'; }

// Criar página
echo '<div class="box"><h2>Criar página</h2><form method="post"><input type="hidden" name="action" value="create_page"><input name="new_page_name" placeholder="Nome da nova página"><button type="submit">Criar</button></form></div>';

// Aplicar menu
echo '<div class="box"><h2>Menu</h2><p>Aplicar o menu do index.html a todas as páginas.</p><form method="post"><input type="hidden" name="action" value="apply_menu_all"><button type="submit">Aplicar menu do index</button></form></div>';

// Duplicar página
echo '<div class="box"><h2>Duplicar página</h2><form method="post"><input type="hidden" name="action" value="duplicate_page"><label>Origem:</label><select name="page">';
foreach ($pages as $p) { echo '<option value="' . htmlspecialchars($p) . '">' . htmlspecialchars($p) . '</option>'; }
echo '</select><label>Novo nome:</label><input name="new_page_name" placeholder="Nome da nova página"><button type="submit">Duplicar</button></form></div>';

// Editor de página (GET ?edit=nome.html) — renderizar uma única vez fora do loop
$editPage = sanitize_page_name($_GET['edit'] ?? '');
if ($editPage) {
    $path = $DOCROOT . '/' . $editPage;
    $currentContent = '';
    if (is_file($path)) {
        $html = file_get_contents($path);
        $currentContent = extract_main_content($html);
    }
    echo '<div class="box"><h2>Editar página: ' . htmlspecialchars($editPage) . '</h2>';
    echo '<form method="post"><input type="hidden" name="action" value="save_page"><input type="hidden" name="page" value="' . htmlspecialchars($editPage) . '">';
    echo '<textarea name="content" rows="18" style="width:100%;">' . htmlspecialchars($currentContent) . '</textarea>';
    echo '<div><button type="submit">Salvar</button> <a href="/admin">Cancelar</a> <a href="/' . htmlspecialchars($editPage) . '" target="_blank">Ver página</a></div></form></div>';

    // Formulário de upload de imagem
    echo '<div class="box"><h2>Imagem da página</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="upload_image">';
    echo '<input type="hidden" name="page" value="' . htmlspecialchars($editPage) . '">';
    echo '<input type="file" name="image" accept="image/*">';
    echo '<label style="display:block;margin-top:8px"><input type="checkbox" name="replace_first" value="1"> Substituir a primeira imagem do conteúdo</label>';
    echo '<div style="margin-top:8px"><button type="submit">Enviar imagem</button></div>';
    echo '<p style="font-size:12px;color:#555;margin-top:8px">Imagens são salvas em <code>/uploads</code>. Após enviar, você pode colar a URL no conteúdo ou marcar para substituir automaticamente.</p>';
    echo '</form></div>';
}

// ===== Renderização do painel admin =====
$pages = list_pages($DOCROOT);
// Recarregar meta e conjunto de ocultas após possíveis alterações
$meta = get_meta($metaPath);
$hiddenSet = [];
foreach (($meta['hidden'] ?? []) as $h) { $hiddenSet[$h] = true; }

// Estrutura HTML e estilos do painel
echo '<!doctype html><html><head><meta charset="utf-8"><title>Admin - Grafica Mundial</title><style>
  body{font-family:sans-serif;max-width:1000px;margin:24px auto;padding:16px;background:#f7f7f7;color:#222}
  header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
  .box{background:#fff;border:1px solid #ddd;border-radius:8px;margin:12px 0;padding:12px}
  .list{display:grid;grid-template-columns:1fr auto auto auto;gap:8px;align-items:center}
  .list .item{display:contents}
  input,select,button{padding:8px;margin:4px}
  .msg{padding:10px;border-radius:6px;background:#e9f6ff;border:1px solid #b6e1ff;margin-bottom:12px}
  .hidden{opacity:0.6}
</style></head><body>';

echo '<header><h1>Admin</h1><a href="/admin?logout=1">Sair</a></header>';

// Mostrar mensagem após processar ações
if (!empty($page_msg)) { echo '<div class="msg">' . htmlspecialchars($page_msg) . '</div>'; }

// Criar página
echo '<div class="box"><h2>Criar página</h2><form method="post"><input type="hidden" name="action" value="create_page"><input name="new_page_name" placeholder="Nome da nova página"><button type="submit">Criar</button></form></div>';

// Aplicar menu
echo '<div class="box"><h2>Menu</h2><p>Aplicar o menu do index.html a todas as páginas.</p><form method="post"><input type="hidden" name="action" value="apply_menu_all"><button type="submit">Aplicar menu do index</button></form></div>';

// Duplicar página
echo '<div class="box"><h2>Duplicar página</h2><form method="post"><input type="hidden" name="action" value="duplicate_page"><label>Origem:</label><select name="page">';
foreach ($pages as $p) { echo '<option value="' . htmlspecialchars($p) . '">' . htmlspecialchars($p) . '</option>'; }
echo '</select><label>Novo nome:</label><input name="new_page_name" placeholder="Nome da nova página"><button type="submit">Duplicar</button></form></div>';

// Editor de página (GET ?edit=nome.html) — renderizar uma única vez fora do loop
$editPage = sanitize_page_name($_GET['edit'] ?? '');
if ($editPage) {
    $path = $DOCROOT . '/' . $editPage;
    $currentContent = '';
    if (is_file($path)) {
        $html = file_get_contents($path);
        $currentContent = extract_main_content($html);
    }
    echo '<div class="box"><h2>Editar página: ' . htmlspecialchars($editPage) . '</h2>';
    echo '<form method="post"><input type="hidden" name="action" value="save_page"><input type="hidden" name="page" value="' . htmlspecialchars($editPage) . '">';
    echo '<textarea name="content" rows="18" style="width:100%;">' . htmlspecialchars($currentContent) . '</textarea>';
    echo '<div><button type="submit">Salvar</button> <a href="/admin">Cancelar</a> <a href="/' . htmlspecialchars($editPage) . '" target="_blank">Ver página</a></div></form></div>';

    // Formulário de upload de imagem
    echo '<div class="box"><h2>Imagem da página</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="upload_image">';
    echo '<input type="hidden" name="page" value="' . htmlspecialchars($editPage) . '">';
    echo '<input type="file" name="image" accept="image/*">';
    echo '<label style="display:block;margin-top:8px"><input type="checkbox" name="replace_first" value="1"> Substituir a primeira imagem do conteúdo</label>';
    echo '<div style="margin-top:8px"><button type="submit">Enviar imagem</button></div>';
    echo '<p style="font-size:12px;color:#555;margin-top:8px">Imagens são salvas em <code>/uploads</code>. Após enviar, você pode colar a URL no conteúdo ou marcar para substituir automaticamente.</p>';
    echo '</form></div>';
}

// ===== Renderização do painel admin =====
echo '<div class="box"><h2>Páginas</h2><div class="list">';
foreach ($pages as $p) {
    $isHidden = isset($hiddenSet[$p]);
    $cls = $isHidden ? 'item hidden' : 'item';
    echo '<div class="' . $cls . '">' . htmlspecialchars($p) . '</div>';
    // Ocultar/Exibir
    if ($isHidden) {
        echo '<form method="post"><input type="hidden" name="action" value="show_page"><input type="hidden" name="page" value="' . htmlspecialchars($p) . '"><button type="submit">Exibir</button></form>';
    } else {
        echo '<form method="post"><input type="hidden" name="action" value="hide_page"><input type="hidden" name="page" value="' . htmlspecialchars($p) . '"><button type="submit">Ocultar</button></form>';
    }
    // Excluir (não permitir excluir index.html)
    if (strtolower($p) !== 'index.html') {
        echo '<form method="post" onsubmit="return confirm(\'Excluir ' . htmlspecialchars($p) . '?\');"><input type="hidden" name="action" value="delete_page"><input type="hidden" name="page" value="' . htmlspecialchars($p) . '"><button type="submit">Excluir</button></form>';
    } else {
        echo '<div></div>';
    }
    // Link Ver · Editar
    echo '<div><a href="/' . htmlspecialchars($p) . '" target="_blank">Ver</a> · <a href="/admin?edit=' . htmlspecialchars($p) . '">Editar</a></div>';
}
echo '</div></div>';

echo '</body></html>';


function mongodb_health_check($uri) {
    $out = [
        'uri_present' => !empty($uri),
        'has_extension' => extension_loaded('mongodb'),
        'has_client_class' => class_exists('\MongoDB\Client'),
        'host' => null,
        'srv_count' => 0,
        'txt' => [],
        'tls_connect' => 'não testado',
    ];
    if (empty($uri)) { return $out; }
    if (preg_match('#^mongodb\+srv://[^@]+@([^/]+)#', $uri, $m)) { $out['host'] = $m[1]; }
    if ($out['host']) {
        $srv = @dns_get_record('_mongodb._tcp.' . $out['host'], DNS_SRV);
        $out['srv_count'] = is_array($srv) ? count($srv) : 0;
        $txt = @dns_get_record($out['host'], DNS_TXT);
        if (is_array($txt)) {
            $out['txt'] = array_values(array_filter(array_map(function($t){ return $t['txt'] ?? ''; }, $txt)));
        }
        if (!empty($srv)) {
            $target = $srv[0]['target'] ?? null;
            $port = $srv[0]['port'] ?? 27017;
            if ($target) {
                $ctx = stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);
                $errno = 0; $errstr = '';
                $conn = @stream_socket_client('tls://' . $target . ':' . $port, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ctx);
                $out['tls_connect'] = $conn ? 'ok' : ('erro ' . $errno . ' ' . $errstr);
                if ($conn) { fclose($conn); }
            }
        }
    }
    return $out;
}

// Mostrar status MongoDB Atlas (health check leve)
$mongoUri = getenv('MONGODB_URI') ?: '';
$maskedUri = $mongoUri ? preg_replace('#^(mongodb\+srv://)([^@]+)@#', '$1***:***@', $mongoUri) : '';
$health = mongodb_health_check($mongoUri);
echo '<div class="box"><h2>MongoDB Atlas</h2>';
echo '<p>URI: ' . ($maskedUri ? htmlspecialchars($maskedUri) : '<em>não definida</em>') . '</p>';
echo '<ul>';
echo '<li>Extensão mongodb: ' . (extension_loaded('mongodb') ? 'presente' : 'ausente') . '</li>';
echo '<li>Cliente MongoDB\\Client: ' . (class_exists('\\MongoDB\\Client') ? 'presente' : 'ausente') . '</li>';
echo '<li>Host: ' . htmlspecialchars($health['host'] ?? '') . '</li>';
echo '<li>SRV registros: ' . intval($health['srv_count'] ?? 0) . '</li>';
echo '<li>TXT: ' . htmlspecialchars(implode(', ', $health['txt'] ?? [])) . '</li>';
echo '<li>TLS conexão: ' . htmlspecialchars($health['tls_connect'] ?? 'não testado') . '</li>';
echo '</ul>';
echo '<p style="font-size:12px;opacity:0.7">Teste leve (DNS/TLS). Integração real pode usar o Data API.</p>';
echo '</div>';
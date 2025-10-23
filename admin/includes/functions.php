<?php
/**
 * Funções Auxiliares
 * Mundial Gráfica - Painel Administrativo
 */

/**
 * Sanitiza entrada de dados
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Gera slug a partir de string
 */
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

/**
 * Formata data para exibição
 */
function formatDate($mongoDate) {
    if ($mongoDate instanceof MongoDB\BSON\UTCDateTime) {
        return $mongoDate->toDateTime()->format('d/m/Y H:i');
    }
    return '';
}

/**
 * Upload de arquivo
 */
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'], $max_size = MAX_FILE_SIZE) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Nenhum arquivo selecionado'];
    }
    
    // Verifica erros
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload do arquivo'];
    }
    
    // Verifica tamanho
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Arquivo muito grande. Máximo: ' . formatBytes($max_size)];
    }
    
    // Verifica extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
    }
    
    // Gera nome único
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = UPLOAD_PATH . $filename;
    
    // Move arquivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => '../uploads/' . $filename
        ];
    }
    
    return ['success' => false, 'message' => 'Erro ao salvar arquivo'];
}

/**
 * Formata bytes para leitura humana
 */
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

/**
 * Remove arquivo
 */
function deleteFile($filename) {
    $filepath = UPLOAD_PATH . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Gera token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Retorna mensagem de sucesso/erro da sessão
 */
function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $message;
}

/**
 * Define mensagem de sucesso/erro na sessão
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Exibe mensagem flash
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $class = $flash['type'] === 'error' ? 'alert-danger' : 'alert-success';
        echo "<div class='alert {$class} alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($flash['message']);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
        echo "</div>";
    }
}

/**
 * Paginação
 */
function paginate($collection, $page = 1, $limit = 10, $filter = []) {
    $skip = ($page - 1) * $limit;
    
    $total = $collection->countDocuments($filter);
    $items = $collection->find($filter, [
        'skip' => $skip,
        'limit' => $limit,
        'sort' => ['created_at' => -1]
    ])->toArray();
    
    $totalPages = ceil($total / $limit);
    
    return [
        'items' => $items,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $total,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Gera HTML de paginação
 */
function renderPagination($pagination, $base_url) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Anterior
    if ($pagination['has_prev']) {
        $prev = $pagination['current_page'] - 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$base_url}?page={$prev}'>Anterior</a></li>";
    }
    
    // Páginas
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = $i === $pagination['current_page'] ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$base_url}?page={$i}'>{$i}</a></li>";
    }
    
    // Próximo
    if ($pagination['has_next']) {
        $next = $pagination['current_page'] + 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$base_url}?page={$next}'>Próximo</a></li>";
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Log de atividades
 */
function logActivity($action, $details = '', $user_id = null) {
    try {
        $db = new Database();
        $logs = $db->getCollection('activity_logs');
        
        $logs->insertOne([
            'user_id' => $user_id ?: ($_SESSION['admin_id'] ?? null),
            'username' => $_SESSION['admin_user'] ?? 'Sistema',
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);
    } catch (Exception $e) {
        // Log silencioso - não interrompe a aplicação
    }
}
?>
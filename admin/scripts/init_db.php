<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $db->initializeCollections();
    echo "Inicialização concluída com sucesso.\n";
} catch (Exception $e) {
    echo "Falha na inicialização: " . $e->getMessage() . "\n";
    exit(1);
}
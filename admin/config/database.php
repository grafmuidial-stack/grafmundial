<?php
/**
 * Configuração de conexão com MongoDB Atlas
 * Mundial Gráfica - Painel Administrativo
 */

class Database {
    private $client;
    private $database;
    private $uri;
    private $dbName;

    public function __construct() {
        try {
            // Carrega o autoloader do Composer para usar a biblioteca MongoDB
            require_once __DIR__ . '/../vendor/autoload.php';

            // Exige a URI via variável de ambiente (não usa credenciais hardcoded)
            $envUri = getenv('MONGODB_URI') ?: getenv('MONGO_URI');
            if ($envUri && $envUri !== '') {
                $this->uri = $envUri;
            } else {
                throw new Exception('Defina MONGODB_URI com sua string SRV do Atlas.');
            }

            if (!extension_loaded('mongodb')) {
                throw new Exception('Extensão mongodb ausente. Ative-a no php.ini.');
            }

            // Conecta e seleciona banco (extraído da URI, ou fallback)
            $this->client = new MongoDB\Client($this->uri);
            $this->dbName = $this->extractDbNameFromUri($this->uri) ?: 'graficamundial';
            $this->database = $this->client->selectDatabase($this->dbName);

            // Valida conexão com ping
            $this->database->command(['ping' => 1]);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
private function extractDbNameFromUri($uri) {
        $parts = parse_url($uri);
        if (!empty($parts['path'])) {
            $path = ltrim($parts['path'], '/');
            if ($path !== '') {
                return $path;
            }
        }
        return null;
    }

    /**
     * Retorna uma coleção específica
     */
    public function getCollection($collectionName) {
        return $this->database->$collectionName;
    }
    
    /**
     * Retorna o cliente MongoDB
     */
    public function getClient() {
        return $this->client;
    }
    
    /**
     * Retorna o banco de dados
     */
    public function getDatabase() {
        return $this->database;
    }
    
    /**
     * Testa a conexão
     */
    public function testConnection() {
        try {
            $this->database->command(['ping' => 1]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Inicializa as coleções básicas se não existirem
     */
    public function initializeCollections() {
        $collections = [
            'admin_users',
            'pages',
            'menus',
            'products',
            'images',
            'settings',
            'activity_logs'
        ];
        
        // Lista existentes e cria somente as ausentes
        $existing = [];
        foreach ($this->database->listCollections() as $c) {
            $existing[] = $c->getName();
        }

        foreach ($collections as $collection) {
            if (!in_array($collection, $existing, true)) {
                try {
                    $this->database->createCollection($collection);
                } catch (Exception $e) {
                    // Ignora condição de corrida
                }
            }
        }

        // Garantir índices nas coleções
        $this->ensureIndexes();

        // Cria admin padrão se não existir
        $users = $this->getCollection('admin_users');
        $adminExists = $users->findOne(['username' => 'admin']);
        if (!$adminExists) {
            $users->insertOne([
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'email' => 'admin@graficamundial.com',
                'role' => 'super_admin',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'active' => true
            ]);
        }
    }
    
    private function ensureIndexes() {
        $users = $this->getCollection('admin_users');
        $users->createIndex(['username' => 1], ['unique' => true]);
        $users->createIndex(['email' => 1], ['unique' => true]);
        $users->createIndex(['active' => 1]);
    
        $pages = $this->getCollection('pages');
        $pages->createIndex(['slug' => 1], ['unique' => true]);
        $pages->createIndex(['status' => 1]);
        $pages->createIndex(['created_at' => -1]);
    
        $menus = $this->getCollection('menus');
        $menus->createIndex(['name' => 1]);
        $menus->createIndex(['position' => 1]);
    
        $products = $this->getCollection('products');
        $products->createIndex(['slug' => 1], ['unique' => true]);
        $products->createIndex(['category' => 1]);
        $products->createIndex(['created_at' => -1]);
    
        $images = $this->getCollection('images');
        $images->createIndex(['filename' => 1]);
        $images->createIndex(['created_at' => -1]);
        $images->createIndex(['action' => 1]);
    
        $logs = $this->getCollection('activity_logs');
        $logs->createIndex(['created_at' => -1]);
        $logs->createIndex(['user_id' => 1]);
        $logs->createIndex(['action' => 1]);
    }
}

// Configurações globais
define('ADMIN_PATH', dirname(__DIR__));
define('ROOT_PATH', dirname(ADMIN_PATH));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Cria pasta de uploads se não existir
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}
?>
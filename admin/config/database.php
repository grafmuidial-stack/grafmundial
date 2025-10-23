<?php
/**
 * Configuração de conexão com MongoDB Atlas
 * Mundial Gráfica - Painel Administrativo
 */

class Database {
    private $client;
    private $database;
    private $uri;
    
    public function __construct() {
        // String de conexão do MongoDB Atlas
        // Substitua pelos seus dados reais do MongoDB Atlas

        $this->uri = "mongodb+srv://ara100limite:ERxkG9nXZjbwvpMk@cluster0.yzf2r.mongodb.net/best?retryWrites=true&w=majority";
        
        try {
            // Carrega o autoloader do Composer para usar a biblioteca MongoDB
            require_once __DIR__ . '/../vendor/autoload.php';
            
            // Conecta ao MongoDB Atlas usando a extensão nativa
            $this->client = new MongoDB\Driver\Manager($this->uri);
            
            // Testa a conexão executando um comando ping
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $this->client->executeCommand('admin', $command);
            
            // Inicializa o banco de dados usando a biblioteca de alto nível
            $mongoClient = new MongoDB\Client($this->uri);
            $this->database = $mongoClient->best;
            
        } catch (Exception $e) {
            die("Erro na conexão com MongoDB Atlas: " . $e->getMessage());
        }
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
            $result = $this->client->listDatabases();
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
            'settings'
        ];
        
        foreach ($collections as $collection) {
            $this->database->createCollection($collection);
        }
        
        // Cria usuário admin padrão se não existir
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
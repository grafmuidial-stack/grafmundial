<?php
/**
 * Sistema de Autenticação
 * Mundial Gráfica - Painel Administrativo
 */

session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $users;
    
    public function __construct() {
        $this->db = new Database();
        $this->users = $this->db->getCollection('admin_users');
    }
    
    /**
     * Realiza o login do usuário
     */
    public function login($username, $password) {
        try {
            $user = $this->users->findOne([
                'username' => $username,
                'active' => true
            ]);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_user'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['admin_id'] = (string)$user['_id'];
                
                // Atualiza último login
                $this->users->updateOne(
                    ['_id' => $user['_id']],
                    ['$set' => ['last_login' => new MongoDB\BSON\UTCDateTime()]]
                );
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Realiza o logout
     */
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    /**
     * Verifica se o usuário está logado
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
    }
    
    /**
     * Verifica se o usuário tem permissão
     */
    public function hasPermission($required_role = 'admin') {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_role = $_SESSION['admin_role'] ?? 'user';
        
        $roles = [
            'user' => 1,
            'admin' => 2,
            'super_admin' => 3
        ];
        
        return ($roles[$user_role] ?? 0) >= ($roles[$required_role] ?? 0);
    }
    
    /**
     * Força o login (redireciona se não logado)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Força permissão específica
     */
    public function requirePermission($required_role = 'admin') {
        $this->requireLogin();
        
        if (!$this->hasPermission($required_role)) {
            die('Acesso negado. Permissão insuficiente.');
        }
    }
    
    /**
     * Retorna dados do usuário logado
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->users->findOne([
            '_id' => new MongoDB\BSON\ObjectId($_SESSION['admin_id'])
        ]);
    }
    
    /**
     * Cria novo usuário admin
     */
    public function createUser($username, $password, $email, $role = 'admin') {
        try {
            // Verifica se usuário já existe
            $exists = $this->users->findOne(['username' => $username]);
            if ($exists) {
                return false;
            }
            
            $result = $this->users->insertOne([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email,
                'role' => $role,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'active' => true
            ]);
            
            return $result->getInsertedId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Altera senha do usuário
     */
    public function changePassword($user_id, $new_password) {
        try {
            $result = $this->users->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                ['$set' => ['password' => password_hash($new_password, PASSWORD_DEFAULT)]]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Função helper para verificar autenticação
 */
function checkAuth() {
    $auth = new Auth();
    $auth->requireLogin();
    return $auth;
}

/**
 * Função helper para verificar permissão
 */
function checkPermission($role = 'admin') {
    $auth = new Auth();
    $auth->requirePermission($role);
    return $auth;
}

/**
 * Função global para verificar se está logado
 */
function isLoggedIn() {
    $auth = new Auth();
    return $auth->isLoggedIn();
}

/**
 * Função global para obter usuário atual
 */
function getCurrentUser() {
    $auth = new Auth();
    return $auth->getCurrentUser();
}
?>
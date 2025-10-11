# 🚀 Instalação do Painel Administrativo - Mundial Gráfica
## Guia Completo para Servidor de Produção

Este guia detalha como instalar e configurar o painel administrativo da Mundial Gráfica em um servidor de produção.

---

## 📋 Pré-requisitos do Servidor

### Requisitos Mínimos:
- **PHP 8.0+** com extensões:
  - `mongodb` (obrigatório)
  - `curl`
  - `json`
  - `mbstring`
  - `openssl`
  - `zip`
- **Apache 2.4+** ou **Nginx 1.18+**
- **Composer** (gerenciador de dependências PHP)
- **SSL/TLS** (certificado válido - recomendado)
- **MongoDB Atlas** (conta gratuita ou paga)

### Verificação do Sistema:
```bash
# Verificar versão do PHP
php -v

# Verificar extensões instaladas
php -m | grep mongodb
php -m | grep curl
php -m | grep json

# Verificar Composer
composer --version
```

---

## 🔧 Instalação Passo a Passo

### 1. Preparação do Servidor

#### Para Ubuntu/Debian:
```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP e extensões
sudo apt install php8.1 php8.1-cli php8.1-curl php8.1-json php8.1-mbstring php8.1-zip php8.1-xml

# Instalar extensão MongoDB
sudo pecl install mongodb
echo "extension=mongodb.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
echo "extension=mongodb.so" | sudo tee -a /etc/php/8.1/cli/php.ini

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Reiniciar Apache
sudo systemctl restart apache2
```

#### Para CentOS/RHEL:
```bash
# Instalar repositório EPEL e Remi
sudo yum install epel-release
sudo yum install https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Instalar PHP 8.1
sudo yum module enable php:remi-8.1
sudo yum install php php-cli php-curl php-json php-mbstring php-zip php-xml

# Instalar extensão MongoDB
sudo pecl install mongodb
echo "extension=mongodb.so" | sudo tee -a /etc/php.ini

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Reiniciar Apache
sudo systemctl restart httpd
```

### 2. Upload dos Arquivos

```bash
# Criar diretório do projeto
sudo mkdir -p /var/www/html/graficamundial
cd /var/www/html/graficamundial

# Upload via SCP (do seu computador local)
scp -r admin/ usuario@servidor:/var/www/html/graficamundial/

# Ou via Git (se tiver repositório privado)
git clone https://github.com/seu-usuario/graficamundial-admin.git admin
```

### 3. Configuração de Permissões

```bash
# Definir proprietário correto
sudo chown -R www-data:www-data /var/www/html/graficamundial

# Permissões de diretórios
sudo find /var/www/html/graficamundial -type d -exec chmod 755 {} \;

# Permissões de arquivos
sudo find /var/www/html/graficamundial -type f -exec chmod 644 {} \;

# Permissões especiais para uploads
sudo chmod 777 /var/www/html/graficamundial/uploads
sudo chmod 777 /var/www/html/graficamundial/admin/logs
```

### 4. Instalação de Dependências

```bash
cd /var/www/html/graficamundial/admin
composer install --no-dev --optimize-autoloader
```

---

## 🗄️ Configuração do Banco de Dados

### 1. MongoDB Atlas Setup

1. **Criar conta**: Acesse [MongoDB Atlas](https://www.mongodb.com/atlas)
2. **Criar cluster**: Escolha a região mais próxima
3. **Configurar usuário**:
   ```
   Username: graficamundial_admin
   Password: [gere uma senha forte]
   ```
4. **Whitelist de IPs**: Adicione o IP do seu servidor
5. **Obter string de conexão**:
   ```
   mongodb+srv://graficamundial_admin:SUA_SENHA@cluster0.mongodb.net/graficamundial?retryWrites=true&w=majority
   ```

### 2. Configuração Local

Edite o arquivo `admin/config/database.php`:

```php
<?php
// Configuração de produção
$mongoUri = "mongodb+srv://graficamundial_admin:SUA_SENHA_AQUI@cluster0.mongodb.net/graficamundial?retryWrites=true&w=majority";

class Database {
    private $client;
    private $database;
    
    public function __construct() {
        try {
            $this->client = new MongoDB\Client($mongoUri);
            $this->database = $this->client->selectDatabase('graficamundial');
        } catch (Exception $e) {
            die('Erro de conexão com MongoDB: ' . $e->getMessage());
        }
    }
    
    public function getCollection($name) {
        return $this->database->selectCollection($name);
    }
}
?>
```

---

## 🔒 Configuração de Segurança

### 1. SSL/HTTPS (Obrigatório para Produção)

#### Com Let's Encrypt (Gratuito):
```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache

# Obter certificado
sudo certbot --apache -d seudominio.com -d www.seudominio.com

# Renovação automática
sudo crontab -e
# Adicionar linha:
0 12 * * * /usr/bin/certbot renew --quiet
```

### 2. Configuração do Apache

Crie o arquivo `/etc/apache2/sites-available/graficamundial.conf`:

```apache
<VirtualHost *:80>
    ServerName seudominio.com
    ServerAlias www.seudominio.com
    DocumentRoot /var/www/html/graficamundial
    
    # Redirecionar para HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName seudominio.com
    ServerAlias www.seudominio.com
    DocumentRoot /var/www/html/graficamundial
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/seudominio.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/seudominio.com/privkey.pem
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Admin Panel Protection
    <Directory "/var/www/html/graficamundial/admin">
        AllowOverride All
        Require all granted
        
        # IP Restriction (opcional - substitua pelos IPs permitidos)
        # Require ip 192.168.1.100
        # Require ip 203.0.113.0/24
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/graficamundial_error.log
    CustomLog ${APACHE_LOG_DIR}/graficamundial_access.log combined
</VirtualHost>
```

Ativar o site:
```bash
sudo a2ensite graficamundial.conf
sudo a2enmod rewrite ssl headers
sudo systemctl reload apache2
```

### 3. Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Ou iptables
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
sudo iptables -A INPUT -j DROP
```

---

## 🚀 Inicialização e Testes

### 1. Criar Usuário Administrativo

Execute o script de inicialização:

```bash
cd /var/www/html/graficamundial/admin
php -r "
require_once 'config/database.php';
\$db = new Database();
\$users = \$db->getCollection('admin_users');

\$users->insertOne([
    'username' => 'admin',
    'password' => password_hash('SUA_SENHA_FORTE_AQUI', PASSWORD_DEFAULT),
    'email' => 'admin@seudominio.com',
    'role' => 'super_admin',
    'active' => true,
    'created_at' => new MongoDB\BSON\UTCDateTime()
]);

echo 'Usuário administrativo criado com sucesso!';
"
```

### 2. Testes de Funcionamento

1. **Acesse**: `https://seudominio.com/admin/`
2. **Login**: Use as credenciais criadas
3. **Teste todas as funcionalidades**:
   - Dashboard
   - Gerenciamento de páginas
   - Upload de imagens
   - Gerenciamento de produtos
   - Logs de atividade

---

## 📊 Monitoramento e Manutenção

### 1. Logs do Sistema

```bash
# Logs do Apache
sudo tail -f /var/log/apache2/graficamundial_error.log
sudo tail -f /var/log/apache2/graficamundial_access.log

# Logs do PHP
sudo tail -f /var/log/php_errors.log

# Logs de segurança do admin
sudo tail -f /var/www/html/graficamundial/admin/logs/security.log
```

### 2. Backup Automático

Crie o script `/root/backup-graficamundial.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/graficamundial"
SITE_DIR="/var/www/html/graficamundial"

# Criar diretório de backup
mkdir -p $BACKUP_DIR

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz $SITE_DIR

# Backup do MongoDB (usando mongodump se disponível)
# mongodump --uri="mongodb+srv://..." --out=$BACKUP_DIR/mongo_$DATE

# Manter apenas os últimos 7 backups
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup concluído: $DATE"
```

Configurar cron:
```bash
sudo crontab -e
# Backup diário às 2h da manhã
0 2 * * * /root/backup-graficamundial.sh >> /var/log/backup.log 2>&1
```

### 3. Atualizações

```bash
# Atualizar dependências
cd /var/www/html/graficamundial/admin
composer update --no-dev

# Atualizar sistema
sudo apt update && sudo apt upgrade -y
sudo systemctl restart apache2
```

---

## 🔧 Solução de Problemas

### Problemas Comuns:

#### 1. Erro "Extension mongodb not found"
```bash
sudo pecl install mongodb
echo "extension=mongodb.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
sudo systemctl restart apache2
```

#### 2. Erro de permissões
```bash
sudo chown -R www-data:www-data /var/www/html/graficamundial
sudo chmod -R 755 /var/www/html/graficamundial
sudo chmod 777 /var/www/html/graficamundial/uploads
```

#### 3. Erro de conexão MongoDB
- Verificar string de conexão
- Confirmar whitelist de IPs no Atlas
- Testar conectividade: `telnet cluster0.mongodb.net 27017`

#### 4. Erro 500 Internal Server Error
```bash
# Verificar logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/php_errors.log

# Verificar sintaxe PHP
php -l /var/www/html/graficamundial/admin/index.php
```

---

## 📞 Suporte e Contato

Para suporte técnico:
- **Email**: suporte@graficamundial.com
- **Telefone**: (XX) XXXX-XXXX
- **Documentação**: https://docs.graficamundial.com

---

## 🔐 Checklist de Segurança Final

- [ ] SSL/HTTPS configurado e funcionando
- [ ] Firewall configurado
- [ ] Permissões de arquivo corretas
- [ ] Senha administrativa forte
- [ ] Backup automático configurado
- [ ] Logs de segurança ativos
- [ ] Headers de segurança configurados
- [ ] MongoDB com autenticação
- [ ] IPs administrativos restritos (opcional)
- [ ] Monitoramento ativo

---

**✅ Instalação Concluída!**

O painel administrativo da Mundial Gráfica está agora instalado e seguro em seu servidor de produção.
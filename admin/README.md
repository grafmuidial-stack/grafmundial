# Painel Administrativo - Mundial Gráfica

Sistema de gerenciamento de conteúdo para o site da Mundial Gráfica, desenvolvido em PHP com MongoDB Atlas.

## 🚀 Funcionalidades

- ✅ **Dashboard** com estatísticas e atividades recentes
- ✅ **Gerenciamento de Páginas** (criar, editar, excluir)
- ✅ **Gerenciamento de Menus** dinâmicos
- ✅ **Gerenciamento de Produtos** do catálogo
- ✅ **Upload e Gerenciamento de Imagens**
- ✅ **Sistema de Autenticação** seguro
- ✅ **Logs de Atividade** completos
- ✅ **Interface Responsiva** (Bootstrap 5)

## 📋 Pré-requisitos

- **XAMPP** com PHP 7.4+ e Apache
- **Composer** para gerenciar dependências
- **Conta MongoDB Atlas** (gratuita)
- **Extensão MongoDB** para PHP

## 🛠️ Instalação

### 1. Instalar Extensão MongoDB

**Windows (XAMPP):**
```bash
# Baixe a extensão php_mongodb.dll compatível com sua versão do PHP
# Coloque em: C:\xampp\php\ext\

# Adicione no php.ini:
extension=mongodb
```

### 2. Instalar Dependências

```bash
cd C:\xampp\htdocs\graficamundial\admin
composer install
```

### 3. Configurar MongoDB Atlas

1. Acesse [MongoDB Atlas](https://www.mongodb.com/atlas)
2. Crie uma conta gratuita
3. Crie um novo cluster
4. Configure o acesso (IP whitelist: 0.0.0.0/0 para desenvolvimento)
5. Crie um usuário de banco de dados
6. Obtenha a string de conexão

### 4. Configurar Banco de Dados

Edite o arquivo `config/database.php`:

```php
// Substitua pela sua string de conexão do MongoDB Atlas
$this->uri = "mongodb+srv://SEU_USUARIO:SUA_SENHA@cluster0.mongodb.net/graficamundial?retryWrites=true&w=majority";
```

### 5. Inicializar Sistema

1. Acesse: `http://localhost/graficamundial/admin/`
2. Faça login com:
   - **Usuário:** admin
   - **Senha:** admin123

## 📁 Estrutura do Projeto

```
admin/
├── config/
│   └── database.php          # Configuração MongoDB
├── includes/
│   ├── auth.php             # Sistema de autenticação
│   └── functions.php        # Funções auxiliares
├── pages/
│   ├── manage-pages.php     # Gerenciar páginas
│   ├── manage-menus.php     # Gerenciar menus
│   ├── manage-products.php  # Gerenciar produtos
│   └── manage-images.php    # Gerenciar imagens
├── assets/
│   ├── css/admin.css        # Estilos do admin
│   └── js/admin.js          # JavaScript do admin
├── index.php                # Dashboard principal
├── login.php                # Página de login
├── logout.php               # Logout
└── composer.json            # Dependências
```

## 🗄️ Estrutura do Banco de Dados

### Coleções MongoDB:

- **admin_users** - Usuários administrativos
- **pages** - Páginas do site
- **menus** - Estrutura de menus
- **products** - Produtos do catálogo
- **images** - Imagens uploadadas
- **settings** - Configurações do site
- **activity_logs** - Logs de atividade

## 🔐 Segurança

- Senhas criptografadas com `password_hash()`
- Proteção CSRF em formulários
- Validação e sanitização de dados
- Controle de acesso por níveis
- Logs de atividade completos

## 🎨 Interface

- **Bootstrap 5** para responsividade
- **Font Awesome** para ícones
- **Design moderno** com gradientes
- **Sidebar fixa** com navegação
- **Cards estatísticos** no dashboard
- **Modais** para ações rápidas

## 📱 Responsividade

- **Desktop:** Sidebar fixa lateral
- **Tablet/Mobile:** Sidebar colapsável
- **Touch-friendly:** Botões e links otimizados

## 🔧 Configurações Avançadas

### Upload de Arquivos
- Tamanho máximo: 5MB
- Formatos aceitos: JPG, PNG, GIF, SVG
- Pasta de destino: `/uploads/`

### Logs de Atividade
- Todas as ações são registradas
- IP e User-Agent capturados
- Histórico completo de mudanças

### Backup Automático
- MongoDB Atlas faz backup automático
- Dados seguros na nuvem
- Recuperação point-in-time

## 🚨 Solução de Problemas

### Erro: "Extensão MongoDB não encontrada"
```bash
# Verifique se a extensão está instalada:
php -m | grep mongodb

# Se não aparecer, instale:
# Windows: Baixe php_mongodb.dll
# Linux: sudo apt-get install php-mongodb
```

### Erro de Conexão MongoDB
1. Verifique a string de conexão
2. Confirme usuário e senha
3. Verifique whitelist de IPs
4. Teste conectividade de rede

### Problemas de Permissão
```bash
# Dê permissão à pasta uploads:
chmod 777 uploads/
```

## 📞 Suporte

Para dúvidas ou problemas:
- Verifique os logs de erro do PHP
- Consulte a documentação do MongoDB
- Teste a conexão com o banco

## 🔄 Atualizações

Para atualizar o sistema:
```bash
composer update
```

---

**Mundial Gráfica** - Sistema desenvolvido com ❤️ em PHP + MongoDB Atlas
# Mundial Gráfica - Site e Painel Administrativo

Este projeto contém o site da Mundial Gráfica e seu painel administrativo.

## 🌐 Site Principal (GitHub Pages)

O site principal é estático (HTML/CSS/JS) e está hospedado no GitHub Pages:
- **URL:** https://[seu-usuario].github.io/graficamundial/
- **Arquivos:** Todos os arquivos HTML, CSS, JS e imagens na raiz do projeto

### Estrutura do Site:
- `index.html` - Página principal
- `styles.css` - Estilos principais
- `*.html` - Páginas do catálogo (produtos, serviços)
- `*.png`, `*.jpg`, `*.svg` - Imagens e ícones
- `uploads/` - Pasta para imagens enviadas

## 🔧 Painel Administrativo (Local - XAMPP)

O painel administrativo é desenvolvido em PHP e **NÃO funciona no GitHub Pages**. 
Ele deve ser executado localmente no XAMPP.

### Pré-requisitos:
- XAMPP com PHP 7.4+
- MongoDB Atlas (conta gratuita)
- Composer

### Instalação Local:

1. **Clone o repositório:**
```bash
git clone https://github.com/[seu-usuario]/graficamundial.git
cd graficamundial
```

2. **Configure o XAMPP:**
   - Coloque o projeto em `C:\xampp\htdocs\graficamundial`
   - Inicie Apache no XAMPP

3. **Instale dependências do admin:**
```bash
cd admin
composer install
```

4. **Configure MongoDB Atlas:**
   - Edite `admin/config/database.php`
   - Substitua a string de conexão pela sua

5. **Acesse o admin:**
   - URL: `http://localhost/graficamundial/admin/`
   - Usuário: `admin`
   - Senha: `admin123`

## 📁 Estrutura do Projeto

```
graficamundial/
├── index.html              # Site principal
├── styles.css              # Estilos do site
├── *.html                  # Páginas do catálogo
├── *.png, *.jpg, *.svg     # Imagens
├── uploads/                # Uploads de imagens
├── admin/                  # Painel administrativo (PHP)
│   ├── config/            # Configurações
│   ├── includes/          # Autenticação e funções
│   ├── pages/             # Páginas do admin
│   ├── assets/            # CSS/JS do admin
│   └── composer.json      # Dependências PHP
└── .gitignore             # Exclui pasta admin do GitHub
```

## 🚀 Deploy

### Site (GitHub Pages):
1. Faça commit dos arquivos estáticos
2. Push para o repositório
3. Configure GitHub Pages nas configurações do repo
4. O site estará disponível em poucos minutos

### Admin (Local):
- O admin permanece funcionando localmente
- Não é enviado para o GitHub (excluído pelo .gitignore)
- Gerencia conteúdo que pode ser sincronizado com o site

## 🔐 Segurança

- **Site:** Estático, sem vulnerabilidades de servidor
- **Admin:** Protegido por autenticação, apenas local
- **Dados:** MongoDB Atlas com criptografia

## 📝 Funcionalidades do Admin

- ✅ Dashboard com estatísticas
- ✅ Gerenciamento de páginas
- ✅ Gerenciamento de menus
- ✅ Gerenciamento de produtos
- ✅ Upload de imagens
- ✅ Sistema de autenticação

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📞 Suporte

Para dúvidas sobre o projeto, entre em contato através do site da Mundial Gráfica.
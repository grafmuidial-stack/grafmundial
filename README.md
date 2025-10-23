# Mundial Gráfica - Site e Painel Administrativo

Este projeto contém o site da Mundial Gráfica e seu painel administrativo.

## 🌐 Site Principal (GitHub Pages via Actions)

O site principal é estático (HTML/CSS/JS) e é publicado via GitHub Actions a partir da pasta `frontend/`.
- **URL:** https://[seu-usuario].github.io/graficamundial/ (ou seu domínio do CNAME)
- **Fonte de publicação:** workflow `.github/workflows/deploy.yml` (deploy de `frontend/`)
- **Arquivos incluídos:** Todos os arquivos HTML, CSS, JS e imagens dentro de `frontend/` (inclui `CNAME`)

### Estrutura do Site:
- `frontend/index.html` - Página principal
- `frontend/styles.css` - Estilos principais
- `frontend/*.html` - Páginas do catálogo (produtos, serviços)
- `frontend/*.png`, `*.jpg`, `*.svg` - Imagens e ícones
- `frontend/uploads/` - Pasta para imagens enviadas

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
cd backend/admin
composer install
```

4. **Configure MongoDB Atlas:**
   - Edite `backend/admin/config/database.php`
   - Substitua a string de conexão pela sua

5. **Acesse o admin:**
   - URL: `http://localhost/graficamundial/admin/`
   - Usuário: `admin`
   - Senha: `admin123`

## 📁 Estrutura do Projeto

```
graficamundial/
├── frontend/              # Site estático publicado no Pages
│   ├── index.html
│   ├── styles.css
│   ├── *.html
│   ├── assets (imgs/svg)
│   └── CNAME              # Domínio customizado (opcional)
├── backend/               # Painel administrativo (PHP), local
│   ├── admin/
│   └── router.php
├── .github/workflows/deploy.yml  # Deploy automático do frontend
├── .gitignore
└── README.md
```

## 🚀 Deploy

### Site (GitHub Pages via Action):
1. Faça commit das mudanças na pasta `frontend/`
2. Push para a branch `main`
3. A Action `deploy.yml` fará o publish para `gh-pages`
4. O site estará disponível em poucos minutos

### Admin (Local):
- O admin permanece funcionando localmente
- Não é utilizado no Pages
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
# Gráfica Mundial

Site da Gráfica Mundial com painel administrativo hospedado no Render.

## Estrutura do Projeto

- `frontend/` - Site estático (HTML, CSS, JS)
- `admin/` - Painel administrativo (PHP)
- `backend/` - API e lógica do servidor
- `uploads/` - Arquivos enviados

## Configuração Local

### Pré-requisitos
- PHP 7.4+
- Servidor web (Apache/Nginx) ou PHP built-in server

### Instalação
1. Clone o repositório
2. Configure o servidor web para servir a partir da raiz do projeto
3. Acesse `admin/` para o painel administrativo

## Deploy no Render

O site está hospedado em: https://grafmundial.onrender.com/

### Configuração de Domínio Customizado

Para apontar `graficamundial.com` para o Render:

1. **No Dashboard do Render:**
   - Acesse o serviço em https://dashboard.render.com
   - Vá em Settings > Custom Domains
   - Adicione `graficamundial.com` e `www.graficamundial.com`

2. **No seu provedor de DNS:**
   - **Domínio raiz (`graficamundial.com`):**
     - Se suporta ALIAS/ANAME: `ALIAS graficamundial.com grafmundial.onrender.com`
     - Se não suporta: `A graficamundial.com 216.24.57.1`
   - **Subdomínio www:**
     - `CNAME www grafmundial.onrender.com`
   - **Remova registros AAAA** (IPv6) se existirem

3. **Verificação:**
   - Volte no Render e clique "Verify" para cada domínio
   - Aguarde a emissão dos certificados SSL (alguns minutos)

### Cloudflare (se usar)
- Use `CNAME` para ambos `@` e `www` apontando para `grafmundial.onrender.com`
- Mantenha "DNS only" até verificar, depois pode usar "Proxied"

## URLs do Site
- **Produção:** https://graficamundial.com (após configurar DNS)
- **Render direto:** https://grafmundial.onrender.com/
- **GitHub (código):** https://github.com/grafmuidial-stack/graficamundial

## Preview Local
```bash
# Servidor completo (raiz + admin) com roteador
php -S localhost:8000 router.php

# Apenas frontend
php -S localhost:8001 -t frontend

# Painel admin
php -S localhost:8002 -t admin
```

## Desenvolvimento
- **Com permissão de escrita:** `git push origin main` (auto-deploy no Render)
- **Sem permissão:** Fork → Pull Request

## Notas Importantes
- O Render faz auto-deploy a cada push na branch `main`
- Configuração do build está em `render.yaml`
- Não use GitHub Pages junto com domínio customizado no Render (conflito de propriedade)

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
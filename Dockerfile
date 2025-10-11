# Dockerfile para servir o site estático no Render via Node + serve
# Usa imagem leve do Node
FROM node:18-alpine

# Diretório de trabalho dentro do container
WORKDIR /usr/src/app

# Copia todo o conteúdo do repositório para o container
COPY . .

# Instala o servidor estático
RUN npm install -g serve

# Porta esperada pelo Render
ENV PORT=10000

# Comando de inicialização: serve estático na porta $PORT
CMD ["sh", "-c", "serve . -l ${PORT}"]
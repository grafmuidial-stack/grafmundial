# Dockerfile para servir o site estático no Render via Node + serve
FROM node:18-alpine

WORKDIR /usr/src/app

# Copia apenas o frontend
COPY ./frontend ./frontend

# Instala servidor estático
RUN npm install -g serve

# Porta esperada pelo Render
ENV PORT=10000

# Comando de inicialização: serve somente o diretório frontend na porta $PORT
CMD ["sh", "-c", "serve ./frontend -l ${PORT}"]
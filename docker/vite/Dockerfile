# C:\serv\sacolinhas\docker\vite\Dockerfile
# Imagem base Node.js, versão LTS e leve
FROM node:18-alpine 

# Define o diretório de trabalho dentro do container
WORKDIR /var/www

# Copia os arquivos de configuração de dependências do Node.js
# Isso permite que o Docker use o cache de camadas se package.json não mudar
COPY package.json package-lock.json ./

# Instala as dependências do Node.js
RUN npm install

# Copia o restante dos arquivos do projeto (frontend)
# Para o Vite, o volume mount já cuida disso, então esta linha pode ser opcional
# se você sempre montar o projeto. Mas para builds de imagem, é comum ter.
COPY . .

# Expõe a porta padrão do servidor de desenvolvimento do Vite
EXPOSE 5173

# Comando para iniciar o servidor de desenvolvimento do Vite
# 'npm run dev' é o comando padrão para iniciar o servidor de desenvolvimento
CMD ["npm", "run", "dev"]
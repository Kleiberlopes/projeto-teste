FROM php:8.2-cli

# Instalar dependências necessárias
RUN apt-get update && apt-get install -y \
    curl \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Definir diretório de trabalho
WORKDIR /app

# Copiar scripts do repositório
COPY faltaaddapi.php /app/

# Definir permissões
RUN chmod +x /app/faltaaddapi.php

# Expor porta 8000 para servidor web
EXPOSE 8000

# Comando padrão - executar como servidor web
CMD ["php", "-S", "0.0.0.0:8000"]

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

# Comando padrão - executar o script
ENTRYPOINT ["php", "/app/faltaaddapi.php"]
CMD []

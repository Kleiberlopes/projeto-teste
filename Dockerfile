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
COPY index.html /app/

# Definir permissões
RUN chmod +x /app/faltaaddapi.php

# Expor porta 8000 para servidor web
EXPOSE 8000

# Criar arquivo de roteamento PHP
RUN cat > /app/router.php << 'EOF'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/' || $path === '/index.html') {
    include '/app/index.html';
    exit;
}

if ($path === '/process' || strpos($path, '.php') !== false || isset($_GET['lista'])) {
    include '/app/faltaaddapi.php';
    exit;
}

// Retornar 404 para outros caminhos
http_response_code(404);
echo json_encode(['erro' => 'Rota não encontrada']);
exit;
EOF

# Comando padrão - executar como servidor web
CMD ["php", "-S", "0.0.0.0:8000", "/app/router.php"]

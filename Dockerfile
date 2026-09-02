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

# Criar arquivo de roteamento PHP (CORRIGIDO)
RUN cat > /app/router.php << 'EOF'
<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Servir arquivos estáticos primeiro
if ($path === '/' || $path === '/index.html') {
    header('Content-Type: text/html; charset=utf-8');
    readfile('/app/index.html');
    exit;
}

// Processar requisições da API (EVITAR LOOP INFINITO)
if (preg_match('/(faltaaddapi\.php|\?lista)/', $_SERVER['REQUEST_URI'])) {
    include '/app/faltaaddapi.php';
    exit;
}

// Retornar 404 para outros caminhos
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['erro' => 'Rota não encontrada']);
exit;
EOF

# Comando padrão - executar como servidor web
CMD ["php", "-S", "0.0.0.0:8000", "/app/router.php"]

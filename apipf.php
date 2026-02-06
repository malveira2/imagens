<?php

header('Content-Type: application/json'); // Definir o tipo de resposta como JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Função para fazer a requisição para a API do Banco do Brasil usando proxy e retentativas
function makeRequest($agencia, $conta, $senha, $proxy, $maxRetries = 5) {
    $url = "https://wallet.bb.com.br/cfe-mbp/api/v1/login/MOV_OUROCARD_PESSOA_FISICA_AGENCIA_CONTA/consultar-titulares";

    // Remover o último dígito da agência e conta, se necessário
    $agencia = substr($agencia, 0, -1);
    $conta = substr($conta, 0, -1);

    // Dados a serem enviados para a API
    $data = json_encode([
        "agencia" => $agencia,
        "conta" => $conta,
        "senha" => $senha
    ]);

    // Configuração dos headers
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json; charset=utf-8",
        "User-Agent: samsung;SM-A235M;Android;13;mbp-android-app"
    ];

    // Configuração do proxy
    $proxyOptions = [
        CURLOPT_PROXY => $proxy, // Proxy informado
        CURLOPT_PROXYUSERPWD => "user-bM2N2zb6jauKcgM7-type-residential-country-BR:ODHRAPXyl3SJDkcr", // Autenticação do proxy
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30, // Timeout de 30 segundos
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data
    ];

    $attempts = 0;
    $success = false;
    $response = '';

    // Loop para continuar tentando a requisição até obter sucesso ou atingir o máximo de tentativas
    while ($attempts < $maxRetries && !$success) {
        $ch = curl_init($url);

        // Definir as opçes de cURL
        curl_setopt_array($ch, $proxyOptions);

        // Executar a requisição
        $response = curl_exec($ch);

        // Verificar se houve erro
        if ($response === false) {
            $error = curl_error($ch);
            $attempts++;
            curl_close($ch);
            // Esperar 2 segundos antes de tentar novamente
            sleep(2);
        } else {
            // Se a resposta for bem-sucedida, marcar sucesso
            $success = true;
        }

        curl_close($ch);
    }

    // Verificar se obteve sucesso
    if (!$success) {
        return json_encode(["status" => "error", "message" => "Falha na conexão após $maxRetries tentativas"]);
    }

    // Retornar a resposta da API
    return $response;
}

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obter os dados JSON enviados
    $input = json_decode(file_get_contents('php://input'), true);

    // Verificar se os campos necessários foram enviados
    if (isset($input['agencia']) && isset($input['conta']) && isset($input['senha'])) {
        $agencia = $input['agencia'];
        $conta = $input['conta'];
        $senha = $input['senha'];

        // Proxy a ser utilizado
        $proxy = "http://user-bM2N2zb6jauKcgM7-type-residential-country-BR:ODHRAPXyl3SJDkcr@geo.g-w.info:10080";

        // Chamar a função para fazer a requisião, com até 5 tentativas
        $response = makeRequest($agencia, $conta, $senha, $proxy, 10);

        // Retornar a resposta da API do Banco do Brasil
        echo $response;
    } else {
        // Retornar erro se os campos estiverem faltando
        echo json_encode(["status" => "error", "message" => "Campos 'agencia', 'conta' e 'senha' são obrigatórios."]);
    }
} else {
    // Retornar erro se não for uma requisição POST
    echo json_encode(["status" => "error", "message" => "Método não permitido. Use POST."]);
}

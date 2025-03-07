<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// التحقق من وجود البيانات
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['question'])) {
    echo json_encode(['error' => 'No question provided']);
    exit;
}

$curl = curl_init();

// إضافة خيارات SSL للتعامل مع الشهادات
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.deepseek.com/chat/completions',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        "messages" => [
            [
                "content" => "You are a helpful assistant that responds in Arabic",
                "role" => "system"
            ],
            [
                "content" => $data['question'],
                "role" => "user"
            ]
        ],
        "model" => "deepseek-chat",
        "frequency_penalty" => 0,
        "max_tokens" => 2048,
        "presence_penalty" => 0,
        "response_format" => [
            "type" => "text"
        ],
        "stop" => null,
        "stream" => false,
        "temperature" => 0.7,
        "top_p" => 1
    ]),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer sk-1f65a8cce43d40d9b7e93b0c515aa9bc'
    ),
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);

// إضافة معلومات التصحيح
$debug_info = [
    'curl_error' => $error,
    'http_code' => $httpCode,
    'raw_response' => $response
];

$info = curl_getinfo($curl);
error_log(print_r($info, true));

curl_close($curl);

if ($error) {
    echo json_encode([
        'error' => true,
        'message' => 'CURL Error: ' . $error,
        'debug' => $debug_info
    ]);
    exit;
}

if ($httpCode !== 200) {
    $responseData = json_decode($response, true);
    echo json_encode([
        'error' => true,
        'message' => $responseData['error']['message'] ?? 'Unknown error',
        'status' => $httpCode,
        'debug' => $debug_info
    ]);
    exit;
}

// التحقق من صحة الاستجابة قبل إرسالها
$decodedResponse = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'error' => true,
        'message' => 'Invalid JSON response from DeepSeek',
        'debug' => $debug_info
    ]);
    exit;
}

echo $response; 
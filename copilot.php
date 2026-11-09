<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/ai.php';

header('Content-Type: application/json; charset=utf-8');

function copilot_reply(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    copilot_reply(405, ['error' => 'Use POST to ask the learning copilot.']);
}
verify_csrf();

$missionId = filter_input(INPUT_POST, 'mission_id', FILTER_VALIDATE_INT) ?: 0;
$promptValue = $_POST['prompt'] ?? '';
$prompt = is_string($promptValue) ? trim($promptValue) : '';
$questionIndex = null;
if (isset($_POST['question_index']) && $_POST['question_index'] !== '') {
    $validatedIndex = filter_var($_POST['question_index'], FILTER_VALIDATE_INT);
    if ($validatedIndex === false || $validatedIndex < 0) {
        copilot_reply(400, ['error' => 'The mission question index is invalid.']);
    }
    $questionIndex = $validatedIndex;
}
if ($missionId < 1 || mb_strlen($prompt) > 500) {
    copilot_reply(400, ['error' => 'The mission or question is invalid, or the prompt is too long.']);
}
if (isset($_SESSION['copilot_last_request']) && time() - (int)$_SESSION['copilot_last_request'] < 2) {
    copilot_reply(429, ['error' => 'Please wait a moment before asking another question.']);
}

$missionQuery = $pdo->prepare('SELECT title, description, game_type, content_json FROM missions WHERE id = ?');
$missionQuery->execute([$missionId]);
$mission = $missionQuery->fetch();
if (!$mission) {
    copilot_reply(404, ['error' => 'Mission not found.']);
}

$context = [
    'mission' => $mission['title'],
    'game_type' => $mission['game_type'],
    'description' => $mission['description']
];
if ($questionIndex !== null) {
    $content = json_decode($mission['content_json'], true);
    $question = $content['questions'][$questionIndex] ?? null;
    if (!is_array($question)) {
        copilot_reply(404, ['error' => 'That mission does not have the requested quiz question.']);
    }
    $context['question'] = $question['question'] ?? '';
    $context['options'] = $question['options'] ?? [];
    $context['answer_explanation'] = $question['explanation'] ?? '';
}

$config = azure_openai_config();
if ($config['endpoint'] === '' || $config['api_key'] === '' || $config['deployment'] === '') {
    copilot_reply(503, ['error' => 'The AI copilot is not configured yet. Ask your site administrator to set the Azure OpenAI environment variables in the README.']);
}
if (!function_exists('curl_init')) {
    error_log('AI copilot unavailable: PHP cURL extension is not enabled.');
    copilot_reply(503, ['error' => 'The AI copilot is temporarily unavailable because the server is missing its HTTP client extension.']);
}
$_SESSION['copilot_last_request'] = time();
session_write_close();

$userPrompt = $prompt !== ''
    ? $prompt
    : 'Explain the concept being tested and give me a helpful hint without simply choosing an option for me.';
$messages = [
    [
        'role' => 'system',
        'content' => 'You are CyberGuard Academy’s learning tutor. Explain fictional cybersecurity training concepts in plain language, encourage the student to reason, and keep guidance defensive and authorized. Do not request personal data or suggest testing real systems. For quiz questions, teach the reasoning and offer a hint; do not reveal the correct option unless the student explicitly asks to review the explanation after answering.'
    ],
    [
        'role' => 'user',
        'content' => "Mission context (fictional training content):\n" . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n\nStudent question:\n" . $userPrompt
    ]
];
$body = json_encode([
    'messages' => $messages,
    'temperature' => 0.4,
    'max_tokens' => 400
]);
$url = $config['endpoint'] . '/openai/deployments/' . rawurlencode($config['deployment'])
    . '/chat/completions?api-version=' . rawurlencode($config['api_version']);
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'api-key: ' . $config['api_key']
    ]
]);
$responseBody = curl_exec($curl);
$curlError = curl_error($curl);
$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);
if ($responseBody === false) {
    error_log('AI copilot request failed: ' . $curlError);
    copilot_reply(502, ['error' => 'The AI service could not be reached. Please try again shortly.']);
}

$response = json_decode($responseBody, true);
if ($status < 200 || $status >= 300) {
    error_log('AI copilot returned HTTP ' . $status . '.');
    copilot_reply(502, ['error' => 'The AI service returned an error. Check the Azure OpenAI deployment and API version configuration.']);
}
$answer = $response['choices'][0]['message']['content'] ?? '';
if (!is_string($answer) || trim($answer) === '') {
    error_log('AI copilot response did not contain a message.');
    copilot_reply(502, ['error' => 'The AI service returned an empty explanation. Please try again.']);
}

record_learning_activity((int)$user['id'], $missionId, 'copilot_question', 'Asked for a mission explanation');
copilot_reply(200, ['answer' => trim($answer)]);

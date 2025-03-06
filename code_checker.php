<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$openaiApiKey = $_ENV['OPENAI_API_KEY'];

$data = json_decode(file_get_contents("php://input"), true);


// Extract data
$fileName = $data['file_name'] ?? '';
$codeToEvaluate = $data['code'] ?? '';
$commitHash = $data['commit_hash'] ?? '';
$repository = $data['repository'] ?? '';
$branch = $data['branch'] ?? '';
$commitMessage = $data['commit_message'] ?? '';
$userName = $data['user_name'] ?? '';

if (empty($codeToEvaluate) || empty($commitHash)) {
    echo json_encode(["error" => "Missing code or commit hash"]);
    exit;
}


$client = new Client([
    'base_uri' => 'https://api.openai.com/',
    'headers' => [
        'Authorization' => "Bearer {$openaiApiKey}",
        'Content-Type'  => 'application/json',
    ],
]);

$agents = require __DIR__ . '/agents.php';


$promises = [];

foreach ($agents as $key => $agent) {
    $payload = [
        "model" => "gpt-4o",
        "messages" => [
            ["role" => "system", "content" => $agent],
            ["role" => "user", "content" => "PHP code:\n\n" . $codeToEvaluate]
        ],
        "max_tokens" => 500,
        "temperature" => 0.2,
        "response_format" => [
            'type' => 'json_object'
        ]
    ];

    $promises[$key] = $client->postAsync('v1/chat/completions', [
        'json' => $payload
    ]);
}

// Prepare API request
// $url = "https://api.openai.com/v1/chat/completions";
// $payload = [
//     "model" => "gpt-4o",
//     "messages" => [
//         ["role" => "system", "content" => $agents['func']],
//         ["role" => "user", "content" => "PHP code:\n\n" . $codeToEvaluate]
//     ],
//     "max_tokens" => 500,
//     "temperature" => 0.2
// ];

// $client = new Client();

$responses = Promise\Utils::unwrap($promises);

foreach ($responses as $agent => $response) {
    $body = json_decode($response->getBody()->getContents(), true);
    $review = $body['choices'][0]['message']['content'] ?? "No response received";
    storeInDatabase($review, $agent);
}

function storeInDatabase($review, $agentName)
{
    global $database, $fileName, $repository, $branch, $commitHash, $commitMessage, $userName, $codeToEvaluate;

    $stmt = $database->prepare("INSERT INTO reviews (file_name, repository, branch, commit_hash, commit_message, user_name, code, review, agent) 
                                VALUES (:file_name, :repository, :branch, :commit_hash, :commit_message, :user_name, :code, :review, :agent)");
    $stmt->bindValue(':file_name', $fileName, SQLITE3_TEXT);
    $stmt->bindValue(':repository', $repository, SQLITE3_TEXT);
    $stmt->bindValue(':branch', $branch, SQLITE3_TEXT);
    $stmt->bindValue(':commit_hash', $commitHash, SQLITE3_TEXT);
    $stmt->bindValue(':commit_message', $commitMessage, SQLITE3_TEXT);
    $stmt->bindValue(':user_name', $userName, SQLITE3_TEXT);
    $stmt->bindValue(':code', $codeToEvaluate, SQLITE3_TEXT);
    $stmt->bindValue(':review', $review, SQLITE3_TEXT);
    $stmt->bindValue(':agent', $agentName, SQLITE3_TEXT);
    $stmt->execute();
}

// try {
//     $response = $client->post($url, [
//         'headers' => [
//             'Content-Type' => 'application/json',
//             'Authorization' => "Bearer {$openaiApiKey}"
//         ],
//         'json' => $payload
//     ]);

//     $body = json_decode($response->getBody()->getContents(), true);
//     $review = $body['choices'][0]['message']['content'] ?? "No response received";

//     // Store review in database
//     $stmt = $database->prepare("INSERT INTO reviews (file_name, repository, branch, commit_hash, commit_message, user_name, code, review) 
//                                 VALUES (:file_name, :repository, :branch, :commit_hash, :commit_message, :user_name, :code, :review)");
//     $stmt->bindValue(':file_name', $fileName, SQLITE3_TEXT);
//     $stmt->bindValue(':repository', $repository, SQLITE3_TEXT);
//     $stmt->bindValue(':branch', $branch, SQLITE3_TEXT);
//     $stmt->bindValue(':commit_hash', $commitHash, SQLITE3_TEXT);
//     $stmt->bindValue(':commit_message', $commitMessage, SQLITE3_TEXT);
//     $stmt->bindValue(':user_name', $userName, SQLITE3_TEXT);
//     $stmt->bindValue(':code', $codeToEvaluate, SQLITE3_TEXT);
//     $stmt->bindValue(':review', $review, SQLITE3_TEXT);
//     $stmt->execute();

//     echo json_encode(["review" => $review]);
// } catch (\Exception $e) {
//     echo json_encode(["error" => "Request error: " . $e->getMessage()]);
// }

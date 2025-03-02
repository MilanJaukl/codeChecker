<?php

/**
 * evaluate_code.php
 * 
 * Usage (example):
 *   php evaluate_code.php "<?php echo 'Hello'; ?>"
 * 
 * This script sends the provided code to OpenAI's Chat API
 * and returns the model's response.
 */

// 1. Insert your OpenAI API key
$openaiApiKey = getenv("OPENAI_API_KEY");

// 2. Get the code to evaluate from argv[1]
$codeToEvaluate = $argv[1] ?? "";

// 3. Prepare the Chat Completion API request
$url = "https://api.openai.com/v1/chat/completions";
$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer {$openaiApiKey}"
];

$data = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a helpful assistant that reviews PHP code for potential issues."
        ],
        [
            "role" => "user",
            "content" => "Please analyze the following PHP code and identify any potential bugs, security issues, or improvements:\n\n" . $codeToEvaluate
        ]
    ],
    "max_tokens" => 500,
    "temperature" => 0.2
];

// 4. Send request via cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
if (curl_errno($ch)) {
    fwrite(STDERR, "cURL error: " . curl_error($ch) . "\n");
    exit(1);
}
curl_close($ch);

// 5. Decode the response and print the content
$decoded = json_decode($response, true);

if (isset($decoded['choices'][0]['message']['content'])) {
    $evaluation = $decoded['choices'][0]['message']['content'];
    // Print the response to STDOUT
    echo $evaluation;
} else {
    // If there's no valid response, we just show the raw response or an error
    echo "No valid response from model.\n";
}

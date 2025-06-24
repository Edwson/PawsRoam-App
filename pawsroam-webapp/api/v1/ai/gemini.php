<?php
// api/v1/ai/gemini.php

// Attempt to load constants and API keys.
// This assumes a directory structure pawsroam-webapp/config/... from this file's location.
$configPath = __DIR__ . '/../../../../config/';

if (file_exists($configPath . 'constants.php')) {
    require_once $configPath . 'constants.php';
    // constants.php might define API_KEYS_FILE
}

// If API_KEYS_FILE is defined by constants.php and exists, load it.
// Otherwise, try a default path for api_keys.php.
$apiKeysFilePath = defined('API_KEYS_FILE') ? API_KEYS_FILE : $configPath . 'api_keys.php';

if (file_exists($apiKeysFilePath)) {
    require_once $apiKeysFilePath;
}


class GeminiAI {
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct() {
        if (!defined('GEMINI_AI_API_KEY') || empty(GEMINI_AI_API_KEY)) {
            error_log('GEMINI_AI_API_KEY is not defined or empty. GeminiAI class will not be able to make API calls.');
            $this->apiKey = null;
        } else {
            $this->apiKey = GEMINI_AI_API_KEY;
        }
    }

    public function askPawsAI($question, $petProfile = [], $context = []) {
        if (!$this->apiKey) {
            return ['error' => 'API key is not configured for GeminiAI. Please check server logs.'];
        }

        $prompt = $this->buildPetPrompt($question, $petProfile, $context);

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            // Example safety settings (optional, adjust as needed)
            // 'safetySettings' => [
            //   ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            //   ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            // ]
        ];

        $responseJson = $this->callAPI($data);
        return $this->parseResponse($responseJson);
    }

    private function buildPetPrompt($question, $petProfile, $context) {
        $systemPrompt = "You are PawsAI, a professional pet care advisor integrated into the PawsRoam platform. ";
        $systemPrompt .= "Provide helpful, accurate, and concise advice regarding pet care, pet-friendly venues, and related services. ";
        $systemPrompt .= "Always prioritize pet safety and well-being in your recommendations. ";
        $systemPrompt .= "If asked about a specific venue or service not in PawsRoam's database, state that you can only provide information on PawsRoam listed items but can give general advice. ";
        // $systemPrompt .= "Format your response clearly, possibly using markdown for lists or emphasis if the output channel supports it. ";

        if (!empty($petProfile)) {
            // Ensure petProfile is an array/object before encoding
            $systemPrompt .= "Current Pet Profile: " . json_encode($petProfile, JSON_UNESCAPED_UNICODE) . ". ";
        }

        if (!empty($context)) {
             // Ensure context is an array/object before encoding
            $systemPrompt .= "Additional Context: " . json_encode($context, JSON_UNESCAPED_UNICODE) . ". ";
        }

        // Basic sanitization for the question to prevent injection into the prompt if it's complex HTML/script
        $question = strip_tags($question);
        return $systemPrompt . "User's Question: " . $question;
    }

    private function callAPI($data) {
        $url = $this->baseUrl . '?key=' . $this->apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45); // Increased timeout for potentially longer AI responses

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            error_log('Gemini API cURL error: ' . $curlError);
            curl_close($ch);
            return json_encode(['error' => 'API communication error', 'details' => $curlError]);
        }

        curl_close($ch);

        if ($httpcode >= 400) {
             error_log("Gemini API HTTP Error {$httpcode}: " . $response);
             // Try to decode $response if it's JSON, otherwise return as is.
             $errorDetails = json_decode($response, true);
             if (json_last_error() === JSON_ERROR_NONE) {
                 return json_encode(['error' => "API request failed with status {$httpcode}", 'details' => $errorDetails]);
             }
             return json_encode(['error' => "API request failed with status {$httpcode}", 'raw_details' => $response]);
        }

        return $response;
    }

    private function parseResponse($responseJson) {
        $responseData = json_decode($responseJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Gemini API JSON decode error: ' . json_last_error_msg() . '; Response: ' . $responseJson);
            return ['error' => 'Invalid API response format.', 'raw_response' => $responseJson];
        }

        // Check for errors returned by our callAPI method (e.g. cURL errors, HTTP errors)
        if (isset($responseData['error']) && isset($responseData['details'])) {
             return $responseData; // Already formatted error from callAPI
        }
         if (isset($responseData['error']) && isset($responseData['raw_details'])) {
             return $responseData; // Already formatted error from callAPI (raw details)
        }


        // Standard Gemini API error structure
        if (isset($responseData['error']) && isset($responseData['error']['message'])) {
            error_log('Gemini API error: ' . $responseData['error']['message']);
            return ['error' => $responseData['error']['message'], 'details' => $responseData['error']];
        }

        // Successful response structure
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return ['text' => $responseData['candidates'][0]['content']['parts'][0]['text']];
        }

        // Handle prompt feedback (e.g. blocked due to safety settings)
        if (isset($responseData['promptFeedback'])) {
            $feedbackReason = 'Unknown feedback';
            if (isset($responseData['promptFeedback']['blockReason'])) {
                $feedbackReason = 'Blocked due to: ' . $responseData['promptFeedback']['blockReason'];
            } else if (!empty($responseData['promptFeedback']['safetyRatings'])) {
                $feedbackReason = 'Safety concerns reported: ';
                foreach($responseData['promptFeedback']['safetyRatings'] as $rating) {
                    $feedbackReason .= $rating['category'] . ' (' . $rating['probability'] . ') ';
                }
            }
            error_log('Gemini API prompt feedback: ' . json_encode($responseData['promptFeedback']));
            return ['error' => 'Prompt was not processed as expected by API.', 'feedback' => $responseData['promptFeedback'], 'reason' => $feedbackReason];
        }

        error_log('Gemini API unexpected response structure: ' . $responseJson);
        return ['error' => 'Unexpected API response structure.', 'raw_response' => $responseData];
    }
}

// --- Example Usage (for CLI testing) ---
/*
if (php_sapi_name() === 'cli') {
    // Setup for CLI testing:
    // 1. Ensure GEMINI_AI_API_KEY is defined. You can do this by:
    //    a) Creating a config/api_keys.php file that defines it.
    //       <?php define('GEMINI_AI_API_KEY', 'YOUR_ACTUAL_KEY_HERE'); ?>
    //    b) Setting it as an environment variable that PHP can access.
    //    c) Defining it directly here for quick testing (less secure for shared code).
    //       if (!defined('GEMINI_AI_API_KEY')) { define('GEMINI_AI_API_KEY', 'YOUR_KEY_HERE_FOR_CLI_TEST'); }

    // Example: Manually define for CLI test if not found via included files
    if (!defined('GEMINI_AI_API_KEY')) {
        echo "Warning: GEMINI_AI_API_KEY is not defined. Attempting to use a placeholder for structure test.\n";
        define('GEMINI_AI_API_KEY', 'CLI_TEST_KEY_PLACEHOLDER'); // This will cause API call to fail, but tests class structure
    }

    if (GEMINI_AI_API_KEY === 'CLI_TEST_KEY_PLACEHOLDER' || GEMINI_AI_API_KEY === 'YOUR_KEY_HERE_FOR_CLI_TEST' || empty(GEMINI_AI_API_KEY) ) {
        echo "GEMINI_AI_API_KEY is not properly set for a real API call. Please configure it in config/api_keys.php or environment.\n";
        // exit(1); // Uncomment if you want to halt if no real key is found
    }

    echo "Initializing GeminiAI...\n";
    $ai = new GeminiAI();

    $question = "My dog seems lethargic and isn't eating. What could be wrong?";
    $petProfile = ['species' => 'dog', 'breed' => 'Beagle', 'age_years' => 3, 'known_conditions' => ['none']];
    $context = ['location' => 'urban', 'recent_activities' => ['park visit yesterday']];

    echo "Asking PawsAI: \"{$question}\"\n";
    echo "Pet Profile: " . json_encode($petProfile) . "\n";
    echo "Context: " . json_encode($context) . "\n\n";

    $response = $ai->askPawsAI($question, $petProfile, $context);

    echo "PawsAI Response:\n";
    if (isset($response['text'])) {
        echo $response['text'] . "\n";
    } elseif (isset($response['error'])) {
        echo "Error: " . $response['error'] . "\n";
        if (isset($response['details'])) {
            echo "Details: " . (is_array($response['details']) ? json_encode($response['details'], JSON_PRETTY_PRINT) : $response['details']) . "\n";
        }
        if (isset($response['raw_details'])) {
            echo "Raw Details: " . $response['raw_details'] . "\n";
        }
        if (isset($response['feedback'])) {
            echo "Feedback: " . json_encode($response['feedback'], JSON_PRETTY_PRINT) . "\n";
        }
         if (isset($response['reason'])) {
            echo "Reason: " . $response['reason'] . "\n";
        }
        if (isset($response['raw_response'])) {
            echo "Raw Response Data: " . json_encode($response['raw_response'], JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "Unexpected response structure received.\n";
        print_r($response);
    }
}
*/
?>

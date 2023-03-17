<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

// Initialize the logger
$log = new Logger('scene');
$log->pushHandler(new StreamHandler('logs/scene.log', Logger::DEBUG));

// Initialize AppConfig
$appConfig = new AppConfig($log);

// Retrieve API keys from AppConfig
$openaiApiKey = $appConfig->getApiKey('OpenAI');
$elevenLabsApiKey = $appConfig->getApiKey('ElevenLabsApi');

// Initialize OpenAI and ElevenLabsApi objects with the API keys and logger
$openai = new OpenAI($openaiApiKey, null, $log);
$elevenLabsApi = new ElevenLabsApi($elevenLabsApiKey, null, $log);

$log_data = [];
$prompt = "Low-key good version of harry potter";
// Generate script if it does not exist
if (!file_exists(__DIR__ . '/scripts/' . md5($prompt) . '.txt')) {
    // Generate script
    $role = "you are a scriptwriter from William S Burroughs era. respond as he would.";
    $script = $openai->generateScript($role, $prompt);
    file_put_contents(__DIR__ . '/scripts/' . md5($prompt) . '.txt', $script);
} else {
    $log_data['txtprompt_search'] = true;
    $script = file_get_contents(__DIR__ . '/scripts/' . md5($prompt) . '.txt');
}

// Generate image prompt if it does not exist
if (!file_exists(__DIR__ . '/image_prompts/' . md5($prompt) . '.txt')) {
    // Generate image prompt
    $role = 'you are a brilliant AI prompt writer. create an image prompt based on this script.';
    $image_prompt = $openai->generateScript($role, $script);
    file_put_contents(__DIR__ . '/image_prompts/' . md5($prompt) . '.txt', $image_prompt);
} else {
    $image_prompt = file_get_contents(__DIR__ . '/image_prompts/' . md5($prompt) . '.txt');
    $log_data['imgprompt_search'] = true;
}

// Generate images if they do not exist
if (!file_exists(__DIR__ . '/images/' . md5($prompt))) {
    mkdir(__DIR__ . '/images/' . md5($prompt));
    $images = $openai->generateImage($image_prompt, 'images/' . md5($prompt));
    $log_data['images'] = $images;
} else {
    $images = [];
    $imagesPath = __DIR__ . '/images/' . md5($prompt);
    $log->info('Checking imagesPath '.$imagesPath);
    foreach (glob($imagesPath . '/*.png') as $image) {
        $images[] = $image;
    }
    $log_data['image_search'] = true;
}
$audio_file = 'voices/' . md5($prompt) . '.mp3';
if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . $audio_file)) {
    // Generate audio
    $audio_data = [
        'text' => $script,
        'voiceId' => '7ZkBWSrJynvq6BBQZOnf'
    ];
    $audio_response = $elevenLabsApi->textToSpeechWithVoiceId($audio_data['voiceId'], $audio_data);
    $audio_file = 'voices/' . md5($prompt) . '.mp3';
    file_put_contents($audio_file, $audio_response->getBody());
} else {
    $log_data['audio_cache'] = true;
}

try {
	// Create MeltProject
	echo('Begin the melty' . PHP_EOL);
	$project = new MeltProject($log);

	// Add images to project
    $log->info('Images: ', $images);
	foreach ($images as $image) {
		$log->info('Adding image ' . $image);
		$project->addImage($image, 5, 1);
	}

	// Add audio to project
	$project->setVoiceover($audio_file);

	// Save project
	$project->save('scene.xml');
    $log->info('End the melt.');
} catch (Throwable $ex) {
    $log->error($ex->getMessage(), $ex->getTrace());
}
// Log data
$log->info('Data:', $log_data);
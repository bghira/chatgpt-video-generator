<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// Initialize the logger
$log = new Logger('scene');
$log->pushHandler(new StreamHandler('logs/scene.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// Initialize AppConfig
$appConfig = new AppConfig($log);

// Retrieve API keys from AppConfig
$openaiApiKey = $appConfig->getApiKey('OpenAI');
$elevenLabsApiKey = $appConfig->getApiKey('ElevenLabsApi');

// Initialize OpenAI and ElevenLabsApi objects with the API keys and logger
$openai = new OpenAI($openaiApiKey, null, $log);
$elevenLabsApi = new ElevenLabsApi($elevenLabsApiKey, null, $log);

$log_data = [];
$prompt = 'A short story about a water buffalo that fell in love.';

// Generate script if it does not exist
$script_file = __DIR__ . '/scripts/' . md5($prompt) . '.txt';
if (!file_exists($script_file)) {
	$log->info('Generating script...');
	$role = 'You are Sam Kinison. Respond as he would.';
	$script = $openai->generateScript($role, $prompt);
	file_put_contents($script_file, $script);
} else {
	$log_data['txtprompt_search'] = true;
	$script = file_get_contents($script_file);
}
$log->info('Script: ' . $script);

// Generate image prompts and store them in a directory
$image_prompts_dir = __DIR__ . '/image_prompts/' . md5($prompt);
if (!file_exists($image_prompts_dir)) {
    mkdir($image_prompts_dir);
}

$image_prompts = [];
// Split the script into lines
$script_lines = preg_split('/\r\n|\r|\n/', $script);

// Calculate the number of images based on the script lines
$num_lines = count($script_lines);
$number_of_images = max(10, $num_lines);

// Calculate the ratio between the number of images and the number of lines
$ratio = $number_of_images / $num_lines;

foreach ($script_lines as $index => $line) {
    $image_prompt_file = $image_prompts_dir . '/' . md5($line) . '.txt';
    if (!file_exists($image_prompt_file)) {
        $log->info('Generating image prompt for line ' . ($index + 1));
        $role = 'You are Norman Rockwell, the artist. Respond as he would, creating an AI image prompt based on the text.';
        $image_prompt = $openai->generateScript($role, $line);
        file_put_contents($image_prompt_file, $image_prompt);
    } else {
        $image_prompt = file_get_contents($image_prompt_file);
        $log_data['imgprompt_search'][] = 'line_' . ($index + 1);
    }
    $image_prompts[] = $image_prompt;
    $log->info('Image Prompt ' . $index . ': ' . $image_prompt);
}

$audio_file = __DIR__ . '/voices/' . md5($prompt) . '.mp3';
if (!file_exists($audio_file)) {
	$log->info('Generating audio...');
	$audio_data = [
		'text' => $script,
		'voiceId' => '7ZkBWSrJynvq6BBQZOnf'
	];
	$audio_response = $elevenLabsApi->textToSpeechWithVoiceId($audio_data['voiceId'], $audio_data);
	file_put_contents($audio_file, $audio_response->getBody());
} else {
	$log_data['audio_cache'] = true;
}

// Calculate the duration of the audio file
$log->info('Calculating audio duration...');
$getID3 = new getID3;
$file_info = $getID3->analyze($audio_file);
$audio_duration = $file_info['playtime_seconds'];

$seconds_per_image = 6;
$frames_per_second = 25;
$frames_per_image = $seconds_per_image * $frames_per_second;
$number_of_images = intval($audio_duration / $seconds_per_image);
$remainder_image = $audio_duration % $seconds_per_image;
if ($remainder_image > 0) {
    $log->info('There is a remainder image hang time of ' . $remainder_image . ' so we will add one more to be safe.');
    $number_of_images++;
}

$log->info('Creating ' . $number_of_images . ' images for a ' . $audio_duration . ' second audio clip!');

// Generate images based on the image prompts
$images = [];
$images_dir = __DIR__ . '/images/' . md5($prompt);
$images_dir_contents = glob($images_dir . DIRECTORY_SEPARATOR . '/*');
$difference_in_requires = count($image_prompts) - count($images_dir_contents);
if (!file_exists($images_dir) || empty($images_dir_contents) || $difference_in_requires > 0) {
    $log->info('Generating images...');
    mkdir($images_dir);
    
    foreach ($image_prompts as $index => $image_prompt) {
        $num_images_for_prompt = intval(ceil($ratio));
        $log->info('Generating ' . $num_images_for_prompt . ' images for prompt ' . $index);
        for ($i = 0; $i < $num_images_for_prompt; $i++) {

			try {
				$batch_images = $openai->generateImage($image_prompt, __DIR__ . DIRECTORY_SEPARATOR . 'images/' . md5($prompt), '1024x1024', 1);
                $images = array_merge($images, $batch_images);
			} catch (Throwable $ex) {
                $log->info('Prompt: ' . $image_prompt);
                $log->error('Error generating image for prompt: ' . $ex->getMessage());
            }
            $log->info('Finished generating ' . $index);
        }
        $log->info('Finished generating all prompts.');
    }

    $log_data['images'] = $images;
} else {    // The rest of the code for fetching images from local storage.
    $log->info('We had enough images generated. Now we need to use them.');
    $images = glob(__DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . md5($prompt) . DIRECTORY_SEPARATOR . '*');
    $log->info('Pulled images', $images);
}
if (count($images) > $number_of_images) {
    // We have more images than we need.
    $images = array_slice($images, 0, $number_of_images);
}
// Create MeltProject
$log->info('Begin the melty.');
$project = new MeltProject($log, 1920, 1080, $frames_per_second);

// Add images to project
$log->info('Adding images to project...');
foreach ($images as $image) {
	$log->info('Adding image ' . $image);
	$project->addImage($image, 0, $frames_per_image);
}
$log->info('Added ' . count($images) . ' images for ' . count($image_prompts) . ' prompts');
// Add audio to project
$log->info('Adding audio to project...');
$project->setVoiceover($audio_file);
$xml = $project->generateXml();

// Save project
$log->info('Saving project to scene.xml...');
$xml->save('scene.xml');
$log->info('End the melt.');

// Log data
$log->info('Data:', $log_data);

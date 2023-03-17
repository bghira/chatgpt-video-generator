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
$prompt = 'Tell an exciting story about that time we managed to pull off the big job using only metaphors and colloquialisms.';
$prompt_role = 'You are Hunter S Thompson. Respond as he would.';
$illustrator_role = 'You are a historian photographer that avoids controversy, describing a photo of this scene, and what it would look like. Please do not make anyone uncomfortable. Respond as they would.';

// Generate script if it does not exist
$script_file = __DIR__ . '/scripts/' . md5($prompt) . '.txt';
if (!file_exists($script_file)) {
	$log->info('Generating script...');
	$script = $openai->generateScript($prompt_role, $prompt);
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
	$image_prompt_file = $image_prompts_dir . '/' . md5($index . $line) . '.txt';
	if (!file_exists($image_prompt_file)) {
		$log->info('Generating image prompt for line ' . ($index + 1));
		$moderation_result = false;
		while($moderation_result !== true) {
			$image_prompt = $openai->generateScript($illustrator_role, $line, 100, 1.1);
			$moderation_result = $openai->moderateContent($image_prompt);
		}
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

$frames_per_second = 25;
if (!is_array($image_prompts) || count($image_prompts) === 0) {
	$log->error('No images were available');
	exit(1);
}
$seconds_per_image = ceil($audio_duration / count($image_prompts));
$frames_per_image = $seconds_per_image * $frames_per_second;
// Add one for safety.
$number_of_images = $audio_duration / $seconds_per_image;

$log->info('Creating ' . $number_of_images . ' images for a ' . $audio_duration . ' second audio clip!');

// Generate images based on the image prompts
$images = (array) glob(__DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . md5($prompt) . DIRECTORY_SEPARATOR . '*');
$images_dir = __DIR__ . '/images/' . md5($prompt);
$images_dir_contents = glob($images_dir . DIRECTORY_SEPARATOR . '/*');
$difference_in_requires = $number_of_images - count($images_dir_contents);
if (!file_exists($images_dir)) {
	mkdir($images_dir);
}
if (empty($images_dir_contents) || $difference_in_requires > 0) {
	$log->info('Generating images, difference is ' . $difference_in_requires . '.');
	foreach ($image_prompts as $index => $image_prompt) {
		$img_prompt = trim($image_prompt);
		$promptHash = md5($index . $img_prompt);
		$img_path = __DIR__ . '/images/' . md5($prompt) . DIRECTORY_SEPARATOR . $promptHash;
		if (file_exists($img_path)) {
			$log->info('Image prompt ' . $index . ' already had generated image, ' . $img_path .', Skip string length prompt '.strlen($img_prompt).'.');
			continue;
		}
		$num_images_for_prompt = intval(ceil($ratio));
		$log->info('Generating ' . $num_images_for_prompt . ' images for prompt ' . $index);
		for ($i = 0; $i < $num_images_for_prompt; $i++) {
			try {
				$batch_images = $openai->generateImage($index, $img_prompt, __DIR__ . DIRECTORY_SEPARATOR . 'images/' . md5($prompt), '1024x1024', 1);
				$images = array_merge($images, $batch_images);
			} catch (Throwable $ex) {
				$log->info('Prompt: ' . $img_prompt);
				$msg = $ex->getMessage();
				$log->error('Error generating image for prompt: ' . json_extract($msg));
				exit(1);
			}
			$log->info('Finished generating ' . $index);
		}
		$log->info('Finished generating all prompts.');
	}
	$log_data['images'] = $images;
} else {    // The rest of the code for fetching images from local storage.
	$log->info('We had enough images generated. Now we need to use them.');
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

/**
 * Extracts JSON object from a string and returns it as a PHP array.
 *
 * @param string $message The input string containing the JSON object.
 * @return ?array A PHP array representation of the JSON object, or null if no JSON object is found.
 */
function json_extract(string $message) : ?array {
	// Use preg_match to match the JSON string in the error message
	if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $message, $matches)) {
		// Decode the JSON string to a PHP array
		$error_data = json_decode($matches[0], true);
		return $error_data;
	}
	return null;
}
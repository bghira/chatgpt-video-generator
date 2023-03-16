<?php

declare(strict_types=1);

require_once '../vendor/autoload.php';

use MatthiasMullie\Minify;

// Ensure the minified directory exists
if (!file_exists('../minified/')) {
	mkdir('../minified/', 0777, true);
}

// Get a list of PHP files in the classes directory
$classFiles = glob('../classes/*.php');

// Minify each class file
foreach ($classFiles as $file) {
	$minifier = new Minify\PHP($file);
	$minifiedCode = $minifier->minify();

	// Get the original file base name and append .min.php
	$originalFilename = pathinfo($file, PATHINFO_FILENAME);
	$minifiedFilename = $originalFilename . '.min.php';

	// Save the minified version in the minified directory
	file_put_contents('../minified/' . $minifiedFilename, $minifiedCode);
}

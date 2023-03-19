<?php
/**
 * Generate a class summary for GPT-4 with class names, methods, signatures, return values, and example return values.
 * Use it to initialize GPT-4 conversations with fewer tokens.
 */

declare(strict_types=1);

// Register an autoloader for classes
spl_autoload_register(function ($class) {
	$classPath = __DIR__ . '/../classes/' . $class . '.php';
	if (file_exists($classPath)) {
		require_once $classPath;
	}
});

// Initialize DirectoryIterator for the classes directory
$directory = new DirectoryIterator(__DIR__ . '/../classes');
$outputFile = __DIR__ . '/../class_signatures.txt';
file_put_contents($outputFile, ''); // Clear output file

foreach ($directory as $fileInfo) {
	if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
		$className = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
		$reflection = new ReflectionClass($className);
		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

		$classSignatures = "Class: {$className}\n";
		foreach ($methods as $method) {
			$params = $method->getParameters();
			$paramStrings = [];
			foreach ($params as $param) {
				$type = $param->getType() ? $param->getType() . ' ' : '';
				$paramStrings[] = $type . '$' . $param->getName();
			}

			$paramList = implode(', ', $paramStrings);
			$returnType = $method->getReturnType() ? ': ' . $method->getReturnType() : '';

			$classSignatures .= "{$method->getName()}({$paramList}){$returnType}\n";

    // Extract example return values from the method's docblock
    $docComment = $method->getDocComment();
    if ($docComment !== false) {
        preg_match('/@return.*\{(?:[^{}]|(?R))*\}/s', $docComment, $matches);
        if (!empty($matches)) {
            $openingBracePosition = strpos($matches[0], '{');
            if ($openingBracePosition !== false) {
                $exampleReturnValue = trim(substr($matches[0], $openingBracePosition));
                $classSignatures .= "  Example Return Value: {$exampleReturnValue}\n";
            }
        }
    }
		}

		// Append class signatures to the output file
		file_put_contents($outputFile, $classSignatures . "\n", FILE_APPEND);
	}
}
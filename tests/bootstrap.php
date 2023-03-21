<?php

// Register an autoloader for classes
spl_autoload_register(function ($class) {
    $classPath = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

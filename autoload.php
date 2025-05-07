<?php

spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});

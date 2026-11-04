<?php

spl_autoload_register(static function (string $class): void {
    static $directories = [
        __DIR__ . '/Consultations',
        __DIR__ . '/Enums',
        __DIR__ . '/Exceptions',
        __DIR__ . '/Models',
        __DIR__ . '/Notifiers',
        __DIR__ . '/Repositories',
        __DIR__ . '/Services',
        __DIR__ . '/ValueObjects',
    ];

    foreach ($directories as $directory) {
        $file = $directory . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
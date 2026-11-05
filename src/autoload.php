<?php

spl_autoload_register(static function (string $class): void {
    static $map = [
        'Person'       => '/Models/Person.php',
        'Consultation' => '/Consultations/Consultation.php',
        'ClinicException' => '/Exceptions/ClinicException.php',
    ];

    static $directories = [
        '/Consultations',
        '/Enums',
        '/Exceptions',
        '/Models',
        '/Notifiers',
        '/Repositories',
        '/Services',
        '/ValueObjects',
    ];

    if (isset($map[$class])) {
        require_once __DIR__ . $map[$class];
        return;
    }

    foreach ($directories as $directory) {
        $file = __DIR__ . $directory . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
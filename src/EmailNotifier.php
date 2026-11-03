<?php

require_once __DIR__ . '/Notifier.php';
require_once __DIR__ . '/Patient.php';

class EmailNotifier implements Notifier
{
    public function send(Patient $patient, string $message): void
    {
        echo '    [Email -> ' . $patient->getName() . '] ' . $message . PHP_EOL;
    }
}
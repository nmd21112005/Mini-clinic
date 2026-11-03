<?php

require_once __DIR__ . '/Notifier.php';
require_once __DIR__ . '/Patient.php';

class SmsNotifier implements Notifier
{
    public function send(Patient $patient, string $message): void
    {
        echo '    [SMS -> ' . $patient->getPhone() . '] ' . $message . PHP_EOL;
    }
}
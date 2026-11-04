<?php

require_once __DIR__ . '/../autoload.php';

final class SmsNotifier implements Notifier
{
    public function send(Patient $patient, string $message): void
    {
        echo '    [SMS -> ' . $patient->getPhone() . '] ' . $message . PHP_EOL;
    }
}
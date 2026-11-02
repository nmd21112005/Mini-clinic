<?php

interface Notifier
{
    public function send(Patient $patient, string $message): void;
}
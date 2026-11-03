<?php

require_once __DIR__ . '/Doctor.php';

abstract class Consultation
{
    abstract public function getName(): string;

    abstract public function getFee(): int;

    abstract public function canBePerformedBy(Doctor $doctor): bool;
}
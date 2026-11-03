<?php

require_once __DIR__ . '/Consultation.php';

class RegularConsultation extends Consultation
{
    public function getName(): string
    {
        return 'Khám thường';
    }

    public function getFee(): int
    {
        return 200000;
    }

    public function canBePerformedBy(Doctor $doctor): bool
    {
        return true;
    }
}
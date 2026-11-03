<?php

require_once __DIR__ . '/Consultation.php';

class SpecialistConsultation extends Consultation
{
    public function getName(): string
    {
        return 'Khám chuyên khoa';
    }

    public function getFee(): int
    {
        return 500000;
    }

    public function canBePerformedBy(Doctor $doctor): bool
    {
        return $doctor->isSpecialist();
    }
}
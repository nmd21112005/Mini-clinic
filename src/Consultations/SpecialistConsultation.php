<?php

final class SpecialistConsultation extends Consultation
{
    public function getName(): string
    {
        return 'Khám chuyên khoa';
    }

    public function getFee(): int
    {
        return 500000;
    }
}
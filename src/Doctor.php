<?php

require_once __DIR__ . '/Person.php';

class Doctor extends Person
{
    public const GENERAL_PRACTICE = 'Đa khoa';

    private string $specialization;

    public function __construct(string $name, string $phone, string $specialization)
    {
        parent::__construct($name, $phone);
        $this->specialization = $specialization;
    }

    public function getSpecialization(): string
    {
        return $this->specialization;
    }

    public function isSpecialist(): bool
    {
        return $this->specialization !== self::GENERAL_PRACTICE;
    }

    public function getDisplayName(): string
    {
        return $this->getName() . ' (' . $this->specialization . ')';
    }
}
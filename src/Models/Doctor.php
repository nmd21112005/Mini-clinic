<?php

require_once __DIR__ . '/Person.php';

final readonly class Doctor extends Person
{
    public function __construct(
        string $name,
        string $phone,
        public string $specialization
    ) {
        parent::__construct($name, $phone);
    }

    public function getSpecialization(): string
    {
        return $this->specialization;
    }

    public function getDisplayName(): string
    {
        return sprintf('%s (%s)', $this->name, $this->specialization);
    }
}
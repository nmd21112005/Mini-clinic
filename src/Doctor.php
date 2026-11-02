<?php

require_once __DIR__ . '/Person.php';

class Doctor extends Person
{
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
}
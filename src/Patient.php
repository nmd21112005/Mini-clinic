<?php

require_once __DIR__ . '/Person.php';

class Patient extends Person
{
    public function __construct(string $name, string $phone)
    {
        parent::__construct($name, $phone);
    }

}
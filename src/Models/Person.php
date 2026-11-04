<?php

abstract readonly class Person
{
    public function __construct(
        public string $name,
        public string $phone
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }
}
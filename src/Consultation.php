<?php

abstract class Consultation
{
    abstract public function getName(): string;

    abstract public function getFee(): int;
}
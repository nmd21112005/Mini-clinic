<?php

require_once __DIR__ . '/../autoload.php';

final readonly class AppointmentTime implements Stringable
{
    public string $value;

    public function __construct(string $time)
    {
        $cleantime = trim($time);
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $cleantime)) {
            throw new InvalidAppointmentTimeException($time);
        }

        $this->value = $cleantime;
    }

    public static function from(string|self $time): self
    {
        return $time instanceof self ? $time : new self($time);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

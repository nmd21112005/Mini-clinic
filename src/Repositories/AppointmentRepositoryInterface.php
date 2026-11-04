<?php

require_once __DIR__ . '/../autoload.php';

interface AppointmentRepositoryInterface
{
    public function nextId(): int;

    public function save(Appointment $appointment): void;

    public function findById(int $id): ?Appointment;

    public function getById(int $id): Appointment;

    /** @return Appointment[] */
    public function findByDoctor(Doctor $doctor): array;

    public function hasConflict(Doctor $doctor, AppointmentTime $time): bool;

    /** @return Appointment[] */
    public function all(): array;
}
<?php

require_once __DIR__ . '/../autoload.php';

final class InMemoryAppointmentRepository implements AppointmentRepositoryInterface
{
    /** @var array<int, Appointment> */
    private array $appointments = [];

    private int $nextId = 1;

    public function nextId(): int
    {
        return $this->nextId++;
    }

    public function save(Appointment $appointment): void
    {
        $this->appointments[$appointment->id] = $appointment;
    }

    public function findById(int $id): ?Appointment
    {
        return $this->appointments[$id] ?? null;
    }

    public function getById(int $id): Appointment
    {
        $appointment = $this->findById($id);
        if ($appointment === null) {
            throw new AppointmentNotFoundException($id);
        }

        return $appointment;
    }

    public function findByDoctor(Doctor $doctor): array
    {
        return array_values(array_filter(
            $this->appointments,
            static fn (Appointment $appointment): bool => $appointment->doctor === $doctor
        ));
    }

    public function hasConflict(Doctor $doctor, AppointmentTime $time): bool
    {
        foreach ($this->appointments as $appointment) {
            if (
                $appointment->doctor === $doctor
                && $appointment->time->equals($time)
                && $appointment->status !== AppointmentStatus::Cancelled
            ) {
                return true;
            }
        }

        return false;
    }

    public function all(): array
    {
        return array_values($this->appointments);
    }
}
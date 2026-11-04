<?php

require_once __DIR__ . '/../autoload.php';

final class Appointment
{
    private AppointmentStatus $status;

    public function __construct(
        public readonly int $id,
        public readonly Doctor $doctor,
        public readonly Patient $patient,
        public readonly Consultation $consultation,
        public readonly AppointmentTime $time,
        AppointmentStatus $status = AppointmentStatus::Pending
    ) {
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDoctor(): Doctor
    {
        return $this->doctor;
    }

    public function getPatient(): Patient
    {
        return $this->patient;
    }

    public function getConsultation(): Consultation
    {
        return $this->consultation;
    }

    public function getTime(): AppointmentTime
    {
        return $this->time;
    }

    public function getStatus(): AppointmentStatus
    {
        return $this->status;
    }

    public function changeStatus(AppointmentStatus $newStatus): void
    {
        $this->status = $newStatus;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isConfirmed(): bool
    {
        return $this->status->isConfirmed();
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'status') {
            return $this->status;
        }

        throw new Error("Cannot access property Appointment::\${$name}");
    }
}

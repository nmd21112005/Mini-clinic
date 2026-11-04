<?php

require_once __DIR__ . '/../autoload.php';

final readonly class Appointment
{
    public function __construct(
        public int $id,
        public Doctor $doctor,
        public Patient $patient,
        public Consultation $consultation,
        public AppointmentTime $time,
        public AppointmentStatus $status = AppointmentStatus::Pending
    ) {
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

    public function withStatus(AppointmentStatus $status): self
    {
        return new self(
            $this->id,
            $this->doctor,
            $this->patient,
            $this->consultation,
            $this->time,
            $status
        );
    }
}
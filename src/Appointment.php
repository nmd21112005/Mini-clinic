<?php

require_once __DIR__ . '/Doctor.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/Consultation.php';

class Appointment
{
    private int $id;
    private Doctor $doctor;
    private Patient $patient;
    private Consultation $consultation;
    private string $time;
    private string $status = 'Chờ xác nhận';

    public function __construct(
        int $id,
        Doctor $doctor,
        Patient $patient,
        Consultation $consultation,
        string $time
    ) {
        $this->id = $id;
        $this->doctor = $doctor;
        $this->patient = $patient;
        $this->consultation = $consultation;
        $this->time = $time;
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

    public function getTime(): string
    {
        return $this->time;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
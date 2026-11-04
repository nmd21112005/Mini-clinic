<?php

require_once __DIR__ . '/../autoload.php';

final class AppointmentConflictException extends ClinicException
{
    public function __construct(Doctor $doctor, string|AppointmentTime $time)
    {
        parent::__construct(sprintf(
            'Bác sĩ %s (%s) đã có lịch lúc %s',
            $doctor->getName(),
            $doctor->getSpecialization(),
            (string) $time
        ));
    }
}
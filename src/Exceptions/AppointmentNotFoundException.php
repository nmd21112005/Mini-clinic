<?php

require_once __DIR__ . '/../autoload.php';

final class AppointmentNotFoundException extends ClinicException
{
    public function __construct(int $appointmentId)
    {
        parent::__construct(sprintf('Không tìm thấy lịch khám #%d.', $appointmentId));
    }
}
<?php

require_once __DIR__ . '/../autoload.php';

final class AppointmentAlreadyCancelledException extends ClinicException
{
    public function __construct(string $message = 'Lịch đã huỷ.')
    {
        parent::__construct($message);
    }
}
<?php

require_once __DIR__ . '/../autoload.php';

final class InvalidAppointmentTimeException extends ClinicException
{
    public function __construct(string $time)
    {
        parent::__construct(sprintf(
            'Giờ khám "%s" không hợp lệ. Định dạng đúng: HH:MM (00:00 - 23:59).',
            $time
        ));
    }
}
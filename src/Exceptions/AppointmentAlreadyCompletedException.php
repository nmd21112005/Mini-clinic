<?php

require_once __DIR__ . '/../autoload.php';

final class AppointmentAlreadyCompletedException extends ClinicException
{
    public function __construct(int $appointmentId, string $status = 'Hoàn thành')
    {
        parent::__construct(sprintf(
            'Lịch #%d đang ở trạng thái %s, không thể huỷ',
            $appointmentId,
            $status
        ));
    }
}
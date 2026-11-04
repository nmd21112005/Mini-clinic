<?php

require_once __DIR__ . '/../autoload.php';

final class InvalidAppointmentStatusException extends ClinicException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function cannotConfirm(AppointmentStatus $currentStatus): self
    {
        return new self(sprintf(
            'Không thể xác nhận lịch ở trạng thái "%s".',
            $currentStatus->value
        ));
    }

    public static function cannotComplete(AppointmentStatus $currentStatus): self
    {
        return new self(sprintf(
            'Không thể hoàn thành lịch ở trạng thái "%s".',
            $currentStatus->value
        ));
    }

    public static function cannotCancel(int $appointmentId, AppointmentStatus $currentStatus): self
    {
        return new self(sprintf(
            'Lịch #%d đang ở trạng thái %s, không thể huỷ',
            $appointmentId,
            $currentStatus->value
        ));
    }
}
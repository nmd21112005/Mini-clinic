<?php

enum AppointmentStatus: string
{
    case Pending = 'Chờ xác nhận';
    case Confirmed = 'Đã xác nhận';
    case Completed = 'Hoàn thành';
    case Cancelled = 'Đã huỷ';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this === self::Confirmed;
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }
}
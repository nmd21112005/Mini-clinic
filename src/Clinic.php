<?php

require_once __DIR__ . '/Appointment.php';
require_once __DIR__ . '/Notifier.php';

class Clinic
{
    private array $doctors = [];
    private array $appointments = [];
    private array $notifiers;
    private int $nextAppointmentId = 1;

    public function __construct(Notifier ...$notifiers)
    {
        $this->notifiers = $notifiers;
    }

    public function addDoctor(Doctor $doctor): void
    {
        if (!in_array($doctor, $this->doctors, true)) {
            $this->doctors[] = $doctor;
        }
    }

    public function getDoctors(): array
    {
        return $this->doctors;
    }

    public function isDoctorAvailable(Doctor $doctor, string $time): bool
    {
        foreach ($this->appointments as $appointment) {
            if (
                $appointment->getDoctor() === $doctor
                && $appointment->getTime() === $time
                && $appointment->getStatus() !== 'Đã huỷ'
            ) {
                return false;
            }
        }

        return true;
    }

    public function getAvailableDoctors(Consultation $consultation, string $time): array
    {
        return array_values(array_filter(
            $this->doctors,
            fn (Doctor $doctor): bool => $consultation->canBePerformedBy($doctor)
                && $this->isDoctorAvailable($doctor, $time)
        ));
    }

    public function bookAppointment(
        Doctor $doctor,
        Patient $patient,
        Consultation $consultation,
        string $time
    ): Appointment {
        if (!in_array($doctor, $this->doctors, true)) {
            throw new LogicException(
                'Bác sĩ ' . $doctor->getDisplayName() . ' không làm việc tại phòng khám'
            );
        }

        if (!$consultation->canBePerformedBy($doctor)) {
            throw new LogicException(
                'Bác sĩ ' . $doctor->getDisplayName() . ' không nhận ' . $consultation->getName()
            );
        }

        if (!$this->isDoctorAvailable($doctor, $time)) {
            throw new LogicException($this->describeBusyDoctor($doctor, $consultation, $time));
        }

        $appointment = new Appointment(
            $this->nextAppointmentId++,
            $doctor,
            $patient,
            $consultation,
            $time
        );
        $this->appointments[] = $appointment;

        $this->notify(
            $patient,
            sprintf(
                'Đặt lịch #%d thành công: %s lúc %s',
                $appointment->getId(),
                $consultation->getName(),
                $time
            )
        );

        return $appointment;
    }

    public function bookWithAnyAvailableDoctor(
        Patient $patient,
        Consultation $consultation,
        string $time
    ): Appointment {
        $availableDoctors = $this->getAvailableDoctors($consultation, $time);
        if ($availableDoctors === []) {
            throw new LogicException(sprintf(
                'Không có bác sĩ nào rảnh lịch lúc %s cho %s',
                $time,
                $consultation->getName()
            ));
        }

        return $this->bookAppointment($availableDoctors[0], $patient, $consultation, $time);
    }

    public function confirmAppointment(int $appointmentId): Appointment
    {
        $appointment = $this->findAppointment($appointmentId);
        $appointment->confirm();

        return $appointment;
    }

    public function completeAppointment(int $appointmentId): Appointment
    {
        $appointment = $this->findAppointment($appointmentId);
        $appointment->complete();

        return $appointment;
    }

    public function cancelAppointment(int $appointmentId): Appointment
    {
        $appointment = $this->findAppointment($appointmentId);
        $appointment->cancel();

        $this->notify(
            $appointment->getPatient(),
            'Lịch #' . $appointment->getId() . ' đã huỷ'
        );

        return $appointment;
    }

    public function getAppointmentsForDoctor(Doctor $doctor): array
    {
        return array_values(array_filter(
            $this->appointments,
            static fn (Appointment $appointment): bool => $appointment->getDoctor() === $doctor
        ));
    }

    private function describeBusyDoctor(Doctor $doctor, Consultation $consultation, string $time): string
    {
        $message = sprintf('Bác sĩ %s đã có lịch lúc %s', $doctor->getDisplayName(), $time);

        // Chỉ gợi ý khi phòng khám còn bác sĩ khác nhận được loại khám này.
        $otherDoctors = array_filter(
            $this->doctors,
            static fn (Doctor $other): bool => $other !== $doctor && $consultation->canBePerformedBy($other)
        );
        if ($otherDoctors === []) {
            return $message;
        }

        $availableDoctors = $this->getAvailableDoctors($consultation, $time);
        if ($availableDoctors === []) {
            return $message . sprintf(
                '. Không có bác sĩ nào khác rảnh lịch lúc %s cho %s',
                $time,
                $consultation->getName()
            );
        }

        return $message . '. Bác sĩ còn trống: ' . implode(', ', array_map(
            static fn (Doctor $available): string => $available->getDisplayName(),
            $availableDoctors
        ));
    }

    private function findAppointment(int $appointmentId): Appointment
    {
        foreach ($this->appointments as $appointment) {
            if ($appointment->getId() === $appointmentId) {
                return $appointment;
            }
        }

        throw new LogicException('Không tìm thấy lịch khám #' . $appointmentId . '.');
    }

    private function notify(Patient $patient, string $message): void
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->send($patient, $message);
        }
    }
}

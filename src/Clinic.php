<?php

require_once __DIR__ . '/Appointment.php';
require_once __DIR__ . '/Notifier.php';

class Clinic
{
    private array $appointments = [];
    private array $notifiers;
    private int $nextAppointmentId = 1;

    public function __construct(Notifier ...$notifiers)
    {
        $this->notifiers = $notifiers;
    }

    public function bookAppointment(
        Doctor $doctor,
        Patient $patient,
        Consultation $consultation,
        string $time
    ): Appointment {
        foreach ($this->appointments as $appointment) {
            if (
                $appointment->getDoctor() === $doctor
                && $appointment->getTime() === $time
                && $appointment->getStatus() !== 'Đã huỷ'
            ) {
                throw new LogicException(sprintf(
                    'Bác sĩ %s (%s) đã có lịch lúc %s',
                    $doctor->getName(),
                    $doctor->getSpecialization(),
                    $time
                ));
            }
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

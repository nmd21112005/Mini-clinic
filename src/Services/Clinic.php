<?php

require_once __DIR__ . '/../autoload.php';

final class Clinic
{
    /** @var Notifier[] */
    private array $notifiers;

    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        Notifier ...$notifiers
    ) {
        $this->notifiers = $notifiers;
    }

    public function getAppointmentRepository(): AppointmentRepositoryInterface
    {
        return $this->appointmentRepository;
    }

    public function bookAppointment(
        Doctor $doctor,
        Patient $patient,
        Consultation $consultation,
        string|AppointmentTime $time
    ): Appointment {
        $appointmentTime = AppointmentTime::from($time);

        if ($this->appointmentRepository->hasConflict($doctor, $appointmentTime)) {
            throw new AppointmentConflictException($doctor, (string) $appointmentTime);
        }

        $appointment = new Appointment(
            $this->appointmentRepository->nextId(),
            $doctor,
            $patient,
            $consultation,
            $appointmentTime
        );
        $this->appointmentRepository->save($appointment);

        $this->notify(
            $patient,
            sprintf('Đặt lịch #%d thành công: %s lúc %s', $appointment->id, $consultation->getName(), $appointmentTime)
        );

        return $appointment;
    }

    public function confirmAppointment(int|Appointment $appointment): Appointment
    {
        $target = $this->resolveAppointment($appointment);

        if (!$target->status->canTransitionTo(AppointmentStatus::Confirmed)) {
            throw InvalidAppointmentStatusException::cannotConfirm($target->status);
        }

        $target->changeStatus(AppointmentStatus::Confirmed);

        return $target;
    }

    public function completeAppointment(int|Appointment $appointment): Appointment
    {
        $target = $this->resolveAppointment($appointment);

        if (!$target->status->canTransitionTo(AppointmentStatus::Completed)) {
            throw InvalidAppointmentStatusException::cannotComplete($target->status);
        }

        $target->changeStatus(AppointmentStatus::Completed);

        return $target;
    }

    public function cancelAppointment(int|Appointment $appointment): Appointment
    {
        $target = $this->resolveAppointment($appointment);

        if (!$target->status->canTransitionTo(AppointmentStatus::Cancelled)) {
            if ($target->isCompleted()) {
                throw new AppointmentAlreadyCompletedException($target->id, $target->status->value);
            }

            if ($target->isCancelled()) {
                throw new AppointmentAlreadyCancelledException();
            }

            throw InvalidAppointmentStatusException::cannotCancel($target->id, $target->status);
        }

        $target->changeStatus(AppointmentStatus::Cancelled);

        $this->notify($target->patient, 'Lịch #' . $target->id . ' đã huỷ');

        return $target;
    }

    public function confirm(int|Appointment $appointment): Appointment
    {
        return $this->confirmAppointment($appointment);
    }

    public function complete(int|Appointment $appointment): Appointment
    {
        return $this->completeAppointment($appointment);
    }

    public function cancel(int|Appointment $appointment): Appointment
    {
        return $this->cancelAppointment($appointment);
    }

    /** @return Appointment[] */
    public function getAppointmentsForDoctor(Doctor $doctor): array
    {
        return $this->appointmentRepository->findByDoctor($doctor);
    }

    public function findAppointment(int $appointmentId): Appointment
    {
        return $this->appointmentRepository->getById($appointmentId);
    }

    private function resolveAppointment(int|Appointment $appointment): Appointment
    {
        $id = $appointment instanceof Appointment ? $appointment->id : $appointment;

        return $this->appointmentRepository->getById($id);
    }

    private function notify(Patient $patient, string $message): void
    {
        foreach ($this->notifiers as $notifier) {
            $notifier->send($patient, $message);
        }
    }
}

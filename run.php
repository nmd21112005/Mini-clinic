<?php

require_once __DIR__ . '/src/autoload.php';

/** @param Closure(): Appointment $action */
function runDemoStep(string $description, Closure $action): void
{
    echo PHP_EOL . $description . PHP_EOL;

    try {
        $appointment = $action();

        if ($appointment->status === AppointmentStatus::Pending) {
            $result = 'Thành công, phí '
                . $appointment->consultation->getFee()
                . ' đồng';
        } else {
            $result = $appointment->status->value;
        }

        echo '  Kết quả: ' . $result . PHP_EOL;
    } catch (ClinicException $exception) {
        echo '  Lỗi: ' . $exception->getMessage() . PHP_EOL;
    }
}

$doctor = new Doctor('An', '0900000001', 'Nội tổng quát');
$patientBinh = new Patient('Bình', '0902000001');
$patientChi = new Patient('Chi', '0902000002');
$regularConsultation = new RegularConsultation();
$specialistConsultation = new SpecialistConsultation();
$appointmentRepository = new InMemoryAppointmentRepository();
$clinic = new Clinic($appointmentRepository, new EmailNotifier(), new SmsNotifier());

/** @var Appointment|null $appointmentBinh */
$appointmentBinh = null;

/** @var Appointment|null $appointmentChi */
$appointmentChi = null;

runDemoStep('Bước 1: Bình đặt khám thường lúc 09:00', function () use (
    $clinic,
    $doctor,
    $patientBinh,
    $regularConsultation,
    &$appointmentBinh
): Appointment {
    return $appointmentBinh = $clinic->bookAppointment(
        $doctor,
        $patientBinh,
        $regularConsultation,
        '09:00'
    );
});

runDemoStep('Bước 2: Chi đặt khám chuyên khoa lúc 09:00', function () use (
    $clinic,
    $doctor,
    $patientChi,
    $specialistConsultation
): Appointment {
    return $clinic->bookAppointment(
        $doctor,
        $patientChi,
        $specialistConsultation,
        '09:00'
    );
});

runDemoStep('Bước 3: Chi đặt khám chuyên khoa lúc 10:00', function () use (
    $clinic,
    $doctor,
    $patientChi,
    $specialistConsultation,
    &$appointmentChi
): Appointment {
    return $appointmentChi = $clinic->bookAppointment(
        $doctor,
        $patientChi,
        $specialistConsultation,
        '10:00'
    );
});

runDemoStep('Bước 4: Xác nhận lịch của Bình', function () use ($clinic, &$appointmentBinh): Appointment {
    return $clinic->confirmAppointment($appointmentBinh->id);
});

runDemoStep('Bước 5: Hoàn thành lịch của Bình', function () use ($clinic, &$appointmentBinh): Appointment {
    return $clinic->completeAppointment($appointmentBinh->id);
});

runDemoStep('Bước 6: Huỷ lịch của Bình', function () use ($clinic, &$appointmentBinh): Appointment {
    return $clinic->cancelAppointment($appointmentBinh->id);
});

runDemoStep('Bước 7: Huỷ lịch 10:00 của Chi', function () use ($clinic, &$appointmentChi): Appointment {
    return $clinic->cancelAppointment($appointmentChi->id);
});

runDemoStep('Bước 8: Chi đặt khám thường lúc 10:00', function () use (
    $clinic,
    $doctor,
    $patientChi,
    $regularConsultation
): Appointment {
    return $clinic->bookAppointment(
        $doctor,
        $patientChi,
        $regularConsultation,
        '10:00'
    );
});

echo PHP_EOL . 'Lịch của Bác sĩ ' . $doctor->getName()
    . ' (' . $doctor->getSpecialization() . '):' . PHP_EOL;

foreach ($clinic->getAppointmentsForDoctor($doctor) as $appointment) {
    echo sprintf(
        '  #%d %s | %s | %s | %s',
        $appointment->id,
        $appointment->time,
        $appointment->patient->getName(),
        $appointment->consultation->getName(),
        $appointment->status->value
    ) . PHP_EOL;
}
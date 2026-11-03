<?php

require_once __DIR__ . '/src/Clinic.php';
require_once __DIR__ . '/src/EmailNotifier.php';
require_once __DIR__ . '/src/SmsNotifier.php';
require_once __DIR__ . '/src/RegularConsultation.php';
require_once __DIR__ . '/src/SpecialistConsultation.php';

final class RecordingNotifier implements Notifier
{
    private array $messages = [];

    public function send(Patient $patient, string $message): void
    {
        $this->messages[] = $patient->getName() . ': ' . $message;
    }

    public function takeMessages(): array
    {
        $messages = $this->messages;
        $this->messages = [];

        return $messages;
    }
}

final class ClinicScenario
{
    public const ACTIONS = [
        'confirm' => ['Xác nhận', 'confirmAppointment', 'Đã xác nhận'],
        'complete' => ['Hoàn thành', 'completeAppointment', 'Hoàn thành'],
        'cancel' => ['Huỷ', 'cancelAppointment', 'Đã huỷ'],
    ];

    private const STATUS_PATHS = [
        'Chờ xác nhận' => [],
        'Đã xác nhận' => ['confirm'],
        'Hoàn thành' => ['confirm', 'complete'],
        'Đã huỷ' => ['cancel'],
    ];

    public Clinic $clinic;
    public RegularConsultation $regular;
    public SpecialistConsultation $specialist;
    private RecordingNotifier $notifications;
    private array $doctors = [];
    private array $patients = [];
    private int $stepNumber = 0;

    public function __construct()
    {
        $this->notifications = new RecordingNotifier();
        $this->clinic = new Clinic(new EmailNotifier(), new SmsNotifier(), $this->notifications);
        $this->regular = new RegularConsultation();
        $this->specialist = new SpecialistConsultation();

        $this->addDoctor('An', 'Nội tổng quát');
        $this->addPatients('Bình', 'Chi');
    }

    public function createDoctor(string $name, string $specialization): Doctor
    {
        $phone = sprintf('09000000%02d', count($this->doctors) + 1);

        return $this->doctors[$name] = new Doctor($name, $phone, $specialization);
    }

    public function addDoctor(string $name, string $specialization): Doctor
    {
        $doctor = $this->createDoctor($name, $specialization);
        $this->clinic->addDoctor($doctor);

        return $doctor;
    }

    public function addPatients(string ...$names): void
    {
        foreach ($names as $name) {
            $phone = sprintf('09020000%02d', count($this->patients) + 1);
            $this->patients[$name] = new Patient($name, $phone);
        }
    }

    public function doctor(string $name): Doctor
    {
        return $this->doctors[$name] ?? throw new RuntimeException("Kịch bản chưa khai báo bác sĩ {$name}.");
    }

    public function patient(string $name): Patient
    {
        return $this->patients[$name] ?? throw new RuntimeException("Kịch bản chưa khai báo bệnh nhân {$name}.");
    }

    public function book(
        string $patient,
        Consultation $consultation,
        string $time,
        ?string $doctor = null,
        ?string $error = null
    ): ?Appointment {
        return $this->step(
            $this->describeBooking($patient, $consultation, $time)
                . ($doctor === null ? '' : " với bác sĩ {$doctor}"),
            fn (): Appointment => $this->clinic->bookAppointment(
                $this->doctor($doctor ?? array_key_first($this->doctors)),
                $this->patient($patient),
                $consultation,
                $time
            ),
            $error
        );
    }

    public function bookAny(
        string $patient,
        Consultation $consultation,
        string $time,
        ?string $doctor = null,
        ?string $error = null
    ): ?Appointment {
        $appointment = $this->step(
            $this->describeBooking($patient, $consultation, $time) . ', phòng khám tự chọn bác sĩ',
            fn (): Appointment => $this->clinic->bookWithAnyAvailableDoctor(
                $this->patient($patient),
                $consultation,
                $time
            ),
            $error
        );

        if ($appointment !== null) {
            echo '  Bác sĩ phụ trách: ' . $appointment->getDoctor()->getDisplayName() . PHP_EOL;
            if ($doctor !== null) {
                $this->assertSame($doctor, $appointment->getDoctor()->getName(), 'Bác sĩ được phòng khám chọn');
            }
        }

        return $appointment;
    }

    public function bookWithStatus(
        string $patient,
        Consultation $consultation,
        string $time,
        string $status
    ): Appointment {
        $appointment = $this->book($patient, $consultation, $time);
        foreach (self::STATUS_PATHS[$status] ?? throw new RuntimeException("Trạng thái {$status} không hợp lệ.") as $action) {
            $this->apply($action, $appointment);
        }

        return $appointment;
    }

    public function confirm(Appointment|int $appointment, ?string $error = null, ?string $description = null): ?Appointment
    {
        return $this->apply('confirm', $appointment, $error, $description);
    }

    public function complete(Appointment|int $appointment, ?string $error = null, ?string $description = null): ?Appointment
    {
        return $this->apply('complete', $appointment, $error, $description);
    }

    public function cancel(Appointment|int $appointment, ?string $error = null, ?string $description = null): ?Appointment
    {
        return $this->apply('cancel', $appointment, $error, $description);
    }

    public function apply(
        string $action,
        Appointment|int $appointment,
        ?string $error = null,
        ?string $description = null
    ): ?Appointment {
        [$label, $method] = self::ACTIONS[$action];
        $id = $appointment instanceof Appointment ? $appointment->getId() : $appointment;
        $description ??= $label . ' lịch ' . ($appointment instanceof Appointment ? 'của ' . $appointment->getPatient()->getName() : '#' . $id);

        return $this->step($description, fn (): Appointment => $this->clinic->{$method}($id), $error);
    }

    public function step(string $description, Closure $action, ?string $error = null): ?Appointment
    {
        echo PHP_EOL . 'Bước ' . ++$this->stepNumber . ': ' . $description . PHP_EOL;

        try {
            $appointment = $action();
        } catch (LogicException $exception) {
            echo '  Lỗi: ' . $exception->getMessage() . PHP_EOL;
            if ($error === null) {
                throw new RuntimeException($description . ': thao tác không thành công như mong đợi.');
            }
            $this->assertSame($error, $exception->getMessage(), $description);

            return null;
        }

        if ($error !== null) {
            throw new RuntimeException($description . ': không phát sinh lỗi như mong đợi.');
        }

        echo '  Kết quả: ' . ($appointment->getStatus() === 'Chờ xác nhận'
            ? 'Thành công, phí ' . $appointment->getConsultation()->getFee() . ' đồng'
            : $appointment->getStatus()) . PHP_EOL;

        return $appointment;
    }

    public function showSchedule(string $doctor, array $expectedLines): void
    {
        $doctorObject = $this->doctor($doctor);
        $lines = array_map(
            static fn (Appointment $appointment): string => sprintf(
                '#%d %s | %s | %s | %s',
                $appointment->getId(),
                $appointment->getTime(),
                $appointment->getPatient()->getName(),
                $appointment->getConsultation()->getName(),
                $appointment->getStatus()
            ),
            $this->clinic->getAppointmentsForDoctor($doctorObject)
        );

        echo PHP_EOL . 'Lịch của Bác sĩ ' . $doctorObject->getDisplayName() . ':' . PHP_EOL;
        echo ($lines === [] ? '  Chưa có lịch khám.' : '  ' . implode(PHP_EOL . '  ', $lines)) . PHP_EOL;
        $this->assertSame($expectedLines, $lines, 'Lịch của bác sĩ ' . $doctor);
    }

    public function showAvailability(string $time, array $expectedDoctors): void
    {
        $available = [];
        echo PHP_EOL . "Tình trạng bác sĩ lúc {$time}:" . PHP_EOL;

        foreach ($this->clinic->getDoctors() as $doctor) {
            $isAvailable = $this->clinic->isDoctorAvailable($doctor, $time);
            echo '  Bác sĩ ' . $doctor->getDisplayName() . ': '
                . ($isAvailable ? 'Còn trống' : 'Đã kín lịch') . PHP_EOL;

            if ($isAvailable) {
                $available[] = $doctor->getName();
            }
        }

        echo '  ' . ($available === []
            ? "Không có bác sĩ nào rảnh lịch lúc {$time}."
            : 'Bác sĩ còn trống: ' . implode(', ', $available)) . PHP_EOL;
        $this->assertSame($expectedDoctors, $available, "Bác sĩ còn trống lúc {$time}");
    }

    public function showDoctors(array $expectedGroups): void
    {
        $groups = [];
        foreach ($this->clinic->getDoctors() as $doctor) {
            $groups[$doctor->isSpecialist() ? 'Bác sĩ chuyên khoa' : 'Bác sĩ đa khoa'][] = $doctor;
        }

        echo PHP_EOL . 'Danh sách bác sĩ của phòng khám:' . PHP_EOL;
        foreach ($groups as $label => $doctors) {
            echo "  {$label}:" . PHP_EOL;
            foreach ($doctors as $doctor) {
                $accepted = array_filter(
                    [$this->regular, $this->specialist],
                    static fn (Consultation $consultation): bool => $consultation->canBePerformedBy($doctor)
                );
                echo '    ' . $doctor->getDisplayName() . ': nhận ' . implode(', ', self::names($accepted)) . PHP_EOL;
            }
        }

        $this->assertSame(
            $expectedGroups,
            array_map(static fn (array $doctors): array => self::names($doctors), $groups),
            'Danh sách bác sĩ theo nhóm'
        );
    }

    public function assertNotifications(array $expected): void
    {
        $this->assertSame($expected, $this->notifications->takeMessages(), 'Thông báo đã gửi');
    }

    public function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(sprintf(
                '%s (mong đợi %s, nhận được %s)',
                $message,
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    private function describeBooking(string $patient, Consultation $consultation, string $time): string
    {
        return $patient . ' đặt ' . lcfirst($consultation->getName()) . ' lúc ' . $time;
    }

    private static function names(array $items): array
    {
        return array_values(array_map(static fn (Person|Consultation $item): string => $item->getName(), $items));
    }
}

function scenarioDemo(ClinicScenario $s): void
{
    $binh = $s->book('Bình', $s->regular, '09:00');
    $s->book('Chi', $s->specialist, '09:00', error: 'Bác sĩ An (Nội tổng quát) đã có lịch lúc 09:00');
    $chi = $s->book('Chi', $s->specialist, '10:00');
    $s->confirm($binh);
    $s->complete($binh);
    $s->cancel($binh, error: 'Lịch #1 đang ở trạng thái Hoàn thành, không thể huỷ');
    $s->cancel($chi, description: 'Huỷ lịch 10:00 của Chi');
    $s->book('Chi', $s->regular, '10:00');

    $s->showSchedule('An', [
        '#1 09:00 | Bình | Khám thường | Hoàn thành',
        '#2 10:00 | Chi | Khám chuyên khoa | Đã huỷ',
        '#3 10:00 | Chi | Khám thường | Chờ xác nhận',
    ]);
}

function scenarioNoDuplicateBooking(ClinicScenario $s): void
{
    $duplicate = 'Bác sĩ An (Nội tổng quát) đã có lịch lúc 09:00';

    $binh = $s->book('Bình', $s->regular, '09:00');
    $s->book('Chi', $s->specialist, '09:00', error: $duplicate);
    $s->confirm($binh);
    $s->book('Chi', $s->specialist, '09:00', error: $duplicate);
    $s->complete($binh);
    $s->book('Chi', $s->specialist, '09:00', error: $duplicate);
    $s->book('Bình', $s->regular, '09:00', error: $duplicate);
    $s->book('Chi', $s->specialist, '10:00');

    $s->showSchedule('An', [
        '#1 09:00 | Bình | Khám thường | Hoàn thành',
        '#2 10:00 | Chi | Khám chuyên khoa | Chờ xác nhận',
    ]);
}

function scenarioCancelAndReuseTime(ClinicScenario $s): void
{
    $first = $s->book('Bình', $s->specialist, '14:00');
    $s->cancel($first);
    $s->cancel($first, error: 'Lịch đã huỷ.');
    $second = $s->book('Chi', $s->regular, '14:00');
    $s->confirm($second);
    $s->book('Bình', $s->regular, '14:00', error: 'Bác sĩ An (Nội tổng quát) đã có lịch lúc 14:00');
    $s->cancel($second);
    $s->book('Bình', $s->regular, '14:00');

    $s->showSchedule('An', [
        '#1 14:00 | Bình | Khám chuyên khoa | Đã huỷ',
        '#2 14:00 | Chi | Khám thường | Đã huỷ',
        '#3 14:00 | Bình | Khám thường | Chờ xác nhận',
    ]);
}

function scenarioStatusTransitions(ClinicScenario $s): void
{
    $invalid = static fn (string $action, string $status): string =>
        sprintf('Không thể %s lịch ở trạng thái "%s".', $action, $status);

    $cases = [
        ['Chờ xác nhận', 'confirm', null],
        ['Chờ xác nhận', 'complete', $invalid('hoàn thành', 'Chờ xác nhận')],
        ['Chờ xác nhận', 'cancel', null],
        ['Đã xác nhận', 'confirm', $invalid('xác nhận', 'Đã xác nhận')],
        ['Đã xác nhận', 'complete', null],
        ['Đã xác nhận', 'cancel', null],
        ['Hoàn thành', 'confirm', $invalid('xác nhận', 'Hoàn thành')],
        ['Hoàn thành', 'complete', $invalid('hoàn thành', 'Hoàn thành')],
        ['Hoàn thành', 'cancel', 'Lịch #%d đang ở trạng thái Hoàn thành, không thể huỷ'],
        ['Đã huỷ', 'confirm', $invalid('xác nhận', 'Đã huỷ')],
        ['Đã huỷ', 'complete', $invalid('hoàn thành', 'Đã huỷ')],
        ['Đã huỷ', 'cancel', 'Lịch đã huỷ.'],
    ];

    foreach ($cases as $index => [$status, $action, $error]) {
        $appointment = $s->bookWithStatus(
            $index % 2 === 0 ? 'Bình' : 'Chi',
            $s->regular,
            sprintf('%02d:00', 7 + $index),
            $status
        );
        [$label, , $successStatus] = ClinicScenario::ACTIONS[$action];

        $s->apply(
            $action,
            $appointment,
            $error === null ? null : sprintf($error, $appointment->getId()),
            sprintf('%s lịch #%d đang ở trạng thái %s', $label, $appointment->getId(), $status)
        );
        $s->assertSame(
            $error === null ? $successStatus : $status,
            $appointment->getStatus(),
            "{$label} lịch đang ở trạng thái {$status}"
        );
    }
}

function scenarioMissingData(ClinicScenario $s): void
{
    $s->showSchedule('An', []);
    foreach (array_keys(ClinicScenario::ACTIONS) as $action) {
        $s->apply($action, 99, 'Không tìm thấy lịch khám #99.');
    }

    $s->createDoctor('Lan', 'Da liễu');
    $s->book('Bình', $s->regular, '09:00', doctor: 'Lan', error: 'Bác sĩ Lan (Da liễu) không làm việc tại phòng khám');
    $s->step(
        'Bình đặt lịch với một bác sĩ An khác (cùng thông tin nhưng chưa được thêm vào phòng khám)',
        fn (): Appointment => $s->clinic->bookAppointment(
            new Doctor('An', '0900000001', 'Nội tổng quát'),
            $s->patient('Bình'),
            $s->regular,
            '09:00'
        ),
        'Bác sĩ An (Nội tổng quát) không làm việc tại phòng khám'
    );

    $s->clinic->addDoctor($s->doctor('An'));
    $s->showDoctors(['Bác sĩ chuyên khoa' => ['An']]);
    $s->showSchedule('An', []);
}

function scenarioMultipleDoctors(ClinicScenario $s): void
{
    $s->addDoctor('Minh', 'Nhi');
    $s->addDoctor('Hoa', Doctor::GENERAL_PRACTICE);
    $s->addPatients('Dũng', 'Giang');
    $s->showDoctors(['Bác sĩ chuyên khoa' => ['An', 'Minh'], 'Bác sĩ đa khoa' => ['Hoa']]);

    $s->book('Bình', $s->regular, '15:00', doctor: 'An');
    $s->showAvailability('15:00', ['Minh', 'Hoa']);
    $s->book('Chi', $s->specialist, '15:00', doctor: 'An',
        error: 'Bác sĩ An (Nội tổng quát) đã có lịch lúc 15:00. Bác sĩ còn trống: Minh (Nhi)');
    $s->book('Chi', $s->specialist, '15:00', doctor: 'Hoa',
        error: 'Bác sĩ Hoa (Đa khoa) không nhận Khám chuyên khoa');
    $s->bookAny('Chi', $s->specialist, '15:00', doctor: 'Minh');
    $s->bookAny('Dũng', $s->specialist, '15:00',
        error: 'Không có bác sĩ nào rảnh lịch lúc 15:00 cho Khám chuyên khoa');
    $s->bookAny('Dũng', $s->regular, '15:00', doctor: 'Hoa');
    $s->showAvailability('15:00', []);
    $s->bookAny('Giang', $s->regular, '15:00',
        error: 'Không có bác sĩ nào rảnh lịch lúc 15:00 cho Khám thường');
    $s->book('Giang', $s->regular, '15:00', doctor: 'An',
        error: 'Bác sĩ An (Nội tổng quát) đã có lịch lúc 15:00. Không có bác sĩ nào khác rảnh lịch lúc 15:00 cho Khám thường');
    $s->book('Giang', $s->regular, '16:00', doctor: 'An');

    $s->showSchedule('An', [
        '#1 15:00 | Bình | Khám thường | Chờ xác nhận',
        '#4 16:00 | Giang | Khám thường | Chờ xác nhận',
    ]);
    $s->showSchedule('Minh', ['#2 15:00 | Chi | Khám chuyên khoa | Chờ xác nhận']);
    $s->showSchedule('Hoa', ['#3 15:00 | Dũng | Khám thường | Chờ xác nhận']);
}

function scenarioStatusOverview(ClinicScenario $s): void
{
    $s->bookWithStatus('Bình', $s->regular, '08:00', 'Chờ xác nhận');
    $s->bookWithStatus('Chi', $s->regular, '09:00', 'Đã xác nhận');
    $s->bookWithStatus('Bình', $s->specialist, '10:00', 'Hoàn thành');
    $s->bookWithStatus('Chi', $s->specialist, '11:00', 'Đã huỷ');

    $s->showSchedule('An', [
        '#1 08:00 | Bình | Khám thường | Chờ xác nhận',
        '#2 09:00 | Chi | Khám thường | Đã xác nhận',
        '#3 10:00 | Bình | Khám chuyên khoa | Hoàn thành',
        '#4 11:00 | Chi | Khám chuyên khoa | Đã huỷ',
    ]);
}

function scenarioDoctorTypes(ClinicScenario $s): void
{
    $s->addDoctor('Hoa', Doctor::GENERAL_PRACTICE);
    $s->addDoctor('Minh', 'Nhi');
    $s->addPatients('Dũng');
    $s->showDoctors(['Bác sĩ chuyên khoa' => ['An', 'Minh'], 'Bác sĩ đa khoa' => ['Hoa']]);
    $s->assertSame([200000, 500000], [$s->regular->getFee(), $s->specialist->getFee()], 'Phí theo loại khám');

    $s->book('Bình', $s->regular, '09:00', doctor: 'Hoa');
    $s->book('Chi', $s->specialist, '09:00', doctor: 'Hoa', error: 'Bác sĩ Hoa (Đa khoa) không nhận Khám chuyên khoa');
    $s->book('Chi', $s->specialist, '09:00', doctor: 'Minh');
    $s->book('Bình', $s->specialist, '10:00', doctor: 'An');
    $s->book('Chi', $s->regular, '10:00', doctor: 'Minh');
    $s->book('Bình', $s->regular, '11:00', doctor: 'An');
    $s->bookAny('Chi', $s->specialist, '11:00', doctor: 'Minh');
    $s->bookAny('Dũng', $s->regular, '11:00', doctor: 'Hoa');

    $s->showSchedule('An', [
        '#3 10:00 | Bình | Khám chuyên khoa | Chờ xác nhận',
        '#5 11:00 | Bình | Khám thường | Chờ xác nhận',
    ]);
    $s->showSchedule('Hoa', [
        '#1 09:00 | Bình | Khám thường | Chờ xác nhận',
        '#7 11:00 | Dũng | Khám thường | Chờ xác nhận',
    ]);
    $s->showSchedule('Minh', [
        '#2 09:00 | Chi | Khám chuyên khoa | Chờ xác nhận',
        '#4 10:00 | Chi | Khám thường | Chờ xác nhận',
        '#6 11:00 | Chi | Khám chuyên khoa | Chờ xác nhận',
    ]);
}

function scenarioNotifications(ClinicScenario $s): void
{
    $binh = $s->book('Bình', $s->regular, '09:00');
    $s->assertNotifications(['Bình: Đặt lịch #1 thành công: Khám thường lúc 09:00']);

    $s->book('Chi', $s->regular, '09:00', error: 'Bác sĩ An (Nội tổng quát) đã có lịch lúc 09:00');
    $s->bookAny('Chi', $s->regular, '09:00', error: 'Không có bác sĩ nào rảnh lịch lúc 09:00 cho Khám thường');
    $s->confirm($binh);
    $s->complete($binh);
    $s->cancel($binh, error: 'Lịch #1 đang ở trạng thái Hoàn thành, không thể huỷ');
    $s->assertNotifications([]);

    $chi = $s->bookAny('Chi', $s->specialist, '10:00', doctor: 'An');
    $s->cancel($chi);
    $s->cancel($chi, error: 'Lịch đã huỷ.');
    $s->assertNotifications([
        'Chi: Đặt lịch #2 thành công: Khám chuyên khoa lúc 10:00',
        'Chi: Lịch #2 đã huỷ',
    ]);
}

function scenarioIndependentDoctors(ClinicScenario $s): void
{
    $s->addDoctor('Minh', 'Nhi');

    $an = $s->book('Bình', $s->regular, '09:00', doctor: 'An');
    $minh = $s->book('Chi', $s->specialist, '09:00', doctor: 'Minh');
    $s->cancel($an);
    $s->book('Bình', $s->specialist, '09:00', doctor: 'Minh',
        error: 'Bác sĩ Minh (Nhi) đã có lịch lúc 09:00. Bác sĩ còn trống: An (Nội tổng quát)');
    $s->confirm($minh);
    $s->complete($minh);
    $s->book('Bình', $s->regular, '10:00', doctor: 'Minh');

    $s->showSchedule('An', ['#1 09:00 | Bình | Khám thường | Đã huỷ']);
    $s->showSchedule('Minh', [
        '#2 09:00 | Chi | Khám chuyên khoa | Hoàn thành',
        '#3 10:00 | Bình | Khám thường | Chờ xác nhận',
    ]);
}

function scenarioAutoAssignment(ClinicScenario $s): void
{
    $s->addDoctor('Minh', 'Nhi');
    $s->addDoctor('Hoa', Doctor::GENERAL_PRACTICE);
    $s->addPatients('Dũng', 'Giang');

    $first = $s->bookAny('Bình', $s->regular, '08:00', doctor: 'An');
    $s->bookAny('Chi', $s->regular, '08:00', doctor: 'Minh');
    $s->bookAny('Dũng', $s->regular, '08:00', doctor: 'Hoa');
    $s->showAvailability('08:00', []);
    $s->cancel($first);
    $s->showAvailability('08:00', ['An']);
    $s->bookAny('Bình', $s->specialist, '08:00', doctor: 'An');
    $s->book('Giang', $s->regular, '08:00', doctor: 'Hoa',
        error: 'Bác sĩ Hoa (Đa khoa) đã có lịch lúc 08:00. Không có bác sĩ nào khác rảnh lịch lúc 08:00 cho Khám thường');
    $s->bookAny('Giang', $s->regular, '09:00', doctor: 'An');

    $s->showSchedule('An', [
        '#1 08:00 | Bình | Khám thường | Đã huỷ',
        '#4 08:00 | Bình | Khám chuyên khoa | Chờ xác nhận',
        '#5 09:00 | Giang | Khám thường | Chờ xác nhận',
    ]);
    $s->showSchedule('Minh', ['#2 08:00 | Chi | Khám thường | Chờ xác nhận']);
    $s->showSchedule('Hoa', ['#3 08:00 | Dũng | Khám thường | Chờ xác nhận']);
}

function getScenarios(): array
{
    return [
        'T0' => ['Demo theo đề bài', 'scenarioDemo'],
        'T1' => ['Không cho đặt trùng giờ', 'scenarioNoDuplicateBooking'],
        'T2' => ['Huỷ lịch rồi đặt lại đúng giờ đó', 'scenarioCancelAndReuseTime'],
        'T3' => ['Kiểm tra mọi hướng chuyển trạng thái', 'scenarioStatusTransitions'],
        'T4' => ['Mã lịch và bác sĩ không tồn tại', 'scenarioMissingData'],
        'T5' => ['Nhiều bác sĩ: gợi ý và tự chọn bác sĩ', 'scenarioMultipleDoctors'],
        'T6' => ['Danh sách lịch đủ bốn trạng thái', 'scenarioStatusOverview'],
        'T7' => ['Bác sĩ đa khoa và bác sĩ chuyên khoa', 'scenarioDoctorTypes'],
        'T8' => ['Thông báo chỉ gửi khi đặt và huỷ lịch', 'scenarioNotifications'],
        'T9' => ['Lịch của các bác sĩ độc lập nhau', 'scenarioIndependentDoctors'],
        'T10' => ['Tự chọn bác sĩ theo thứ tự và khi có lịch huỷ', 'scenarioAutoAssignment'],
    ];
}

function runScenario(string $code, array $scenario): bool
{
    [$title, $run] = $scenario;
    echo PHP_EOL . "Kịch bản: {$code} - {$title}" . PHP_EOL;

    try {
        $run(new ClinicScenario());
    } catch (Throwable $exception) {
        echo PHP_EOL . 'Thất bại: ' . $exception->getMessage() . PHP_EOL;

        return false;
    }

    return true;
}

function runAllScenarios(array $scenarios): bool
{
    $failedCodes = [];
    foreach ($scenarios as $code => $scenario) {
        if (!runScenario($code, $scenario)) {
            $failedCodes[] = $code;
        }
    }

    echo PHP_EOL . sprintf(
        'Tổng kết: %d/%d kịch bản đạt%s',
        count($scenarios) - count($failedCodes),
        count($scenarios),
        $failedCodes === [] ? '' : ' (lỗi: ' . implode(', ', $failedCodes) . ')'
    ) . PHP_EOL;

    return $failedCodes === [];
}

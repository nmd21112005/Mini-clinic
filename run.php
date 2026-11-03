<?php

require_once __DIR__ . '/scenarios.php';

$scenarios = getScenarios();
$availableCodes = implode(', ', array_keys($scenarios));

echo "Nhập mã kịch bản ({$availableCodes}, ALL): ";
$scenarioCode = strtoupper(trim(fgets(STDIN) ?: ''));

if ($scenarioCode === 'ALL') {
    exit(runAllScenarios($scenarios) ? 0 : 1);
}

if (!isset($scenarios[$scenarioCode])) {
    echo "Không tìm thấy kịch bản." . PHP_EOL;
    exit(1);
}

exit(runScenario($scenarioCode, $scenarios[$scenarioCode]) ? 0 : 1);

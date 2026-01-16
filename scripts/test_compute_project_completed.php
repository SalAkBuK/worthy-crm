<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/external_projects_normalize.php';

function assert_case(string $label, bool $expected, bool $actual): void {
  if ($expected !== $actual) {
    fwrite(STDERR, "[FAIL] {$label}: expected " . ($expected ? 'true' : 'false') . ", got " . ($actual ? 'true' : 'false') . PHP_EOL);
    exit(1);
  }
  fwrite(STDOUT, "[PASS] {$label}" . PHP_EOL);
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');

assert_case(
  'project_completed=true overrides future date',
  true,
  compute_project_is_completed(['project_completed' => true, 'expected_completion_date' => '2099-01-01'])
);

assert_case(
  'past expected_completion_date marks completed',
  true,
  compute_project_is_completed(['project_completed' => false, 'expected_completion_date' => '2020-01-01'])
);

assert_case(
  'today expected_completion_date marks completed',
  true,
  compute_project_is_completed(['project_completed' => false, 'expected_completion_date' => $today])
);

assert_case(
  'future expected_completion_date not completed',
  false,
  compute_project_is_completed(['project_completed' => false, 'expected_completion_date' => '2099-01-01'])
);

assert_case(
  '1970-01-01 placeholder not completed',
  false,
  compute_project_is_completed(['project_completed' => false, 'expected_completion_date' => '1970-01-01'])
);

assert_case(
  'empty expected_completion_date not completed',
  false,
  compute_project_is_completed(['project_completed' => false, 'expected_completion_date' => ''])
);

assert_case(
  'null expected_completion_date not completed',
  false,
  compute_project_is_completed(['project_completed' => false, 'expected_completion_date' => null])
);

assert_case(
  'invalid expected_completion_date not completed',
  false,
  compute_project_is_completed(['project_completed' => false, 'expected_completion_date' => 'not-a-date'])
);

fwrite(STDOUT, "All tests passed." . PHP_EOL);

<?php

namespace Tests\Unit;

use App\Services\PeriodValidator;
use PHPUnit\Framework\TestCase;

class PeriodValidatorTest extends TestCase
{
    public function test_all_periods_are_reported_missing_when_payload_is_empty(): void
    {
        $result = PeriodValidator::missing([]);

        $this->assertArrayHasKey('first inspections', $result);
        $this->assertArrayHasKey('seconds inspections', $result);
        $this->assertArrayHasKey('samples', $result);
        $this->assertArrayHasKey('packagings', $result);
        $this->assertArrayHasKey('judgements', $result);

        $this->assertEquals([1, 2, 3, 4], $result['first inspections']);
        $this->assertEquals([1, 2, 3, 4], $result['seconds inspections']);
        $this->assertEquals([1, 2, 3, 4], $result['samples']);
        $this->assertEquals([1, 2, 3, 4], $result['packagings']);
        $this->assertEquals([1, 2, 3, 4], $result['judgements']);
    }

    public function test_no_periods_are_reported_missing_when_all_periods_are_filled(): void
    {
        $completeSection = [
            'p1' => ['dummy' => 'val'],
            'p2' => ['dummy' => 'val'],
            'p3' => ['dummy' => 'val'],
            'p4' => ['dummy' => 'val'],
        ];

        $payload = [
            'first_inspections' => $completeSection,
            'second_inspections' => $completeSection,
            'samples' => $completeSection,
            'packagings' => $completeSection,
            'judgements' => $completeSection,
        ];

        $result = PeriodValidator::missing($payload);

        foreach ($result as $section => $missing) {
            $this->assertEmpty($missing, "Expected {$section} to have no missing periods.");
        }
    }

    public function test_detects_partial_missing_periods_accurately(): void
    {
        $payload = [
            'first_inspections' => [
                'p1' => ['ok'],
                'p2' => [], // Empty array treated as missing
                'p3' => ['ok'],
            ],
            'second_inspections' => [
                'p1' => ['ok'],
            ],
            'samples' => [
                'p1' => ['ok'],
                'p4' => ['ok'],
            ],
            'packagings' => [
                'p1' => ['ok'],
                'p2' => ['ok'],
                'p3' => ['ok'],
                'p4' => ['ok'],
            ],
            'judgements' => [],
        ];

        $result = PeriodValidator::missing($payload);

        $this->assertEquals([2, 4], $result['first inspections']);
        $this->assertEquals([2, 3, 4], $result['seconds inspections']);
        $this->assertEquals([2, 3], $result['samples']);
        $this->assertEmpty($result['packagings']);
        $this->assertEquals([1, 2, 3, 4], $result['judgements']);
    }
}

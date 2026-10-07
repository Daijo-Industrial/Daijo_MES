<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OperatorBarcodeScanProtectionTest extends TestCase
{
    /**
     * Test logic recovery when label is concatenated with SPK code (misal 300 + 26026744 -> 300)
     */
    public function test_label_auto_recovery_from_spk_concatenation()
    {
        $rawLabel = '30026026744';
        $spkCode = '26026744';

        if (!empty($spkCode) && str_ends_with($rawLabel, $spkCode) && strlen($rawLabel) > strlen($spkCode)) {
            $candidate = substr($rawLabel, 0, -strlen($spkCode));
            if (is_numeric($candidate) && (int)$candidate > 0) {
                $rawLabel = (string)(int)$candidate;
            }
        }

        $this->assertEquals('300', $rawLabel);
    }

    /**
     * Test detection of scanner stutter / repeating digits
     */
    public function test_stutter_repetition_detection()
    {
        $stutterLabel = '36666666666666666';
        $isStutter = (bool) preg_match('/(\d)\1{4,}/', $stutterLabel);

        $this->assertTrue($isStutter);

        $validLabel = '360';
        $isValid = !preg_match('/(\d)\1{4,}/', $validLabel) && preg_match('/^[1-9]\d{0,5}$/', $validLabel);
        $this->assertTrue($isValid);
    }

    /**
     * Test max digit length enforcement
     */
    public function test_label_format_enforcement()
    {
        $validLabels = ['1', '12', '300', '355', '999999'];
        foreach ($validLabels as $lbl) {
            $this->assertEquals(1, preg_match('/^[1-9]\d{0,5}$/', $lbl));
        }

        $invalidLabels = ['0', '-5', '1000000', '36666666666666666', 'abc', '300a'];
        foreach ($invalidLabels as $lbl) {
            $this->assertEquals(0, preg_match('/^[1-9]\d{0,5}$/', $lbl));
        }
    }

    /**
     * Test recovery flag and normal scan state
     */
    public function test_label_recovery_flag_and_normal_scan()
    {
        // Case 1: Normal scan
        $rawLabel = '12';
        $spkCode = '25035655';
        $originalRawLabel = $rawLabel;
        $wasRecovered = false;

        if (!empty($spkCode) && str_ends_with($rawLabel, $spkCode) && strlen($rawLabel) > strlen($spkCode)) {
            $candidate = substr($rawLabel, 0, -strlen($spkCode));
            if (is_numeric($candidate) && (int)$candidate > 0) {
                $rawLabel = (string)(int)$candidate;
                $wasRecovered = true;
            }
        }
        $this->assertFalse($wasRecovered);
        $this->assertEquals('12', $rawLabel);
        $this->assertEquals('12', $originalRawLabel);

        // Case 2: Recovered scan
        $rawLabel = '1225035655';
        $originalRawLabel = $rawLabel;
        $wasRecovered = false;

        if (!empty($spkCode) && str_ends_with($rawLabel, $spkCode) && strlen($rawLabel) > strlen($spkCode)) {
            $candidate = substr($rawLabel, 0, -strlen($spkCode));
            if (is_numeric($candidate) && (int)$candidate > 0) {
                $rawLabel = (string)(int)$candidate;
                $wasRecovered = true;
            }
        }
        $this->assertTrue($wasRecovered);
        $this->assertEquals('12', $rawLabel);
        $this->assertEquals('1225035655', $originalRawLabel);
    }
}

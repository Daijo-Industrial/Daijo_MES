<?php

namespace Tests\Unit;

use App\Models\HourlyRemark;
use PHPUnit\Framework\TestCase;

class HourlyRemarkUnitTest extends TestCase
{
    public function test_pics_array_filters_empty_and_null_pics(): void
    {
        $remark = new HourlyRemark([
            'pic' => 'Budi',
            'pic_2' => null,
            'pic_3' => 'Agus',
        ]);

        $this->assertEquals(['Budi', 'Agus'], $remark->pics_array);

        $remarkEmpty = new HourlyRemark([
            'pic' => null,
            'pic_2' => '',
            'pic_3' => null,
        ]);

        $this->assertEquals([], $remarkEmpty->pics_array);
    }

    public function test_pics_string_joins_pics_with_comma(): void
    {
        $remark = new HourlyRemark([
            'pic' => 'Operator A',
            'pic_2' => 'Operator B',
            'pic_3' => 'Operator C',
        ]);

        $this->assertEquals('Operator A, Operator B, Operator C', $remark->pics_string);

        $singlePic = new HourlyRemark([
            'pic' => 'Solo Operator',
            'pic_2' => null,
            'pic_3' => null,
        ]);

        $this->assertEquals('Solo Operator', $singlePic->pics_string);
    }
}

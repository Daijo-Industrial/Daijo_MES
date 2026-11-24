<?php

namespace Database\Seeders;

use App\Models\MasterZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserZoneSeeder extends Seeder
{
    public function run()
    {
        $zoneMap = [
            'A' => ['0450J', '0350F', '0450H', '0450I', '0450J', '0450F', '0450G', '0550B', '0650D', '0650E', '0850D'],
            'B' => ['1300C', '1050C', '0850B', '1300A', '1050A', '1050D', '0650A', '0850C', '0650C', '1050B', '0700A', '0700B', '1300B'],
            'C1' => ['0450C', '0150E', '0150F', '0360A', '0360B', '0360C', '0360D', '0450B', '0450A'],
            'C2' => ['0350E', '0450E', '0150B', '0150C', '0150D', '0050A', '0050B', '0110A', '0240A', '0300A', '0300B', '0170A', '0170B', '0150A', 'VTC'],
        ];

        foreach ($zoneMap as $zoneName => $userNames) {
            $zone = MasterZone::where('zone_name', $zoneName)->first();

            if ($zone) {
                User::whereIn('name', $userNames)->update(['zone_id' => $zone->id]);
            }
        }
    }
}

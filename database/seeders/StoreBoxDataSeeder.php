<?php

namespace Database\Seeders;

use App\Models\StoreBoxData;
use Illuminate\Database\Seeder;

class StoreBoxDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['part_no' => '601-68921B000P', 'part_name' => 'BOX 5mm+PART 3mm COVER ASSY INST LWR LH/RH LHD/RHD'],
            ['part_no' => '601-80960B031P', 'part_name' => 'BOX 5mm+PART 3mm FIN ASSY PWR WDW FR LH/RH RHD/LHD'],
            ['part_no' => '601-82961B011P', 'part_name' => 'BOX 5mm+PART 3mm FIN ASSY PWR WDW RR LH/RH RHD/LHD'],
            ['part_no' => '601-8011B927XB', 'part_name' => 'BOX 5mm+PARTISI 3mm COVER FLR CONS SIDE FR LHD/RHD'],
            ['part_no' => '601-8065A227XB', 'part_name' => 'BOX 5mm+PARTISI 3mm COVER STRG COLUMN LWR/BLACK'],
            ['part_no' => '601-8065A228XB', 'part_name' => 'BOX 5mm+PARTISI 3mm COVER STRG COLUMN UPR/BLACK'],
            ['part_no' => '602-6699', 'part_name' => 'BOX CONTAINER 6699 1340x335x195mm'],
            ['part_no' => '602-6033', 'part_name' => 'BOX CONTAINER BLUE 6033 505x335x185mm (OUTER)'],
            ['part_no' => '602-6622', 'part_name' => 'BOX CONTAINER BLUE 6622 415x285x285mm'],
            ['part_no' => '602-6678', 'part_name' => 'BOX CONTAINER BLUE 6678 670x503x380mm (OUTER)'],
            ['part_no' => '602-DAIJO6-INJ', 'part_name' => 'BOX CONTAINER BLUE DJ6 870x550x480mm (OUTER)'],
            ['part_no' => '602-DAIJO9-INJ', 'part_name' => 'BOX CONTAINER BLUE DJ9 630x500x430mm (OUTER)'],
            ['part_no' => '601-DAIJO85', 'part_name' => 'BOX IMPRABOARD BLUE T5 1100x450x520mm W/PARTISI 6HOLE'],
            ['part_no' => '601-DAIJO80', 'part_name' => 'BOX IMPRABOARD BLUE T5 1100x550x550mm'],
            ['part_no' => '601-DAIJO80-1', 'part_name' => 'BOX IMPRABOARD BLUE T5 1100x610x550mm'],
            ['part_no' => '601-DAIJO81', 'part_name' => 'BOX IMPRABOARD BLUE T5 430x400x350mm'],
            ['part_no' => '601-DAIJO86', 'part_name' => 'BOX IMPRABOARD BLUE T5 640x380x330mm W/PARTISI 6HOLE'],
            ['part_no' => '601-DAIJO51', 'part_name' => 'BOX PP BOARD BLUE T5 1000x410x270mm'],
            ['part_no' => '601-DAIJO12', 'part_name' => 'BOX PP BOARD BLUE T5 1190x505x380mm'],
            ['part_no' => '601-DAIJO28', 'part_name' => 'BOX PP BOARD BLUE t5 580x470x600mm'],
            ['part_no' => '601-DAIJO88', 'part_name' => 'BOX PP BOARD BLUE T5 580x530x460mm'],
            ['part_no' => '601-DAIJO10-1', 'part_name' => 'BOX PP BOARD T4 1020x445x630mm'],
            ['part_no' => '601-DAIJO47', 'part_name' => 'BOX PP BOARD T4 460x295x165mm'],
            ['part_no' => '601-DAIJO50', 'part_name' => 'BOX PP BOARD T4 565x490x165mm'],
            ['part_no' => '601-DAIJO9', 'part_name' => 'BOX PP BOARD T4 580x450x430mm'],
            ['part_no' => '601-DAIJO29SET-EVA', 'part_name' => 'BOX PP BOARD t4 900x630x340mm W/PARTISI & EVA'],
            ['part_no' => '601-DAIJO10', 'part_name' => 'BOX PP BOARD T4 965x445x630mm'],
            ['part_no' => '601-DAIJO30SET-EVA', 'part_name' => 'BOX PP BOARD t4 990x640x350mm W/PARTISI & EVA'],
            ['part_no' => '601-DAIJO48BLUE', 'part_name' => 'BOX PP BOARD T4 BLUE 1010x400x160mm'],
            ['part_no' => '601-DAIJO48', 'part_name' => 'BOX PP BOARD T4 YELLOW 1010x400x160mm'],
            ['part_no' => '601-DAIJO33-SET', 'part_name' => 'BOX PP BOARD t5 1240x545x760mm W/PARTISI'],
            ['part_no' => '601-DAIJO32SET-EVA', 'part_name' => 'BOX PP BOARD t5 1290x490x430mm W/PARTISI & EVA'],
            ['part_no' => '601-DAIJO11', 'part_name' => 'BOX PP BOARD T5 1470x568x375mm'],
            ['part_no' => '601-DAIJO45', 'part_name' => 'BOX PP BOARD T5 340x295x430mm'],
            ['part_no' => '601-DAIJO20', 'part_name' => 'BOX PP BOARD t5 360X255X170'],
            ['part_no' => '601-DAIJO19', 'part_name' => 'BOX PP BOARD t5 410X340X180'],
            ['part_no' => '601-DAIJO36', 'part_name' => 'BOX PP BOARD T5 570x335x385mm'],
            ['part_no' => '601-DAIJO46', 'part_name' => 'BOX PP BOARD T5 585x550x140mm'],
            ['part_no' => '601-DAIJO16', 'part_name' => 'BOX PP BOARD t5 620X565X170'],
            ['part_no' => '601-DAIJO34', 'part_name' => 'BOX PP BOARD t5 625x430x320mm'],
            ['part_no' => '601-DAIJO8', 'part_name' => 'BOX PP BOARD T5 650x560x550mm'],
            ['part_no' => '601-DAIJO31SET-EVA', 'part_name' => 'BOX PP BOARD t5 700x450x320mm W/PARTISI & EVA'],
            ['part_no' => '601-DAIJO14', 'part_name' => 'BOX PP BOARD t5 720X715X230'],
            ['part_no' => '601-DAIJO30', 'part_name' => 'BOX PP BOARD t5 990x640x350mm'],
            ['part_no' => '601-DAIJO30-1', 'part_name' => 'BOX PP BOARD t5 990x640x350mm (DAIJO INDUSTRIAL)'],
            ['part_no' => '601-DAIJO82', 'part_name' => 'BOX PP BOARD T5 BLUE 720x490x250mm'],
            ['part_no' => '601-DAIJO39BLUE', 'part_name' => 'BOX PP BOARD T5 BLUE 730x550x200mm'],
            ['part_no' => '601-DAIJO40BLUE', 'part_name' => 'BOX PP BOARD T5 BLUE 730x550x330mm'],
            ['part_no' => '601-DAIJO49SET', 'part_name' => 'BOX PP BOARD T5 YELLOW 1120x645x340mm W/EVA SPONS T3'],
            ['part_no' => '601-DAIJO83', 'part_name' => 'BOX PP BOARD T5 YELLOW 580x450x100mm'],
            ['part_no' => '601-DAIJO87', 'part_name' => 'BOX PP BOARD T5 YELLOW 730x550x150mm'],
            ['part_no' => '601-DAIJO39', 'part_name' => 'BOX PP BOARD T5 YELLOW 730x550x200mm'],
            ['part_no' => '601-DAIJO41', 'part_name' => 'BOX PP BOARD T5 YELLOW 730x550x310mm'],
            ['part_no' => '601-DAIJO40', 'part_name' => 'BOX PP BOARD T5 YELLOW 730x550x330mm'],
            ['part_no' => '601-DAIJO29', 'part_name' => 'BOX PP BOARD T5 YELLOW 900x630x340mm'],
            ['part_no' => '601-DAIJO89', 'part_name' => 'BOX PP BOARD YELLOW T5 1200x600x400mm'],
            ['part_no' => '601-S1500X1250XT3', 'part_name' => 'IMPRABOARD SHEET BLUE T3 1500x1250mm'],
            ['part_no' => '601-S800X400XT5', 'part_name' => 'IMPRABOARD SHEET GREEN T5 800x400mm'],
            ['part_no' => '601-DAIJO7', 'part_name' => 'IMPRABOARD t4 1000x500x410mm'],
            ['part_no' => '601-DAIJO5', 'part_name' => 'IMPRABOARD t4 580x450x400mm'],
            ['part_no' => '601-DAIJO6', 'part_name' => 'IMPRABOARD t4 820x500x480mm'],
            ['part_no' => '601-DAIJO6-1', 'part_name' => 'IMPRABOARD t4 820x500x480mm'],
            ['part_no' => '602-80961B00ZP', 'part_name' => 'POLYBOX 6655+PARTS 2mm FIN ASSY PWR WDW SW FR LH RH'],
            ['part_no' => '602-82961B00ZP', 'part_name' => 'POLYBOX 6655+PARTS 2mm FIN ASSY PWR WDW SW RR LH RH'],
            ['part_no' => '601-DAIJO3', 'part_name' => 'PP BOX BLUE (720x540x310)'],
        ];

        // Insert unique only
        foreach ($data as $row) {
            StoreBoxData::firstOrCreate(
                ['part_no' => $row['part_no']],
                ['part_name' => $row['part_name']]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\SecondProcessDefectAlias;
use App\Models\SecondProcessDefectCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecondProcessDefectCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = [
            'DIRTY' => [
                'desc' => 'Kotoran atau debu menempel pada part',
                'aliases' => [],
            ],
            'HAIR MARK' => [
                'desc' => 'Garis rambut halus pada permukaan cat/part',
                'aliases' => ['HAIRMARK', 'HAIRMARKS', 'HARI MARK', 'HAIRMAK'],
            ],
            'SCRATCH' => [
                'desc' => 'Goresan atau baret pada permukaan',
                'aliases' => ['SCRATH'],
            ],
            'DENTED' => [
                'desc' => 'Penyok atau dekok pada part',
                'aliases' => ['DANTED', 'DENTID'],
            ],
            'OVER CUT' => [
                'desc' => 'Pemotongan sisa runner/gate berlebih',
                'aliases' => ['OVERCUT'],
            ],
            'OIL' => [
                'desc' => 'Kontaminasi minyak atau bercak oli',
                'aliases' => ['0IL', 'OLI', 'OIL(BOLONG)', 'OIL/BOLONG', 'OIL BOLONG', 'BOLONG', 'MINYAK'],
            ],
            'OVER SPRAY' => [
                'desc' => 'Semprotan cat berlebih atau menyebar ke area luar',
                'aliases' => ['OVERSPRAY', 'OVERSPARY', 'OVER SPAY', 'OVER  SPRAY'],
            ],
            'UNDER SPRAY' => [
                'desc' => 'Semprotan cat tipis atau kurang menutup',
                'aliases' => [
                    'UNDERSPRAY', 'UNDER SPARY', 'UNDER SPREAY',
                    'UNFER SPRAY', 'UDER SPRAY', 'UNDER SPAY', 'UNJDER SPRAY',
                ],
            ],
            'ORANGE PEEL' => [
                'desc' => 'Tekstur permukaan seperti kulit jeruk (orange peel)',
                'aliases' => ['ORANGE SKIN', 'ORESNKIN', 'ORANGESKIN', '0RANGE SKIN', 'KULIT JERUK'],
            ],
            'BUBBLE' => [
                'desc' => 'Gelembung udara terperangkap pada lapisan cat',
                'aliases' => ['BUBLE'],
            ],
            'PEEL OFF' => [
                'desc' => 'Lapisan cat mengelupas atau tidak menempel',
                'aliases' => [
                    'FEEL OFF', 'FELL OFF', 'FEELOFF', 'PEELOOF',
                    'PELL OFF', 'FELL OF', 'FELOFF', 'FILOF', 'PERL OFF',
                ],
            ],
            'SAGGING' => [
                'desc' => 'Cat meleleh atau mengalir ke bawah',
                'aliases' => ['SAGING', 'SAG / MELELEH', 'PAINT RUN'],
            ],
            'SANDING' => [
                'desc' => 'Cacat proses amplas atau permukaan kasar',
                'aliases' => ['AMPLASAN', 'SANDINGAN'],
            ],
            'SANDING MARK' => [
                'desc' => 'Garis atau goresan bekas amplas/buffing',
                'aliases' => ['BUFF MARK', 'BUFFING MARK'],
            ],
            'OVER SANDING' => [
                'desc' => 'Pengamplasan terlalu dalam melebihi batas toleransi',
                'aliases' => ['OVER SENDING'],
            ],
            'CRACK' => [
                'desc' => 'Part retak atau patah rambut',
                'aliases' => ['CRACKING', 'CRAKING'],
            ],
            'METALIK' => [
                'desc' => 'Cacat sebaran partikel metalik atau bercak cat metalik',
                'aliases' => ['METALIC', 'METALLIK'],
            ],
            'FINGER MARK' => [
                'desc' => 'Bekas sidik jari operator pada cat basah',
                'aliases' => ['FINGER'],
            ],
            'BLISTER' => [
                'desc' => 'Lepuhan atau bintil melepuh pada cat',
                'aliases' => [],
            ],
            'BURRY' => [
                'desc' => 'Burr atau sisa cetakan plastik tajam',
                'aliases' => [],
            ],
            'FLOW MARK' => [
                'desc' => 'Garis aliran plastik bekas injeksi moulding',
                'aliases' => ['FLOWMARK', 'FLOW'],
            ],
            'DUST SPRAY' => [
                'desc' => 'Debu atau partikel kering saat spray berlangsung',
                'aliases' => ['DUST', 'DUSSPARY', 'DUYST SPRAY', 'DASH', 'DUST / DEBU'],
            ],
            'WELD LINE' => [
                'desc' => 'Garis sambungan aliran plastik moulding',
                'aliases' => ['WELDLINE', 'WEDLINE'],
            ],
            'ABSORB' => [
                'desc' => 'Cat terserap ke dalam pori-pori material',
                'aliases' => ['ABSOURD'],
            ],
            'BELUM KERING' => [
                'desc' => 'Cat belum kering sempurna atau belum matang oven',
                'aliases' => ['KERING', 'SPRAY KERING', 'BELUM MATANG'],
            ],
            'BLACK DOT' => [
                'desc' => 'Bintik hitam pada resin atau part',
                'aliases' => ['BLACKDOT'],
            ],
            'PIN MARK' => [
                'desc' => 'Bekas pin ejector moulding menonjol/tenggelam',
                'aliases' => ['PINMARK'],
            ],
            'PIN PATAH' => [
                'desc' => 'Pin locating atau cantolan patah',
                'aliases' => [],
            ],
            'POPPING' => [
                'desc' => 'Lubang jarum akibat letupan solvent mendidih (pinholing)',
                'aliases' => ['POPING'],
            ],
            'EMBOSS LESS' => [
                'desc' => 'Tekstur motif atau emboss kurang timbul',
                'aliases' => ['EMBOW LESS'],
            ],
            'BURN MARK' => [
                'desc' => 'Bekas hangus atau gosong pada material',
                'aliases' => ['GOSONG'],
            ],
            'SINK MARK' => [
                'desc' => 'Cekungan akibat penyusutan plastik moulding',
                'aliases' => ['SINKMARK', 'SHINKMARK', 'SIN MARK', 'SINMARK'],
            ],
            'BINTIK' => [
                'desc' => 'Bintik kotoran kecil pada cat',
                'aliases' => ['BRUNTUS'],
            ],
            'UNDER GLOSS' => [
                'desc' => 'Tingkat kilap kurang atau buram (kusam)',
                'aliases' => ['UNDERGLOSY', 'UNDERGLOSS', 'KUSAM'],
            ],
            'OVER GLOSS' => [
                'desc' => 'Tingkat kilap terlalu tinggi melebihi standar',
                'aliases' => ['OVER GLOS'],
            ],
            'CORROSIVE' => [
                'desc' => 'Korosi atau karat pada komponen metal',
                'aliases' => ['COROSIV'],
            ],
            'PUNCHING METAL' => [
                'desc' => 'Cacat proses pembolongan punching part metal',
                'aliases' => ['PUNCING METAL', 'PUCHING METAL'],
            ],
            'WARNA UNMATCH' => [
                'desc' => 'Warna cat belang atau tidak cocok dengan master sampel',
                'aliases' => ['WARNA UNMATCHING'],
            ],
            'BENDING' => [
                'desc' => 'Part melengkung atau bengkok',
                'aliases' => ['BANDING'],
            ],
            'PRINTING' => [
                'desc' => 'Cacat sablon, pad printing, atau cetakan huruf',
                'aliases' => [],
            ],
            'GAP' => [
                'desc' => 'Celah antar part tidak presisi',
                'aliases' => [],
            ],
            'SERAT' => [
                'desc' => 'Serat kain atau debu benang menempel',
                'aliases' => [],
            ],
        ];

        DB::beginTransaction();
        try {
            foreach ($definitions as $canonicalName => $data) {
                $catalog = SecondProcessDefectCatalog::firstOrCreate(
                    ['name' => strtoupper($canonicalName)],
                    [
                        'description' => $data['desc'] ?? null,
                        'is_active' => true,
                    ]
                );

                foreach ($data['aliases'] as $alias) {
                    $cleanAlias = strtoupper(trim($alias));
                    // Never map an alias that is already identical to the canonical name
                    if ($cleanAlias === $catalog->name) {
                        continue;
                    }

                    SecondProcessDefectAlias::firstOrCreate(
                        ['alias' => $cleanAlias],
                        ['defect_catalog_id' => $catalog->id]
                    );
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

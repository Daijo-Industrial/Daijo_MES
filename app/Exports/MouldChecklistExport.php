<?php

namespace App\Exports;

use App\Models\MouldChecklist;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MouldChecklistExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $dateFrom;
    protected $dateTo;
    protected $status;
    protected $customer;
    protected $search;

    public function __construct($dateFrom = null, $dateTo = null, $status = null, $customer = null, $search = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->customer = $customer;
        $this->search = $search;
    }

    public function collection()
    {
        $query = MouldChecklist::with(['details', 'issuedBy', 'checkedBy'])->latest('check_date');

        if ($this->dateFrom) {
            $query->where('check_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('check_date', '<=', $this->dateTo);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->customer) {
            $query->where('customer', $this->customer);
        }
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('mould_no', 'LIKE', $searchTerm)
                    ->orWhere('part_no', 'LIKE', $searchTerm)
                    ->orWhere('part_name', 'LIKE', $searchTerm)
                    ->orWhere('technician_name', 'LIKE', $searchTerm);
            });
        }

        $records = $query->get();

        return $records->map(function ($c, $idx) {
            $ngItems = $c->details->filter(fn ($d) => $d->cleaning_status === 'NG' || $d->lubricate_status === 'NG')->count();

            return [
                'No' => $idx + 1,
                'Tanggal' => $c->check_date ? $c->check_date->format('Y-m-d') : '-',
                'Doc No' => $c->doc_number . ' Rev ' . $c->revision,
                'Mould No' => $c->mould_no,
                'Part No' => $c->part_no,
                'Part Name' => $c->part_name,
                'Customer' => $c->customer,
                'Machine No' => $c->machine_number ?: '-',
                'Teknisi' => $c->technician_name,
                'Jam Kerja' => ($c->start_time && $c->end_time) ? ($c->start_time . ' - ' . $c->end_time) : '-',
                'NG Items' => $ngItems,
                'Status' => $c->status,
                'Issued By' => $c->issued_name ?: ($c->issuedBy?->name ?: '-'),
                'Issued At' => $c->issued_at ? $c->issued_at->format('Y-m-d H:i') : '-',
                'Checked By' => $c->checked_name ?: ($c->checkedBy?->name ?: '-'),
                'Checked At' => $c->checked_at ? $c->checked_at->format('Y-m-d H:i') : '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'No. Dokumen',
            'Mould No',
            'Part No',
            'Part Name',
            'Customer',
            'Mesin',
            'Teknisi',
            'Jam Kerja',
            'Jml NG',
            'Status',
            'Issued By',
            'Issued At',
            'Checked By',
            'Checked At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A8A'],
                ],
            ],
        ];
    }
}

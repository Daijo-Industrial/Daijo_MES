<?php

namespace App\Exports;

use App\Models\Delivery\DelschedFinalWip;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DelschedFinalWipExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DelschedFinalWip::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'FG Link ID',
            'Delivery Date',
            'SO Number',
            'Customer Code',
            'Customer Name',
            'Item Code',
            'Item Name',
            'Outstanding Delivery',
            'WIP Code',
            'WIP Name',
            'Department',
            'BOM Level',
            'BOM Quantity',
            'Required Quantity',
            'Stock WIP',
            'Balance WIP',
            'Outstanding WIP',
            'Packaging Code',
            'Standard Pack',
            'Packaging Quantity',
            'Document Status',
            'Status',
            'Remark',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->fglink_id,
            $row->delivery_date,
            $row->so_number,
            $row->customer_code,
            $row->customer_name,
            $row->item_code,
            $row->item_name,
            $row->outstanding_del,
            $row->wip_code,
            $row->wip_name,
            $row->departement,
            $row->bom_level,
            $row->bom_quantity,
            $row->req_quantity,
            $row->stock_wip,
            $row->balance_wip,
            $row->outstanding_wip,
            $row->packaging_code,
            $row->standar_pack,
            $row->packaging_qty,
            $row->doc_status,
            $row->status,
            $row->remark,
        ];
    }
}

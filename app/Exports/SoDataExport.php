<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SoDataExport extends DefaultValueBinder implements FromCollection, WithCustomValueBinder
{
    use Exportable;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    /**
     * Bind value ensuring strings with trailing dots are exported explicitly as string.
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && str_ends_with($value, '.')) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}

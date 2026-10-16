<?php

namespace App\DataTables;

use App\Models\CapLineSummary;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CapLineSummaryDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('status', function ($row) {
                return $row->capacity_req_percent > 100
                    ? 'Danger'
                    : 'Normal';
            })
            ->addColumn('action', 'caplinesummary.action')
            ->setRowId('id')
            ->rawColumns(['status'])
            ->setRowAttr([
                'class' => function ($row) {
                    return $row->capacity_req_percent > 100 ? 'text-red-500' : 'text-green-500';
                },
            ]);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(CapLineSummary $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('caplinesummary-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('<"flex justify-between items-center mb-4"lfB>t<"flex justify-between items-center mt-4"ip>')
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload'),
            ])
            ->language([
                'paginate' => [
                    'previous' => '←',
                    'next' => '→',
                ],
                'search' => 'Search:',
                'lengthMenu' => 'Show _MENU_ entries',
                'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
            ])
            ->parameters([
                'columnDefs' => [
                    ['className' => 'px-4 py-2 border', 'targets' => '_all'], // Tailwind padding for all columns
                ],
                'pageLength' => 25, // Default to show 25 rows
                'lengthMenu' => [10, 25, 50, 100],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id'),
            Column::make('department'),
            Column::make('line_category'),
            Column::make('line_quantity'),
            Column::make('work_day'),
            Column::make('ready_time'),
            Column::make('efficiency'),
            Column::make('max_capacity'),
            Column::make('capacity_req_hour'),
            Column::make('capacity_req_percent'),
            Column::make('status'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'CapLineSummary_' . date('YmdHis');
    }
}

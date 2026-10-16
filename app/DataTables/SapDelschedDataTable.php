<?php

namespace App\DataTables;

use App\Models\Delivery\SapDelsched;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SapDelschedDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'sapdelsched.action')
            ->setRowId('id');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\SapDelsched $model
     */
    public function query(SapDelsched $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('sapdelsched-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('<"flex justify-between items-center mb-4"lf>t<"flex justify-between items-center mt-4"ip>')
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
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('item_code'),
            Column::make('delivery_date'),
            Column::make('delivery_qty'),
            Column::make('so_number'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'SapDelsched_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\InvLineList;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class InvLineListDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($data) {
                // Generate dynamic modal IDs based on line_code
                $editModalId = 'edit-line-modal' . str_replace(' ', '', $data->line_code);
                $deleteModalId = 'delete-confirmation-modal-' . str_replace(' ', '', $data->line_code);

                // Generate buttons with the dynamic modal IDs
                return '
                <button onclick="openEditModal(\'' . $editModalId . '\')" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                    <i class="bx bx-edit">Edit</i>
                </button>
                <button onclick="openDeleteModal(\'' . $deleteModalId . '\')" class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 ml-2">
                    <i class="bx bx-trash">Delete</i>
                </button>
                ';
            })
            ->setRowId('id');
    }

    /**
     * Get query source of dataTable.
     */
    public function query(InvLineList $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('invlinelist-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('<"flex justify-between items-center mb-4"lfB>t<"flex justify-between items-center mt-4"ip>')
            ->orderBy(1)
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
            Column::make('line_code'),
            Column::make('line_name'),
            Column::make('category'),
            Column::make('area'),
            Column::make('departement'),
            Column::make('daily_minutes'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center')
                ->addClass('align-middle'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'InvLineList_' . date('YmdHis');
    }
}

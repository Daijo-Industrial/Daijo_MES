<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MES Operating Timezone (UTC+7 / WIB)
    |--------------------------------------------------------------------------
    |
    | Define the primary operational timezone for shop floor logs and shifts.
    |
    */
    'timezone' => env('APP_TIMEZONE'),

    /*
    |--------------------------------------------------------------------------
    | Factory Shift Schedules
    |--------------------------------------------------------------------------
    |
    | Define the start and end times for each shift.
    | This is used by dashboards to auto-detect the current active shift.
    | Times should be in 'H:i' format.
    |
    */
    'shifts' => [
        1 => [
            'name' => 'Shift 1',
            'start' => '07:30',
            'end' => '15:30',
        ],
        2 => [
            'name' => 'Shift 2',
            'start' => '15:30',
            'end' => '23:30',
        ],
        3 => [
            'name' => 'Shift 3',
            'start' => '23:30',
            'end' => '07:30',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Active Shifts
    |--------------------------------------------------------------------------
    |
    | Define active operating shifts for Second Process (1 to 2 shifts).
    |
    */
    'sp_shifts' => [
        1 => [
            'name'  => 'Shift 1',
            'start' => '07:30',
            'end'   => '15:30',
        ],
        2 => [
            'name'  => 'Shift 2',
            'start' => '15:30',
            'end'   => '23:30',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Production Lines
    |--------------------------------------------------------------------------
    |
    | Centralized mapping of URL slugs to display names for SP shop floor lines.
    |
    */
    'sp_lines' => [
        'line-a'    => 'Line A',
        'line-b'    => 'Line B',
        'line-c'    => 'Line C',
        'line-d'    => 'Line D',
        'buffing-1' => 'Buffing 1',
        'buffing-2' => 'Buffing 2',
        'buffing-3' => 'Buffing 3',
        'buffing-4' => 'Buffing 4',
        'buffing-5' => 'Buffing 5',
        'buffing-6' => 'Buffing 6',
        'buffing-7' => 'Buffing 7',
        'buffing-8' => 'Buffing 8',
        'buffing-9' => 'Buffing 9',
        'buffing-10' => 'Buffing 10',
        'buffing-11' => 'Buffing 11',
        'amplas'    => 'Area Amplas/Treatment',
        'packing-1' => 'Packing 1',
        'packing-2' => 'Packing 2',
        'packing-3' => 'Packing 3',
        'assy'      => 'Area Assy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chemical / Material Parameter Processes
    |--------------------------------------------------------------------------
    |
    | Processes that require Paint Code, Thinner Code, Ink Code, and Viscosity
    | fields in First Piece Inspection forms.
    |
    */
    'chemical_processes' => [
        'Painting',
        'Repair'
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Manpower Roles
    |--------------------------------------------------------------------------
    |
    | Standard worker roles/positions for Second Process production lines,
    | aligned with legacy SecondProcessReport manpower schema.
    |
    */
    'sp_manpower_roles' => [
        'loading'  => 'Loading / Input',
        'sprayer'  => 'Sprayer',
        'checker'  => 'Checker',
        'qc'       => 'QC',
        'packing'  => 'Packing',
        'operator' => 'Operator',
        'leader'   => 'Leader',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Production Processes
    |--------------------------------------------------------------------------
    |
    | Standard production processes for Second Process operations.
    |
    */
    'sp_processes' => [
        'Painting',
        'Painting Basecoat',
        'Painting Topcoat',
        'Buffing',
        'Amplas',
        'Treatment',
        'Packing',
        'Rework',
        'Repair',
        'Assy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Output Destinations
    |--------------------------------------------------------------------------
    |
    | Destination step after current Second Process completes.
    |
    */
    'sp_output_destinations' => [
        'fg'           => 'Finished Goods (FG)',
        'buffing'      => 'Buffing',
        'next_process' => 'Next Process Area',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Default Paint Materials
    |--------------------------------------------------------------------------
    |
    | Initial default paint preparation items for new reports / closeout.
    |
    */
    'sp_default_paint_materials' => [
        'Paint',
        'Paint Basecoat',
        'Paint Topcoat',
        'Hardener',
        'Thinner',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Default Part / WIP Materials
    |--------------------------------------------------------------------------
    |
    | Initial fallback WIP/repairan lot items for new reports / closeout.
    |
    */
    'sp_default_part_materials' => [
        'WIP 1',
        'WIP 2',
        'WIP 3',
        'Repairan 1',
        'Repairan 2',
        'Repairan 3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Default NG (Defect) Types
    |--------------------------------------------------------------------------
    |
    | Default defect column headers for Second Process hourly quality tracking.
    |
    */
    'sp_default_ng_types' => [
        'SCRATCH',
        'DIRTY',
        'HAIR MARK',
        'DENTED',
        'OVER CUT',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Trouble / Downtime Categories
    |--------------------------------------------------------------------------
    |
    | Standard categories for incident-based downtime and problem tracking.
    |
    */
    'sp_trouble_categories' => [
        'Man'        => 'Man',
        'Mesin'      => 'Mesin',
        'Part'       => 'Part',
        'PPS'        => 'PPS',
        'Lingkungan' => 'Lingkungan',
        'Other'      => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process NG Remark Preset Categories
    |--------------------------------------------------------------------------
    |
    | Standardized categories for NG root cause / defect source breakdown.
    | Standardized in UPPERCASE with hyphen ('-') format for consistent data-processing.
    |
    */
    'sp_ng_remark_categories' => [
        'NG-INPUT'  => 'NG-INPUT',
        'NG-PROSES' => 'NG-PROSES',
    ],

    /*
    |--------------------------------------------------------------------------
    | Second Process Default Target NG Rate (%)
    |--------------------------------------------------------------------------
    |
    | Baseline NG percentage threshold line for analytics charts and KPI alerts.
    |
    */
    'sp_target_ng_rate' => 2.0,
];


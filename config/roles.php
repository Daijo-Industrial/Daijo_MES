<?php

return [
    'hierarchy' => [
        'SUPER-ADMIN' => ['SUPER-ADMIN', 'ADMIN', 'WORKSHOP', 'WAREHOUSE', 'OPERATOR', 'PE', 'STORE', 'PPIC', 'MAINTENANCE', 'SECONDPROCESS', 'ASSEMBLYPROCESS', 'BUSINESS', 'PRODUCTION', 'QUALITY', 'CHECKER', 'LEADER', 'SUPERVISOR'],
        'ADMIN' => ['ADMIN', 'WORKSHOP', 'WAREHOUSE', 'OPERATOR', 'PE', 'STORE', 'PPIC', 'MAINTENANCE', 'SECONDPROCESS', 'ASSEMBLYPROCESS', 'BUSINESS', 'PRODUCTION', 'QUALITY', 'CHECKER', 'LEADER', 'SUPERVISOR'],
        'WORKSHOP' => ['WORKSHOP'],
        'WAREHOUSE' => ['WAREHOUSE'],
        'OPERATOR' => ['OPERATOR'],
        'PE' => ['PE'],
        'STORE' => ['STORE'],
        'PPIC' => ['PPIC'],
        'MAINTENANCE' => ['MAINTENANCE'],
        'SECONDPROCESS' => ['SECONDPROCESS'],
        'ASSEMBLYPROCESS' => ['ASSEMBLYPROCESS'],
        'BUSINESS' => ['BUSINESS'],
        'PRODUCTION' => ['PRODUCTION'],
        'QUALITY' => ['QUALITY'],
        'CHECKER' => ['CHECKER'],
        'LEADER' => ['LEADER'],
        'SUPERVISOR' => ['SUPERVISOR'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Signature Role Mapping
    |--------------------------------------------------------------------------
    |
    | Configurable mapping defining which user roles are permitted to sign
    | each signature slot/step across MES domains.
    |
    | For Second Process Daily Reports:
    |   - 'checker': Initial report submitter / operator
    |   - 'leader': Production team leader
    |   - 'pqc': Quality control lane inspector (optional)
    |   - 'acknowledged': Production supervisor / section head
    |
    | Note: 'SUPER-ADMIN' and 'ADMIN' always possess universal signing permission.
    |
    */
    'signature_mapping' => [
        'second_process' => [
            'checker'      => ['CHECKER', 'ADMIN', 'SUPER-ADMIN'],
            'leader'       => ['LEADER', 'ADMIN', 'SUPER-ADMIN'],
            'pqc'          => ['QUALITY', 'ADMIN', 'SUPER-ADMIN'],
            'acknowledged' => ['SUPERVISOR', 'ADMIN', 'SUPER-ADMIN'],
        ],
        'first_piece' => [
            'prepared' => ['OPERATOR', 'CHECKER', 'ADMIN', 'SUPER-ADMIN'],
            'checked'  => ['QUALITY', 'ADMIN', 'SUPER-ADMIN'],
            'approved' => ['QUALITY', 'LEADER', 'ADMIN', 'SUPER-ADMIN'],
        ],
    ],
];

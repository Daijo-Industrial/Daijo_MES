<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;

class MachineStatusController extends Controller
{
    /**
     * Display the machine status monitoring dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('production.machine-status');
    }
}

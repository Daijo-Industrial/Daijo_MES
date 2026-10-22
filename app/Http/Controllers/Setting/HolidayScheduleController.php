<?php

namespace App\Http\Controllers\Setting;

use App\Exports\HolidayScheduleExport;
use App\Http\Controllers\Controller;
use App\Imports\HolidayScheduleImport;
use App\Models\Setting\HolidaySchedule;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HolidayScheduleController extends Controller
{
    public function index()
    {
        $datas = HolidaySchedule::all();

        return view('setting.holiday-schedule.index', compact('datas'));
    }

    public function create()
    {
        // Return the view for creating a new holiday schedule
        return view('setting.holiday-schedule.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'injection' => 'required|string|max:255',
            'second_process' => 'required|string|max:255',
            'assembly' => 'required|string|max:255',
            'moulding' => 'required|string|max:255',
            'half_day' => 'required|in:0,1',
        ]);

        HolidaySchedule::create($request->all());

        return redirect()->route('setting.holiday-schedule.index')
            ->with('success', 'Holiday schedule created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'injection' => 'required|string',
            'second_process' => 'required|string',
            'assembly' => 'required|string',
            'moulding' => 'required|string',
            'half_day' => 'required|in:0,1',
        ]);

        $data = HolidaySchedule::findOrFail($id);
        $data->update([
            'description' => $request->description,
            'injection' => $request->injection,
            'second_process' => $request->second_process,
            'assembly' => $request->assembly,
            'moulding' => $request->moulding,
            'half_day' => (int) $request->half_day,
        ]);

        return redirect()->route('setting.holiday-schedule.index')->with('success', 'Holiday schedule updated successfully');
    }

    public function export()
    {
        // Trigger the export
        return Excel::download(new HolidayScheduleExport, 'holiday_schedule.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        // Import the data
        Excel::import(new HolidayScheduleImport, $request->file('file'));

        return redirect()->route('setting.holiday-schedule.index')->with('success', 'Holiday schedule data imported successfully.');
    }
}

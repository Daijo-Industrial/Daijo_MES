<?php

namespace App\Http\Controllers;

use App\Models\OperatorUser;
use App\Models\MasterZone;
use App\Models\ZoneLog;
use App\Models\ZonePengawas;
use App\Models\User;
use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use App\Imports\OperatorUsersImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;


class OperatorUserController extends Controller
{
    public function showQr()
    {
        // Retrieve all operator users
        $users = OperatorUser::all();

        // Initialize an empty array for QR codes
        $qrCodes = [];

        // Loop through each user to generate QR codes
        foreach ($users as $user) {
            $name = $user->name;
            $password = $user->password;

            // Prepare the QR code content (name and password separated by tab)
            $barcodeData = $name . "\t" . $password;

            // Create a new QR code with specified parameters
            $qrCode = new QrCode(
                data: $barcodeData,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 100,  // Adjust the size as needed
                margin: 5    // Adjust the margin as needed
            );

            // Generate the QR code using the PngWriter
            $writer = new PngWriter();
            $qrCodeResult = $writer->write($qrCode);

            // Get the PNG image as a string
            $qrCodeImage = $qrCodeResult->getString();

            // Base64 encode the image to embed in HTML
            $qrcoded = base64_encode($qrCodeImage);

            // Store the name and the generated QR code data URL
            $qrCodes[] = [
                'name' => $name,
                'qrCode' => 'data:image/png;base64,' . $qrcoded,
            ];
        }

        // Pass the QR codes and user names to the view
        return view('barcode.qr_operator', ['qrCodes' => $qrCodes]);
    }

    function getNikByName($name, $employeeData)
    {
        foreach ($employeeData as $employee) {
            if (strtolower(trim($employee['Name'])) === strtolower(trim($name))) {
                return $employee['NIK']; // asumsikan NIK = EMPLOYEE_NO
            }
        }
        return null;
    }

    public function showIdCard()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic QVBJPUV4VCtEQCFqMDpEQCFqMEBKcDR5cjAxMQ=='
        ])->post('http://192.168.6.75/JPayroll/thirdparty/ext/API_View_Master_Employee.php', [
            'CompanyArea' => '10000'
        ]);

        $data = $response->json();
        $users = OperatorUser::all();

        // 🔥 STEP 1: group employee by name + ambil NIK terbaru
        $employeeMap = collect($data['data'] ?? [])
            ->filter(function ($emp) {
                return isset($emp['Name'], $emp['NIK'], $emp['StartDate']);
            })
            ->groupBy(function ($emp) {
                return strtolower(trim($emp['Name']));
            })
            ->map(function ($group) {
                return $group->sortByDesc(function ($emp) {
                    try {
                        return Carbon::createFromFormat('d/m/Y', $emp['StartDate']);
                    } catch (\Exception $e) {
                        return Carbon::create(1900, 1, 1);
                    }
                })->first()['NIK'] ?? null;
            });

        $qrCodes = [];

        // 🔥 STEP 2: generate QR + ambil NIK dari map (O(1))
        foreach ($users as $user) {
            $nameKey = strtolower(trim($user->name));
            $nik = $employeeMap[$nameKey] ?? 'NIK Not Found';

            $barcodeData = $user->name . "\t" . $user->password;

            $qrCode = new QrCode(
                data: $barcodeData,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 100,
                margin: 5
            );

            $writer = new PngWriter();
            $qrCodeResult = $writer->write($qrCode);

            $qrCodeImage = $qrCodeResult->getString();
            $qrcoded = base64_encode($qrCodeImage);

            $qrCodes[] = [
                'name' => $user->name,
                'qrCode' => 'data:image/png;base64,' . $qrcoded,
                'photo' => $user->profile_picture,
                'role' => $user->position,
                'department' => $user->department,
                'nik' => $nik,
            ];
        }

        return view('barcode.id_card', compact('qrCodes'));
    }

    public function index()
    {
        $users = OperatorUser::all();
        return view('operator.index', compact('users'));
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:operator_user,id',
            'profile_picture' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = OperatorUser::findOrFail($request->user_id);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('profile_pictures', $filename, 'public');

            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Update user's profile picture
            $user->update(['profile_picture' => $path]);
        }

        return back()->with('success', 'Profile picture updated successfully.');
    }


    public function uploadForm()
    {
        return view('uploadExcelOperator');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new OperatorUsersImport, $request->file('file'));

        return redirect()->back()->with('success', 'Users imported successfully!');
    }

    public function editZone()
    {
        $zones = MasterZone::all();
        $zoneData = ZonePengawas::all();
        $adjusters = OperatorUser::where('position', 'Adjuster')->get();
    
        return view('zonepengawas', compact('zones', 'adjusters', 'zoneData'));
    }

    public function updateZone(Request $request)
    {
        $request->validate([
            'zone_id' => 'required|exists:master_zone,id',
            'pengawas' => 'required|exists:operator_user,name',
            'shift' => 'required|in:1,2,3',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

         // Check if the record exists for this zone and shift
            $zonePengawas = ZonePengawas::where('zone_id', $request->zone_id)
                ->where('shift', $request->shift)
                ->first();

            if ($zonePengawas) {
            // Update existing record
                $zonePengawas->update([
                'pengawas' => $request->pengawas,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                ]);
            } else {
                // Create new record
                ZonePengawas::create([
                'zone_id' => $request->zone_id,
                'pengawas' => $request->pengawas,
                'shift' => $request->shift,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                ]);
            }

            ZoneLog::create([
                'zone_id' => $request->zone_id,
                'pengawas' => $request->pengawas,
                'shift' => $request->shift,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
    

        return redirect()->back()->with('success', 'Zone updated successfully.');
    }

    public function showAllOperator()
    {
        $operators = OperatorUser::whereNotBetween('id', [1, 10])->get();
        return view('showalloperator', compact('operators'));
    }

    public function createOperator()
    {
        $departments = ['390', '351'];
        $positions = ['Operator', 'Adjuster', 'Setup Mold', 'Maintenance'];

        return view('create-operator', compact('departments', 'positions'));
    }

    public function storeOperator(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|in:390,351',
            'position' => 'required|in:Operator,Adjuster,Setup Mold,Maintenance',
        ]);

        $password = Str::random(10);

        OperatorUser::create([
            'name' => $request->name,
            'password' => $password,
            'department' => $request->department,
            'position' => $request->position,
        ]);

        return redirect()->route('show.all.operators')->with('success', 'Operator berhasil ditambahkan!');
    }
}

<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Imports\BillOfMaterialChildImport;
use App\Models\File;
use App\Models\Production\PRD_BillOfMaterialChild;
use App\Models\Production\PRD_BillOfMaterialParent;
use App\Models\Production\PRD_BrokenChild;
use App\Models\Production\PRD_ListAllMasterItem;
use App\Models\Production\PRD_MaterialLog;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BillOfMaterialController extends Controller
{
    public function index()
    {
        $bomParentsQuery = PRD_BillOfMaterialParent::with('children');
        // dd($datas);
        $user = auth()->user();

        if ($user->role->name === 'WAREHOUSE') {
            $bomParentsQuery->whereHas('children', function ($query) {
                $query->where('action_type', 'buyfinish')->orWhere('action_type', 'buyprocess');
            });
        }

        $bomParents = $bomParentsQuery->get();

        return view('production.bom.index', compact('bomParents', 'user'));
    }

    public function create()
    {
        $datas = PRD_ListAllMasterItem::get();

        return view('production.bom.create', compact('datas'));
    }

    public function destroy($id)
    {
        // Find the parent BOM record
        $parent = PRD_BillOfMaterialParent::findOrFail($id);

        // Delete all associated child records
        $parent->children()->delete(); // Assuming you have a relationship defined in the model

        // Delete the parent record
        $parent->delete();

        return redirect()->route('production.bom.index')->with('success', 'BOM and its children deleted successfully.');
    }

    public function show($id)
    {
        $user = auth()->user();
        $bomParent = PRD_BillOfMaterialParent::with('children', 'children.materialProcess', 'children.brokenChild')->findOrFail($id);
        $actionTypeCounts = $bomParent->children->groupBy('action_type')->map(function ($group) {
            return $group->count();
        });

        $childrenQuery = PRD_BillOfMaterialChild::with('materialProcess', 'brokenChild')->where('parent_id', $bomParent->id);
        if ($user->role->name === 'WAREHOUSE') {
            $childrenQuery->where(function ($query) {
                $query->where('action_type', 'buyfinish')
                    ->orWhere('action_type', 'buyprocess');
            });
        }

        $children = $childrenQuery->get();
        // dd($nonWarehouseChildren);)

        return view('production.bom.detail', compact('bomParent', 'user', 'actionTypeCounts', 'children'));
    }

    public function update(Request $request, $id)
    {
        $bomParent = PRD_BillOfMaterialParent::findOrFail($id);

        $bomParent->update($request->only(['code', 'description', 'type', 'customer']));

        return redirect()->route('production.bom.show', $id)->with('success', 'BOM Parent updated successfully.');
    }

    public function updateChild(Request $request, $id)
    {
        // Validate input data
        $request->validate([
            'item_code' => 'required|string|max:255',
            'item_description' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'measure' => 'required|string|max:50',
        ]);

        // Find the child item by ID
        $childItem = PRD_BillOfMaterialChild::findOrFail($id);

        // Update the child item fields
        $childItem->item_code = $request->item_code;
        $childItem->item_description = $request->item_description;
        $childItem->quantity = $request->quantity;
        $childItem->measure = $request->measure;
        $childItem->save();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Child item updated successfully!');
    }

    public function cancel($id)
    {
        $child = PRD_BillOfMaterialChild::findOrFail($id);
        $child->status = 'Canceled'; // Or any other field indicating cancellation
        $child->save();

        return redirect()->back()->with('success', 'Material has been canceled successfully.');
    }

    public function destroyChild($id)
    {
        // Find the child item by its ID
        $childItem = PRD_BillOfMaterialChild::find($id);

        // Check if the item exists
        if ($childItem) {
            $childItem->delete(); // Delete the item

            return redirect()->back()->with('success', 'Child item deleted successfully.');
        }

        return redirect()->back()->with('error', 'Child item not found.');
    }

    public function store(Request $request)
    {
        // dd($request->all());
        // Validate common fields
        $validated = $request->validate([
            'code' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|string|in:production,moulding',
            'customer' => 'required|string',
            'excel_file' => 'nullable|file',
        ]);
        // dd($validated);

        // Create the parent BOM record
        $parent = PRD_BillOfMaterialParent::create([
            'code' => $validated['code'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'customer' => $validated['customer'],
        ]);

        // Check if an Excel file is uploaded
        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');

            // Load the Excel file
            $spreadsheet = IOFactory::load($file->getPathName());
            $sheet = $spreadsheet->getActiveSheet();

            // Get the highest row and column numbers referenced in the sheet
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            // Loop through the rows, starting from the second row (since we skip the first row)
            for ($row = 2; $row <= $highestRow; $row++) {
                // Read cells in the row, excluding the first column (A)
                $childItemCode = $sheet->getCell(Coordinate::stringFromColumnIndex(2) . $row)->getValue(); // Column B
                $childItemDescription = $sheet->getCell(Coordinate::stringFromColumnIndex(3) . $row)->getValue(); // Column C
                $quantity = $sheet->getCell(Coordinate::stringFromColumnIndex(4) . $row)->getValue(); // Column D
                $measure = $sheet->getCell(Coordinate::stringFromColumnIndex(5) . $row)->getValue(); // Column E

                // Skip empty rows
                if (empty($childItemCode) || empty($childItemDescription)) {
                    continue;
                }
                // Create the child record
                PRD_BillOfMaterialChild::create([
                    'parent_id' => $parent->id,
                    'code' => $childItemCode,
                    'description' => $childItemDescription,
                    'quantity' => $quantity,
                    'measure' => $measure,
                ]);
            }
        } else {
            // Validate manual input fields for child items
            $validated = $request->validate([
                'child_item_code' => 'required|array|min:1',
                'child_item_code.*' => 'required|string',
                'child_item_description' => 'required|array|min:1',
                'child_item_description.*' => 'required|string',
                'quantity' => 'required|array|min:1',
                'quantity.*' => 'required|numeric',
                'measure' => 'required|array|min:1',
                'measure.*' => 'required|string',
            ]);

            // Loop through manually inputted child items and create records
            foreach ($validated['child_item_code'] as $key => $child_item_code) {
                PRD_BillOfMaterialChild::create([
                    'parent_id' => $parent->id,
                    'item_code' => $child_item_code,
                    'item_description' => $validated['child_item_description'][$key],
                    'quantity' => $validated['quantity'][$key],
                    'measure' => $validated['measure'][$key],
                ]);
            }
        }

        // Redirect back or to the BOM index page
        return redirect()->route('production.bom.index')->with('success', 'BOM created successfully');
    }

    public function storeChild(Request $request, PRD_BillOfMaterialParent $bomParent)
    {
        // dd($bomParent);
        $request->validate([
            'child.*.item_code' => 'required|string',
            'child.*.item_description' => 'required|string',
            'child.*.quantity' => 'required|numeric',
            'child.*.measure' => 'required|string',
        ]);

        foreach ($request->child as $childData) {
            // Add the parent_id to the child data before creating the child record
            $childData['parent_id'] = $bomParent->id;

            // Create the child record
            $bomParent->children()->create($childData);
        }

        return redirect()->route('production.bom.show', $bomParent)->with('success', 'Child items added successfully!');
    }

    public function uploadChildItems(Request $request, $bomParentId)
    {
        $bomParent = PRD_BillOfMaterialParent::findOrFail($bomParentId);

        // Check if the file was uploaded
        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');

            // Load the Excel file
            $spreadsheet = IOFactory::load($file->getPathName());
            $sheet = $spreadsheet->getActiveSheet();

            // Get the highest row and column numbers referenced in the sheet
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            // Loop through the rows, starting from the second row (since we skip the first row)
            for ($row = 2; $row <= $highestRow; $row++) {
                // Read cells in the row, excluding the first column (A)
                $childItemCode = $sheet->getCell(Coordinate::stringFromColumnIndex(2) . $row)->getValue(); // Column B
                $childItemDescription = $sheet->getCell(Coordinate::stringFromColumnIndex(3) . $row)->getValue(); // Column C
                $quantity = $sheet->getCell(Coordinate::stringFromColumnIndex(4) . $row)->getValue(); // Column D
                $measure = $sheet->getCell(Coordinate::stringFromColumnIndex(5) . $row)->getValue(); // Column E

                // Skip empty rows
                if (empty($childItemCode) || empty($childItemDescription)) {
                    continue;
                }

                // Create the child record
                PRD_BillOfMaterialChild::create([
                    'parent_id' => $bomParent->id,
                    'item_code' => $childItemCode,
                    'item_description' => $childItemDescription,
                    'quantity' => $quantity,
                    'measure' => $measure,
                ]);
            }

            return redirect()->route('production.bom.show', $bomParent->id)
                ->with('success', 'Child items uploaded successfully.');
        }

        return redirect()->route('production.bom.show', $bomParent->id)
            ->with('error', 'No file selected.');
    }

    public function assignType(Request $request, $childId)
    {
        $child = PRD_BillOfMaterialChild::findOrFail($childId);
        $child->action_type = $request->input('action_type');
        if ($child->action_type === 'stockfinish') {
            $child->status = 'Finished';
        }
        $child->save();

        return redirect()->back()->with('success', 'Action type assigned successfully.');
    }

    public function updateStatusChild($id)
    {
        // Find the child item by ID
        $child = PRD_BillOfMaterialChild::findOrFail($id);

        if ($child->action_type === 'buyfinish') {
            $child->status = 'Finished';
        } else {
            $child->status = 'Available';
        }
        // Update the status to "Available"
        $child->save();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Item status updated to Available.');
    }

    public function assignProcess(Request $request, $id)
    {
        // Retrieve the processes from the request
        $processes = $request->input('all_process');

        // Validate if processes are provided
        if (empty($processes)) {
            return redirect()->back()->with('error', 'No processes were selected.');
        }

        $child = PRD_BillOfMaterialChild::find($id);
        if ($child->status === 'Finished') {
            $child->status = 'Finished - Modified';
            $child->save(); // Save the updated status
        }

        // Loop through each process and insert it into the PRD_MaterialLog table
        foreach ($processes as $process) {
            PRD_MaterialLog::create([
                'child_id' => $id,           // The child ID passed to the method
                'process_name' => $process,   // The process name from the form
                'scan_in' => null,            // Set default value or leave as null
                'scan_out' => null,           // Set default value or leave as null
                // Status will use the default value of 0 in the database
            ]);
        }

        return redirect()->back()->with('success', 'Processes assigned successfully.');
    }

    public function materialDetail($id)
    {

        // Retrieve the child and its related material processes
        $child = PRD_BillOfMaterialChild::with([
            'materialProcess' => function ($query) {
                $query->orderByRaw('scan_in IS NULL, scan_in ASC');
            },
            'parent',
            'materialProcess.workers',
            'brokenChild',
        ])->findOrFail($id);

        $temp = $child->parent->id;

        $barcodeData = $child->item_code . '~' . $child->id; // Item Code and ID separated by ~

        // Generate the barcode PNG content
        $barcodePNG = DNS1D::getBarcodePNG($barcodeData, 'C128', 3, 10);

        // Define the file name and path
        $fileName = 'barcode_' . $child->item_code . '_' . $child->id . '.png';
        $filePath = 'public/barcode/' . $fileName;

        // Save the barcode image to storage
        Storage::put($filePath, base64_decode($barcodePNG));

        // Get the URL for the barcode image
        $barcodeUrl = asset('storage/barcode/' . $fileName);

        $image = File::where('item_code', $child->item_code)->get();

        $qrCodeWriter = new PngWriter;
        $qrcoded = null;

        $qrCode = new QrCode(
            data: $barcodeData,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 100,
            margin: 5
        );

        $writer = new PngWriter;
        $qrCodeResult = $writer->write($qrCode);

        // Get the PNG image as a string
        $qrCodeImage = $qrCodeResult->getString();

        // Base64 encode the image to embed in HTML
        $qrcoded = base64_encode($qrCodeImage);

        // Pass the data to the view
        return view('production.bom.child_detail', compact('child', 'barcodeUrl', 'temp', 'image', 'qrcoded'));
    }

    public function printAllMaterial($id)
    {
        $bomParent = PRD_BillOfMaterialParent::with('children')->findOrFail($id);

        $writer = new PngWriter;

        $childrenWithDetails = $bomParent->children->map(function ($child) use ($writer) {

            $barcodeData = $child->item_code . '~' . $child->id;
            // Fetch the image for the child using item_code
            $image = File::where('item_code', $child->item_code)->first();

            // Generate QR code for the child (using item_code or any other relevant data)
            $qrCode = new QrCode(data: $barcodeData, errorCorrectionLevel: ErrorCorrectionLevel::Medium, size: 70, margin: 2);
            $qrCodeResult = $writer->write($qrCode);

            // Get the PNG image as a string and base64 encode it for embedding in HTML
            $qrCodeImage = $qrCodeResult->getString();
            $qrcoded = base64_encode($qrCodeImage);

            // Add the image to the child data (if it exists)
            $child->barcode = $qrcoded;
            $child->image_url = $image ? asset('storage/files/' . $image->name) : null; // Assuming 'path' holds the file path

            return $child;
        });
        // dd($childrenWithDetails);

        return view('production.bom.allmaterialsprint', compact('bomParent', 'childrenWithDetails'));
    }

    public function addBrokenQuantity(Request $request, $childId)
    {
        $request->validate([
            'broken_quantity' => 'required|integer|min:0',
            'remark' => 'nullable|string|max:255',
        ]);

        $child = PRD_BillOfMaterialChild::findOrFail($childId);

        // Calculate the total existing broken quantity for the child_id
        $existingBrokenQuantity = PRD_BrokenChild::where('child_id', $childId)->sum('broken_quantity');

        // Validation: Check if the total broken quantity will exceed the child's available quantity
        $newTotalQuantity = $existingBrokenQuantity + $request->input('broken_quantity');
        if ($newTotalQuantity > $child->quantity) {
            return redirect()->back()->withErrors(['broken_quantity' => 'The total broken quantity exceeds the available quantity.']);
        }

        // Insert the new broken quantity into the PRD_BrokenChild table
        PRD_BrokenChild::create([
            'child_id' => $childId,
            'broken_quantity' => $request->input('broken_quantity'),
            'remark' => $request->input('remark'),
        ]);

        return redirect()->back()->with('success', 'Broken quantity added successfully.');
    }

    public function getItemCodes(Request $request)
    {
        $query = $request->get('query');

        if (! $query) {
            return response()->json([]);
        }

        $items = PRD_ListAllMasterItem::where('item_code', 'LIKE', "%{$query}%")
            ->select('item_code', 'item_description', 'uom')
            ->get();

        return response()->json($items);
    }

    public function destroyProcess($id)
    {
        // Find the process by ID
        $process = PRD_MaterialLog::find($id);

        // Check if the process exists
        if (! $process) {
            return redirect()->back()->with('error', 'Process not found.');
        }

        // Check if the process can be deleted (no scan_in)
        if ($process->scan_in) {
            return redirect()->back()->with('error', 'Cannot delete a process that has already started.');
        }

        // Delete the process
        $process->delete();

        return redirect()->back()->with('success', 'Process deleted successfully.');
    }

    public function dashboardTv()
    {
        $parents = PRD_BillOfMaterialParent::with(['children.materialProcess.workers'])->get();

        $projectProgress = [];
        $distinctUsers = [];

        // Loop through each parent project
        foreach ($parents as $parent) {
            $totalChildren = $parent->children->count(); // Total children in the parent project
            $completedChildren = 0;
            $usersInvolved = [];

            // Loop through each child project
            foreach ($parent->children as $child) {
                // Check if the child status is 'Finished' or 'Finished - Modified'
                if ($child->status === 'Finished' || $child->status === 'Finished - Modified') {
                    // Consider the child as completed if the status is Finished or Finished - Modified
                    $completedChildren++;

                    // Collect distinct users involved in this child's processes (if it's already marked as completed)
                    foreach ($child->materialProcess as $materialLog) {
                        foreach ($materialLog->workers as $worker) {
                            // Ensure to store distinct usernames
                            $usersInvolved[$worker->username] = $worker->username;
                        }
                    }
                    continue; // Skip the process counting for this child, since it's already considered completed
                }

                // If the child status is not 'Finished' or 'Finished - Modified', continue with the process count
                $totalProcesses = $child->materialProcess->count(); // Total processes (material logs) for the child
                $completedProcesses = $child->materialProcess->filter(function ($log) {
                    // Assuming you have a 'status' or 'completed' field in the material log to determine completion
                    return $log->status == '2';
                })->count();

                // Calculate child completion percentage
                if ($totalProcesses > 0) {
                    $childCompletion = ($completedProcesses / $totalProcesses) * 100;
                } else {
                    $childCompletion = 0;
                }

                // Increment completed children counter if all processes are completed
                if ($childCompletion == 100) {
                    $completedChildren++;
                }

                // Collect distinct users involved in this child's processes
                foreach ($child->materialProcess as $materialLog) {
                    foreach ($materialLog->workers as $worker) {
                        // Ensure to store distinct usernames
                        $usersInvolved[$worker->username] = $worker->username;
                    }
                }
            }

            // Calculate the overall project completion
            $projectCompletion = ($completedChildren / $totalChildren) * 100;

            // Store the progress for each parent project
            $projectProgress[] = [
                'parent_id' => $parent->id,
                'project_name' => $parent->code,
                'completion' => $projectCompletion,
            ];

            // Store the distinct number of users for each parent project
            $distinctUsers[$parent->id] = count($usersInvolved); // Count the unique users involved
        }

        // Pass the data to the view
        return view('dashboard-tv', compact('projectProgress', 'distinctUsers'));
    }

    public function accept($id)
    {
        $process = PRD_MaterialLog::findOrFail($id);

        if ($process->is_draft) {
            $process->is_draft = false; // Mark as accepted
            $process->save();

            return back()->with('success', 'Process accepted successfully.');
        }

        return back()->with('error', 'Process is not in draft status.');
    }

    public function delete($id)
    {
        $process = PRD_MaterialLog::findOrFail($id);

        if (is_null($process->scan_start)) {
            $process->delete();

            return back()->with('success', 'Process has been deleted successfully.');
        }

        return back()->with('error', 'Cannot delete the process. It has already started.');
    }

    public function destroyImage($id)
    {
        $img = File::findOrFail($id);

        // Delete the file from storage
        Storage::delete('files/' . $img->name);

        // Delete the image record from the database
        $img->delete();

        return redirect()->back()->with('success', 'File deleted successfully');
    }

    public function uploadExcelBom(Request $request)
    {

        $request->validate([
            'excel_file' => 'required|mimes:xls,xlsx',
            'bom_parent_id' => 'required|integer',
        ]);

        $bomParentId = $request->bom_parent_id;

        Excel::import(new BillOfMaterialChildImport($bomParentId), $request->file('excel_file'));

        return back()->with('success', 'Excel file imported successfully!');
    }
}

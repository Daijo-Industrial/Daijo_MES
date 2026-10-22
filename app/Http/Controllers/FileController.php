<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    // function untuk upload file ke dalam database
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:16000',
            'item_code' => 'string',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '-' . $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $file->storeAs('public/files', $fileName);
                // $file->move(public_path('files'), $fileName);

                File::create([
                    'item_code' => $request->item_code,
                    'name' => $fileName,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $fileSize,
                ]);
            }
        }

        // Flash a success message to the session
        session()->flash('success', 'Files uploaded successfully!');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully!',
            ]);
        }

        return redirect()->back()->with('success', 'Files uploaded successfully!');
    }

    // function untuk menghapus file dari database
    public function destroy($id)
    {
        $file = File::find($id);

        $filename = $file->name;
        Storage::delete('public/files/' . $filename);
        $file->delete();

        return redirect()->back()->with('success', 'Files deleted successfully!');
    }
}

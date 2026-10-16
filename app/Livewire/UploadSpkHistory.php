<?php

namespace App\Livewire;

use App\Imports\SpkItemHistoryImport;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class UploadSpkHistory extends Component
{
    use WithFileUploads;

    public $file;

    public $progress = 0;

    public $isUploading = false;

    public $uploadStatus = '';

    public $jobId;

    public function uploadFile()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            $this->jobId = uniqid('upload_spk_');
            $path = $this->file->store('temp');

            // Initialize cache for progress
            Cache::put($this->jobId . '_status', 'processing', now()->addHours(1));
            Cache::put($this->jobId . '_processed', 0, now()->addHours(1));
            Cache::put($this->jobId . '_total', 1, now()->addHours(1));

            // Detect if SAP disguised a TSV as XLS
            $extension = strtolower($this->file->getClientOriginalExtension());
            $readerType = null;
            $delimiter = ',';

            if ($extension === 'csv') {
                $readerType = \Maatwebsite\Excel\Excel::CSV;
                $delimiter = ',';
            } elseif ($extension === 'xls') {
                $absolutePath = storage_path('app/' . $path);
                $content = file_get_contents($absolutePath);
                // Check if it's actually a TSV
                if (strpos($content, "\t") !== false && strpos($content, '<?xml') === false) {
                    $readerType = \Maatwebsite\Excel\Excel::CSV;
                    $delimiter = "\t";
                } else {
                    $readerType = \Maatwebsite\Excel\Excel::XLS;
                }
            } elseif ($extension === 'xlsx') {
                $readerType = \Maatwebsite\Excel\Excel::XLSX;
            }

            // Queue the import process with custom delimiter and reader type
            Excel::queueImport(new SpkItemHistoryImport($this->jobId, $delimiter), $path, null, $readerType);

            $this->isUploading = true;
            $this->progress = 0;
            $this->uploadStatus = 'Mempersiapkan data...';

            $this->reset('file');
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat mengantri proses upload: ' . $e->getMessage());
        }
    }

    public function checkProgress()
    {
        if (! $this->isUploading) {
            return;
        }

        $status = Cache::get($this->jobId . '_status');
        $processed = Cache::get($this->jobId . '_processed', 0);
        $total = Cache::get($this->jobId . '_total', 1);

        if ($total > 0) {
            // Calculate percentage, max out at 99% until finished
            $calculated = round(($processed / $total) * 100);
            $this->progress = min(99, $calculated);
        }

        if ($status === 'finished') {
            $this->isUploading = false;
            $this->progress = 100;
            $this->uploadStatus = 'Selesai!';
            session()->flash('success', 'Upload dan proses data SPK berhasil dilakukan sepenuhnya.');
        } elseif ($status === 'failed') {
            $this->isUploading = false;
            $this->uploadStatus = 'Gagal';
            session()->flash('error', 'Terjadi kesalahan saat memproses data di background.');
        }
    }

    public function render()
    {
        return view('livewire.upload-spk-history');
    }
}

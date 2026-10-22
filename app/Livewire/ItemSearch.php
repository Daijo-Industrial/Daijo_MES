<?php

namespace App\Livewire;

use App\Models\MasterListItem;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ItemSearch extends Component
{
    use WithPagination;

    public $search = ''; // Bind this to the search input

    public $showOnlyNoFiles = false;

    public $showAllNoFiles = false;

    #[On('refresh-items')]
    public function refreshItems()
    {
        // Simply triggers a re-render
        $this->resetPage();
    }

    public function toggleShowOnlyNoFiles()
    {
        $this->showOnlyNoFiles = ! $this->showOnlyNoFiles;
        $this->resetPage(); // reset ke halaman 1 pas filter berubah
    }

    public function toggleShowAllNoFiles()
    {
        $this->showAllNoFiles = ! $this->showAllNoFiles;
        $this->showOnlyNoFiles = false; // matikan filter daily saat all aktif
        $this->resetPage();
    }

    public function deleteFile($fileId)
    {
        $file = \App\Models\File::find($fileId);
        if ($file) {
            $filename = $file->name;
            \Illuminate\Support\Facades\Storage::delete('public/files/' . $filename);
            $file->delete();
            $this->dispatch('showAlert', message: 'File deleted successfully!', type: 'success');
        }
    }

    public function render()
    {
        $today = Carbon::today()->toDateString(); // atau use now()->toDateString()

        $items = MasterListItem::when($this->showOnlyNoFiles, function ($query) use ($today) {
            $query->doesntHave('files')
                ->whereIn('item_code', function ($subQuery) use ($today) {
                    $subQuery->select('item_code')
                        ->from('daily_item_codes')
                        ->whereDate('schedule_date', $today);
                });
        })
            ->when($this->showAllNoFiles, function ($query) {
                $query->doesntHave('files');
            })
            ->when(! empty(trim($this->search)), function ($query) {
                $query->where(function ($q) {
                    $q->where('item_code', 'like', '%' . $this->search . '%')
                        ->orWhereIn('item_code', function ($subQuery) {
                            $subQuery->select('item_code')
                                ->from('files')
                                ->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with('files')
            ->orderBy('item_code', 'asc')
            ->paginate(10);

        return view('livewire.item-search', ['items' => $items]);
    }
}

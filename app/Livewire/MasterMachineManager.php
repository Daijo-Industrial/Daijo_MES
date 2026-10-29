<?php

namespace App\Livewire;

use App\Models\MasterMachine;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MasterMachineManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filters & Search
    public string $search = '';

    public string $plantFilter = 'ALL'; // ALL, KBN, KARAWANG

    public string $statusFilter = 'ALL'; // ALL, ACTIVE, INACTIVE

    public int $perPage = 25;

    // Modal Form State
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $plant = 'KBN';

    public string $tonnage = '';

    public string $brand = '';

    public string $line_or_zone = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount()
    {
        // Hanya sinkronkan dari user operator jika tabel master_machines masih kosong sama sekali
        if (MasterMachine::withTrashed()->count() === 0) {
            $this->syncFromOperatorUsers(false);
        }
    }

    public function syncFromOperatorUsers(bool $notify = true)
    {
        $operatorUsers = User::whereHas('role', function ($q) {
            $q->where('name', 'OPERATOR');
        })->get();

        $added = 0;
        foreach ($operatorUsers as $u) {
            $cleanName = strtoupper(trim($u->name));
            if (empty($cleanName)) {
                continue;
            }

            // Periksa apakah mesin sudah ada di master_machines (termasuk yang soft-deleted)
            $existing = MasterMachine::withTrashed()->where('name', $cleanName)->first();
            if ($existing) {
                // Jika sudah ada (aktif, non-aktif, atau sudah pernah dihapus/soft-deleted),
                // lewati agar tidak memicu SQLSTATE[23000] Duplicate entry error
                continue;
            }

            $isKarawang = str_starts_with($cleanName, 'K');
            MasterMachine::create([
                'name' => $cleanName,
                'plant' => $isKarawang ? 'KARAWANG' : 'KBN',
                'is_active' => true,
                'description' => 'Disinkronkan dari User Operator (' . $u->email . ')',
            ]);

            $added++;
        }

        if ($notify) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Sinkronisasi berhasil: {$added} mesin baru dari User Operator berhasil dimasukkan ke daftar master.",
            ]);
        }
    }

    // Auto-detect plant based on machine name prefix 'K'
    public function updatedName($value)
    {
        $trimmed = strtoupper(trim($value));
        if (str_starts_with($trimmed, 'K')) {
            $this->plant = 'KARAWANG';
        } elseif (! empty($trimmed) && $this->plant === 'KARAWANG' && ! str_starts_with($trimmed, 'K')) {
            $this->plant = 'KBN';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPlantFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function toggleActive(int $id)
    {
        $machine = MasterMachine::findOrFail($id);
        $machine->is_active = ! $machine->is_active;
        $machine->save();

        $statusText = $machine->is_active ? 'ditampilkan di dashboard' : 'disembunyikan dari dashboard';
        $this->dispatch('notify', [
            'type' => $machine->is_active ? 'success' : 'info',
            'message' => "Mesin {$machine->name} sekarang {$statusText}.",
        ]);
    }

    public function setAllActive(bool $status)
    {
        $query = MasterMachine::query();

        if ($this->plantFilter !== 'ALL') {
            $query->where('plant', $this->plantFilter);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('brand', 'like', "%{$this->search}%")
                    ->orWhere('tonnage', 'like', "%{$this->search}%");
            });
        }

        $count = $query->update(['is_active' => $status, 'updated_at' => now()]);

        $label = $status ? 'diaktifkan (ditampilkan)' : 'dinonaktifkan (disembunyikan)';
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Berhasil: {$count} mesin {$label}.",
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id)
    {
        $machine = MasterMachine::findOrFail($id);
        $this->editingId = $machine->id;
        $this->name = $machine->name;
        $this->plant = $machine->plant;
        $this->tonnage = $machine->tonnage ?? '';
        $this->brand = $machine->brand ?? '';
        $this->line_or_zone = $machine->line_or_zone ?? '';
        $this->description = $machine->description ?? '';
        $this->is_active = (bool) $machine->is_active;

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->plant = 'KBN';
        $this->tonnage = '';
        $this->brand = '';
        $this->line_or_zone = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function saveMachine()
    {
        $this->name = strtoupper(trim($this->name));

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('master_machines', 'name')->whereNull('deleted_at')->ignore($this->editingId),
            ],
            'plant' => ['required', 'in:KBN,KARAWANG'],
            'tonnage' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:50'],
            'line_or_zone' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data = [
            'name' => $this->name,
            'plant' => $this->plant,
            'tonnage' => ! empty($this->tonnage) ? $this->tonnage : null,
            'brand' => ! empty($this->brand) ? $this->brand : null,
            'line_or_zone' => ! empty($this->line_or_zone) ? $this->line_or_zone : null,
            'description' => ! empty($this->description) ? $this->description : null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $machine = MasterMachine::findOrFail($this->editingId);
            $machine->update($data);
            $message = "Mesin {$this->name} berhasil diperbarui.";
        } else {
            // Periksa apakah mesin dengan nama ini pernah dihapus (soft-deleted)
            $existingTrashed = MasterMachine::onlyTrashed()->where('name', $this->name)->first();
            if ($existingTrashed) {
                $existingTrashed->restore();
                $existingTrashed->update($data);
                $message = "Mesin {$this->name} berhasil diaktifkan kembali dan diperbarui.";
            } else {
                MasterMachine::create($data);
                $message = "Mesin baru {$this->name} berhasil ditambahkan.";
            }

            // Pastikan user akun mesin ada di tabel users agar kompatibel dengan modul lain
            $role = Role::firstOrCreate(['name' => 'OPERATOR']);
            User::firstOrCreate(
                ['name' => $this->name],
                [
                    'email' => strtolower($this->name) . '@daijo.co.id',
                    'password' => bcrypt('password'),
                    'role_id' => $role->id,
                ]
            );
        }

        $this->closeModal();
        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);
    }

    public function deleteMachine(int $id)
    {
        $machine = MasterMachine::findOrFail($id);
        $name = $machine->name;
        $machine->delete();

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "Mesin {$name} berhasil dihapus dari daftar master.",
        ]);
    }

    public function render()
    {
        $query = MasterMachine::query();

        if ($this->plantFilter !== 'ALL') {
            $query->where('plant', $this->plantFilter);
        }

        if ($this->statusFilter === 'ACTIVE') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'INACTIVE') {
            $query->where('is_active', false);
        }

        if (! empty($this->search)) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('brand', 'like', "%{$term}%")
                    ->orWhere('tonnage', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $machines = $query->orderBy('name')->paginate($this->perPage);

        // Stats calculation
        $allMachines = MasterMachine::all();
        $stats = [
            'total' => $allMachines->count(),
            'active' => $allMachines->where('is_active', true)->count(),
            'inactive' => $allMachines->where('is_active', false)->count(),
            'kbn' => $allMachines->where('plant', 'KBN')->count(),
            'karawang' => $allMachines->where('plant', 'KARAWANG')->count(),
        ];

        return view('livewire.master-machine-manager', [
            'machines' => $machines,
            'stats' => $stats,
        ])->layout('layouts.app', ['title' => 'Master Mesin - Kontrol Tampilan Dashboard']);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\MasterCustomerDelivery;
use App\Models\MasterCustomerLog;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerDeliveryManager extends Component
{
    use WithPagination;

    public $search = '';

    // Add customer form fields
    public $newCustomerCode = '';

    public $newCustomerName = '';

    // Inline edit state
    public $editingItemId = null;

    public $editingField = null;

    public $editingValue = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function startEdit($itemId, $field)
    {
        $customer = MasterCustomerDelivery::findOrFail($itemId);
        $this->editingItemId = $itemId;
        $this->editingField = $field;
        $this->editingValue = $customer->$field;
    }

    public function cancelEdit()
    {
        $this->editingItemId = null;
        $this->editingField = null;
        $this->editingValue = '';
    }

    public function saveEdit()
    {
        $customer = MasterCustomerDelivery::findOrFail($this->editingItemId);
        $field = $this->editingField;

        $rules = [
            'customer_code' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
        ];

        // Validasi
        $this->validate([
            'editingValue' => $rules[$field],
        ]);

        $oldValue = $customer->$field;
        $newValue = trim($this->editingValue);

        // Jika mengubah customer_code, pastikan unik (kecuali milik sendiri)
        if ($field === 'customer_code' && $oldValue !== $newValue) {
            $exists = MasterCustomerDelivery::where('customer_code', $newValue)
                ->where('id', '!=', $customer->id)
                ->exists();

            if ($exists) {
                session()->flash('error', "Customer Code '{$newValue}' sudah terdaftar untuk customer lain.");
                $this->cancelEdit();

                return;
            }
        }

        if ($oldValue !== $newValue) {
            $customer->$field = $newValue;
            $customer->save();

            // Log activity
            MasterCustomerLog::create([
                'user_id' => auth()->id(),
                'customer_code' => $customer->customer_code,
                'action' => 'inline_edit',
                'old_values' => [$field => $oldValue],
                'new_values' => [$field => $newValue],
            ]);

            session()->flash('message', "Customer '{$customer->customer_name}' updated successfully.");
        }

        $this->cancelEdit();
    }

    public function createCustomer()
    {
        $this->validate([
            'newCustomerCode' => 'required|string|max:255|unique:master_customer_delivery,customer_code',
            'newCustomerName' => 'required|string|max:255',
        ], [
            'newCustomerCode.required' => 'Customer Code wajib diisi.',
            'newCustomerCode.unique' => 'Customer Code sudah terdaftar.',
            'newCustomerName.required' => 'Customer Name wajib diisi.',
        ]);

        $customer = MasterCustomerDelivery::create([
            'customer_code' => trim($this->newCustomerCode),
            'customer_name' => trim($this->newCustomerName),
        ]);

        // Log activity
        MasterCustomerLog::create([
            'user_id' => auth()->id(),
            'customer_code' => $customer->customer_code,
            'action' => 'create',
            'old_values' => null,
            'new_values' => $customer->only(['customer_code', 'customer_name']),
        ]);

        session()->flash('message', "Customer '{$this->newCustomerName}' added successfully.");

        // Reset form
        $this->newCustomerCode = '';
        $this->newCustomerName = '';
    }

    public function deleteCustomer($id)
    {
        $customer = MasterCustomerDelivery::findOrFail($id);
        $name = $customer->customer_name;
        $code = $customer->customer_code;

        // Log activity before delete
        MasterCustomerLog::create([
            'user_id' => auth()->id(),
            'customer_code' => $code,
            'action' => 'delete',
            'old_values' => $customer->only(['customer_code', 'customer_name']),
            'new_values' => null,
        ]);

        $customer->delete();

        session()->flash('message', "Customer '{$name}' deleted successfully.");
    }

    public function render()
    {
        $customers = MasterCustomerDelivery::where(function ($query) {
            $query->where('customer_code', 'like', '%' . $this->search . '%')
                ->orWhere('customer_name', 'like', '%' . $this->search . '%');
        })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.admin.customer-delivery-manager', [
            'customers' => $customers,
        ]);
    }
}

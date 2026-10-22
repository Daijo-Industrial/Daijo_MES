<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4 mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Branches & Departments</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Manage company plant locations, active branches, and corporate departments.</p>
                    </div>
                    <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 py-1 px-3 rounded-full border border-indigo-100">
                        Corporate Master Portal
                    </span>
                </div>

                <livewire:admin.branch-department-manager :initialTab="request('tab', $initialTab ?? null)" />
            </div>
        </div>
    </div>
</x-app-layout>

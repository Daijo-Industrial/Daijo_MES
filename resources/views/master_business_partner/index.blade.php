<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Master Business Partner Manager</h2>
                        <p class="text-xs text-gray-500 mt-1">Data Rekanan Bisnis SAP (Customer, Vendor, dan Internal Accounts)</p>
                    </div>
                    <span class="text-xs text-gray-500 font-semibold bg-gray-100 py-1 px-3 rounded-full">SAP Master Data Portal</span>
                </div>

                <livewire:admin.business-partner-manager />
            </div>
        </div>
    </div>
</x-app-layout>

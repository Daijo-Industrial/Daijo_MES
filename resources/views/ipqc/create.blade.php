<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <a href="{{ route('ipqc-inspections.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded transition text-sm">&larr; Back to List</a>
                <h2 class="text-2xl font-bold">New IPQC Inspection</h2>
            </div>
            <form action="{{ route('ipqc-inspections.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('ipqc._form')
            </form>
        </div>
    </div>
</x-app-layout>

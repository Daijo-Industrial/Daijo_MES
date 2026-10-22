<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <a href="{{ route('ipqc-inspections.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded transition text-sm">&larr; Back to List</a>
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-bold">Edit IPQC Inspection #{{ $inspection->id }}</h2>
                    @if($inspection->status === 'ongoing')
                        <span class="px-3 py-1 text-xs font-bold rounded bg-blue-100 text-blue-700 uppercase tracking-wide">Ongoing</span>
                    @else
                        <span class="px-3 py-1 text-xs font-bold rounded bg-green-100 text-green-700 uppercase tracking-wide">Completed</span>
                    @endif
                </div>
            </div>
            <form action="{{ route('ipqc-inspections.update', $inspection->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('ipqc._form')
            </form>
        </div>
    </div>
</x-app-layout>

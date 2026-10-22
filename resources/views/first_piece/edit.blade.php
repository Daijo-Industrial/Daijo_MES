<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('first-piece-inspections.update', $inspection->id) }}" method="POST" enctype="multipart/form-data" id="first-piece-form">
                @csrf
                @method('PUT')

                @include('first_piece._form')
            </form>
        </div>
    </div>
</x-app-layout>

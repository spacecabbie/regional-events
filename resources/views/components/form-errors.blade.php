@if ($errors->any())
    <div id="form-errors" role="alert" tabindex="-1" class="mb-6 border border-red-800 bg-red-50 p-4 text-red-900">
        <p class="font-semibold">The form needs a few changes.</p>
        <ul class="mt-2 list-disc ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if (session('status'))
    <p role="status" class="mb-6 border border-stone-300 bg-white p-4">{{ session('status') }}</p>
@endif

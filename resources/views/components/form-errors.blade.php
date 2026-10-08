@if ($errors->any())
    <div id="form-errors" role="alert" tabindex="-1" class="alert alert-error">
        <p class="font-semibold">The form needs a few changes.</p>
        <ul class="mt-2 list-disc ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if (session('status'))
    <p role="status" class="alert alert-status">{{ session('status') }}</p>
@endif

{{-- Result flash for shared admin write actions (doc 06 §7.2):
     successes arrive as a coded flash message, returned envelope errors
     as flash_error (code: message), validation failures as $errors from
     the framework's redirect-back. No logic beyond display. --}}
@if (session('flash'))
    <div class="alert alert-success" role="alert">{{ session('flash') }}</div>
@endif

@if (session('flash_error'))
    <div class="alert alert-danger" role="alert">{{ session('flash_error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

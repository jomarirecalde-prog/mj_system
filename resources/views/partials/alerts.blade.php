@if($errors->any())
    <div class="alert alert--error" role="alert">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1" style="margin:0.5rem 0 0 1.1rem;padding:0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($image)
    <div style="margin-top: 10px;">
        <img src="{{ asset('storage/' . $image) }}" style="max-width: 200px; border-radius: 8px;">
    </div>
@endif

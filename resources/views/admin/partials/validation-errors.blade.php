@if ($errors->any())
    <div style="color:#842029; background:#f8d7da; border:1px solid #f5c2c7; padding:12px; margin-bottom:15px; border-radius:4px;">
        <strong>Please correct the following errors:</strong>
        <ul style="margin:8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

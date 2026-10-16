<div class="sidebar">
    <h3>Admin Panel</h3>
    
    @foreach(config('sidebar') as $item)
        @php 
            // Route ache kina check kora, na thakle # boshabe
            $url = Route::has($item['route']) ? route($item['route']) : $item['route']; 
        @endphp

        @if($item['type'] === 'all')
            <a href="{{ $url }}">{{ $item['title'] }}</a>
            
        @elseif($item['type'] === 'permission')
            @can($item['key'], 'admin')
                <a href="{{ $url }}">{{ $item['title'] }}</a>
            @endcan
            
        @elseif($item['type'] === 'role')
            @role($item['key'], 'admin')
                <a href="{{ $url }}">{{ $item['title'] }}</a>
            @endrole
            
        @endif
    @endforeach
</div>
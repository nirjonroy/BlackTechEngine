<h1>Dashboard</h1>

{{-- logout --}}

<a href="{{ route('admin.logout') }}"
   onclick="event.preventDefault();
            document.getElementById('logout-form').submit();">
    {{ __('Logout') }}
</a>

<!-- Ei hidden form ti add korun -->
<form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
    @csrf
</form>
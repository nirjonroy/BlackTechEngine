<div class="header">
    <h4>Welcome, Admin</h4>
    <div>
        <!-- Logout Link & Hidden Form -->
        <a href="{{ route('admin.logout') }}" class="btn-logout"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Logout
        </a>
        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</div>
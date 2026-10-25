<!-- Anti-FOUC Theme Initialization (Dark Mode Default) -->
<script>
if (localStorage.getItem('bte_theme') === 'light') {
    document.documentElement.classList.remove('dark');
    document.documentElement.classList.add('light');
} else {
    document.documentElement.classList.add('dark');
    document.documentElement.classList.remove('light');
}
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
<script src="https://unpkg.com/lucide@1.61.0/dist/umd/lucide.min.js"></script>

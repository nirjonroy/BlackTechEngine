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

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    darkMode: 'class',
    theme: {
    extend: {
        colors: {
        brand: { 50: '#EEF2FF', 100: '#E0E7FF', 500: '#6366F1', 600: '#4F46E5', 700: '#4338CA' },
        violet: { 500: '#8B5CF6', 600: '#7C3AED' },
        cyan: { 400: '#22D3EE', 500: '#06B6D4' },
        dark: { bg: '#090D16', surface: '#0E1526', card: '#131C31', border: '#222F4C' }
        },
        fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        heading: ['"Outfit"', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace']
        }
    }
    }
}
</script>
<script src="https://unpkg.com/lucide@latest"></script>
<!-- Asset Helper bebohar kora hoyeche -->
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
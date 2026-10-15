/**
 * BlackTechEngine - Production Dual Theme System (Default: DARK MODE)
 */

(function () {
  function getPreferredTheme() {
    const saved = localStorage.getItem('bte_theme');
    if (saved === 'light') {
      return 'light';
    }
    // Default is strictly DARK MODE
    return 'dark';
  }

  function applyTheme(theme) {
    const root = document.documentElement;
    if (theme === 'dark') {
      root.classList.add('dark');
      root.classList.remove('light');
    } else {
      root.classList.remove('dark');
      root.classList.add('light');
    }
    localStorage.setItem('bte_theme', theme);
    updateIcons(theme);
  }

  function updateIcons(theme) {
    document.querySelectorAll('.theme-toggle-btn, #themeToggle, #themeToggleNav, #themeToggleSidebar').forEach(btn => {
      const sun = btn.querySelector('.theme-icon-sun');
      const moon = btn.querySelector('.theme-icon-moon');
      const text = btn.querySelector('.theme-text');

      if (theme === 'dark') {
        if (sun) sun.classList.remove('hidden');
        if (moon) moon.classList.add('hidden');
        if (text) text.textContent = 'Light Mode';
        btn.setAttribute('title', 'Switch to Light Mode');
      } else {
        if (sun) sun.classList.add('hidden');
        if (moon) moon.classList.remove('hidden');
        if (text) text.textContent = 'Dark Mode';
        btn.setAttribute('title', 'Switch to Dark Mode');
      }
    });
  }

  window.toggleTheme = function () {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const nextTheme = current === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme);
    window.dispatchEvent(new CustomEvent('bteThemeChanged', { detail: { theme: nextTheme } }));
  };

  // Immediate execution before DOM ready
  applyTheme(getPreferredTheme());

  document.addEventListener('DOMContentLoaded', () => {
    updateIcons(getPreferredTheme());

    document.querySelectorAll('.theme-toggle-btn, #themeToggle, #themeToggleNav, #themeToggleSidebar').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.toggleTheme();
      });
    });
  });
})();

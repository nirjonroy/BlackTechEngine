/**
 * BlackTechEngine - Dual Theme System (Default: DARK MODE)
 * Persistent theme management across all public, user, and admin screens.
 */

(function () {
  // Theme state initialization
  function getPreferredTheme() {
    const saved = localStorage.getItem('bte_theme');
    if (saved) {
      return saved;
    }
    // Default is strictly DARK MODE as specified in the requirements
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
    updateToggleIcons(theme);
  }

  function updateToggleIcons(theme) {
    const toggleBtns = document.querySelectorAll('.theme-toggle-btn, #themeToggle, #themeToggleNav, #themeToggleSidebar');
    toggleBtns.forEach(btn => {
      const sunIcon = btn.querySelector('.theme-icon-sun');
      const moonIcon = btn.querySelector('.theme-icon-moon');
      const label = btn.querySelector('.theme-label');

      if (theme === 'dark') {
        if (sunIcon) sunIcon.classList.remove('hidden');
        if (moonIcon) moonIcon.classList.add('hidden');
        if (label) label.textContent = 'Light Mode';
        btn.setAttribute('title', 'Switch to Light Mode');
      } else {
        if (sunIcon) sunIcon.classList.add('hidden');
        if (moonIcon) moonIcon.classList.remove('hidden');
        if (label) label.textContent = 'Dark Mode';
        btn.setAttribute('title', 'Switch to Dark Mode');
      }
    });
  }

  window.toggleTheme = function () {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const nextTheme = current === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme);

    // Trigger custom event if charts or dynamic elements need repainting
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: nextTheme } }));
  };

  // Immediate execution before DOM ready to prevent any flash of unstyled content
  const currentTheme = getPreferredTheme();
  applyTheme(currentTheme);

  // Bind to DOM when loaded
  document.addEventListener('DOMContentLoaded', () => {
    updateToggleIcons(getPreferredTheme());

    document.querySelectorAll('.theme-toggle-btn, #themeToggle, #themeToggleNav, #themeToggleSidebar').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.toggleTheme();
      });
    });
  });
})();

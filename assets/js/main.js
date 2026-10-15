/**
 * BlackTechEngine - Main Interactivity Engine (Vanilla ES6+)
 */

document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide icons if available
  if (window.lucide) {
    window.lucide.createIcons();
  }

  // Setup generic modal triggers
  setupModals();

  // Setup dropdowns
  setupDropdowns();

  // Setup mobile navigation
  setupMobileNav();

  // Setup accordions
  setupAccordions();

  // Setup Savings Calculator if present
  initSavingsCalculator();

  // Setup Affiliate Calculator if present
  initAffiliateCalculator();

  // Setup Pricing Toggle if present
  initPricingToggle();

  // Setup Feature Showcase Tabs if present
  initFeatureTabs();

  // Setup Website Scanner if present
  initWebsiteScanner();
});

// Toast Notification System
window.showToast = function (message, type = 'success') {
  let toastContainer = document.getElementById('bte-toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'bte-toast-container';
    toastContainer.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bgColors = {
    success: 'bg-emerald-600 dark:bg-emerald-500 text-white',
    error: 'bg-rose-600 dark:bg-rose-500 text-white',
    info: 'bg-indigo-600 dark:bg-indigo-500 text-white',
    warning: 'bg-amber-600 dark:bg-amber-500 text-white'
  };

  const icons = {
    success: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`,
    error: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`,
    info: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
    warning: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`
  };

  toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl backdrop-blur-md transform transition-all duration-300 translate-y-4 opacity-0 pointer-events-auto ${bgColors[type] || bgColors.success}`;
  toast.innerHTML = `
    <span>${icons[type] || icons.success}</span>
    <span class="text-sm font-medium">${message}</span>
  `;

  toastContainer.appendChild(toast);

  // Trigger animation
  setTimeout(() => {
    toast.classList.remove('translate-y-4', 'opacity-0');
  }, 10);

  // Remove toast after 3.5s
  setTimeout(() => {
    toast.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 3500);
};

// Modal Handling
window.openModal = function (modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    if (window.lucide) window.lucide.createIcons();
  }
};

window.closeModal = function (modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
  }
};

function setupModals() {
  document.querySelectorAll('[data-modal-target]').forEach(button => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-modal-target');
      window.openModal(targetId);
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(button => {
    button.addEventListener('click', () => {
      const modal = button.closest('.modal-container') || button.closest('[id$="Modal"]');
      if (modal) {
        window.closeModal(modal.id);
      }
    });
  });

  // Close when clicking modal backdrop
  window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
      const modal = e.target.closest('[id$="Modal"]') || e.target.parentElement;
      if (modal) window.closeModal(modal.id);
    }
  });

  // Close on ESC key
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-container:not(.hidden)').forEach(modal => {
        window.closeModal(modal.id);
      });
      // Also close drawer if open
      const calendarDrawer = document.getElementById('calendarDrawer');
      if (calendarDrawer && !calendarDrawer.classList.contains('translate-x-full')) {
        window.closeCalendarDrawer();
      }
    }
  });
}

// Dropdowns Handling
function setupDropdowns() {
  const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
  dropdownButtons.forEach(button => {
    button.addEventListener('click', (e) => {
      e.stopPropagation();
      const targetId = button.getAttribute('data-dropdown-toggle');
      const targetMenu = document.getElementById(targetId);
      
      // Close all other dropdowns
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== targetId) menu.classList.add('hidden');
      });

      if (targetMenu) {
        targetMenu.classList.toggle('hidden');
      }
    });
  });

  // Close dropdowns on outside click
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.classList.add('hidden');
    });
  });
}

// Mobile Navigation
function setupMobileNav() {
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });
  }
}

// Accordions
function setupAccordions() {
  const accordionButtons = document.querySelectorAll('.accordion-header');
  accordionButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const content = btn.nextElementSibling;
      const icon = btn.querySelector('.accordion-icon');
      
      const isOpen = !content.classList.contains('hidden');
      
      // Close peers if in single-mode
      const parent = btn.closest('.accordion-group');
      if (parent && parent.dataset.exclusive !== 'false') {
        parent.querySelectorAll('.accordion-content').forEach(c => c.classList.add('hidden'));
        parent.querySelectorAll('.accordion-icon').forEach(i => i.classList.remove('rotate-180'));
      }

      if (!isOpen) {
        content.classList.remove('hidden');
        if (icon) icon.classList.add('rotate-180');
      } else {
        content.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
      }
    });
  });
}

// Interactive Savings Calculator (Homepage)
function initSavingsCalculator() {
  const slider = document.getElementById('articlesSlider');
  if (!slider) return;

  const countDisplay = document.getElementById('articlesCountDisplay');
  const freelanceCostDisplay = document.getElementById('freelanceCostDisplay');
  const bteCostDisplay = document.getElementById('bteCostDisplay');
  const savingsDisplay = document.getElementById('savingsDisplay');
  const hoursSavedDisplay = document.getElementById('hoursSavedDisplay');

  function update() {
    const articles = parseInt(slider.value, 10);
    const freelanceRate = 140; // $140 avg cost per high quality 2000-word SEO article with research
    const freelanceTotal = articles * freelanceRate;
    
    // Tier cost based on articles
    let bteCost = 99;
    if (articles <= 10) bteCost = 49;
    else if (articles > 30) bteCost = 249;

    const savedMoney = freelanceTotal - bteCost;
    const hoursSaved = articles * 5.5; // ~5.5 hours to write, edit, format & publish an article

    if (countDisplay) countDisplay.textContent = articles;
    if (freelanceCostDisplay) freelanceCostDisplay.textContent = `$${freelanceTotal.toLocaleString()}`;
    if (bteCostDisplay) bteCostDisplay.textContent = `$${bteCost.toLocaleString()}`;
    if (savingsDisplay) savingsDisplay.textContent = `$${savedMoney.toLocaleString()}`;
    if (hoursSavedDisplay) hoursSavedDisplay.textContent = `${Math.round(hoursSaved)} hrs`;
  }

  slider.addEventListener('input', update);
  update();
}

// Interactive Affiliate Commission Calculator (Affiliate Page)
function initAffiliateCalculator() {
  const slider = document.getElementById('affiliateSlider');
  if (!slider) return;

  const referralsDisplay = document.getElementById('referralsCount');
  const monthlyEarningsDisplay = document.getElementById('monthlyEarnings');
  const yearlyEarningsDisplay = document.getElementById('yearlyEarnings');

  function update() {
    const referrals = parseInt(slider.value, 10);
    const avgPlanPrice = 99; // Growth plan
    const commissionRate = 0.30; // 30% lifetime recurring
    const monthlyPerRef = avgPlanPrice * commissionRate; // $29.70

    const monthlyTotal = Math.round(referrals * monthlyPerRef);
    const yearlyTotal = monthlyTotal * 12;

    if (referralsDisplay) referralsDisplay.textContent = referrals;
    if (monthlyEarningsDisplay) monthlyEarningsDisplay.textContent = `$${monthlyTotal.toLocaleString()}`;
    if (yearlyEarningsDisplay) yearlyEarningsDisplay.textContent = `$${yearlyTotal.toLocaleString()}`;
  }

  slider.addEventListener('input', update);
  update();
}

// Pricing Toggle (Monthly vs Annual)
function initPricingToggle() {
  const toggle = document.getElementById('billingToggle');
  if (!toggle) return;

  const starterPrice = document.getElementById('priceStarter');
  const growthPrice = document.getElementById('priceGrowth');
  const agencyPrice = document.getElementById('priceAgency');
  const periodLabels = document.querySelectorAll('.price-period');

  toggle.addEventListener('change', () => {
    const isAnnual = toggle.checked;
    if (isAnnual) {
      if (starterPrice) starterPrice.textContent = '39';
      if (growthPrice) growthPrice.textContent = '79';
      if (agencyPrice) agencyPrice.textContent = '199';
      periodLabels.forEach(lbl => lbl.textContent = '/mo (billed annually)');
    } else {
      if (starterPrice) starterPrice.textContent = '49';
      if (growthPrice) growthPrice.textContent = '99';
      if (agencyPrice) agencyPrice.textContent = '249';
      periodLabels.forEach(lbl => lbl.textContent = '/month');
    }
  });
}

// Feature Showcase Tabs (Homepage)
function initFeatureTabs() {
  const tabs = document.querySelectorAll('.feature-tab-btn');
  const panels = document.querySelectorAll('.feature-tab-panel');
  if (!tabs.length || !panels.length) return;

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const targetId = tab.dataset.target;

      tabs.forEach(t => {
        t.classList.remove('bg-indigo-600', 'text-white', 'shadow-lg', 'shadow-indigo-500/25');
        t.classList.add('text-slate-400', 'hover:text-slate-200');
      });

      tab.classList.add('bg-indigo-600', 'text-white', 'shadow-lg', 'shadow-indigo-500/25');
      tab.classList.remove('text-slate-400', 'hover:text-slate-200');

      panels.forEach(p => {
        if (p.id === targetId) {
          p.classList.remove('hidden');
          p.classList.add('animate-fadeIn');
        } else {
          p.classList.add('hidden');
        }
      });
    });
  });
}

// Live Website Scanner Simulation (Homepage)
function initWebsiteScanner() {
  const scanForm = document.getElementById('websiteScannerForm');
  if (!scanForm) return;

  scanForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const urlInput = document.getElementById('scannerUrlInput');
    const url = urlInput ? urlInput.value.trim() : 'https://thehorizonsunlimited.com';
    
    // Open scanner modal
    window.openModal('scannerResultModal');
    
    const step1 = document.getElementById('scanStep1');
    const step2 = document.getElementById('scanStep2');
    const step3 = document.getElementById('scanStep3');
    const scanProgressBar = document.getElementById('scanProgressBar');
    const scanLoadingView = document.getElementById('scanLoadingView');
    const scanCompletedView = document.getElementById('scanCompletedView');
    const scannedUrlLabel = document.getElementById('scannedUrlLabel');

    if (scannedUrlLabel) scannedUrlLabel.textContent = url || 'https://thehorizonsunlimited.com';

    // Reset view
    if (scanLoadingView) scanLoadingView.classList.remove('hidden');
    if (scanCompletedView) scanCompletedView.classList.add('hidden');
    if (scanProgressBar) scanProgressBar.style.width = '15%';

    // Step 1
    setTimeout(() => {
      if (step1) {
        step1.querySelector('.status-dot').className = 'status-dot w-4 h-4 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px]';
        step1.querySelector('.status-dot').innerHTML = '✓';
      }
      if (scanProgressBar) scanProgressBar.style.width = '45%';
    }, 900);

    // Step 2
    setTimeout(() => {
      if (step2) {
        step2.querySelector('.status-dot').className = 'status-dot w-4 h-4 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px]';
        step2.querySelector('.status-dot').innerHTML = '✓';
      }
      if (scanProgressBar) scanProgressBar.style.width = '80%';
    }, 1800);

    // Step 3 & Show Results
    setTimeout(() => {
      if (step3) {
        step3.querySelector('.status-dot').className = 'status-dot w-4 h-4 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px]';
        step3.querySelector('.status-dot').innerHTML = '✓';
      }
      if (scanProgressBar) scanProgressBar.style.width = '100%';

      setTimeout(() => {
        if (scanLoadingView) scanLoadingView.classList.add('hidden');
        if (scanCompletedView) scanCompletedView.classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
      }, 500);
    }, 2700);
  });
}

// Calendar Slide-over Drawer
window.openCalendarDrawer = function (cardData) {
  const drawer = document.getElementById('calendarDrawer');
  const backdrop = document.getElementById('calendarDrawerBackdrop');
  if (!drawer) return;

  if (cardData) {
    const titleEl = document.getElementById('drawerArticleTitle');
    const statusEl = document.getElementById('drawerStatusBadge');
    const keywordEl = document.getElementById('drawerTargetKeyword');
    const scoreEl = document.getElementById('drawerSeoScore');
    const dateEl = document.getElementById('drawerPublishDate');
    const thumbEl = document.getElementById('drawerThumbnail');

    if (titleEl && cardData.title) titleEl.textContent = cardData.title;
    if (statusEl && cardData.status) {
      statusEl.textContent = cardData.status;
      if (cardData.status === 'Published') {
        statusEl.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
      } else if (cardData.status === 'Scheduled') {
        statusEl.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-sky-500/20 text-sky-400 border border-sky-500/30';
      } else {
        statusEl.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30';
      }
    }
    if (keywordEl && cardData.keyword) keywordEl.textContent = cardData.keyword;
    if (scoreEl && cardData.score) scoreEl.textContent = `${cardData.score}/100`;
    if (dateEl && cardData.date) dateEl.textContent = cardData.date;
    if (thumbEl && cardData.image) thumbEl.src = cardData.image;
  }

  if (backdrop) backdrop.classList.remove('hidden');
  drawer.classList.remove('translate-x-full');
  document.body.style.overflow = 'hidden';
  if (window.lucide) window.lucide.createIcons();
};

window.closeCalendarDrawer = function () {
  const drawer = document.getElementById('calendarDrawer');
  const backdrop = document.getElementById('calendarDrawerBackdrop');
  if (!drawer) return;

  drawer.classList.add('translate-x-full');
  if (backdrop) backdrop.classList.add('hidden');
  document.body.style.overflow = '';
};

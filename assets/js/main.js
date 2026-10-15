/**
 * BlackTechEngine - Production Interactive Engine
 */

document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) {
    window.lucide.createIcons();
  }

  setupModals();
  setupDropdowns();
  setupMobileNav();
  setupAccordions();
  initSavingsCalculator();
  initAffiliateCalculator();
  initPricingToggle();
  initDynamicCalendar();
  initWebsiteScanner();
  initVoiceSliderPreview();
});

// Toast System
window.showToast = function (message, type = 'success') {
  let container = document.getElementById('bte-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'bte-toast-container';
    container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  const bgClasses = {
    success: 'bg-emerald-600 text-white shadow-emerald-500/20',
    error: 'bg-rose-600 text-white shadow-rose-500/20',
    info: 'bg-indigo-600 text-white shadow-indigo-500/20',
    warning: 'bg-amber-600 text-white shadow-amber-500/20'
  };

  const icons = {
    success: `<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`,
    error: `<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`,
    info: `<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
    warning: `<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`
  };

  toast.className = `flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-xl backdrop-blur-md transform transition-all duration-300 translate-y-3 opacity-0 pointer-events-auto font-medium text-sm ${bgClasses[type] || bgClasses.success}`;
  toast.innerHTML = `
    <span>${icons[type] || icons.success}</span>
    <span>${message}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.remove('translate-y-3', 'opacity-0');
  }, 20);

  setTimeout(() => {
    toast.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => toast.remove(), 300);
  }, 3800);
};

window.bteToast = {
  show: function (msg, type = 'success') {
    window.showToast(msg, type);
  }
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
  document.querySelectorAll('[data-modal-target]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-modal-target');
      window.openModal(target);
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal-container') || btn.closest('[id$="Modal"]');
      if (modal) window.closeModal(modal.id);
    });
  });

  window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
      const modal = e.target.closest('[id$="Modal"]') || e.target.parentElement;
      if (modal) window.closeModal(modal.id);
    }
  });

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-container:not(.hidden)').forEach(modal => {
        window.closeModal(modal.id);
      });
      const drawer = document.getElementById('calendarDrawer');
      if (drawer && !drawer.classList.contains('translate-x-full')) {
        window.closeCalendarDrawer();
      }
    }
  });
}

// Dropdowns
function setupDropdowns() {
  document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
    button.addEventListener('click', (e) => {
      e.stopPropagation();
      const targetId = button.getAttribute('data-dropdown-toggle');
      const targetMenu = document.getElementById(targetId);

      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== targetId) menu.classList.add('hidden');
      });

      if (targetMenu) {
        targetMenu.classList.toggle('hidden');
      }
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.classList.add('hidden');
    });
  });
}

// Mobile Nav & Sidebar Drawer
function setupMobileNav() {
  const btn = document.getElementById('mobileMenuBtn');
  const menu = document.getElementById('mobileMenu');
  if (btn && menu) {
    btn.addEventListener('click', () => {
      menu.classList.toggle('hidden');
    });
  }

  // Dashboard & Admin mobile sidebar toggle
  const sidebarBtn = document.getElementById('sidebarMobileBtn');
  const sidebar = document.getElementById('userSidebar') || document.getElementById('adminSidebar');
  
  if (sidebarBtn && sidebar) {
    // Backdrop for mobile
    let backdrop = document.getElementById('sidebarMobileBackdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.id = 'sidebarMobileBackdrop';
      backdrop.className = 'fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-30 hidden lg:hidden transition-opacity';
      document.body.appendChild(backdrop);
    }

    const openSidebar = () => {
      sidebar.classList.remove('-translate-x-full');
      backdrop.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    };

    const closeSidebar = () => {
      sidebar.classList.add('-translate-x-full');
      backdrop.classList.add('hidden');
      document.body.style.overflow = '';
    };

    sidebarBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (sidebar.classList.contains('-translate-x-full')) {
        openSidebar();
      } else {
        closeSidebar();
      }
    });

    backdrop.addEventListener('click', closeSidebar);
  }
}

// Accordions
function setupAccordions() {
  document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
      const content = header.nextElementSibling;
      const icon = header.querySelector('.accordion-icon');
      const isHidden = content.classList.contains('hidden');

      const parent = header.closest('.accordion-group');
      if (parent && parent.dataset.exclusive !== 'false') {
        parent.querySelectorAll('.accordion-content').forEach(c => c.classList.add('hidden'));
        parent.querySelectorAll('.accordion-icon').forEach(i => i.classList.remove('rotate-180'));
      }

      if (isHidden) {
        content.classList.remove('hidden');
        if (icon) icon.classList.add('rotate-180');
      } else {
        content.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
      }
    });
  });
}

// Savings Calculator (Homepage)
function initSavingsCalculator() {
  const slider = document.getElementById('articlesSlider');
  if (!slider) return;

  const countDisplay = document.getElementById('articlesCountDisplay');
  const freelanceDisplay = document.getElementById('freelanceCostDisplay');
  const bteDisplay = document.getElementById('bteCostDisplay');
  const savingsDisplay = document.getElementById('savingsDisplay');
  const hoursDisplay = document.getElementById('hoursSavedDisplay');

  function calculate() {
    const articles = parseInt(slider.value, 10);
    const freelanceRate = 150;
    const freelanceTotal = articles * freelanceRate;

    let bteCost = 99;
    if (articles <= 10) bteCost = 49;
    else if (articles > 30) bteCost = 249;

    const saved = freelanceTotal - bteCost;
    const hours = Math.round(articles * 6);

    if (countDisplay) countDisplay.textContent = articles;
    if (freelanceDisplay) freelanceDisplay.textContent = `$${freelanceTotal.toLocaleString()}`;
    if (bteDisplay) bteDisplay.textContent = `$${bteCost.toLocaleString()}`;
    if (savingsDisplay) savingsDisplay.textContent = `$${saved.toLocaleString()}`;
    if (hoursDisplay) hoursDisplay.textContent = `${hours} hrs`;
  }

  slider.addEventListener('input', calculate);
  calculate();
}

// Affiliate Calculator
function initAffiliateCalculator() {
  const slider = document.getElementById('affiliateSlider');
  if (!slider) return;

  const referralsCount = document.getElementById('referralsCount');
  const monthlyDisplay = document.getElementById('monthlyEarnings');
  const yearlyDisplay = document.getElementById('yearlyEarnings');

  function calculate() {
    const referrals = parseInt(slider.value, 10);
    const avgMonthlyCommission = 99 * 0.30; // $29.70 per active customer
    const monthlyTotal = Math.round(referrals * avgMonthlyCommission);
    const yearlyTotal = monthlyTotal * 12;

    if (referralsCount) referralsCount.textContent = referrals;
    if (monthlyDisplay) monthlyDisplay.textContent = `$${monthlyTotal.toLocaleString()}`;
    if (yearlyDisplay) yearlyDisplay.textContent = `$${yearlyTotal.toLocaleString()}`;
  }

  slider.addEventListener('input', calculate);
  calculate();
}

// Pricing Toggle
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
      periodLabels.forEach(lbl => lbl.textContent = '/month (billed annually)');
    } else {
      if (starterPrice) starterPrice.textContent = '49';
      if (growthPrice) growthPrice.textContent = '99';
      if (agencyPrice) agencyPrice.textContent = '249';
      periodLabels.forEach(lbl => lbl.textContent = '/month');
    }
  });
}

// Dynamic Calendar Engine
function initDynamicCalendar() {
  const monthSelector = document.getElementById('calendarMonthSelector');
  const viewMonthBtn = document.getElementById('viewMonthBtn');
  const viewWeekBtn = document.getElementById('viewWeekBtn');
  const monthGrid = document.getElementById('calendarMonthGrid');
  const weekGrid = document.getElementById('calendarWeekGrid');

  // Month / Week View Toggle
  if (viewMonthBtn && viewWeekBtn && monthGrid && weekGrid) {
    viewMonthBtn.addEventListener('click', () => {
      viewMonthBtn.className = 'px-3 py-1.5 rounded-xl font-bold bg-indigo-600 text-white shadow-sm';
      viewWeekBtn.className = 'px-3 py-1.5 rounded-xl font-medium text-slate-500 hover:text-slate-900 dark:hover:text-white';
      monthGrid.classList.remove('hidden');
      weekGrid.classList.add('hidden');
    });

    viewWeekBtn.addEventListener('click', () => {
      viewWeekBtn.className = 'px-3 py-1.5 rounded-xl font-bold bg-indigo-600 text-white shadow-sm';
      viewMonthBtn.className = 'px-3 py-1.5 rounded-xl font-medium text-slate-500 hover:text-slate-900 dark:hover:text-white';
      weekGrid.classList.remove('hidden');
      monthGrid.classList.add('hidden');
    });
  }

  // Content Type Filter Pills
  document.querySelectorAll('.calendar-filter-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.calendar-filter-pill').forEach(p => {
        p.classList.remove('bg-indigo-600', 'text-white', 'shadow-md');
        p.classList.add('text-slate-500');
      });
      pill.classList.add('bg-indigo-600', 'text-white', 'shadow-md');
      pill.classList.remove('text-slate-500');

      const filterType = pill.dataset.filter;
      document.querySelectorAll('.calendar-card-item').forEach(card => {
        if (filterType === 'all' || card.dataset.type === filterType) {
          card.classList.remove('hidden');
        } else {
          card.classList.add('hidden');
        }
      });
    });
  });
}

// Slide-over Calendar Drawer
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
        statusEl.className = 'px-3 py-1 text-xs font-bold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
      } else if (cardData.status === 'Scheduled') {
        statusEl.className = 'px-3 py-1 text-xs font-bold rounded-full bg-sky-500/20 text-sky-400 border border-sky-500/30';
      } else {
        statusEl.className = 'px-3 py-1 text-xs font-bold rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30';
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

// Website Scanner
function initWebsiteScanner() {
  const form = document.getElementById('websiteScannerForm');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const input = document.getElementById('scannerUrlInput');
    const url = input ? input.value.trim() : 'thehorizonsunlimited.com';

    window.openModal('scannerResultModal');

    const label = document.getElementById('scannedUrlLabel');
    if (label) label.textContent = url;

    const pBar = document.getElementById('scanProgressBar');
    const loadingView = document.getElementById('scanLoadingView');
    const completedView = document.getElementById('scanCompletedView');

    if (loadingView) loadingView.classList.remove('hidden');
    if (completedView) completedView.classList.add('hidden');
    if (pBar) pBar.style.width = '20%';

    setTimeout(() => {
      const s1 = document.getElementById('scanStep1');
      if (s1) s1.innerHTML = `<span class="w-4 h-4 rounded-full bg-emerald-500 text-white text-[10px] flex items-center justify-center font-bold">✓</span> <span class="text-emerald-500 font-semibold">Sitemap & 34 URL entities crawled</span>`;
      if (pBar) pBar.style.width = '55%';
    }, 800);

    setTimeout(() => {
      const s2 = document.getElementById('scanStep2');
      if (s2) s2.innerHTML = `<span class="w-4 h-4 rounded-full bg-emerald-500 text-white text-[10px] flex items-center justify-center font-bold">✓</span> <span class="text-emerald-500 font-semibold">Brand Voice: Authoritative & Inspiring</span>`;
      if (pBar) pBar.style.width = '85%';
    }, 1600);

    setTimeout(() => {
      const s3 = document.getElementById('scanStep3');
      if (s3) s3.innerHTML = `<span class="w-4 h-4 rounded-full bg-emerald-500 text-white text-[10px] flex items-center justify-center font-bold">✓</span> <span class="text-emerald-500 font-semibold">Found 45 High-Intent Keyword Opportunities</span>`;
      if (pBar) pBar.style.width = '100%';

      setTimeout(() => {
        if (loadingView) loadingView.classList.add('hidden');
        if (completedView) completedView.classList.remove('hidden');
        if (window.lucide) window.lucide.createIcons();
      }, 400);
    }, 2400);
  });
}

// Live Tone of Voice Slider Preview
function initVoiceSliderPreview() {
  const formalSlider = document.getElementById('formalitySlider');
  const sampleText = document.getElementById('voiceSampleText');
  if (!formalSlider || !sampleText) return;

  const samples = {
    casual: `"Hey there! If you're looking to break into tech without spending 4 years at college, our cloud engineering program has got your back. We keep it 100% practical, zero fluff."`,
    balanced: `"Looking to transition into high-paying cloud architecture? Horizons Unlimited provides structured, hands-on immersive bootcamps designed to prepare professionals for real-world engineering teams."`,
    formal: `"Horizons Unlimited serves as a premier technical accelerator. We specialize in empirical career transition pedagogy for cloud architecture and artificial intelligence engineering."`
  };

  formalSlider.addEventListener('input', () => {
    const val = parseInt(formalSlider.value, 10);
    if (val < 35) sampleText.textContent = samples.casual;
    else if (val > 65) sampleText.textContent = samples.formal;
    else sampleText.textContent = samples.balanced;
  });
}

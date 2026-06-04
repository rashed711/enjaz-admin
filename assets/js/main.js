/**
 * Main JavaScript - نظام إنجاز للحلول الذكية
 * Alpine.js + Vanilla JS Utilities
 */

// ── Sidebar Toggle ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  const sidebar        = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const sidebarToggle  = document.getElementById('sidebarToggle');

  function openSidebar() {
    sidebar?.classList.add('open');
    sidebarOverlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarOverlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  sidebarToggle?.addEventListener('click', () => {
    sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  sidebarOverlay?.addEventListener('click', closeSidebar);

  // ── Live Clock ───────────────────────────────────────────────
  const clockEl = document.getElementById('liveClock');
  if (clockEl) {
    const updateClock = () => {
      const now  = new Date();
      const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                     hour: '2-digit', minute: '2-digit', calendar: 'gregory' };
      clockEl.textContent = now.toLocaleDateString('ar-EG', opts);
    };
    updateClock();
    setInterval(updateClock, 60000);
  }

  // ── Active Nav Link ──────────────────────────────────────────
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach(link => {
    const href = link.getAttribute('href') || '';
    if (href && currentPath.includes(href.replace(/^.*\//, '').replace('.php', ''))) {
      link.classList.add('active');
    }
  });

  // ── Auto-dismiss Alerts ──────────────────────────────────────
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
    const delay = parseInt(alert.dataset.autoDismiss) || 4000;
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity    = '0';
      alert.style.transform  = 'translateY(-8px)';
      setTimeout(() => alert.remove(), 400);
    }, delay);
  });

  // ── Confirm Delete ───────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      const msg = el.dataset.confirm || 'هل أنت متأكد من هذا الإجراء؟';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // ── Number Format Inputs ─────────────────────────────────────
  document.querySelectorAll('input[data-type="money"]').forEach(input => {
    input.addEventListener('blur', () => {
      const val = parseFloat(input.value.replace(/,/g, ''));
      if (!isNaN(val)) input.value = val.toFixed(2);
    });
  });

  // ── Auto-calculate remaining ─────────────────────────────────
  const totalInput   = document.getElementById('totalAmount');
  const paidInput    = document.getElementById('paidAmount');
  const remainingEl  = document.getElementById('remainingAmount');

  if (totalInput && paidInput && remainingEl) {
    const calc = () => {
      const total   = parseFloat(totalInput.value) || 0;
      const paid    = parseFloat(paidInput.value)  || 0;
      const remaining = total - paid;
      remainingEl.textContent = remaining.toLocaleString('ar-EG', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
      }) + ' ' + (window.CURRENCY || 'جنيه');
      remainingEl.style.color = remaining > 0 ? 'var(--danger)' : 'var(--success)';
    };
    totalInput.addEventListener('input', calc);
    paidInput.addEventListener('input',  calc);
    calc();
  }

  // ── Subscription end date auto-calculate ────────────────────
  const startDateInput    = document.getElementById('startDate');
  const durationSelect    = document.getElementById('durationMonths');
  const endDateInput      = document.getElementById('endDate');

  if (startDateInput && durationSelect && endDateInput) {
    const calcEndDate = () => {
      const start    = startDateInput.value;
      const months   = parseInt(durationSelect.value);
      if (start && months > 0) {
        const d = new Date(start);
        d.setMonth(d.getMonth() + months);
        d.setDate(d.getDate() - 1);
        endDateInput.value = d.toISOString().split('T')[0];
      }
    };
    startDateInput.addEventListener('change', calcEndDate);
    durationSelect.addEventListener('change', calcEndDate);
  }

  // ── Table Search Filter ──────────────────────────────────────
  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    tableSearch.addEventListener('input', (e) => {
      const query = e.target.value.toLowerCase().trim();
      document.querySelectorAll('table.data-table tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }

  // ── Tooltips ─────────────────────────────────────────────────
  document.querySelectorAll('[data-tooltip]').forEach(el => {
    el.setAttribute('title', el.dataset.tooltip);
  });

  // ── Form Validation ──────────────────────────────────────────
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', (e) => {
      let valid = true;
      form.querySelectorAll('[required]').forEach(field => {
        const group = field.closest('.form-group');
        if (!field.value.trim()) {
          valid = false;
          field.classList.add('is-invalid');
          let err = group?.querySelector('.invalid-feedback');
          if (!err) {
            err = document.createElement('div');
            err.className = 'invalid-feedback';
            err.innerHTML = '<i class="fas fa-exclamation-circle"></i> هذا الحقل مطلوب';
            group?.appendChild(err);
          }
        } else {
          field.classList.remove('is-invalid');
          group?.querySelector('.invalid-feedback')?.remove();
        }
      });
      if (!valid) {
        e.preventDefault();
        // Scroll to first error
        const firstErr = form.querySelector('.is-invalid');
        firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    // Live validation
    form.querySelectorAll('[required]').forEach(field => {
      field.addEventListener('input', () => {
        if (field.value.trim()) {
          field.classList.remove('is-invalid');
          field.closest('.form-group')?.querySelector('.invalid-feedback')?.remove();
        }
      });
    });
  });

  // ── Print button ─────────────────────────────────────────────
  document.querySelectorAll('[data-print]').forEach(btn => {
    btn.addEventListener('click', () => window.print());
  });

  // ── Copy to clipboard ────────────────────────────────────────
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = btn.dataset.copy;
      navigator.clipboard?.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => btn.innerHTML = orig, 1500);
      });
    });
  });

});

// ── Global Helpers ─────────────────────────────────────────────

/**
 * Modal helpers
 */
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => modal.querySelector('.modal')?.classList.add('animate-in'));
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.style.display = 'none';
    document.body.style.overflow = '';
  }
});

// Close modal on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay[style*="flex"]').forEach(m => {
      m.style.display = 'none';
      document.body.style.overflow = '';
    });
  }
});

/**
 * Show toast notification
 */
function showToast(message, type = 'success', duration = 3500) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
      position: fixed; bottom: 24px; left: 24px; z-index: 9999;
      display: flex; flex-direction: column; gap: 10px; pointer-events: none;
    `;
    document.body.appendChild(container);
  }

  const icons = { success: 'check-circle', error: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
  const toast = document.createElement('div');
  toast.style.cssText = `
    display: flex; align-items: center; gap: 10px;
    background: #fff; border-radius: 10px; padding: 12px 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,.15); pointer-events: all;
    font-family: 'Cairo', sans-serif; font-size: 13.5px; font-weight: 600;
    min-width: 240px; max-width: 340px; direction: rtl;
    animation: slideUp .25s ease;
    border-right: 4px solid var(--${type === 'error' ? 'danger' : type});
  `;
  toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}" style="color:var(--${type === 'error' ? 'danger' : type}); font-size:17px; flex-shrink:0;"></i><span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.transition = 'opacity .3s, transform .3s';
    toast.style.opacity    = '0';
    toast.style.transform  = 'translateY(8px)';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/**
 * AJAX helper
 */
async function apiPost(url, data = {}) {
  try {
    const formData = new FormData();
    Object.entries(data).forEach(([k, v]) => formData.append(k, v));
    const res  = await fetch(url, { method: 'POST', body: formData });
    return await res.json();
  } catch (err) {
    return { success: false, message: 'خطأ في الاتصال بالخادم' };
  }
}

/**
 * Format number as currency
 */
function formatMoney(amount, currency = window.CURRENCY || 'جنيه') {
  return parseFloat(amount).toLocaleString('ar-EG', {
    minimumFractionDigits: 2, maximumFractionDigits: 2
  }) + ' ' + currency;
}

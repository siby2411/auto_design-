// ============================================
// OMEGA TECH AUTO — JavaScript Principal
// ============================================

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('show');
}

// Auto-dismiss flash après 4s
const flash = document.getElementById('flashMsg');
if (flash) {
  setTimeout(() => {
    flash.style.opacity = '0';
    flash.style.transform = 'translateY(-10px)';
    flash.style.transition = 'all .4s ease';
    setTimeout(() => flash.remove(), 400);
  }, 4000);
}

// Modal helpers
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
  document.body.style.overflow = '';
}
// Clic overlay = ferme
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

// Image preview upload
function previewImage(input, targetId) {
  const target = document.getElementById(targetId);
  if (!target) return;
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      target.src = e.target.result;
      target.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Table search
function tableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.addEventListener('keyup', () => {
    const val = input.value.toLowerCase();
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
  });
}

// Print page
function printPage() { window.print(); }

// Confirm delete
function confirmDelete(form, msg) {
  if (confirm(msg || 'Supprimer cet élément ?')) form.submit();
}

// Gallery filter
function filterGallery(type) {
  document.querySelectorAll('.series-tab').forEach(t => t.classList.remove('active'));
  event.target.classList.add('active');

  document.querySelectorAll('.vehicle-card').forEach(card => {
    if (type === 'all') {
      card.style.display = '';
    } else {
      const cardType = card.dataset.type || '';
      card.style.display = (cardType === type || cardType === 'les_deux') ? '' : 'none';
    }
  });
}

// Number formatting
function fmtNumber(n) {
  return parseInt(n).toLocaleString('fr-FR');
}

// Dynamic price calculation for rental form
function calcRentalTotal() {
  const start = document.getElementById('start_date');
  const end   = document.getElementById('end_date');
  const rate  = document.getElementById('daily_rate');
  const total = document.getElementById('total_amount_display');
  if (!start || !end || !rate || !total) return;

  const d1 = new Date(start.value);
  const d2 = new Date(end.value);
  const days = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
  if (days > 0 && rate.value) {
    const amt = days * parseFloat(rate.value);
    total.textContent = fmtNumber(amt) + ' FCFA (' + days + ' jour' + (days > 1 ? 's' : '') + ')';
  } else {
    total.textContent = '—';
  }
}
document.getElementById('start_date')?.addEventListener('change', calcRentalTotal);
document.getElementById('end_date')?.addEventListener('change', calcRentalTotal);
document.getElementById('daily_rate')?.addEventListener('input', calcRentalTotal);

// Vehicle card hover detail
document.querySelectorAll('.vehicle-card').forEach(card => {
  card.addEventListener('click', function() {
    const url = this.dataset.url;
    if (url) window.location.href = url;
  });
});

console.log('%c OMEGA TECH AUTO v1.0 ', 'background:#C9A84C;color:#000;font-weight:bold;padding:4px 8px;border-radius:4px;');

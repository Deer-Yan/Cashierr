// Shared utilities used across all pages

function formatRupiah(num) {
  num = Number(num) || 0;
  return 'Rp ' + num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function formatDateTime(str) {
  const d = new Date(str.replace(' ', 'T'));
  return d.toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

function toast(message, type = 'success') {
  let el = document.getElementById('toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast';
    el.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:2000;padding:13px 18px;border-radius:10px;font-size:13.5px;font-weight:600;box-shadow:0 6px 20px rgba(0,0,0,.15);transition:opacity .2s, transform .2s; max-width: 90vw;';
    document.body.appendChild(el);
  }
  el.textContent = message;
  el.style.background = type === 'success' ? '#2f9e44' : '#d92d20';
  el.style.color = '#fff';
  el.style.opacity = '1';
  el.style.transform = 'translateY(0)';
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(8px)';
  }, 2800);
}

// Semua panggilan API dibatasi waktu (timeout) — kalau backend tidak merespons
// dalam 15 detik (misal query nyangkut / server tidak jalan), fetch dibatalkan
// dan mengembalikan {success:false, message:...} alih-alih membuat halaman
// menunggu selamanya tanpa pesan apa pun ("Memuat..." yang tidak pernah selesai).
async function apiJson(url, method = 'GET', body = null) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 15000);
  try {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      signal: controller.signal,
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(API_BASE + '/' + url, opts);
    clearTimeout(timeoutId);
    return await res.json();
  } catch (err) {
    clearTimeout(timeoutId);
    const message = err.name === 'AbortError'
      ? 'Server tidak merespons (timeout). Coba lagi.'
      : 'Tidak bisa terhubung ke server backend.';
    return { success: false, message };
  }
}

async function apiForm(url, formData) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 20000);
  try {
    const res = await fetch(API_BASE + '/' + url, {
      method: 'POST',
      body: formData,
      credentials: 'include',
      signal: controller.signal,
    });
    clearTimeout(timeoutId);
    return await res.json();
  } catch (err) {
    clearTimeout(timeoutId);
    const message = err.name === 'AbortError'
      ? 'Server tidak merespons (timeout). Coba lagi.'
      : 'Tidak bisa terhubung ke server backend.';
    return { success: false, message };
  }
}

// Prefix path foto/upload dari backend
function uploadUrl(filename) {
  return API_BASE + '/uploads/' + filename;
}

// Sidebar toggle for mobile + bind the logout button immediately, since it's
// now static markup present on page load (not injected only after auth succeeds).
function initSidebar() {
  const burger = document.getElementById('hamburger');
  const sidebar = document.getElementById('sidebar');

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn && !logoutBtn.dataset.bound) {
    logoutBtn.dataset.bound = '1';
    logoutBtn.addEventListener('click', doLogout);
  }

  if (!burger || !sidebar) return;
  burger.addEventListener('click', () => sidebar.classList.toggle('open'));
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && e.target !== burger && !burger.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    }
  });
}

async function doLogout() {
  await apiJson('api/logout.php', 'POST');
  window.location.href = 'index.html';
}

// Cek session ke backend. Jika tidak login, lempar ke halaman login.
// Jika login, kembalikan data user (nama_pengguna, nama_toko, email, role).
// Punya guard sederhana agar tidak bolak-balik redirect tanpa henti kalau ada
// masalah (misal cookie sesi tidak konsisten) — kalau sudah dilempar lebih dari
// sekali dalam waktu singkat, berhenti dan tampilkan pesan alih-alih loop diam-diam.
async function requireAuth() {
  try {
    const res = await apiJson('api/me.php');
    if (!res.success) {
      redirectToLogin();
      return null;
    }
    return res.data;
  } catch (err) {
    redirectToLogin();
    return null;
  }
}

function redirectToLogin() {
  const key = 'kt_redirect_count';
  const count = parseInt(sessionStorage.getItem(key) || '0', 10) + 1;
  sessionStorage.setItem(key, String(count));
  if (count > 3) {
    // Kemungkinan loop — berhenti redirect otomatis dan biarkan pengguna klik manual.
    document.body.innerHTML = `
      <div style="max-width:420px; margin:80px auto; text-align:center; font-family:system-ui,sans-serif; padding:0 20px;">
        <h3>Sesi bermasalah</h3>
        <p style="color:#6b7280; font-size:13.5px;">Tidak bisa memverifikasi sesi login Anda. Silakan login ulang.</p>
        <a href="index.html" style="display:inline-block; margin-top:10px; padding:10px 18px; background:#3b5bdb; color:#fff; border-radius:8px; text-decoration:none; font-size:14px; font-weight:600;">Ke Halaman Login</a>
      </div>`;
    sessionStorage.removeItem(key);
    return;
  }
  window.location.href = 'index.html';
}

// Halaman yang boleh diakses akun role 'staff' — di luar ini akan dilempar ke Penjualan.
const STAFF_ALLOWED_PAGES = ['penjualan.html'];

// Render isi sidebar (nav + info toko) secara dinamis — tombol Keluar sendiri
// sudah ada permanen di HTML setiap halaman, jadi selalu bisa diklik walau
// bagian ini belum/gagal ter-render.
function renderSidebar(user) {
  const current = currentPageName();
  const isAdmin = user.role === 'admin';

  const menu = isAdmin
    ? [
        { href: 'dashboard.html', label: 'Dashboard' },
        { href: 'barang.html', label: 'Barang' },
        { href: 'penjualan.html', label: 'Penjualan' },
        { href: 'riwayat.html', label: 'Riwayat Penjualan' },
        { href: 'pengguna.html', label: 'Kelola Pengguna' },
      ]
    : [
        { href: 'penjualan.html', label: 'Penjualan' },
      ];

  const navHtml = menu.map(m => `
    <a href="${m.href}" class="nav-item ${m.href === current ? 'active' : ''}">${m.label}</a>
  `).join('');

  const brandLabel = document.getElementById('sidebarRoleLabel');
  if (brandLabel) brandLabel.textContent = isAdmin ? 'Admin' : 'Staff';

  const navEl = document.getElementById('sidebarNav');
  if (navEl) navEl.innerHTML = navHtml;

  const userEl = document.getElementById('sidebarUser');
  if (userEl) {
    userEl.innerHTML = `
      <strong>${escapeHtmlGlobal(user.nama_toko)}</strong>
      ${escapeHtmlGlobal(user.nama_pengguna)}
    `;
  }
}

function currentPageName() {
  // Setiap halaman terproteksi mendeklarasikan namanya sendiri lewat
  // window.CURRENT_PAGE (lihat tag <script> di awal <body>). Ini sengaja
  // tidak dibaca dari window.location.pathname, karena beberapa dev server
  // (Live Server, VS Code Live Share, dsb.) menyajikan halaman tanpa akhiran
  // ".html" di address bar — kalau dibaca dari URL, itu bisa salah deteksi
  // halaman dan memicu redirect loop.
  return window.CURRENT_PAGE || 'index.html';
}

function escapeHtmlGlobal(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// Auth guard + render sidebar untuk halaman terproteksi.
// Panggil di awal setiap halaman (selain index.html) lalu lanjutkan load data.
// Akun role 'staff' otomatis dilempar ke Penjualan jika mencoba buka halaman lain.
async function initProtectedPage() {
  const user = await requireAuth();
  if (!user) return null;

  sessionStorage.removeItem('kt_redirect_count');

  const current = currentPageName();
  if (user.role !== 'admin' && !STAFF_ALLOWED_PAGES.includes(current)) {
    window.location.href = 'penjualan.html';
    return null;
  }

  renderSidebar(user);
  initSidebar();
  return user;
}

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
});

/* Shared UI Script for Admin, BPS, and OPD layouts */

/**
 * Helper constant untuk key theme di localStorage.
 */
const THEME_KEY = 'kp_theme';

/**
 * Mendapatkan tema saat ini dari attribute html, default fallback ke 'light'.
 * @returns {string} Tema ('dark' atau 'light')
 */
function getTheme() {
  return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

/**
 * Menyimpan dan mengimplementasikan tema ke DOM (html class & attribute) dan localStorage.
 * Diikuti dengan pemanggilan fungsi untuk update UI Toggle Icon.
 * @param {string} theme Tema yang ingin diset ('dark' atau 'light')
 */
function setTheme(theme) {
  const nextTheme = theme === 'dark' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', nextTheme);
  if (nextTheme === 'dark') {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
  try { localStorage.setItem(THEME_KEY, nextTheme); } catch(e) {}
  updateThemeToggleUi(nextTheme);
}

/**
 * Mengupdate icon tombol pengubah tema sesuai opsi saat ini.
 * @param {string} theme Tema saat ini (misal di return dari fungsi getTheme)
 */
function updateThemeToggleUi(theme) {
  const btns = document.querySelectorAll('.theme-toggle');
  btns.forEach(btn => {
    // Karena struktur HTML kita ada id 'themeToggleIcon' dan 'themeToggleText' di dalamnya,
    // kita cari berdasarkan selector tag/class di dalam btn jika id dupikat, atau i / span.
    const icon = btn.querySelector('i');
    const text = btn.querySelector('span:not(.nav-ico)'); // span yang berisi text
    
    if(theme === 'dark') {
      if(icon) icon.className = 'bi bi-brightness-high-fill';
      if(text) text.textContent = 'Light Mode';
      btn.setAttribute('title', 'Ganti ke Light Mode');
      btn.setAttribute('aria-label', 'Ganti ke Light Mode');
    } else {
      if(icon) icon.className = 'bi bi-moon-stars-fill';
      if(text) text.textContent = 'Dark Mode';
      btn.setAttribute('title', 'Ganti ke Dark Mode');
      btn.setAttribute('aria-label', 'Ganti ke Dark Mode');
    }
  });
}

/**
 * Memulai (Init) fungsionalitas Toggle switch untuk tema, memasang listener click pada class '.theme-toggle'.
 */
function initThemeToggle() {
  updateThemeToggleUi(getTheme());
  const btns = document.querySelectorAll('.theme-toggle');
  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      setTheme(getTheme() === 'dark' ? 'light' : 'dark');
    });
  });
}

/**
 * Menampilkan Vanilla Custom Toast (Notifikasi Pop-up) di layout App (Admin/Umum).
 * @param {string} message Pesan yang ingin ditampilkan di dalam body toast.
 * @param {string} type Jenis feedback (error, warning, success, info, dark, atau danger).
 * @param {string} targetId ID element container pembungkus toast (default 'appToast').
 */
function showToast(message, type='dark', targetId='appToast') {
  const el = document.getElementById(targetId);
  const body = document.getElementById(targetId + 'Body');
  if(!el || !body) return;

  // Reset semua warna dan hide element sebelumnya.
  el.classList.remove('bg-gray-800', 'bg-green-600', 'bg-red-600', 'bg-yellow-600', 'bg-blue-600');
  el.classList.add('hidden', 'opacity-0'); // Ensure it's hidden initially

  // Definisikan map styling background berdasar jenis toast
  const map = {
    dark: 'bg-gray-800',
    success: 'bg-green-600', // Hijau
    danger: 'bg-red-600',    // Merah
    error: 'bg-red-600',     // Merah
    warning: 'bg-yellow-600',// Kuning
    info: 'bg-blue-600'      // Biru
  };
  el.classList.add(map[type] || map.dark);
  
  body.textContent = message;
  
  el.classList.remove('hidden');
  setTimeout(() => {
    el.classList.add('opacity-100');
  }, 10);

  // Auto-hide toast setelah 3 detik
  setTimeout(() => {
    el.classList.remove('opacity-100');
    el.classList.add('opacity-0');
    setTimeout(() => {
      el.classList.add('hidden');
    }, 300);
  }, 3000);
}

/**
 * Toggle (Buka/Tutup) Sidebar Menu pada mode Tampilan Mobile Layar Kecil.
 * Akan melakukan transisi slide-in untuk sidebar, dan fade-in untuk overlay penggelap belakang.
 */
function toggleMobileSidebar() {
  const sidebar = document.getElementById('mobileSidebar');
  const overlay = document.getElementById('mobileSidebarOverlay');
  if(!sidebar || !overlay) return;
  
  if (sidebar.classList.contains('-translate-x-full')) {
      sidebar.classList.remove('-translate-x-full');
      overlay.classList.remove('hidden');
      setTimeout(() => overlay.classList.add('opacity-100'), 10);
  } else {
      sidebar.classList.remove('opacity-100');
      sidebar.classList.add('-translate-x-full');
      overlay.classList.remove('opacity-100');
      setTimeout(() => overlay.classList.add('hidden'), 300);
  }
}

/**
 * Inactivity Logout Logic
 * Redirects to login page after 15 minutes (900 seconds) of inactivity.
 */
function initInactivityTimer() {
    // Only run if user is logged in (not on login page)
    if (window.location.pathname.includes('/login')) return;

    let timeout;
    const INACTIVITY_TIME = 15 * 60 * 1000; // 15 minutes

    function resetTimer() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            // Redirect to logout first via POST if possible, 
            // but for simple inactivity we can just redirect to login with flag.
            // Our Auth middleware will handle session expiration.
            window.location.href = '/login?timeout=1';
        }, INACTIVITY_TIME);
    }

    // Events that count as activity
    const activityEvents = [
        'mousedown', 'mousemove', 'keypress', 
        'scroll', 'touchstart', 'click'
    ];

    activityEvents.forEach(eventName => {
        document.addEventListener(eventName, resetTimer, true);
    });

    resetTimer(); // Start timer
}

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initInactivityTimer();
});

window.showToast = showToast;
window.toggleMobileSidebar = toggleMobileSidebar;

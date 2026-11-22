// Funcoes puras de apoio.
export function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[ch]));
}

export function formatViews(n) {
  n = Number(n || 0);
  return n >= 1000 ? (n / 1000).toFixed(1).replace('.0', '') + 'k' : String(n);
}

export function formatDuration(seconds) {
  const s = Number(seconds || 0);
  return s < 3600 ? Math.floor(s / 60) + ' min' : (s / 3600).toFixed(1) + ' h';
}

export function debounce(fn, ms) {
  let timer = null;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), ms);
  };
}

export function sameModel(a, b) {
  return String(a || '').toLowerCase() === String(b || '').toLowerCase();
}

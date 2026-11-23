// Animate progress bars from 0 to their server-calculated width.
(function () {
  'use strict';
  const bars = document.querySelectorAll('[data-width]');
  requestAnimationFrame(() => requestAnimationFrame(() => {
    bars.forEach((b) => { b.style.width = Math.max(0, Math.min(100, Number(b.dataset.width) || 0)) + '%'; });
  }));
})();

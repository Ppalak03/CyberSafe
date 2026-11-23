// Reduce-motion preference: a harmless UI setting kept in this browser (localStorage).
(function () {
  'use strict';
  const box = document.getElementById('pref-motion');
  if (!box) return;
  let on = false;
  try { on = localStorage.getItem('cs_reduce_motion') === '1'; } catch (e) { /* storage unavailable */ }
  box.checked = on;
  box.addEventListener('change', () => {
    try { localStorage.setItem('cs_reduce_motion', box.checked ? '1' : '0'); } catch (e) { /* ignore */ }
    document.documentElement.dataset.reduceMotion = box.checked ? '1' : '0';
    window.showToast('success', box.checked ? 'Animations reduced.' : 'Animations restored.');
  });
})();

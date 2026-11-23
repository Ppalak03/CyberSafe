// Answer selection and submission. The server decides correctness; this only improves UX.
(function () {
  'use strict';
  const form = document.getElementById('answer-form');
  if (!form) return;
  const btn = document.getElementById('submit-btn');
  const radios = form.querySelectorAll('input[name=option_id]');
  const started = Date.now();
  let busy = false;

  btn.disabled = true; // enabled once an answer is chosen

  function sync() {
    let chosen = false;
    radios.forEach((r) => {
      r.closest('.choice').classList.toggle('selected', r.checked);
      chosen = chosen || r.checked;
    });
    btn.disabled = !chosen || busy;
  }
  radios.forEach((r) => r.addEventListener('change', sync));
  sync();

  form.addEventListener('submit', async (e) => {
    const chosen = form.querySelector('input[name=option_id]:checked');
    if (!chosen) { e.preventDefault(); window.showToast('warning', 'Please select an answer.'); return; }
    if (!window.fetch) return; // fall back to the normal form post
    e.preventDefault();
    if (busy) return; // ignore double-clicks
    busy = true; btn.disabled = true; btn.classList.add('is-loading'); btn.textContent = 'Checking…';

    try {
      const res = await fetch(form.dataset.api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': form.elements.csrf.value },
        credentials: 'same-origin',
        body: JSON.stringify({
          scenario_id: Number(form.elements.scenario_id.value),
          option_id: Number(chosen.value),
          attempt_token: form.elements.attempt_token.value,
          response_time: Math.round((Date.now() - started) / 1000),
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.ok && data.redirect) { window.location.href = data.redirect; return; }
      window.showToast('error', data.error || 'Unable to save your progress. Please try again.');
      if (res.status === 401) { window.location.href = window.CYBERSAFE.base + '/auth/login.php'; return; }
    } catch (err) {
      window.showToast('error', 'Network problem. Please check your connection and try again.');
    }
    busy = false; btn.classList.remove('is-loading'); btn.textContent = 'Submit answer'; sync();
  });
})();

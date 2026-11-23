// Friendly client-side checks for login/register. The server re-validates everything.
(function () {
  'use strict';
  const form = document.querySelector('form[data-validate]');
  if (!form) return;
  const mode = form.dataset.validate;

  const setError = (name, msg) => {
    const input = form.elements[name];
    const out = form.querySelector('[data-error-for="' + name + '"]');
    if (out) out.textContent = msg;
    if (input) input.classList.toggle('is-invalid', !!msg);
  };

  function problems(pw) {
    const p = [];
    if (pw.length < 8) p.push('Use at least 8 characters.');
    if (!/[A-Z]/.test(pw)) p.push('Add an uppercase letter.');
    if (!/[a-z]/.test(pw)) p.push('Add a lowercase letter.');
    if (!/\d/.test(pw)) p.push('Add a number.');
    return p;
  }

  function validate() {
    let ok = true;
    const f = form.elements;
    if (mode === 'register') {
      const name = f.name.value.trim();
      setError('name', name ? '' : 'Enter your full name.'); ok = ok && !!name;
    }
    const emailOk = f.email.checkValidity() && f.email.value.trim() !== '';
    setError('email', emailOk ? '' : 'Enter a valid email address.'); ok = ok && emailOk;

    if (mode === 'register') {
      const pr = problems(f.password.value);
      setError('password', pr.join(' ')); ok = ok && pr.length === 0;
      const match = f.password.value === f.confirm_password.value;
      setError('confirm_password', match ? '' : 'Passwords do not match.'); ok = ok && match;
    } else {
      const has = f.password.value !== '';
      setError('password', has ? '' : 'Enter your password.'); ok = ok && has;
    }
    return ok;
  }

  form.addEventListener('submit', (e) => {
    if (!validate()) { e.preventDefault(); return; }
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true; // prevents double submits
    btn.classList.add('is-loading');
  });
})();

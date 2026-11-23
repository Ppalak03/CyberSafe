// Show only the content fields that belong to the selected scenario type.
(function () {
  'use strict';
  const select = document.getElementById('content_type');
  if (!select) return;
  const sets = document.querySelectorAll('fieldset.ctype');
  function sync() { sets.forEach((s) => { s.hidden = s.dataset.type !== select.value; }); }
  select.addEventListener('change', sync);
  sync();
})();

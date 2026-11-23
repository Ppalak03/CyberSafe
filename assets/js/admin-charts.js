// Admin statistics charts. Data is embedded as JSON by the server (no extra request).
(function () {
  'use strict';
  const holder = document.getElementById('admin-chart-data');
  if (!holder) return;
  const err = document.getElementById('chart-error');
  if (typeof Chart === 'undefined') { if (err) err.hidden = false; return; }
  let d;
  try { d = JSON.parse(holder.textContent); } catch (e) { if (err) err.hidden = false; return; }

  const C = { blue: '#38BDF8', teal: '#2DD4BF', green: '#34D399', red: '#F87171', amber: '#FBBF24', text: '#94A3B8', grid: 'rgba(51,70,95,.6)' };
  Chart.defaults.color = C.text; Chart.defaults.borderColor = C.grid;
  Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
  const reduce = document.documentElement.dataset.reduceMotion === '1' || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  Chart.defaults.animation = reduce ? false : { duration: 500 };
  const integerY = { beginAtZero: true, ticks: { precision: 0 } };
  const pctY = { min: 0, max: 100, ticks: { callback: (v) => v + '%' } };
  const mk = (id, cfg) => { const el = document.getElementById(id); if (el) new Chart(el, cfg); };
  const noLegend = { legend: { display: false } };
  const color = (v) => (v >= 75 ? C.green : v >= 50 ? C.amber : C.red);

  mk('c-reg', { type: 'bar', data: { labels: d.registrations.labels, datasets: [{ data: d.registrations.values, backgroundColor: C.teal, borderRadius: 4 }] },
    options: { maintainAspectRatio: false, scales: { y: integerY, x: { ticks: { maxTicksLimit: 8 } } }, plugins: noLegend } });
  mk('c-att', { type: 'line', data: { labels: d.attempts.labels, datasets: [{ data: d.attempts.values, borderColor: C.blue, backgroundColor: 'rgba(56,189,248,.15)', fill: true, tension: .3, pointRadius: 2 }] },
    options: { maintainAspectRatio: false, scales: { y: integerY, x: { ticks: { maxTicksLimit: 8 } } }, plugins: noLegend } });
  mk('c-pop', { type: 'bar', data: { labels: d.popularity.labels, datasets: [{ data: d.popularity.values, backgroundColor: C.blue, borderRadius: 4 }] },
    options: { indexAxis: 'y', maintainAspectRatio: false, scales: { x: integerY }, plugins: noLegend } });
  mk('c-cat', { type: 'bar', data: { labels: d.category.labels, datasets: [{ data: d.category.values, backgroundColor: d.category.values.map(color), borderRadius: 6 }] },
    options: { maintainAspectRatio: false, scales: { y: pctY, x: { ticks: { maxRotation: 45, minRotation: 0 } } }, plugins: noLegend } });
  mk('c-diff', { type: 'bar', data: { labels: d.difficulty.labels, datasets: [{ data: d.difficulty.values, backgroundColor: d.difficulty.values.map(color), borderRadius: 6 }] },
    options: { maintainAspectRatio: false, scales: { y: pctY }, plugins: noLegend } });
})();

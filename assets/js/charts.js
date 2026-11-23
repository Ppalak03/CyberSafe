// Draws the three progress charts from api/progress-data.php (read-only JSON).
(function () {
  'use strict';
  const line = document.getElementById('chart-line');
  if (!line) return;
  const errBox = document.getElementById('chart-error');
  const fail = () => { if (errBox) errBox.hidden = false; };
  if (typeof Chart === 'undefined') { fail(); return; }

  const C = { blue: '#38BDF8', teal: '#2DD4BF', green: '#34D399', red: '#F87171', amber: '#FBBF24', text: '#94A3B8', grid: 'rgba(51,70,95,.6)' };
  Chart.defaults.color = C.text;
  Chart.defaults.font.family = "Inter, system-ui, sans-serif";
  Chart.defaults.borderColor = C.grid;
  const reduce = document.documentElement.dataset.reduceMotion === '1' || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  Chart.defaults.animation = reduce ? false : { duration: 600 };

  fetch(window.CYBERSAFE.base + '/api/progress-data.php', { credentials: 'same-origin' })
    .then((r) => r.json())
    .then((d) => {
      if (!d.ok) throw new Error(d.error || 'bad data');

      new Chart(line, {
        type: 'line',
        data: { labels: d.timeline.labels, datasets: [
          { label: 'Running accuracy (%)', data: d.timeline.cumulative, borderColor: C.blue, backgroundColor: 'rgba(56,189,248,.15)', fill: true, tension: .3, pointRadius: 4 },
        ] },
        options: { maintainAspectRatio: false, scales: { y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } } }, plugins: { legend: { display: false } } },
      });

      new Chart(document.getElementById('chart-doughnut'), {
        type: 'doughnut',
        data: { labels: ['Correct', 'Incorrect'], datasets: [{ data: [d.outcome.correct, d.outcome.incorrect], backgroundColor: [C.green, C.red], borderColor: '#1B2B42', borderWidth: 3 }] },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
      });

      new Chart(document.getElementById('chart-bar'), {
        type: 'bar',
        data: { labels: d.category.labels, datasets: [{ label: 'Accuracy (%)', data: d.category.accuracy,
          backgroundColor: d.category.accuracy.map((a) => (a >= 75 ? C.green : a >= 50 ? C.amber : C.red)), borderRadius: 6 }] },
        options: { maintainAspectRatio: false, scales: { y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } }, x: { ticks: { maxRotation: 45, minRotation: 0 } } }, plugins: { legend: { display: false } } },
      });
    })
    .catch(fail);
})();

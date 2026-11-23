<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    redirect(($_SESSION['role'] ?? 'user') === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
}

// Category cards come from MySQL; fail soft so the landing page still renders.
$categories = [];
try {
    $categories = db()->query(
        'SELECT c.name, c.description, c.icon, COUNT(s.id) AS scenarios
         FROM categories c LEFT JOIN scenarios s ON s.category_id = c.id AND s.is_active = 1
         WHERE c.is_active = 1 GROUP BY c.id, c.name, c.description, c.icon ORDER BY c.id'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('[landing] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/app.log');
}

$layout = 'public';
$pageTitle = 'Think before you click';
$extraCss = ['landing.css'];
include __DIR__ . '/includes/header.php';
?>
<section class="hero" id="home">
  <div class="hero-copy">
    <p class="eyebrow">Interactive cybersecurity training</p>
    <h1>Think Before You Click.</h1>
    <p class="lead">Learn to identify online threats, recognize suspicious messages, and protect yourself through interactive cybersecurity challenges.</p>
    <div class="btn-row">
      <a class="btn btn-primary" href="<?= e(url('auth/register.php')) ?>">Start Learning</a>
      <a class="btn btn-ghost" href="#categories">Explore Challenges</a>
    </div>
    <ul class="hero-points">
      <li><i class="bi bi-check2-circle"></i> Realistic scenarios</li>
      <li><i class="bi bi-check2-circle"></i> Instant feedback</li>
      <li><i class="bi bi-check2-circle"></i> Track progress</li>
    </ul>
  </div>
  <div class="hero-visual" aria-hidden="true">
    <div class="mail-card">
      <span class="tag">Example scenario</span>
      <p class="mail-meta">From: Security Team &lt;alert@secure-example.test&gt;</p>
      <p class="mail-meta">Subject: Immediate action required</p>
      <p>Your account will be suspended today. Verify your details using the link below.</p>
      <p class="mail-link">secure-login-example.test</p>
      <div class="choices"><span class="c-safe">Safe</span><span class="c-susp">Suspicious</span><span class="c-scam">Scam</span></div>
    </div>
  </div>
</section>

<section class="section" id="about">
  <div class="split">
    <div>
      <h2>About CyberSafe</h2>
      <p class="muted">CyberSafe is an educational platform that turns everyday online risks into short, hands-on challenges. You read a realistic fictional message, classify it as Safe, Suspicious or Scam, and get an immediate explanation of the clues you should have noticed.</p>
    </div>
    <div>
      <h2>Why awareness matters</h2>
      <p class="muted">Most attacks succeed by persuading a person, not by breaking software. Urgent language, look-alike links and requests for money or one-time codes work on everyone. Practising in a safe space builds the habit of pausing and verifying.</p>
    </div>
  </div>
</section>

<section class="section" id="features">
  <h2>Main features</h2>
  <div class="grid-3">
    <article class="card feat"><i class="bi bi-envelope-exclamation"></i><h3>Realistic scenarios</h3><p class="muted">Emails, texts, job offers, links, login pages and chats, all using fictional brands and safe example domains.</p></article>
    <article class="card feat"><i class="bi bi-lightbulb"></i><h3>Instant explanations</h3><p class="muted">Every answer comes with the reasoning, the red flags and a practical safety tip.</p></article>
    <article class="card feat"><i class="bi bi-search"></i><h3>Interactive threat detection</h3><p class="muted">Decide for yourself before you see the answer. Correctness is checked on the server.</p></article>
    <article class="card feat"><i class="bi bi-trophy"></i><h3>XP and achievements</h3><p class="muted">Earn XP, climb six levels and unlock badges as your skills grow.</p></article>
    <article class="card feat"><i class="bi bi-graph-up"></i><h3>Personal analytics</h3><p class="muted">See accuracy over time and find the categories where you need more practice.</p></article>
    <article class="card feat"><i class="bi bi-shield-lock"></i><h3>Safe by design</h3><p class="muted">No real credentials are ever collected and no live malicious links exist anywhere in the app.</p></article>
  </div>
</section>

<section class="section" id="categories">
  <h2>Challenge categories</h2>
  <?php if ($categories): ?>
  <div class="grid-3">
    <?php foreach ($categories as $c): ?>
    <article class="card feat">
      <i class="bi <?= e($c['icon']) ?>"></i>
      <h3><?= e($c['name']) ?></h3>
      <p class="muted"><?= e($c['description']) ?></p>
      <p class="count"><?= (int)$c['scenarios'] ?> scenarios</p>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p class="muted">Categories are not available right now. Please check back soon.</p>
  <?php endif; ?>
  <div class="btn-row"><a class="btn btn-primary" href="<?= e(url('auth/register.php')) ?>">Create a free account</a></div>
</section>

<section class="section" id="how-it-works">
  <h2>How it works</h2>
  <ol class="steps">
    <li><span>1</span><div><h3>Read the scenario</h3><p class="muted">Inspect the sender, wording, link and request.</p></div></li>
    <li><span>2</span><div><h3>Make your call</h3><p class="muted">Choose Safe, Suspicious or Scam.</p></div></li>
    <li><span>3</span><div><h3>Learn why</h3><p class="muted">See the explanation, red flags and a safety tip.</p></div></li>
    <li><span>4</span><div><h3>Level up</h3><p class="muted">Earn XP and badges, then target your weak spots.</p></div></li>
  </ol>
</section>

<section class="section" id="benefits">
  <h2>Learning benefits</h2>
  <ul class="benefits">
    <li><i class="bi bi-check2-circle"></i> Recognize common warning signs faster</li>
    <li><i class="bi bi-check2-circle"></i> Build the habit of verifying before acting</li>
    <li><i class="bi bi-check2-circle"></i> Practise safely with no real-world risk</li>
    <li><i class="bi bi-check2-circle"></i> Track improvement over time</li>
  </ul>
</section>

<footer class="site-footer">
  <a class="brand" href="#home"><i class="bi bi-shield-lock-fill"></i> CyberSafe</a>
  <p class="muted">All scenarios are fictional and for education only.</p>
  <p class="muted">&copy; <?= date('Y') ?> CyberSafe</p>
</footer>
<?php include __DIR__ . '/includes/footer.php'; ?>

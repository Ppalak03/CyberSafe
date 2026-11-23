<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';
require_once __DIR__ . '/../includes/scenario_render.php';

$s = db()->prepare('SELECT s.*, c.name AS category_name FROM scenarios s JOIN categories c ON c.id = s.category_id WHERE s.id = ?');
$s->execute([(int)($_GET['id'] ?? 0)]);
$sc = $s->fetch();
if (!$sc) { flash('warning', 'Scenario not found.'); redirect('admin/scenarios.php'); }
$opts = db()->prepare("SELECT option_text, classification, is_correct FROM options WHERE scenario_id = ? ORDER BY FIELD(classification,'SAFE','SUSPICIOUS','SCAM')");
$opts->execute([$sc['id']]);
$opts = $opts->fetchAll();
$content = json_decode((string)$sc['content'], true) ?: [];

$layout = 'app'; $activeNav = 'scenarios'; $pageTitle = 'Scenario preview';
$extraCss = ['challenges.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>
<p class="crumb"><a href="<?= e(url('admin/scenarios.php')) ?>">Scenarios</a> / Preview</p>
<div class="challenge">
  <p><span class="pill"><?= e($sc['category_name']) ?></span> <span class="pill <?= e(strtolower($sc['difficulty'])) ?>"><?= e($sc['difficulty']) ?></span>
     <span class="status <?= $sc['is_active'] ? 'done' : 'off' ?>"><?= $sc['is_active'] ? 'Active' : 'Inactive' ?></span></p>
  <h1><?= e($sc['title']) ?></h1>
  <?php render_scenario((string)$sc['content_type'], $content); ?>
  <section class="card"><h2>Answer options</h2>
    <ul class="flags"><?php foreach ($opts as $o): ?><li><?= e($o['option_text']) ?><?= $o['is_correct'] ? ' <strong class="c-green">(correct)</strong>' : '' ?></li><?php endforeach; ?></ul>
    <h3>Explanation</h3><p><?= e($sc['explanation']) ?></p>
    <h3>Red flags</h3><ul class="flags"><?php foreach (array_filter(array_map('trim', explode("\n", (string)$sc['red_flags']))) as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
    <p class="tip"><i class="bi bi-lightbulb"></i> <strong>Safety tip:</strong> <?= e($sc['safety_tip']) ?></p>
  </section>
  <div class="btn-row"><a class="btn btn-primary" href="<?= e(url('admin/scenario-form.php?id=' . (int)$sc['id'])) ?>">Edit</a>
    <a class="btn btn-ghost" href="<?= e(url('admin/scenarios.php')) ?>">Back to list</a></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

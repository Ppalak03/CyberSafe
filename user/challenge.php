<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/challenge_service.php';
require_once __DIR__ . '/../includes/scenario_render.php';

$scenario = get_active_scenario((int)($_GET['id'] ?? 0));
if (!$scenario) {
    flash('warning', 'This scenario is no longer active.');
    redirect('user/challenges.php');
}
$options = scenario_options((int)$scenario['id']);
if (count($options) !== 3) { // data problem: never show a broken challenge
    flash('error', 'This scenario is not ready yet.');
    redirect('user/challenges.php');
}
$content = json_decode((string)$scenario['content'], true) ?: [];
[$pos, $total] = scenario_position((int)$scenario['id'], (int)$scenario['category_id']);
$token = issue_attempt_token((int)$scenario['id']);
$labels = ['SAFE' => ['bi-check-circle', 'safe'], 'SUSPICIOUS' => ['bi-exclamation-triangle', 'susp'], 'SCAM' => ['bi-x-octagon', 'scam']];

$layout = 'app'; $activeNav = 'challenges'; $pageTitle = $scenario['category_name'];
$extraCss = ['challenges.css'];
$extraJs = ['challenges.js'];
include __DIR__ . '/../includes/header.php';
?>
<p class="crumb"><a href="<?= e(url('user/challenges.php')) ?>">Challenges</a> / <a href="<?= e(url('user/scenario-list.php?category=' . (int)$scenario['category_id'])) ?>"><?= e($scenario['category_name']) ?></a></p>
<div class="challenge">
  <p><span class="pill"><?= e($scenario['category_name']) ?></span> <span class="pill <?= e(strtolower($scenario['difficulty'])) ?>"><?= e($scenario['difficulty']) ?></span></p>
  <h1><?= e($scenario['title']) ?></h1>
  <p class="muted q">Does this look trustworthy?</p>

  <?php render_scenario((string)$scenario['content_type'], $content); ?>

  <form id="answer-form" method="post" action="<?= e(url('user/submit-answer.php')) ?>" data-api="<?= e(url('api/submit-answer.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="scenario_id" value="<?= (int)$scenario['id'] ?>">
    <input type="hidden" name="attempt_token" value="<?= e($token) ?>">
    <fieldset class="decision">
      <legend>Your decision</legend>
      <div class="choices">
        <?php foreach ($options as $o): [$icon, $cls] = $labels[$o['classification']]; ?>
        <label class="choice <?= e($cls) ?>">
          <input type="radio" name="option_id" value="<?= (int)$o['id'] ?>" required>
          <i class="bi <?= e($icon) ?>"></i><span><?= e(strtoupper($o['option_text'])) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <div class="submit-row">
      <button id="submit-btn" type="submit" class="btn btn-primary">Submit answer</button>
      <span class="muted">Scenario <?= $pos ?> of <?= $total ?></span>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

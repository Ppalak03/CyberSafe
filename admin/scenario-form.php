<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';
require_once __DIR__ . '/../includes/scenario_schema.php';

$schema = scenario_schema();
$classes = ['SAFE', 'SUSPICIOUS', 'SCAM'];
$diffs = ['Easy', 'Medium', 'Hard'];

$id = (int)($_GET['id'] ?? 0);
$existing = null; $existingCorrect = 'SCAM'; $content = []; $attemptCount = 0;
if ($id > 0) {
    $s = db()->prepare('SELECT * FROM scenarios WHERE id = ?');
    $s->execute([$id]);
    $existing = $s->fetch();
    if (!$existing) { flash('warning', 'Scenario not found.'); redirect('admin/scenarios.php'); }
    $content = json_decode((string)$existing['content'], true) ?: [];
    $c = db()->prepare('SELECT classification FROM options WHERE scenario_id = ? AND is_correct = 1');
    $c->execute([$id]);
    $existingCorrect = (string)($c->fetchColumn() ?: 'SCAM');
    $attemptCount = scalar('SELECT COUNT(*) FROM attempts WHERE scenario_id = ?', [$id]);
}
$cats = db()->query('SELECT id, name, is_active FROM categories ORDER BY name')->fetchAll();

$errors = [];
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
$v = [
    'title'    => $existing['title'] ?? '',
    'category' => (int)($existing['category_id'] ?? 0),
    'difficulty' => $existing['difficulty'] ?? 'Easy',
    'type'     => $existing['content_type'] ?? 'email',
    'context'  => (string)($content['context'] ?? ''),
    'explanation' => $existing['explanation'] ?? '',
    'flags'    => $existing['red_flags'] ?? '',
    'tip'      => $existing['safety_tip'] ?? '',
    'active'   => (int)($existing['is_active'] ?? 1),
    'correct'  => $existingCorrect,
];

if ($posted) {
    csrf_require();
    $v = [
        'title' => trim((string)($_POST['title'] ?? '')),
        'category' => (int)($_POST['category_id'] ?? 0),
        'difficulty' => (string)($_POST['difficulty'] ?? ''),
        'type' => (string)($_POST['content_type'] ?? ''),
        'context' => trim((string)($_POST['context'] ?? '')),
        'explanation' => trim((string)($_POST['explanation'] ?? '')),
        'flags' => trim((string)($_POST['red_flags'] ?? '')),
        'tip' => trim((string)($_POST['safety_tip'] ?? '')),
        'active' => isset($_POST['is_active']) ? 1 : 0,
        'correct' => (string)($_POST['correct'] ?? ''),
    ];

    if ($v['title'] === '' || mb_strlen($v['title']) > 150) $errors['title'] = 'Enter a title (up to 150 characters).';
    $okCat = false;
    foreach ($cats as $c) {
        if ((int)$c['id'] === $v['category'] && ($c['is_active'] || $v['category'] === (int)($existing['category_id'] ?? 0))) $okCat = true;
    }
    if (!$okCat) $errors['category'] = 'Choose an active category.';
    if (!in_array($v['difficulty'], $diffs, true)) $errors['difficulty'] = 'Choose a difficulty.';
    if (!isset($schema[$v['type']])) $errors['type'] = 'Choose a scenario type.';
    if (mb_strlen($v['context']) > 300) $errors['context'] = 'Context is too long (300 characters max).';
    if ($v['explanation'] === '' || mb_strlen($v['explanation']) > 5000) $errors['explanation'] = 'Enter an explanation.';
    if ($v['tip'] === '' || mb_strlen($v['tip']) > 500) $errors['tip'] = 'Enter a safety tip (up to 500 characters).';
    $flagLines = array_values(array_filter(array_map('trim', preg_split('/\R/', $v['flags'])), fn($l) => $l !== ''));
    if (!$flagLines || count($flagLines) > 10 || max(array_map('mb_strlen', $flagLines)) > 200) $errors['flags'] = 'Enter 1 to 10 red flags, one per line.';
    if (!in_array($v['correct'], $classes, true)) $errors['correct'] = 'Select exactly one correct answer.';

    $built = [];
    if (isset($schema[$v['type']])) {
        $built = build_scenario_content($v['type'], (array)($_POST['c'][$v['type']] ?? []), $v['context'], $errors);
    }

    if (!$errors) {
        $pdo = db();
        try {
            $json = json_encode($built, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $pdo->beginTransaction();
            if ($existing) {
                $pdo->prepare('UPDATE scenarios SET category_id=?, title=?, content_type=?, content=?, difficulty=?, explanation=?, red_flags=?, safety_tip=?, is_active=? WHERE id=?')
                    ->execute([$v['category'], $v['title'], $v['type'], $json, $v['difficulty'], $v['explanation'], implode("\n", $flagLines), $v['tip'], $v['active'], $id]);
                $sid = $id;
            } else {
                $pdo->prepare('INSERT INTO scenarios (category_id, title, content_type, content, difficulty, explanation, red_flags, safety_tip, is_active) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$v['category'], $v['title'], $v['type'], $json, $v['difficulty'], $v['explanation'], implode("\n", $flagLines), $v['tip'], $v['active']]);
                $sid = (int)$pdo->lastInsertId();
            }
            // Options are updated in place (never deleted) because past attempts reference them.
            $up = $pdo->prepare('INSERT INTO options (scenario_id, option_text, classification, is_correct) VALUES (?,?,?,?)
                                 ON DUPLICATE KEY UPDATE is_correct = ?');
            foreach ($classes as $cl) {
                $flag = $cl === $v['correct'] ? 1 : 0;
                $up->execute([$sid, ucfirst(strtolower($cl)), $cl, $flag, $flag]);
            }
            $chk = $pdo->prepare('SELECT COUNT(*) AS n, COALESCE(SUM(is_correct),0) AS ok FROM options WHERE scenario_id = ?');
            $chk->execute([$sid]);
            $r = $chk->fetch();
            if ((int)$r['n'] !== 3 || (int)$r['ok'] !== 1) {
                throw new RuntimeException('Option rule violated'); // exactly 3 options, exactly 1 correct
            }
            $pdo->commit();
            flash('success', 'Scenario saved.');
            redirect('admin/scenarios.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[scenario-form] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log');
            $errors['form'] = 'Unable to save the scenario. Nothing was changed.';
        }
    }
}

/** Current value of a content field: posted text on error, otherwise stored content. */
$cv = function (string $type, string $key, string $kind) use ($posted, $existing, $content): string {
    if ($posted) return (string)($_POST['c'][$type][$key] ?? '');
    if ($existing && $existing['content_type'] === $type) return content_to_text($kind, $content[$key] ?? '');
    return '';
};

$layout = 'app'; $activeNav = 'scenarios'; $pageTitle = $existing ? 'Edit scenario' : 'Add scenario';
$extraCss = ['admin.css'];
$extraJs = ['admin-form.js'];
include __DIR__ . '/../includes/header.php';
?>
<p class="crumb"><a href="<?= e(url('admin/scenarios.php')) ?>">Scenarios</a> / <?= $existing ? 'Edit' : 'Add' ?></p>
<div class="page-head"><h1><?= $existing ? 'Edit scenario' : 'Add scenario' ?></h1>
  <p class="muted">Admin form for scenario content, options and learning feedback. Use fictional brands and .example / .test domains only. Never enter real credentials or live links.</p></div>
<?php if (isset($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['form'])): ?><div class="alert alert-error" role="alert">Please fix the highlighted problems below.</div><?php endif; ?>

<form method="post" action="" novalidate class="card form-card">
  <?= csrf_field() ?>
  <div class="form-grid">
    <div class="field span-2"><label for="title">Scenario title</label><input id="title" name="title" type="text" maxlength="150" value="<?= e($v['title']) ?>">
      <p class="field-error"><?= e($errors['title'] ?? '') ?></p></div>
    <div class="field"><label for="category_id">Category</label><select id="category_id" name="category_id"><option value="0">Select…</option>
      <?php foreach ($cats as $c): if (!$c['is_active'] && (int)$c['id'] !== $v['category']) continue; ?>
      <option value="<?= (int)$c['id'] ?>" <?= $v['category'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?><?= $c['is_active'] ? '' : ' (inactive)' ?></option><?php endforeach; ?></select>
      <p class="field-error"><?= e($errors['category'] ?? '') ?></p></div>
    <div class="field"><label for="difficulty">Difficulty</label><select id="difficulty" name="difficulty">
      <?php foreach ($diffs as $d): ?><option <?= $v['difficulty'] === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select>
      <p class="field-error"><?= e($errors['difficulty'] ?? '') ?></p></div>
    <div class="field"><label for="content_type">Scenario type</label><select id="content_type" name="content_type">
      <?php foreach ($schema as $k => $def): ?><option value="<?= e($k) ?>" <?= $v['type'] === $k ? 'selected' : '' ?>><?= e($def['label']) ?></option><?php endforeach; ?></select>
      <p class="field-error"><?= e($errors['type'] ?? '') ?></p></div>
    <div class="field"><label for="context">Context shown above the scenario (optional)</label><input id="context" name="context" type="text" maxlength="300" value="<?= e($v['context']) ?>">
      <p class="field-error"><?= e($errors['context'] ?? '') ?></p></div>
  </div>

  <h2 class="form-h">Scenario content</h2>
  <?php if (!empty($errors['content'])): ?><ul class="field-error list"><?php foreach (array_unique($errors['content']) as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul><?php endif; ?>
  <?php foreach ($schema as $type => $def): ?>
  <fieldset class="ctype" data-type="<?= e($type) ?>" <?= $v['type'] === $type ? '' : 'hidden' ?>>
    <legend class="sr-only"><?= e($def['label']) ?> fields</legend>
    <?php foreach ($def['fields'] as $key => [$label, $kind, $required, $isUrl]):
      $name = 'c[' . $type . '][' . $key . ']'; $fid = "f-$type-$key"; $val = $cv($type, $key, $kind); ?>
    <div class="field"><label for="<?= e($fid) ?>"><?= e($label) ?><?= $required ? ' *' : '' ?></label>
      <?php if ($kind === 'text'): ?><input id="<?= e($fid) ?>" name="<?= e($name) ?>" type="text" value="<?= e($val) ?>">
      <?php else: ?><textarea id="<?= e($fid) ?>" name="<?= e($name) ?>" rows="<?= $kind === 'textarea' ? 4 : 3 ?>"><?= e($val) ?></textarea><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </fieldset>
  <?php endforeach; ?>

  <h2 class="form-h">Answer options</h2>
  <fieldset class="decision"><legend class="sr-only">Correct answer</legend>
    <p class="muted">Every scenario has three options. Choose the one correct classification.</p>
    <div class="radio-row"><?php foreach ($classes as $cl): ?>
      <label class="radio"><input type="radio" name="correct" value="<?= e($cl) ?>" <?= $v['correct'] === $cl ? 'checked' : '' ?>> <?= e(ucfirst(strtolower($cl))) ?></label>
    <?php endforeach; ?></div>
    <p class="field-error"><?= e($errors['correct'] ?? '') ?></p>
    <?php if ($attemptCount > 0): ?><p class="hint">This scenario has <?= $attemptCount ?> past attempt(s). Changing the correct answer does not re-grade them.</p><?php endif; ?>
  </fieldset>

  <h2 class="form-h">Learning feedback</h2>
  <div class="field"><label for="explanation">Explanation</label><textarea id="explanation" name="explanation" rows="4"><?= e($v['explanation']) ?></textarea>
    <p class="field-error"><?= e($errors['explanation'] ?? '') ?></p></div>
  <div class="field"><label for="red_flags">Red flags (one per line; for safe scenarios list the signs it is legitimate)</label><textarea id="red_flags" name="red_flags" rows="4"><?= e($v['flags']) ?></textarea>
    <p class="field-error"><?= e($errors['flags'] ?? '') ?></p></div>
  <div class="field"><label for="safety_tip">Safety tip</label><input id="safety_tip" name="safety_tip" type="text" maxlength="500" value="<?= e($v['tip']) ?>">
    <p class="field-error"><?= e($errors['tip'] ?? '') ?></p></div>
  <label class="switch-row"><input type="checkbox" name="is_active" value="1" <?= $v['active'] ? 'checked' : '' ?>> <span><strong>Published</strong><small class="muted">Visible to learners when checked.</small></span></label>

  <div class="btn-row"><button class="btn btn-primary" type="submit">Save and publish</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/scenarios.php')) ?>">Cancel</a></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';

const DIFFS = ['Easy', 'Medium', 'Hard'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'toggle') {
        db()->prepare('UPDATE scenarios SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Scenario status updated.');
    } elseif ($action === 'delete') {
        // Scenarios with attempts must be kept (history), so only unused ones can be deleted.
        if (scalar('SELECT COUNT(*) FROM attempts WHERE scenario_id = ?', [$id]) > 0) {
            flash('error', 'This scenario has learner attempts and cannot be deleted. Deactivate it instead.');
        } else {
            db()->prepare('DELETE FROM scenarios WHERE id = ?')->execute([$id]); // options cascade
            flash('success', 'Scenario deleted.');
        }
    }
    parse_str((string)($_POST['return'] ?? ''), $ret);
    $qs = http_build_query(allowed_query($ret, ['q', 'category', 'difficulty', 'status', 'page']));
    redirect('admin/scenarios.php' . ($qs ? '?' . $qs : ''));
}

$q = trim((string)($_GET['q'] ?? ''));
$cat = (int)($_GET['category'] ?? 0);
$diff = in_array($_GET['difficulty'] ?? '', DIFFS, true) ? $_GET['difficulty'] : '';
$status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';

$where = ['1=1']; $params = [];
if ($q !== '')      { $where[] = 's.title LIKE ?';       $params[] = like_term($q); }
if ($cat > 0)       { $where[] = 's.category_id = ?';    $params[] = $cat; }
if ($diff !== '')   { $where[] = 's.difficulty = ?';     $params[] = $diff; }
if ($status !== '') { $where[] = 's.is_active = ?';      $params[] = $status === 'active' ? 1 : 0; }
$w = implode(' AND ', $where);

$perPage = 15;
$total = scalar("SELECT COUNT(*) FROM scenarios s WHERE $w", $params);
$pages = max(1, (int)ceil($total / $perPage));
$page = max(1, min($pages, (int)($_GET['page'] ?? 1)));
$off = ($page - 1) * $perPage;
$stmt = db()->prepare(
    "SELECT s.id, s.title, s.difficulty, s.content_type, s.is_active, c.name AS category,
            (SELECT COUNT(*) FROM attempts a WHERE a.scenario_id = s.id) AS attempts
     FROM scenarios s JOIN categories c ON c.id = s.category_id
     WHERE $w ORDER BY s.id DESC LIMIT $perPage OFFSET $off"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$cats = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$return = http_build_query($_GET);

$layout = 'app'; $activeNav = 'scenarios'; $pageTitle = 'Scenario management';
$extraCss = ['progress.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head head-row"><div><h1>Scenario management</h1><p class="muted">Searchable scenario library with status and management actions.</p></div>
  <a class="btn btn-primary" href="<?= e(url('admin/scenario-form.php')) ?>"><i class="bi bi-plus-lg"></i> Add new</a></div>

<form class="filters card" method="get" action="">
  <div class="field grow"><label for="q">Search title</label><input id="q" name="q" type="text" value="<?= e($q) ?>"></div>
  <div class="field"><label for="category">Category</label><select id="category" name="category"><option value="0">All</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="difficulty">Difficulty</label><select id="difficulty" name="difficulty"><option value="">All</option>
    <?php foreach (DIFFS as $d): ?><option <?= $diff === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All</option>
    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
  <button class="btn btn-primary" type="submit">Filter</button>
  <?php if ($q !== '' || $cat > 0 || $diff !== '' || $status !== ''): ?><a class="btn btn-ghost" href="<?= e(url('admin/scenarios.php')) ?>">Reset</a><?php endif; ?>
</form>

<section class="card">
  <p class="muted"><?= (int)$total ?> scenario<?= $total === 1 ? '' : 's' ?></p>
  <?php if (!$rows): ?><p class="muted">No scenarios match.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Title</th><th>Category</th><th>Difficulty</th><th>Status</th><th>Attempts</th><th>Actions</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?><tr>
      <td><?= e($r['title']) ?></td><td><?= e($r['category']) ?></td>
      <td><span class="pill <?= e(strtolower($r['difficulty'])) ?>"><?= e($r['difficulty']) ?></span></td>
      <td><span class="status <?= $r['is_active'] ? 'done' : 'off' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></span></td>
      <td><?= (int)$r['attempts'] ?></td>
      <td class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/scenario-preview.php?id=' . (int)$r['id'])) ?>">View</a>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/scenario-form.php?id=' . (int)$r['id'])) ?>">Edit</a>
        <form method="post" action=""><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="return" value="<?= e($return) ?>">
          <button class="btn btn-ghost btn-sm" name="action" value="toggle" type="submit"><?= $r['is_active'] ? 'Deactivate' : 'Activate' ?></button>
          <button class="btn btn-danger btn-sm" name="action" value="delete" type="submit" data-confirm="Delete this scenario permanently? This cannot be undone.">Delete</button></form>
      </td></tr><?php endforeach; ?></tbody>
  </table></div>
  <?php admin_pager($page, $pages); endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';

$q = trim((string)($_GET['q'] ?? ''));
$status = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
[$rows, $total] = admin_users($q, $status, $perPage, ($page - 1) * $perPage);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) { $page = $pages; [$rows, $total] = admin_users($q, $status, $perPage, ($page - 1) * $perPage); }

$layout = 'app'; $activeNav = 'users'; $pageTitle = 'User management';
$extraCss = ['progress.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>User management</h1><p class="muted">Search and review registered accounts.</p></div>
<form class="filters card" method="get" action="">
  <div class="field grow"><label for="q">Search name or email</label><input id="q" name="q" type="text" value="<?= e($q) ?>" placeholder="e.g. priya or @example.test"></div>
  <div class="field"><label for="status">Status</label>
    <select id="status" name="status"><option value="">All</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
  <button class="btn btn-primary" type="submit">Filter</button>
  <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-ghost" href="<?= e(url('admin/users.php')) ?>">Reset</a><?php endif; ?>
</form>

<section class="card">
  <p class="muted"><?= (int)$total ?> account<?= $total === 1 ? '' : 's' ?></p>
  <?php if (!$rows): ?><p class="muted">No users match your search.</p><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>XP</th><th>Attempts</th><th>Registered</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $u): ?>
      <tr>
        <td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td>
        <td><span class="status <?= $u['is_active'] ? 'done' : 'off' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
        <td><?= (int)$u['total_xp'] ?></td><td><?= (int)$u['attempts'] ?></td>
        <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
        <td><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/user-detail.php?id=' . (int)$u['id'])) ?>">View</a></td>
      </tr><?php endforeach; ?></tbody>
  </table></div>
  <?php admin_pager($page, $pages); endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

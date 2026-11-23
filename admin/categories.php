<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_init.php';

const ICONS = ['bi-envelope-exclamation', 'bi-chat-left-text', 'bi-briefcase', 'bi-link-45deg', 'bi-box-arrow-in-right', 'bi-people',
               'bi-shield-check', 'bi-bug', 'bi-key', 'bi-wifi', 'bi-credit-card', 'bi-phone', 'bi-cloud', 'bi-bank', 'bi-globe2', 'bi-lock'];

$errors = [];
$editId = (int)($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle') {
        db()->prepare('UPDATE categories SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', 'Category status updated.');
        redirect('admin/categories.php');
    }
    if ($action === 'delete') {
        if (scalar('SELECT COUNT(*) FROM scenarios WHERE category_id = ?', [$id]) > 0) {
            flash('error', 'This category still has scenarios, so it cannot be deleted. Deactivate it instead.');
        } else {
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Category deleted.');
        }
        redirect('admin/categories.php');
    }
    if ($action === 'save') {
        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $icon = (string)($_POST['icon'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || mb_strlen($name) > 80) $errors['name'] = 'Enter a name (up to 80 characters).';
        if ($desc === '' || mb_strlen($desc) > 255) $errors['description'] = 'Enter a description (up to 255 characters).';
        if (!in_array($icon, ICONS, true)) $errors['icon'] = 'Choose an icon from the list.';
        if (!$errors) {
            try {
                if ($id > 0) {
                    db()->prepare('UPDATE categories SET name=?, description=?, icon=?, is_active=? WHERE id=?')->execute([$name, $desc, $icon, $active, $id]);
                } else {
                    db()->prepare('INSERT INTO categories (name, description, icon, is_active) VALUES (?,?,?,?)')->execute([$name, $desc, $icon, $active]);
                }
                flash('success', 'Category saved.');
                redirect('admin/categories.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') { $errors['name'] = 'A category with this name already exists.'; }
                else { error_log('[categories] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/app.log'); $errors['form'] = 'Unable to save the category.'; }
            }
        }
        $editId = $id;
        $form = ['id' => $id, 'name' => $name, 'description' => $desc, 'icon' => $icon, 'is_active' => $active];
    }
}

if (!isset($form)) {
    $form = ['id' => 0, 'name' => '', 'description' => '', 'icon' => ICONS[0], 'is_active' => 1];
    if ($editId > 0) {
        $s = db()->prepare('SELECT id, name, description, icon, is_active FROM categories WHERE id = ?');
        $s->execute([$editId]);
        $form = $s->fetch() ?: $form;
    }
}

$rows = db()->query(
    'SELECT c.id, c.name, c.description, c.icon, c.is_active, COUNT(s.id) AS total, COALESCE(SUM(s.is_active),0) AS active
     FROM categories c LEFT JOIN scenarios s ON s.category_id = c.id GROUP BY c.id, c.name, c.description, c.icon, c.is_active ORDER BY c.id'
)->fetchAll();

$layout = 'app'; $activeNav = 'categories'; $pageTitle = 'Category management';
$extraCss = ['progress.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Category management</h1><p class="muted">Maintain challenge categories and their descriptions.</p></div>
<?php if (isset($errors['form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>

<section class="card form-card">
  <h2><?= (int)$form['id'] > 0 ? 'Edit category' : 'Add category' ?></h2>
  <form method="post" action="" novalidate>
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
    <div class="form-grid">
      <div class="field"><label for="name">Name</label><input id="name" name="name" type="text" maxlength="80" value="<?= e($form['name']) ?>"><p class="field-error"><?= e($errors['name'] ?? '') ?></p></div>
      <div class="field"><label for="icon">Icon</label><select id="icon" name="icon"><?php foreach (ICONS as $i): ?><option value="<?= e($i) ?>" <?= $form['icon'] === $i ? 'selected' : '' ?>><?= e(substr($i, 3)) ?></option><?php endforeach; ?></select><p class="field-error"><?= e($errors['icon'] ?? '') ?></p></div>
      <div class="field span-2"><label for="description">Description</label><input id="description" name="description" type="text" maxlength="255" value="<?= e($form['description']) ?>"><p class="field-error"><?= e($errors['description'] ?? '') ?></p></div>
    </div>
    <label class="switch-row"><input type="checkbox" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>> <span><strong>Active</strong><small class="muted">Inactive categories are hidden from learners.</small></span></label>
    <div class="btn-row"><button class="btn btn-primary" type="submit">Save category</button>
      <?php if ((int)$form['id'] > 0): ?><a class="btn btn-ghost" href="<?= e(url('admin/categories.php')) ?>">Cancel</a><?php endif; ?></div>
  </form>
</section>

<section class="card" style="margin-top:1rem">
  <h2>Manage categories</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Category</th><th>Description</th><th>Scenarios</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?><tr>
      <td><i class="bi <?= e($r['icon']) ?>"></i> <?= e($r['name']) ?></td><td><?= e($r['description']) ?></td>
      <td><?= (int)$r['total'] ?> (<?= (int)$r['active'] ?> active)</td>
      <td><span class="status <?= $r['is_active'] ? 'done' : 'off' ?>"><?= $r['is_active'] ? 'Active' : 'Inactive' ?></span></td>
      <td class="actions"><a class="btn btn-ghost btn-sm" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
        <form method="post" action=""><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-ghost btn-sm" name="action" value="toggle" type="submit"><?= $r['is_active'] ? 'Deactivate' : 'Activate' ?></button>
          <?php if ((int)$r['total'] === 0): ?><button class="btn btn-danger btn-sm" name="action" value="delete" type="submit" data-confirm="Delete this category?">Delete</button><?php endif; ?></form></td>
    </tr><?php endforeach; ?></tbody>
  </table></div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
declare(strict_types=1);
$layout = $layout ?? 'public';
$extraJs = $extraJs ?? [];
?>
<?php if ($layout === 'app'): ?>
    </main>
  </div>
</div>
<?php else: ?>
  </main>
<?php endif; ?>
<script nonce="<?= e($cspNonce ?? '') ?>">window.CYBERSAFE = { base: <?= json_encode(BASE_URL) ?>, csrf: <?= json_encode(csrf_token()) ?>, timeout: <?= empty($_SESSION['user_id']) ? 0 : (int)SESSION_TIMEOUT ?> };</script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php foreach ($extraJs as $js): ?>
<script src="<?= e(url('assets/js/' . $js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>

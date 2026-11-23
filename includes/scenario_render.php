<?php
// Renders a scenario's structured JSON content. Every value is escaped with e().
declare(strict_types=1);

function nl(?string $s): string
{
    return nl2br(e($s));
}

function render_scenario(string $type, array $c): void
{
    if (!empty($c['context'])) {
        echo '<p class="context"><i class="bi bi-info-circle"></i> ' . e($c['context']) . '</p>';
    }
    switch ($type) {
        case 'email': ?>
<div class="mock email">
  <div class="mock-bar">Inbox / Message preview</div>
  <p class="row"><span>From</span> <?= e($c['from_name'] ?? '') ?> &lt;<?= e($c['from_email'] ?? '') ?>&gt;</p>
  <p class="row"><span>Subject</span> <strong><?= e($c['subject'] ?? '') ?></strong></p>
  <div class="body"><?= nl($c['body'] ?? '') ?></div>
  <?php if (!empty($c['link_text'])): ?>
  <p class="link-preview"><i class="bi bi-link-45deg"></i> <u><?= e($c['link_text']) ?></u><small>Link points to: <code><?= e($c['link_target'] ?? '') ?></code></small></p>
  <?php endif; ?>
  <?php if (!empty($c['attachment'])): ?>
  <p class="attachment"><i class="bi bi-paperclip"></i> <?= e($c['attachment']) ?></p>
  <?php endif; ?>
</div>
<?php break;
        case 'sms': ?>
<div class="mock phone">
  <div class="phone-head"><i class="bi bi-chat-left-text"></i> <?= e($c['sender'] ?? '') ?></div>
  <div class="bubble in"><?= nl($c['body'] ?? '') ?></div>
  <p class="stamp"><?= e($c['timestamp'] ?? '') ?></p>
  <?php if (!empty($c['link'])): ?><p class="link-preview"><i class="bi bi-link-45deg"></i> <code><?= e($c['link']) ?></code></p><?php endif; ?>
</div>
<?php break;
        case 'job_offer': ?>
<div class="mock job">
  <div class="mock-bar">Recruitment message</div>
  <h3><?= e($c['job_title'] ?? '') ?></h3>
  <p class="muted"><?= e($c['company'] ?? '') ?><?= !empty($c['contact']) ? ' · ' . e($c['contact']) : '' ?></p>
  <p class="row"><span>Salary</span> <?= e($c['salary'] ?? '') ?></p>
  <p class="row"><span>Process</span> <?= e($c['process'] ?? '') ?></p>
  <?php if (!empty($c['requests'])): ?>
  <div class="row"><span>Requests</span><ul><?php foreach ((array)$c['requests'] as $r): ?><li><?= e((string)$r) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <div class="body"><?= nl($c['message'] ?? '') ?></div>
</div>
<?php break;
        case 'url': ?>
<div class="mock urlbox">
  <div class="mock-bar">URL inspection</div>
  <p class="row"><span>Displayed URL</span></p>
  <p class="url-display"><?= e($c['displayed_url'] ?? '') ?></p>
  <p class="row"><span>Claims to be</span> <?= e($c['claimed_destination'] ?? '') ?></p>
  <?php if (!empty($c['clues'])): ?>
  <div class="row"><span>Domain details</span><ul><?php foreach ((array)$c['clues'] as $r): ?><li><?= e((string)$r) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
</div>
<?php break;
        case 'login_page': ?>
<div class="mock browser">
  <div class="address"><i class="bi bi-globe2"></i> <?= e($c['domain'] ?? '') ?></div>
  <div class="login-preview">
    <h3><?= e($c['brand'] ?? '') ?></h3>
    <p class="muted"><?= e($c['heading'] ?? '') ?></p>
    <?php foreach ((array)($c['fields'] ?? []) as $f): ?>
      <div class="fake-field"><?= e((string)$f) ?></div>
    <?php endforeach; ?>
    <?php if (!empty($c['notice'])): ?><p class="notice"><?= e($c['notice']) ?></p><?php endif; ?>
    <div class="fake-btn"><?= e($c['button'] ?? 'Sign in') ?></div>
  </div>
  <p class="preview-note"><i class="bi bi-shield-check"></i> Preview only. These fields are not real, and nothing you do here is collected.</p>
</div>
<?php break;
        case 'chat': ?>
<div class="mock chat">
  <?php foreach ((array)($c['messages'] ?? []) as $m): $mine = ($m['sender'] ?? '') === 'You'; ?>
  <div class="msg <?= $mine ? 'me' : 'them' ?>"><small><?= e($m['sender'] ?? '') ?></small><p><?= nl($m['text'] ?? '') ?></p></div>
  <?php endforeach; ?>
</div>
<?php break;
    }
}

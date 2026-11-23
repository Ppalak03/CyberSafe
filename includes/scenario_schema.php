<?php
// Describes the structured fields of each scenario type and validates admin input into the JSON stored in scenarios.content.
declare(strict_types=1);

/** field key => [label, kind (text|textarea|list|chat), required, must be a safe example URL] */
function scenario_schema(): array
{
    return [
        'email' => ['label' => 'Phishing email', 'fields' => [
            'from_name'   => ['From name', 'text', true, false],
            'from_email'  => ['From email address', 'text', true, false],
            'subject'     => ['Subject', 'text', true, false],
            'body'        => ['Message body', 'textarea', true, false],
            'link_text'   => ['Link text shown in the email', 'text', false, false],
            'link_target' => ['Link points to', 'text', false, true],
            'attachment'  => ['Attachment description', 'text', false, false],
        ]],
        'sms' => ['label' => 'SMS message', 'fields' => [
            'sender'    => ['Sender', 'text', true, false],
            'timestamp' => ['Timestamp (e.g. Today, 9:41 AM)', 'text', true, false],
            'body'      => ['Message', 'textarea', true, false],
            'link'      => ['Link in the message', 'text', false, true],
        ]],
        'job_offer' => ['label' => 'Job offer', 'fields' => [
            'company'   => ['Company name', 'text', true, false],
            'job_title' => ['Job title', 'text', true, false],
            'salary'    => ['Salary or benefits', 'text', true, false],
            'process'   => ['Application process', 'text', true, false],
            'requests'  => ['Requests made of the applicant (one per line)', 'list', false, false],
            'contact'   => ['How they made contact', 'text', false, false],
            'message'   => ['Message text', 'textarea', true, false],
        ]],
        'url' => ['label' => 'URL inspection', 'fields' => [
            'displayed_url'       => ['Displayed URL', 'text', true, true],
            'claimed_destination' => ['What it claims to be', 'text', true, false],
            'clues'               => ['Domain facts shown to the learner (one per line)', 'list', false, false],
        ]],
        'login_page' => ['label' => 'Login page', 'fields' => [
            'brand'   => ['Fictional brand name', 'text', true, false],
            'domain'  => ['Address bar URL', 'text', true, true],
            'heading' => ['Page heading', 'text', true, false],
            'fields'  => ['Form field labels (one per line; labels only, nothing is collected)', 'list', true, false],
            'button'  => ['Button label', 'text', false, false],
            'notice'  => ['Notice text', 'text', false, false],
        ]],
        'chat' => ['label' => 'Chat / conversation', 'fields' => [
            'messages' => ['Messages, one per line as "Name: message" (use "You" for the learner)', 'chat', true, false],
        ]],
    ];
}

/** Only fictional .example / .test hosts are allowed, so no real or live link can be entered. */
function url_is_safe(string $value): bool
{
    if (!preg_match('~^(?:https?://)?([a-z0-9.-]+)(?:[/:?#].*)?$~i', trim($value), $m)) {
        return false;
    }
    $host = strtolower($m[1]);
    return (bool)preg_match('/\.(example|test)$/', $host);
}

/** Build the JSON content array for $type from posted values; push readable messages into $errors['content']. */
function build_scenario_content(string $type, array $in, string $context, array &$errors): array
{
    $schema = scenario_schema()[$type]['fields'];
    $out = [];
    if ($context !== '') {
        $out['context'] = $context;
    }
    foreach ($schema as $key => [$label, $kind, $required, $isUrl]) {
        $raw = trim((string)($in[$key] ?? ''));
        $short = preg_replace('/ \(.*/', '', $label);
        if ($raw === '') {
            if ($required) {
                $errors['content'][] = $short . ' is required.';
            }
            continue;
        }
        switch ($kind) {
            case 'text':
            case 'textarea':
                if (mb_strlen($raw) > ($kind === 'text' ? 255 : 2000)) {
                    $errors['content'][] = $short . ' is too long.';
                    break;
                }
                if ($isUrl && !url_is_safe($raw)) {
                    $errors['content'][] = $short . ': use a safe example domain ending in .example or .test.';
                    break;
                }
                $out[$key] = $raw;
                break;
            case 'list':
                $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $raw)), fn($l) => $l !== ''));
                if (count($lines) > 10 || max(array_map('mb_strlen', $lines)) > 200) {
                    $errors['content'][] = $short . ': at most 10 lines of 200 characters.';
                    break;
                }
                $out[$key] = $lines;
                break;
            case 'chat':
                $msgs = [];
                foreach (array_values(array_filter(array_map('trim', preg_split('/\R/', $raw)), fn($l) => $l !== '')) as $i => $line) {
                    $pos = strpos($line, ':');
                    $name = $pos === false ? '' : trim(substr($line, 0, $pos));
                    $text = $pos === false ? '' : trim(substr($line, $pos + 1));
                    if ($name === '' || $text === '' || mb_strlen($name) > 40 || mb_strlen($text) > 500) {
                        $errors['content'][] = 'Chat line ' . ($i + 1) . ' must look like "Name: message".';
                        continue;
                    }
                    $msgs[] = ['sender' => $name, 'text' => $text];
                }
                if (count($msgs) < 2 || count($msgs) > 12) {
                    $errors['content'][] = 'A chat needs between 2 and 12 messages.';
                }
                $out[$key] = $msgs;
                break;
        }
    }
    return $out;
}

/** Turn stored content back into the text shown in a form input. */
function content_to_text(string $kind, mixed $value): string
{
    if ($kind === 'list') {
        return implode("\n", array_map('strval', (array)$value));
    }
    if ($kind === 'chat') {
        return implode("\n", array_map(fn($m) => ($m['sender'] ?? '') . ': ' . ($m['text'] ?? ''), (array)$value));
    }
    return is_scalar($value) ? (string)$value : '';
}

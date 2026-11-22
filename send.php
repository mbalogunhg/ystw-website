<?php
// You Should Travel Well — form handler.
// Emails each submission to info@youshouldtravelwell.com and keeps a backup copy
// in a CSV file outside the public web folder.

declare(strict_types=1);

const TO_EMAIL   = 'info@youshouldtravelwell.com';
const FROM_EMAIL = 'website@youshouldtravelwell.com';

function back(string $path): void {
    header('Location: ' . $path, true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    back('/');
}

$forms = [
    'creator'    => ['label' => 'Creator enquiry',  'return' => '/creators/'],
    'trip'       => ['label' => 'Trip enquiry',     'return' => '/'],
    'newsletter' => ['label' => 'Newsletter sign-up', 'return' => '/'],
];
$type = $_POST['form'] ?? '';
if (!isset($forms[$type])) {
    back('/');
}
$return = $forms[$type]['return'];

// Bot checks: hidden field must be empty; form must not be submitted instantly.
if (!empty($_POST['website'])) {
    back('/thanks/?f=' . $type);
}
$t = (int)($_POST['t'] ?? 0);
if ($t > 0 && (microtime(true) * 1000 - $t) < 2500) {
    back('/thanks/?f=' . $type);
}

// Simple rate limit: 5 submissions per IP per 10 minutes.
$dataDir = dirname(__DIR__) . '/form-data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0750, true);
}
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rlFile = $dataDir . '/rl-' . hash('sha256', $ip) . '.txt';
$now = time();
$hits = [];
if (is_file($rlFile)) {
    foreach (explode("\n", (string)@file_get_contents($rlFile)) as $h) {
        if ($h !== '' && ($now - (int)$h) < 600) { $hits[] = (int)$h; }
    }
}
if (count($hits) >= 5) {
    back($return . '?error=busy#form');
}
$hits[] = $now;
@file_put_contents($rlFile, implode("\n", $hits));

function clean(string $key, int $max = 500): string {
    $v = trim((string)($_POST[$key] ?? ''));
    $v = str_replace(["\r", "\0"], '', $v);
    return mb_substr($v, 0, $max);
}

$fieldsByForm = [
    'creator'    => ['name' => 'Name', 'email' => 'Email', 'platform' => 'Main platform', 'handle' => 'Handle or link', 'followers' => 'Followers', 'existing' => 'Already with InteleTravel?', 'message' => 'Message'],
    'trip'       => ['name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'where' => 'Where', 'when' => 'When', 'who' => 'Who is travelling', 'budget' => 'Rough budget', 'message' => 'Anything else'],
    'newsletter' => ['email' => 'Email'],
];

$data = [];
foreach ($fieldsByForm[$type] as $key => $label) {
    $data[$label] = clean($key, $key === 'message' ? 3000 : 300);
}
$email = $data['Email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back($return . '?error=email#form');
}
if ($type !== 'newsletter' && $data['Name'] === '') {
    back($return . '?error=name#form');
}
if (empty($_POST['consent'])) {
    back($return . '?error=consent#form');
}

// Backup copy (CSV, outside public_html).
$row = array_merge([date('c'), $forms[$type]['label']], array_values($data));
$fh = @fopen($dataDir . '/submissions-' . $type . '.csv', 'a');
if ($fh) {
    if (filesize($dataDir . '/submissions-' . $type . '.csv') === 0) {
        fputcsv($fh, array_merge(['Received', 'Form'], array_keys($data)));
    }
    fputcsv($fh, $row);
    fclose($fh);
}

// Email.
$lines = [];
foreach ($data as $label => $value) {
    $lines[] = $label . ': ' . ($value === '' ? '—' : $value);
}
$body = $forms[$type]['label'] . " from youshouldtravelwell.com\n\n" . implode("\n", $lines) . "\n\nSent " . date('j M Y, H:i') . "\n";
$subject = 'YSTW website: ' . $forms[$type]['label'] . ($type !== 'newsletter' ? ' from ' . $data['Name'] : '');
$subject = '=?UTF-8?B?' . base64_encode(str_replace(["\n", "\r"], '', $subject)) . '?=';
$headers = [
    'From: You Should Travel Well website <' . FROM_EMAIL . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
];
@mail(TO_EMAIL, $subject, $body, implode("\r\n", $headers), '-f' . FROM_EMAIL);

back('/thanks/?f=' . $type);

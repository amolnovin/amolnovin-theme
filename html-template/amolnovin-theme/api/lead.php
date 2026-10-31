<?php
/**
 * Minimal lead endpoint (optional).  Receives the forms of the template and mails them to the owner.
 * Requires PHP mail() / local MTA.  If it is missing the forms fall back to WhatsApp automatically.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
$TO = 'info@amolnovin.ir';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit; }
$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$clean = function ($v, $max = 600) { $v = trim(strip_tags((string)$v)); $v = preg_replace('/[\r\n]+/', ' ', $v); return mb_substr($v, 0, $max); };
if (!empty($in['website'])) { echo json_encode(['ok' => true]); exit; }              // honeypot
$name = $clean($in['name'] ?? '', 120); $phone = preg_replace('/[^0-9+]/', '', (string)($in['phone'] ?? ''));
if (mb_strlen($name) < 2 || !preg_match('/^(\+98|0)?9[0-9]{9}$/', $phone)) { http_response_code(422); echo json_encode(['ok' => false, 'error' => 'invalid']); exit; }
$ip = $_SERVER['REMOTE_ADDR'] ?? '0'; $f = sys_get_temp_dir() . '/lead_' . md5($ip);
if (is_file($f) && time() - filemtime($f) < 30) { http_response_code(429); echo json_encode(['ok' => false, 'error' => 'rate']); exit; }
touch($f);
$body = "نام: $name\nموبایل: $phone\nخدمت: " . $clean($in['service'] ?? '') . "\nپیام: " . $clean($in['message'] ?? '', 1500) . "\nصفحه: " . $clean($in['page'] ?? '') . "\nIP: $ip\n";
$subject = '=?UTF-8?B?' . base64_encode('درخواست مشاوره‌ی جدید از سایت') . '?=';
$ok = @mail($TO, $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\nFrom: no-reply@amolnovin.ir");
echo json_encode(['ok' => (bool)$ok]);

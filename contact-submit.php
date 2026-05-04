<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!empty($_POST['website'] ?? '')) {
    echo json_encode(['ok' => true, 'message' => 'Thanks, your enquiry has been sent.']);
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$projectType = trim((string)($_POST['budget'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$safeName = preg_replace('/[\r\n]+/', ' ', $name);

if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please complete your name, email and message.']);
    exit;
}

$to = 'scottchowen@gmail.com';
$subject = 'SCC Web Design enquiry from ' . $safeName;
$body = "Name: {$name}\nEmail: {$email}\nProject type: {$projectType}\n\nMessage:\n{$message}\n";
$headers = [
    'From: SCC Web Design <hello@sccwebdesign.co.uk>',
    'Reply-To: ' . $safeName . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'The server could not send the enquiry. Please email scottchowen@gmail.com.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Thanks, your enquiry has been sent.']);

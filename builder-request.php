<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$request = json_decode($raw, true);

if (!is_array($request)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid request payload.']);
    exit;
}

$businessName = trim((string)($request['business_name'] ?? ''));
$clientEmail = trim((string)($request['email'] ?? ''));
$location = trim((string)($request['location'] ?? ''));
$businessType = trim((string)($request['business_type'] ?? ''));
$ttl = (int)($request['preview_ttl_minutes'] ?? 15);
$safeBusinessName = preg_replace('/[\r\n]+/', ' ', $businessName);

if (!in_array($ttl, [15, 30, 60], true)) {
    $ttl = 15;
}

if ($businessName === '' || $clientEmail === '' || $location === '' || $businessType === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please complete the required brief details.']);
    exit;
}

$json = json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$to = 'scottchowen@gmail.com';
$subject = 'Website factory request: ' . $safeBusinessName;
$body = "A new SCC website builder request has been submitted.\n\n";
$body .= "Business: {$businessName}\nClient email: {$clientEmail}\nLocation: {$location}\nPreview lifetime: {$ttl} minutes\n\n";
$body .= "Structured request:\n{$json}\n\n";
$body .= "Next step: run the private SCC Site Factory workflow with this JSON. If preview email is enabled in the workflow, the client can receive the temporary preview link after deployment.\n";
$headers = [
    'From: SCC Web Design <hello@sccwebdesign.co.uk>',
    'Reply-To: ' . $clientEmail,
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'The server could not send the request. Please use Copy request and email it to scottchowen@gmail.com.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => "Request sent to SCC. Your website preview can be prepared shortly and will expire {$ttl} minutes after it is generated."
]);

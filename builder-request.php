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
$configPath = __DIR__ . '/scc-private-config.php';
$factory = is_file($configPath) ? require $configPath : [];
$dispatchResult = null;

if (is_array($factory) && !empty($factory['token']) && !empty($factory['repo']) && !empty($factory['workflow'])) {
    $dispatchResult = dispatch_site_factory($factory, $json, $ttl);
}

$to = 'scottchowen@gmail.com';
$subject = 'Website factory request: ' . $safeBusinessName;
$body = "A new SCC website builder request has been submitted.\n\n";
$body .= "Business: {$businessName}\nClient email: {$clientEmail}\nLocation: {$location}\nPreview lifetime: {$ttl} minutes\n\n";
$body .= "Structured request:\n{$json}\n\n";
if ($dispatchResult === true) {
    $body .= "The private SCC Site Factory workflow was triggered automatically. If preview deployment and client email are configured, the client should receive the temporary preview link after deployment.\n";
} else {
    $body .= "Next step: run the private SCC Site Factory workflow with this JSON. If preview email is enabled in the workflow, the client can receive the temporary preview link after deployment.\n";
    if (is_string($dispatchResult) && $dispatchResult !== '') {
        $body .= "\nAutomatic workflow trigger note: {$dispatchResult}\n";
    }
}
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
    'message' => $dispatchResult === true
        ? "Request sent. The website preview is being prepared and will expire {$ttl} minutes after it is generated."
        : "Request sent to SCC. Your website preview can be prepared shortly and will expire {$ttl} minutes after it is generated."
]);

function dispatch_site_factory(array $factory, string $requestJson, int $ttl): bool|string
{
    $repo = trim((string)$factory['repo']);
    $workflow = trim((string)$factory['workflow']);
    $token = trim((string)$factory['token']);
    $ref = trim((string)($factory['ref'] ?? 'main'));

    if ($repo === '' || $workflow === '' || $token === '') {
        return 'Site Factory token, repo or workflow is missing.';
    }

    $payload = json_encode([
        'ref' => $ref,
        'inputs' => [
            'request_json' => $requestJson,
            'deploy_preview' => true,
            'preview_ttl_minutes' => (string)$ttl,
            'email_client' => true,
        ],
    ], JSON_UNESCAPED_SLASHES);

    $url = "https://api.github.com/repos/{$repo}/actions/workflows/{$workflow}/dispatches";
    $ch = curl_init($url);
    if ($ch === false) {
        return 'Could not initialise GitHub request.';
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'User-Agent: SCC-Web-Design-Site-Builder',
            'X-GitHub-Api-Version: 2022-11-28',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($status === 204) {
        return true;
    }

    if ($error !== '') {
        return 'GitHub API error: ' . $error;
    }

    return 'GitHub API returned status ' . $status . ($response ? ': ' . substr((string)$response, 0, 240) : '');
}

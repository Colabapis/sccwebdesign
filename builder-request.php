<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$request = read_request_payload();

if (!is_array($request)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid request payload.']);
    exit;
}

$uploadResult = attach_uploaded_assets($request);
if ($uploadResult !== true) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $uploadResult]);
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
    'request' => $request,
    'message' => $dispatchResult === true
        ? "Request sent. The website preview is being prepared and will expire {$ttl} minutes after it is generated."
        : "Request sent to SCC. Your website preview can be prepared shortly and will expire {$ttl} minutes after it is generated."
]);

function read_request_payload(): ?array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'multipart/form-data') !== false) {
        $requestJson = (string)($_POST['request_json'] ?? '');
        $request = json_decode($requestJson, true);

        if (is_array($request)) {
            return $request;
        }

        return [
            'job' => 'generate_static_website',
            'business_name' => trim((string)($_POST['business_name'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'location' => trim((string)($_POST['location'] ?? '')),
            'live_domain' => trim((string)($_POST['live_domain'] ?? '')),
            'business_type' => trim((string)($_POST['business_type'] ?? '')),
            'style' => trim((string)($_POST['style'] ?? '')),
            'colour' => trim((string)($_POST['colour'] ?? '')),
            'banner_style' => trim((string)($_POST['banner_style'] ?? '')),
            'banner_image_count' => (int)($_POST['banner_image_count'] ?? 3),
            'pages' => array_values((array)($_POST['pages'] ?? [])),
            'assets' => array_values((array)($_POST['assets'] ?? [])),
            'logo_url' => trim((string)($_POST['logo_url'] ?? '')),
            'client_image_urls' => preg_split('/\r\n|\r|\n/', (string)($_POST['client_image_urls'] ?? '')) ?: [],
            'analytics_measurement_id' => trim((string)($_POST['analytics_measurement_id'] ?? '')),
            'output' => trim((string)($_POST['output'] ?? 'temporary_preview')),
            'preview_ttl_minutes' => (int)($_POST['preview_ttl_minutes'] ?? 15),
            'notes' => trim((string)($_POST['notes'] ?? '')),
            'generated_at' => gmdate('c'),
        ];
    }

    $raw = file_get_contents('php://input') ?: '';
    $request = json_decode($raw, true);
    return is_array($request) ? $request : null;
}

function attach_uploaded_assets(array &$request)
{
    $hasLogoUpload = isset($_FILES['logo_file']) && is_uploaded_file((string)($_FILES['logo_file']['tmp_name'] ?? ''));
    $hasImageUploads = has_uploaded_file_set($_FILES['client_images'] ?? null);

    if (!$hasLogoUpload && !$hasImageUploads) {
        unset($request['logo_upload'], $request['client_image_uploads']);
        return true;
    }

    $businessName = trim((string)($request['business_name'] ?? 'website'));
    $folder = create_upload_folder($businessName);

    if ($folder === null) {
        return 'The server could not create an upload folder for the images.';
    }

    $baseUrl = public_base_url() . '/site-factory-uploads/' . basename($folder);

    if ($hasLogoUpload) {
        $logo = save_uploaded_image($_FILES['logo_file'], $folder, 'logo', true);
        if (is_string($logo) && substr($logo, 0, 6) === 'error:') {
            return substr($logo, 6);
        }
        if (is_string($logo) && $logo !== '') {
            $request['logo_url'] = $baseUrl . '/' . $logo;
        }
    }

    $uploadedImages = save_uploaded_image_set($_FILES['client_images'] ?? null, $folder, 10);
    if (is_string($uploadedImages)) {
        return $uploadedImages;
    }

    if (!isset($request['client_image_urls']) || !is_array($request['client_image_urls'])) {
        $request['client_image_urls'] = [];
    }

    foreach ($uploadedImages as $imageName) {
        array_unshift($request['client_image_urls'], $baseUrl . '/' . $imageName);
    }

    $request['client_image_urls'] = array_slice(array_values(array_filter($request['client_image_urls'])), 0, 10);
    unset($request['logo_upload'], $request['client_image_uploads']);

    return true;
}

function create_upload_folder(string $businessName): ?string
{
    $root = __DIR__ . '/site-factory-uploads';
    if (!is_dir($root) && !mkdir($root, 0755, true)) {
        return null;
    }

    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $businessName), '-')) ?: 'website';
    $folder = $root . '/' . gmdate('Ymd-His') . '-' . $slug . '-' . bin2hex(random_bytes(3));

    if (!mkdir($folder, 0755, true)) {
        return null;
    }

    return $folder;
}

function public_base_url(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'sccwebdesign.co.uk';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https://' : 'http://') . $host;
}

function save_uploaded_image_set($files, string $folder, int $limit)
{
    if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $saved = [];
    $count = min(count($files['name']), $limit);

    for ($index = 0; $index < $count; $index++) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $file = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
        $result = save_uploaded_image($file, $folder, 'photo-' . ($index + 1), false);

        if (is_string($result) && substr($result, 0, 6) === 'error:') {
            return substr($result, 6);
        }
        if (is_string($result) && $result !== '') {
            $saved[] = $result;
        }
    }

    return $saved;
}

function has_uploaded_file_set($files): bool
{
    if (!is_array($files) || !isset($files['tmp_name']) || !is_array($files['tmp_name'])) {
        return false;
    }

    foreach ($files['tmp_name'] as $tmpName) {
        if (is_string($tmpName) && $tmpName !== '' && is_uploaded_file($tmpName)) {
            return true;
        }
    }

    return false;
}

function save_uploaded_image(array $file, string $folder, string $prefix, bool $allowSvg): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'error:One of the uploaded images could not be received. Please try a smaller file.';
    }

    if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return 'error:Uploaded images must be 5MB or smaller.';
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return 'error:The uploaded image was not recognised by the server.';
    }

    $mime = mime_content_type($tmp) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if ($allowSvg) {
        $allowed['image/svg+xml'] = 'svg';
    }

    if (!isset($allowed[$mime])) {
        return 'error:Please upload JPG, PNG or WebP images. SVG is allowed for logos only.';
    }

    $filename = $prefix . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $folder . '/' . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        return 'error:The server could not save an uploaded image.';
    }

    chmod($target, 0644);
    return $filename;
}

function dispatch_site_factory(array $factory, string $requestJson, int $ttl)
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
    $headers = [
        'Accept: application/vnd.github+json',
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'User-Agent: SCC-Web-Design-Site-Builder',
        'X-GitHub-Api-Version: 2022-11-28',
    ];

    $response = null;
    $status = 0;
    $error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return 'Could not initialise GitHub request.';
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $response = file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $status = (int)$matches[1];
        }
        if ($response === false) {
            $error = 'file_get_contents failed when calling GitHub API.';
        }
    }

    if ($status === 204) {
        return true;
    }

    if ($error !== '') {
        return 'GitHub API error: ' . $error;
    }

    return 'GitHub API returned status ' . $status . ($response ? ': ' . substr((string)$response, 0, 240) : '');
}

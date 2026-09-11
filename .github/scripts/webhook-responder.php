<?php

// Minimal responder for WebhookSubscriptionsTest.
//
// Run via PHP's built-in server (`php -S 127.0.0.1:8080 webhook-responder.php`) and exposed
// through a cloudflared quick tunnel so the API can reach it during webhook registration.
// The API verifies a freshly created subscription by sending a `TestMessage` and expects an HTTP 200.

http_response_code(200);
header('Content-Type: application/json');
echo '{"status":200}';

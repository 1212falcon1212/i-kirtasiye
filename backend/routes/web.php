<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// GitHub Webhook for Auto-Deploy (via /api/deploy/webhook)
Route::post('/api/deploy/webhook', function (Request $request) {
    $secret = 'b2b-idepo-webhook-secret-2026';
    $signature = $request->header('X-Hub-Signature-256');
    $payload = $request->getContent();

    // Verify signature
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $signature ?? '')) {
        return response()->json(['error' => 'Invalid signature'], 403);
    }

    // Only process push events
    if ($request->header('X-GitHub-Event') !== 'push') {
        return response()->json(['message' => 'Not a push event']);
    }

    // Check branch
    $data = $request->json()->all();
    $branch = str_replace('refs/heads/', '', $data['ref'] ?? '');
    if ($branch !== 'main') {
        return response()->json(['message' => "Push to $branch, skipping"]);
    }

    // Run deploy in background
    $repoPath = base_path('..');
    $logFile = $repoPath . '/deploy/deploy.log';
    $deployScript = $repoPath . '/deploy/deploy.sh';

    // Log the deploy
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Deploy triggered by webhook\n", FILE_APPEND);

    // Execute deploy script in background
    exec("nohup bash $deployScript >> $logFile 2>&1 &");

    return response()->json([
        'status' => 'success',
        'message' => 'Deploy started',
        'commit' => $data['head_commit']['id'] ?? 'N/A'
    ]);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

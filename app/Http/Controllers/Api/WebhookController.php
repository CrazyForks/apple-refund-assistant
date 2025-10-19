<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookService $hookService,
    ) {}

    /**
     * @throws AppStoreServerNotificationException
     */
    public function store(Request $request, int $id)
    {
        $bodyJson = $request->getContent();
        Log::info($bodyJson, ['title' => 'notification']);

        $this->hookService->handleNotification($bodyJson, $id);

        return new Response('SUCCESS');
    }
}

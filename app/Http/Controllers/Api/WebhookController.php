<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;

class WebhookController extends Controller
{
    protected WebhookService $hookService;
    public function __construct(WebhookService $service)
    {
        $this->hookService = $service;
    }
    //

    /**
     * @throws AppStoreServerNotificationException
     */
    public function store(Request $request, int $id)
    {
        $resp = $this->hookService->handleNotification($request->getContent(), $id);
        return new Response($resp);
    }
}

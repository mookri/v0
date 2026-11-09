<?php

namespace App\Modules\Customers\Providers\Contracts;

use App\Modules\Customers\Providers\NotificationMessage;
use App\Modules\Customers\Providers\ProviderSendResult;
use App\Modules\Customers\Providers\WebhookVerificationResult;
use Illuminate\Http\Request;

/**
 * Universal multi-channel notification provider contract (E4).
 */
interface NotificationProvider
{
    public function send(NotificationMessage $message): ProviderSendResult;

    public function verifyWebhook(Request $request): WebhookVerificationResult;
}

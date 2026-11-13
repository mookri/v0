<?php

declare(strict_types=1);

namespace App\Modules\Feedback;

use App\Modules\Feedback\Application\IssueFeedbackInvitationUseCase;
use App\Modules\Feedback\Application\RecomputeFeedbackQualityService;
use App\Modules\Feedback\Contracts\ReadsFeedbackQuality;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;
use App\Platform\Outbox\OutboxEvent;
use App\Platform\Outbox\OutboxHandlers;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->bind(ReadsFeedbackQuality::class, RecomputeFeedbackQualityService::class);
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        if ($this->app->bound(OutboxHandlers::class)) {
            /** @var OutboxHandlers $handlers */
            $handlers = $this->app->make(OutboxHandlers::class);
            foreach (IssueFeedbackInvitationUseCase::EVENT_TYPES as $eventType) {
                $handlers->register(
                    $eventType,
                    'feedback.invitation-producer',
                    function (OutboxEvent $event): void {
                        $this->app->make(IssueFeedbackInvitationUseCase::class)->handleOutbox($event);
                    }
                );
            }
        }
    }
}

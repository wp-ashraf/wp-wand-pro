<?php

namespace WPWand\Modules;

use WPWand\Rest\Controllers\CustomPromptsController;

/** Custom prompt templates + AI characters (Pro). UI lives in the Settings → Custom Prompts tab. */
final class CustomPromptsModule extends AbstractModule
{
    public function id(): string
    {
        return 'custom-prompts';
    }

    public function label(): string
    {
        return 'Custom Prompts';
    }

    public function requires_pro(): bool
    {
        return true;
    }

    public function boot(): void
    {
        $this->rest([CustomPromptsController::class]);
    }
}

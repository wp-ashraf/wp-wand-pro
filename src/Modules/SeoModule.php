<?php

namespace WPWand\Modules;

use WPWand\Admin\SeoIntegration;
use WPWand\Rest\Controllers\SeoController;

/** SEO meta-description generation for Yoast / Rank Math (Pro). */
final class SeoModule extends AbstractModule
{
    public function id(): string
    {
        return 'seo';
    }

    public function label(): string
    {
        return 'SEO';
    }

    public function requires_pro(): bool
    {
        return true;
    }

    public function boot(): void
    {
        $this->admin(static fn () => (new SeoIntegration())->register());
        $this->rest([SeoController::class]);
    }
}

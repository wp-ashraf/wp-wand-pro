<?php

namespace WPWand\Modules;

use WPWand\Admin\LicensePage;
use WPWand\Rest\Controllers\LicenseController;

/** License activation screen + API (Pro). */
final class LicenseModule extends AbstractModule
{
    public function id(): string
    {
        return 'license';
    }

    public function label(): string
    {
        return 'License';
    }

    public function requires_pro(): bool
    {
        return true;
    }

    public function boot(): void
    {
        $this->admin(static fn () => (new LicensePage())->register());
        $this->rest([LicenseController::class]);
    }
}

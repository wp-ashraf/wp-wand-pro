<?php

namespace WPWand\Modules;

use WPWand\Admin\LicensePage;
use WPWand\License\UpdateChecker;
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
        // The License screen must stay reachable WITHOUT an active license — it's how you activate
        // (or re-activate after a deactivation). It's only registered when the Pro plugin is present.
        return false;
    }

    public function boot(): void
    {
        $this->admin(static fn () => (new LicensePage())->register());
        $this->rest([LicenseController::class]);

        // Self-hosted plugin updates (replaces the legacy WPWandUdChecker in inc/tala.php).
        (new UpdateChecker())->register();
    }
}

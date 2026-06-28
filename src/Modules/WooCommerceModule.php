<?php

namespace WPWand\Modules;

use WPWand\Admin\WooCommerceIntegration;
use WPWand\Rest\Controllers\WooCommerceController;

/** WooCommerce product title/description generator (Pro). */
final class WooCommerceModule extends AbstractModule
{
    public function id(): string
    {
        return 'woocommerce';
    }

    public function label(): string
    {
        return 'WooCommerce';
    }

    public function requires_pro(): bool
    {
        return true;
    }

    public function boot(): void
    {
        $this->admin(static fn () => (new WooCommerceIntegration())->register());
        $this->rest([WooCommerceController::class]);
    }
}

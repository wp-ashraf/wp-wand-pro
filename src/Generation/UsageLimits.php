<?php

namespace WPWand\Generation;

use WPWand\License\LicenseService;

/**
 * Monthly usage caps for the Pro generation features, by license tier.
 *
 * Two independent monthly buckets:
 *   - BULK       (manual "Bulk Posts")     Solo 100 / Growth 300 / Agency unlimited
 *   - AUTOMATION (scheduled automation)    2× the bulk cap → Solo 200 / Growth 600 / Agency ∞
 *
 * The effective cap is derived from the tier at check time via {@see limits()}, NOT from a value
 * frozen at activation — so changing these numbers takes effect for every existing install with no
 * re-activation. The stored `wpwand_pgc_limit` (-1 / 20 / 10) is still written on activation and is
 * used here purely as the tier MARKER (agency / growth / solo), keeping backward compatibility.
 *
 * Both counters reset automatically every 30 days **while the license is active** (the requirement:
 * "limits reset monthly if the license is working"). Agency (-1) is unlimited and never blocks.
 */
class UsageLimits
{
    public const OPT_BULK_USED = 'wpwand_pgc_total_bulk_generated';
    public const OPT_AUTO_USED = 'wpwand_pgc_total_automation_generated';
    public const OPT_PERIOD    = 'wpwand_usage_period_start';

    private const PERIOD       = 30 * DAY_IN_SECONDS;
    private const UNLIMITED    = -1;

    /** Effective monthly caps for the current tier. @return array{bulk:int, automation:int} */
    public static function limits(): array
    {
        $marker = (int) get_option('wpwand_pgc_limit', 10);

        if ($marker === self::UNLIMITED) {
            return ['bulk' => self::UNLIMITED, 'automation' => self::UNLIMITED]; // agency
        }
        $bulk = $marker >= 20 ? 300 : 100; // growth : solo

        return ['bulk' => $bulk, 'automation' => $bulk * 2];
    }

    // ---- Bulk ------------------------------------------------------------------------------

    public static function bulk_limit(): int
    {
        return self::limits()['bulk'];
    }

    public static function bulk_used(): int
    {
        self::maybe_reset();
        return (int) get_option(self::OPT_BULK_USED, 0);
    }

    /** How many more bulk posts may be generated now (PHP_INT_MAX when unlimited). */
    public static function bulk_remaining(): int
    {
        $limit = self::bulk_limit();
        if ($limit === self::UNLIMITED) {
            return PHP_INT_MAX;
        }
        return max(0, $limit - self::bulk_used());
    }

    public static function can_bulk(int $need = 1): bool
    {
        return self::bulk_limit() === self::UNLIMITED || self::bulk_remaining() >= max(1, $need);
    }

    public static function consume_bulk(int $count): void
    {
        if (self::bulk_limit() === self::UNLIMITED || $count < 1) {
            return;
        }
        update_option(self::OPT_BULK_USED, self::bulk_used() + $count);
    }

    // ---- Automation ------------------------------------------------------------------------

    public static function automation_limit(): int
    {
        return self::limits()['automation'];
    }

    public static function automation_used(): int
    {
        self::maybe_reset();
        return (int) get_option(self::OPT_AUTO_USED, 0);
    }

    public static function automation_remaining(): int
    {
        $limit = self::automation_limit();
        if ($limit === self::UNLIMITED) {
            return PHP_INT_MAX;
        }
        return max(0, $limit - self::automation_used());
    }

    public static function can_automation(int $need = 1): bool
    {
        return self::automation_limit() === self::UNLIMITED || self::automation_remaining() >= max(1, $need);
    }

    public static function consume_automation(int $count): void
    {
        if (self::automation_limit() === self::UNLIMITED || $count < 1) {
            return;
        }
        update_option(self::OPT_AUTO_USED, self::automation_used() + $count);
    }

    // ---- Monthly reset ---------------------------------------------------------------------

    /**
     * Reset both counters every 30 days while the license is active. No-op when the license isn't
     * active (Pro features don't run then anyway) so a lapsed license can't silently refill its quota.
     */
    public static function maybe_reset(): void
    {
        if (get_option(LicenseService::OPT_STATUS) !== 'activated') {
            return;
        }

        $now   = (int) current_time('timestamp');
        $start = (int) get_option(self::OPT_PERIOD, 0);

        if ($start <= 0) {
            update_option(self::OPT_PERIOD, $now); // first run — start the clock
            return;
        }
        if ($now >= $start + self::PERIOD) {
            update_option(self::OPT_BULK_USED, 0);
            update_option(self::OPT_AUTO_USED, 0);
            update_option(self::OPT_PERIOD, $now);
        }
    }

    /** Human label for the bulk counter, e.g. "12/100" or "Unlimited". */
    public static function bulk_text(): string
    {
        $limit = self::bulk_limit();
        return $limit === self::UNLIMITED
            ? __('Unlimited', 'wp-wand-pro')
            : self::bulk_used() . '/' . $limit;
    }

    /** Human label for the automation counter, e.g. "30/600" or "Unlimited". */
    public static function automation_text(): string
    {
        $limit = self::automation_limit();
        return $limit === self::UNLIMITED
            ? __('Unlimited', 'wp-wand-pro')
            : self::automation_used() . '/' . $limit;
    }
}

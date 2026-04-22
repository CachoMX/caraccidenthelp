<?php

declare(strict_types=1);

namespace VIXI\CahSplit;

if (!defined('ABSPATH')) {
    exit;
}

final class Cron
{
    public const RETRY_HOOK = 'cah_split_retry_make_forwards';

    public function __construct(private readonly MakeForwarder $forwarder)
    {
    }

    public function boot(): void
    {
        \add_action(self::RETRY_HOOK, [$this, 'run']);
        \add_action('init', [$this, 'scheduleRetries']);
    }

    public function scheduleRetries(): void
    {
        if (!\wp_next_scheduled(self::RETRY_HOOK)) {
            \wp_schedule_event(\time() + 300, 'hourly', self::RETRY_HOOK);
        }
    }

    public function run(): void
    {
        $this->forwarder->retryPending();
    }

    public static function unschedule(): void
    {
        $next = \wp_next_scheduled(self::RETRY_HOOK);
        if ($next !== false) {
            \wp_unschedule_event($next, self::RETRY_HOOK);
        }
    }
}

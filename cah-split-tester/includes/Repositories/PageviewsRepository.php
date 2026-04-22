<?php

declare(strict_types=1);

namespace VIXI\CahSplit\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

final class PageviewsRepository
{
    public function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'cah_pageviews';
    }

    public function create(array $data): int
    {
        global $wpdb;
        $row = \array_merge(['created_at' => \current_time('mysql')], $data);
        $wpdb->insert($this->table(), $row);
        return (int) $wpdb->insert_id;
    }
}

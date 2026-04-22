<?php

declare(strict_types=1);

namespace VIXI\CahSplit\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

final class LeadsRepository
{
    public const MAKE_STATUS_PENDING = 'pending';
    public const MAKE_STATUS_SUCCESS = 'success';
    public const MAKE_STATUS_FAILED  = 'failed';

    public function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'cah_leads';
    }

    public function create(array $data): int
    {
        global $wpdb;
        $now  = \current_time('mysql');
        $row  = \array_merge([
            'make_status'  => self::MAKE_STATUS_PENDING,
            'make_attempts' => 0,
            'created_at'   => $now,
        ], $data);

        $wpdb->insert($this->table(), $row);
        return (int) $wpdb->insert_id;
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $table = $this->table();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        return \is_array($row) ? $row : null;
    }

    public function markForwardSuccess(int $id, ?string $response = null): void
    {
        global $wpdb;
        $wpdb->update($this->table(), [
            'make_status'       => self::MAKE_STATUS_SUCCESS,
            'make_forwarded_at' => \current_time('mysql'),
            'make_response'     => $response,
        ], ['id' => $id]);
    }

    public function markForwardFailed(int $id, string $response): void
    {
        global $wpdb;
        $table = $this->table();
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET make_status = %s, make_attempts = make_attempts + 1, make_response = %s WHERE id = %d",
            self::MAKE_STATUS_FAILED,
            $response,
            $id
        ));
    }

    public function findRetryable(int $maxAttempts = 3, int $limit = 25): array
    {
        global $wpdb;
        $table = $this->table();
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE make_status = %s AND make_attempts < %d ORDER BY id ASC LIMIT %d",
                self::MAKE_STATUS_FAILED,
                $maxAttempts,
                $limit
            ),
            ARRAY_A
        );
        return \is_array($rows) ? $rows : [];
    }

    public function countFailed(int $maxAttempts = 3): int
    {
        global $wpdb;
        $table = $this->table();
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE make_status = %s AND make_attempts < %d",
            self::MAKE_STATUS_FAILED,
            $maxAttempts
        ));
    }
}

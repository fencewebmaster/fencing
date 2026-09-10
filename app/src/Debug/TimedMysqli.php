<?php

declare(strict_types=1);

namespace Fc\Admin\Debug;

/**
 * mysqli subclass handed out by DatabaseConfigService::connectMysqli() ONLY while
 * DebugbarServer::collectQueries() is true. It times query() and counts prepare()
 * without touching results, errors, or the connection lifecycle - every caller in the
 * tree checks `instanceof \mysqli`, which this satisfies.
 *
 * Statement execution can't be centrally timed for mysqli (no PDO-style statement-class
 * hook), so prepares are logged with their SQL and no duration.
 */
class TimedMysqli extends \mysqli
{
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): \mysqli_result|bool
    {
        $t0 = hrtime(true);
        try {
            $result = parent::query($query, $result_mode);
            DebugbarServer::recordQuery(
                'mysqli.query',
                $query,
                (hrtime(true) - $t0) / 1e6,
                $result === false ? ($this->error !== '' ? $this->error : 'query failed') : null
            );

            return $result;
        } catch (\mysqli_sql_exception $e) {
            DebugbarServer::recordQuery('mysqli.query', $query, (hrtime(true) - $t0) / 1e6, $e->getMessage());

            throw $e;
        }
    }

    public function prepare(string $query): \mysqli_stmt|false
    {
        try {
            $stmt = parent::prepare($query);
            DebugbarServer::recordQuery(
                'mysqli.prepare',
                $query,
                null,
                $stmt === false ? ($this->error !== '' ? $this->error : 'prepare failed') : null
            );

            return $stmt;
        } catch (\mysqli_sql_exception $e) {
            DebugbarServer::recordQuery('mysqli.prepare', $query, null, $e->getMessage());

            throw $e;
        }
    }
}

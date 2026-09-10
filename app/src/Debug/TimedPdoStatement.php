<?php

declare(strict_types=1);

namespace Fc\Admin\Debug;

/**
 * PDOStatement subclass installed via PDO::ATTR_STATEMENT_CLASS by
 * DatabaseConfigService::pdo() ONLY while DebugbarServer::collectQueries() is true.
 * Times execute() and passes everything else through untouched.
 *
 * Note: PDO::query() calls (two sites, both in the lookup services) do not route
 * through execute() and are not timed here - the Debugbar documents that gap.
 */
class TimedPdoStatement extends \PDOStatement
{
    /** PDO requires a non-public constructor on statement subclasses. */
    protected function __construct()
    {
    }

    public function execute(?array $params = null): bool
    {
        $t0 = hrtime(true);
        try {
            $ok = parent::execute($params);
            DebugbarServer::recordQuery(
                'pdo.execute',
                (string) $this->queryString,
                (hrtime(true) - $t0) / 1e6,
                $ok ? null : 'execute failed'
            );

            return $ok;
        } catch (\PDOException $e) {
            DebugbarServer::recordQuery('pdo.execute', (string) $this->queryString, (hrtime(true) - $t0) / 1e6, $e->getMessage());

            throw $e;
        }
    }
}

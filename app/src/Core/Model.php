<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

use Fc\Admin\Services\Database;

/**
 * Base data-access model (CodeIgniter 4 convention — the CodeIgniter\Model analog).
 *
 * Table/connection resolution here is dynamic per-request (multi-site switching, demo
 * suffixing) rather than knowable at class-definition time, so subclasses override the
 * resolveTable()/connectionConfig() hooks instead of relying solely on the $table property.
 */
abstract class Model
{
    protected ?string $table = null;

    protected string $primaryKey = 'id';

    /** @var list<string> */
    protected array $allowedFields = [];

    /**
     * BARE table name (no prefix) — Database::insert()/update()/select_where()/delete() all
     * prepend their own $this->prefix internally (see Services\Database, `$this->prefix.$table`).
     * Passing an already-prefixed name here double-prefixes (e.g. "wp_wp_users") and silently
     * matches zero rows. Override when resolution is dynamic (site/prefix-dependent) — return
     * the same bare name any existing Database-consuming code in this class already uses.
     */
    protected function resolveTable(): string
    {
        if ($this->table === null || $this->table === '') {
            throw new \RuntimeException(static::class . ' must set $table or override resolveTable().');
        }

        return $this->table;
    }

    /**
     * DB connection config to use. Null defers to Database's own default (DatabaseConfigService::resolveConfig()).
     * Override when a model must always target a specific connection (e.g. the pinned auth DB).
     *
     * @return array{host?:string,database?:string,username?:string,password?:string,prefix?:string}|null
     */
    protected function connectionConfig(): ?array
    {
        return null;
    }

    /**
     * New Database connection for this model. Named (not `db()`) to avoid colliding with
     * existing Models that already expose their own static `db(): ?\mysqli` accessor.
     */
    protected function newConnection(): Database
    {
        return new Database($this->connectionConfig());
    }

    /**
     * @return object|array{}
     */
    public function find(int|string $id): object|array
    {
        $db = $this->newConnection();
        $where = $db->where_clause([$this->primaryKey => $id]);

        return $db->select_where($this->resolveTable(), $where);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string}
     */
    public function insert(array $data): array
    {
        return $this->newConnection()->insert($this->resolveTable(), $this->filterAllowedFields($data));
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string}
     */
    public function update(int|string $id, array $data): array
    {
        return $this->newConnection()->update(
            $this->resolveTable(),
            $this->filterAllowedFields($data),
            [$this->primaryKey => $id]
        );
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function delete(int|string $id): array
    {
        return $this->newConnection()->delete($this->resolveTable(), [$this->primaryKey => $id]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function filterAllowedFields(array $data): array
    {
        if ($this->allowedFields === []) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->allowedFields));
    }
}

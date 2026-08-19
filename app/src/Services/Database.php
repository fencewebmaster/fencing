<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

use Fc\Admin\Helpers\UrlHelper;

class Database {

    // Properties
    public $name;
    public $color;
    /** @var string Last connection error for diagnostics */
    public $last_connect_error = '';

    /**
     * @param array{host?:string,database?:string,username?:string,password?:string,prefix?:string}|null $cfg
     */
    public function __construct(?array $cfg = null) {
        $cfg = $cfg ?? DatabaseConfigService::resolveConfig();

        $this->host     = (string) ($cfg['host'] ?? '');
        $this->database = (string) ($cfg['database'] ?? '');
        $this->username = (string) ($cfg['username'] ?? '');
        $this->password = (string) ($cfg['password'] ?? '');
        $this->prefix   = (string) ($cfg['prefix'] ?? 'wp_');

        $this->is_demo = '';
        if (UrlHelper::inUriSegment(SiteRegistryService::demoStages())) {
            $this->is_demo = 'demo';
        }
    }

    //----------------------------------------------------------------------------------

    function connect() {
        $result = DatabaseConfigService::connectMysqli([
            'host'     => $this->host,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ]);

        $this->last_connect_error = (string) ($result['error'] ?? '');

        return $result['conn'] instanceof \mysqli ? $result['conn'] : null;
    }

    //----------------------------------------------------------------------------------

    function insert($table, $data) {
        $table = implode('_', array_filter([$this->prefix.$table, $this->is_demo]));

        $conn = $this->connect();

        if (! $conn instanceof \mysqli) {
            return [
                'success' => FALSE,
                'message' => 'Database error: ' . ($this->last_connect_error ?: 'connection failed'),
            ];
        }

        $new_data = array();

        foreach($data as $k => $v) {
            // Escaping is mandatory: unescaped text broke every save containing an apostrophe.
            $new_data[$k] = $conn->real_escape_string((string) (is_array($v) ? json_encode($v) : $v));
        }

        $columns = implode(', ', array_keys($data));
        $values  = "'" .implode("','", array_values($new_data)). "'";

        $sql = "INSERT INTO ".$table." (".$columns.") VALUES (".$values.")";

        try {
            $ok = $conn->query($sql);
        } catch (\mysqli_sql_exception $e) {
            $conn->close();
            error_log('FC Database::insert failed: ' . $sql . ' -- ' . $e->getMessage());
            return [
                'success' => FALSE,
                'message' => 'Database error. See server error log for details.',
            ];
        }

        if ($ok === TRUE) {
            $conn->close();
            return [
                'success' => TRUE,
                'message' => "New record created successfully"
            ];
        }

        $err = $conn->error;
        $conn->close();
        error_log('FC Database::insert failed: ' . $sql . ' -- ' . $err);
        return [
            'success' => FALSE,
            'message' => 'Database error. See server error log for details.',
        ];
    }

    //----------------------------------------------------------------------------------

    /**
     * Build a WHERE clause with every string value quoted.
     *
     * Bare numeric values made MySQL compare numerically, so an all-digit planner_id
     * cast the whole varchar column and could match an unrelated row.
     *
     * @param array<string, mixed> $where
     */
    function where_clause($where, $conn = null) {
        $parts = array();

        foreach ($where as $key => $value) {
            if (is_int($value) || is_float($value)) {
                $parts[] = '`' . $key . '`=' . $value;
                continue;
            }

            $value = (string) $value;
            $escaped = $conn instanceof \mysqli ? $conn->real_escape_string($value) : addslashes($value);

            $parts[] = '`' . $key . "`='" . $escaped . "'";
        }

        return implode(' AND ', $parts);
    }

    //----------------------------------------------------------------------------------

    function update($table = '', $data  = array(), $where  = array()) {
        $table = implode('_', array_filter([$this->prefix.$table, $this->is_demo]));

        $conn = $this->connect();

        if (! $conn instanceof \mysqli) {
            return [
                'success' => FALSE,
                'message' => 'Database error: ' . ($this->last_connect_error ?: 'connection failed'),
            ];
        }

        $new_data = array();

        foreach ($data as $key => $value) {
            // Only bare SQL literals for real int/float — never for numeric strings (e.g. mobile "0412…").
            if ( is_int( $value ) || is_float( $value ) ) {
                $new_data[] = $key . '=' . $value;
            } else {
                $new_data[] = $key . "='" . $conn->real_escape_string( (string) ( is_array( $value ) ? json_encode( $value ) : $value ) ) . "'";
            }
        }

        $set_data = implode(', ', $new_data);

        $where_data = $this->where_clause($where, $conn);

        $sql = "UPDATE ".$table." SET $set_data WHERE $where_data;";

        try {
            $ok = $conn->query($sql);
        } catch (\mysqli_sql_exception $e) {
            $conn->close();
            error_log('FC Database::update failed: ' . $sql . ' -- ' . $e->getMessage());
            return [
                'success' => FALSE,
                'message' => 'Database error. See server error log for details.',
            ];
        }

        if ($ok === TRUE) {
            $conn->close();
            return [
                'success' => TRUE,
                'message' => "Record is updated successfully"
            ];
        }

        $err = $conn->error;
        $conn->close();
        error_log('FC Database::update failed: ' . $sql . ' -- ' . $err);
        return [
            'success' => FALSE,
            'message' => 'Database error. See server error log for details.',
        ];
    }

    //----------------------------------------------------------------------------------

    function delete($table = '', $where = array()) {
        $table = implode('_', array_filter([$this->prefix.$table, $this->is_demo]));

        $conn = $this->connect();

        if (! $conn instanceof \mysqli) {
            return [
                'success' => FALSE,
                'message' => 'Database error: ' . ($this->last_connect_error ?: 'connection failed'),
            ];
        }

        $where_data = $this->where_clause($where, $conn);

        $sql = "DELETE FROM ".$table." WHERE $where_data;";

        try {
            $ok = $conn->query($sql);
        } catch (\mysqli_sql_exception $e) {
            $conn->close();
            error_log('FC Database::delete failed: ' . $sql . ' -- ' . $e->getMessage());
            return [
                'success' => FALSE,
                'message' => 'Database error. See server error log for details.',
            ];
        }

        if ($ok === TRUE) {
            $conn->close();
            return [
                'success' => TRUE,
                'message' => "Record is deleted successfully"
            ];
        }

        $err = $conn->error;
        $conn->close();
        error_log('FC Database::delete failed: ' . $sql . ' -- ' . $err);
        return [
            'success' => FALSE,
            'message' => 'Database error. See server error log for details.',
        ];
    }

    //----------------------------------------------------------------------------------

    function updateOrCreate($table, $data, $where) {
        $where_data = $this->where_clause($where);

        $find = $this->select_where($table, $where_data, 'id');

        if( $find ) {
            $q = $this->update($table, $data, $where);
        } else {
            $q = $this->insert($table, $data);
        }

        return $q;
    }

    //----------------------------------------------------------------------------------

    function select_where($table, $where, $select = '*') {
        $table = implode('_', array_filter([$this->prefix.$table, $this->is_demo]));

        $sql = "SELECT $select FROM ".$table." WHERE ".$where .' ORDER BY id DESC';

        $conn = $this->connect();

        try {
            $data = $conn->query($sql);
        } catch (\mysqli_sql_exception $e) {
            $conn->close();
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'FC Database::select_where: ' . $e->getMessage() );
            }
            return array();
        }

        $conn->close();

        if ( ! $data || $data->num_rows == 0 ) {
            return array();
        }

        return $data->fetch_object();
    }

    //----------------------------------------------------------------------------------

}

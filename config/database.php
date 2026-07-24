<?php

require_once __DIR__ . '/db_config.php';

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
        $cfg = $cfg ?? fc_db_resolve_config();

        $this->host     = (string) ($cfg['host'] ?? '');
        $this->database = (string) ($cfg['database'] ?? '');
        $this->username = (string) ($cfg['username'] ?? '');
        $this->password = (string) ($cfg['password'] ?? '');
        $this->prefix   = (string) ($cfg['prefix'] ?? 'wp_');

        $this->is_demo = '';
        if (function_exists('in_uri_segment') && function_exists('demo_stages') && in_uri_segment(demo_stages())) {
            $this->is_demo = 'demo';
        }
    }

    //----------------------------------------------------------------------------------

    function connect() {
        $result = fc_db_connect_mysqli([
            'host'     => $this->host,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ]);

        $this->last_connect_error = (string) ($result['error'] ?? '');

        return $result['conn'] instanceof mysqli ? $result['conn'] : null;
    }

    //----------------------------------------------------------------------------------

    function insert($table, $data) {
        $table = implode('_', array_filter([$this->prefix.$table, $this->is_demo]));

        $new_data = array();

        foreach($data as $k => $v) {
            $new_data[$k] = array_to_json($v);
        }

        $columns = implode(', ', array_keys($data));
        $values  = "'" .implode("','", array_values($new_data)). "'";

        $sql = "INSERT INTO ".$table." (".$columns.") VALUES (".$values.")";

        $conn = $this->connect();

        try {
            $ok = $conn->query($sql);
        } catch (\mysqli_sql_exception $e) {
            $conn->close();
            return [
                'success' => FALSE,
                'message' => 'Database error: ' . $e->getMessage(),
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
        return [
            'success' => FALSE,
            'message' => "Error: " . $sql . "<br>" . $err
        ];
    }

    //----------------------------------------------------------------------------------

    function update($table = '', $data  = array(), $where  = array()) {
        $table = implode('_', array_filter([$this->prefix.$table, $this->is_demo]));

        $new_data = $where_data = array();

        foreach ($data as $key => $value) {
            // Only bare SQL literals for real int/float — never for numeric strings (e.g. mobile "0412…").
            if ( is_int( $value ) || is_float( $value ) ) {
                $new_data[] = $key . '=' . $value;
            } else {
                $new_data[] = $key . "='" . array_to_json( $value ) . "'";
            }
        }

        $set_data = implode(', ', $new_data);

        foreach ($where as $key => $value) {
            $where_data[] = $key."=".(is_numeric($value) ? $value : "'".$value."'");
        }

        $where_data = implode(' AND ', $where_data);


        $sql = "UPDATE ".$table." SET $set_data WHERE $where_data;";

        $conn = $this->connect();

        try {
            $ok = $conn->query($sql);
        } catch (\mysqli_sql_exception $e) {
            $conn->close();
            return [
                'success' => FALSE,
                'message' => 'Database error: ' . $e->getMessage(),
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
        return [
            'success' => FALSE,
            'message' => "Error: " . $sql . "<br>" . $err
        ];
    }
    
    //----------------------------------------------------------------------------------

    function updateOrCreate($table, $data, $where) {
        $where_data = array();
        
        foreach ($where as $key => $value) {
            $where_data[] = $key."=".(is_numeric($value) ? $value : "'".$value."'");
        }

        $where_data = implode(' AND ', $where_data);

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




<?php
class Database {

    // Properties
    public $name;
    public $color;

    public function __construct() {   
        $config = config();
        $mysql = $config->mysql;

    	if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
	
	        $this->host     = "localhost";
            $this->database = $mysql->localhost->database;
	        $this->username = $mysql->localhost->username;
	        $this->password = $mysql->localhost->password;
	        $this->prefix   = $mysql->localhost->prefix;

	    } elseif( $_SERVER['HTTP_HOST'] == 'fencingwarehouse.au' ) {

            $this->host     = "localhost";
            $this->database = $mysql->fencingwarehouse->database;
            $this->username = $mysql->fencingwarehouse->username;
            $this->password = $mysql->fencingwarehouse->password;
            $this->prefix   = $mysql->fencingwarehouse->prefix;

        } else {
            
            // fencesperth.com
	        $this->host     = "localhost";
            $this->database = $mysql->default->database;
            $this->username = $mysql->default->username;
            $this->password = $mysql->default->password;
            $this->prefix   = $mysql->default->prefix;
	    }

        $this->is_demo = '';
        if( in_uri_segment(demo_stages()) ) {
            $this->is_demo = 'demo';
        }
    }

    //----------------------------------------------------------------------------------

    function connect() {
        // Create connection
        $conn = new mysqli($this->host, $this->username, $this->password, $this->database);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        return $conn;
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

        if ($conn->query($sql) === TRUE) {
            return [
                'success' => TRUE,
                'message' => "New record created successfully"
            ];
        } else {
            return [
                'success' => FALSE,
                'message' => "Error: " . $sql . "<br>" . $conn->error
            ];
        }

        $conn->close();
    }

    //----------------------------------------------------------------------------------

    function update($table = '', $data  = array(), $where  = array()) {
        $table = implode('_', array_filter([$this->prefix.$table, $this->is_demo]));

        $new_data = $where_data = array();

        foreach ($data as $key => $value) {
            $new_data[] = $key."=".(is_numeric($value) ? $value : "'".array_to_json($value)."'");
        }

        $set_data = implode(', ', $new_data);

        foreach ($where as $key => $value) {
            $where_data[] = $key."=".(is_numeric($value) ? $value : "'".$value."'");
        }

        $where_data = implode(' AND ', $where_data);


        $sql = "UPDATE ".$table." SET $set_data WHERE $where_data;";

        $conn = $this->connect();

        if ($conn->query($sql) === TRUE) {
            return [
                'success' => TRUE,
                'message' => "Record is updated successfully"
            ];
        } else {
            return [
                'success' => FALSE,
                'message' => "Error: " . $sql . "<br>" . $conn->error
            ];
        }

        $conn->close();
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

        $data = $conn->query($sql);

        $conn->close();

        if( $data->num_rows == 0 ) {
            return array();
        }

        return $data->fetch_object();
    }

    //----------------------------------------------------------------------------------

}




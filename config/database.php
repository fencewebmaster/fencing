<?php
class Database {

    // Properties
    public $name;
    public $color;

    public function __construct()
    {

    	if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
	
	        $this->host     = "localhost";
	        $this->username = "root";
	        $this->password = "";
	        $this->database = "fencing_calculator";
	        $this->prefix   = 'fc_';

	    } else {

	        $this->host     = "localhost";
	        $this->username = "u643294075_aMvzg";
	        $this->password = "mW5LMKeLEf";
	        $this->database = "u643294075_H57MF";
	        $this->prefix   = 'wp_';

	    }

    }

    function connect() {

        // Create connection
        $conn = new mysqli($this->host, $this->username, $this->password, $this->database);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        return $conn;

    }

    function insert($table, $data) {

        $new_data = array();

        foreach($data as $k => $v) {
            $new_data[$k] = array_to_json($v);
        }

        $columns = implode(', ', array_keys($data));
        $values  = "'" .implode("','", array_values($new_data)). "'";

        $sql = "INSERT INTO ".$this->prefix.$table." (".$columns.") VALUES (".$values.")";

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

}




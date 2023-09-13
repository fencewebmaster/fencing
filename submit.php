<?php
session_start();

$_SESSION["fc_data"] = $data = $_POST;

echo json_encode($data);
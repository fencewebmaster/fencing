<?php
include 'helpers.php';

$redirect_to = base_url('planner');

echo exec('git pull');

header("Location: ".$redirect_to);

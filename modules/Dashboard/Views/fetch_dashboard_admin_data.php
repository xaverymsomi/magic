<?php

use Libs\Database;

$db = new Database();

$data = [];

echo json_encode($data, JSON_NUMERIC_CHECK);

<?php

namespace Core\database;

//use Core\App;
use Libs\ApiLib;
use Libs\ApiLog;
use PDO;
use PDOException;

class Connection
{
    public static function make()
    {
        try {
            return new PDO($_ENV['DB_TYPE'] . ':Server=' . $_ENV['DB_HOST'] . ';Database=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
        } catch (PDOException $e) {
            ApiLog::dbConnection(json_encode(['message' => $e->getMessage(), 'trace' => $e->getTrace()]));
            ApiLib::handleResponse('Failed to establish database connection', [], 500);
        }
    }
}

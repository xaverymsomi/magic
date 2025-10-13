<?php

namespace Core\database;

use Libs\ApiLib;
use PDO;
use PDOException;
use Exception;

class DB
{
    static $pdo;
//    private static string $config;

    public function __construct()
    {
//        self::$config = 'sqlsrv';
        self::$pdo = Connection::make();
    }

    public function beginTransaction(): bool
    {
        return self::$pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return self::$pdo->commit();
    }

    public function prepare($query)
    {
        return self::$pdo->prepare($query);
    }

    public function rollBack(): bool
    {
        return self::$pdo->rollBack();
    }

    public function lastInsertId()
    {
        return self::$pdo->lastInsertId();
    }

    public static function selectAll($table)
    {
        $statement = self::$pdo->prepare("select * from {$table}");
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_CLASS);
    }

    public static function select($sql, $array = array(), $fetchMode = PDO::FETCH_ASSOC)
    {
        try {
            $sth = self::$pdo->prepare($sql);

            foreach ($array as $key => $value) {
                $sth->bindValue("$key", $value);
            }
            $sth->execute();
            $result = $sth->fetchAll($fetchMode);
            unset($sth);
            return $result;
        } catch (PDOException $e) {
            ApiLib::handleResponse('PDO-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        } catch (Exception $e) {
            ApiLib::handleResponse('DB-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    public static function save($table, $data, $model = null)
    {
        ksort($data);

        try {
            $fieldNames = [];
            $fieldValues = ':' . implode(', :', array_keys($data));
            $qry = '';
            switch (App::get('config')['sqlsrv']['DB_TYPE']) {
                case 'mysql':
                    $fieldNames = (new DB)->extract_mysql_insert_fields($data);
                    $qry = (new DB)->write_mysql_insert_statement($table, $fieldNames, $fieldValues);
                    break;
                case 'odbc':
                case 'sqlsrv':
                    $fieldNames = (new DB)->extract_sqlsrv_insert_fields($data);
                    $qry = (new DB)->write_sqlsrv_insert_statement($table, $fieldNames, $fieldValues);
                    break;
                default:
                    break;
            }

            $sth = self::$pdo->prepare($qry);
            foreach ($data as $key => $value) {
                $sth->bindValue(":$key", $value);
            }
            $result = $sth->execute();
            unset($sth);
            return $result;
        } catch (Exception $e) {
            ApiLib::handleResponse('DB-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    private function extract_mysql_insert_fields($data): string
    {
        return implode('`, `', array_keys($data));
    }

    private function extract_sqlsrv_insert_fields($data): string
    {
        return implode('], [', array_keys($data));
    }

    private function write_mysql_insert_statement($table, $names, $values): string
    {
        return "INSERT INTO $table (`$names`) VALUES ($values)";
    }

    private function write_sqlsrv_insert_statement($table, $names, $values): string
    {
        return "INSERT INTO $table ([$names]) VALUES ($values)";
    }

    public static function update($table, $data, $where)
    {
        ksort($data);

        try {
            $fieldDetails = '';
            switch ($_ENV['DB_TYPE']) {
                case 'mysql':
                    $fieldDetails = (new DB)->extract_mysql_update_fields($data);
                    break;
                case 'odbc':
                case 'sqlsrv':
                    $fieldDetails = (new DB)->extract_sqlsrv_update_fields($data);
                    break;
                default:
                    break;
            }

            $sql = "UPDATE $table SET $fieldDetails WHERE id = '$where'";

            $sth = self::$pdo->prepare($sql);
            foreach ($data as $key => $value) {
                $sth->bindValue(":$key", $value);
            }

            $result = $sth->execute();
            unset($sth);
            return $result;
        } catch (PDOException $e) {
            echo $e->getMessage();
            ApiLib::handleResponse('PDO-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        } catch (Exception $e) {
            ApiLib::handleResponse('DB-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    public static function updateCustom($table, $data, $where)
    {
        ksort($data);
        $whereClause = '';

        try {
            $fieldDetails = '';
            switch ($_ENV['DB_TYPE']) {
                case 'mysql':
                    $fieldDetails = (new DB)->extract_mysql_update_fields($data);
                    break;
                case 'odbc':
                case 'sqlsrv':
                    $fieldDetails = (new DB)->extract_sqlsrv_update_fields($data);
                    break;
                default:
                    break;
            }

            foreach ($where as $value) {
                $whereClause .= ' ' . $value;
            }

            $sth = self::$pdo->prepare("UPDATE $table SET $fieldDetails WHERE $whereClause");

            foreach ($data as $key => $value) {
                $sth->bindValue(":$key", $value);
            }

            $result = $sth->execute();
            unset($sth);
            return $result;
        } catch (PDOException $e) {
            ApiLib::handleResponse('PDO-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        } catch (Exception $e) {
            ApiLib::handleResponse('DB-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    public static function updateFiltered($table, $data, $where)
    {
        ksort($data);
        $whereClause = '';

        try {
            $fieldDetails = '';
            switch ($_ENV['DB_TYPE']) {
                case 'mysql':
                    $fieldDetails = (new DB)->extract_mysql_update_fields($data);
                    break;
                case 'sqlsrv':
                    $fieldDetails = (new DB)->extract_sqlsrv_update_fields($data);
                    break;
                default:
                    break;
            }

            foreach ($where as $key => $value) {
                $whereClause .= "$key" . " = :$key AND ";
            }

            $whereClause = trim($whereClause, ' AND ');
            $sql = "UPDATE $table SET $fieldDetails WHERE $whereClause";
            $sth = self::$pdo->prepare($sql);
            $dataObj = [];

            foreach ($data as $key => $value) {
                $dataObj[':' . $key] = $value;
            }
            foreach ($where as $key => $value) {
                $dataObj[':' . $key] = $value;
            }

            $result = $sth->execute($dataObj);
            unset($sth);
            return $result;
        } catch (PDOException $e) {
            ApiLib::handleResponse('PDO-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        } catch (Exception $e) {
            ApiLib::handleResponse('DB-EXCEPTION: ' . $e->getMessage(), [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    private function extract_mysql_update_fields($data): string
    {
        $value = '';
        foreach ($data as $key => $val) {
            $value .= "`$key`=:$key,";
        }
        return rtrim($value, ',');
    }

    private function extract_sqlsrv_update_fields($data): string
    {
        $value = '';
        foreach ($data as $key => $val) {
            $value .= "[$key]=:$key,";
        }
        return rtrim($value, ',');
    }
}

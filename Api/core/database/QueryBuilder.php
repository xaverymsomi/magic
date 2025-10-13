<?php

namespace Core\database;

use Libs\ApiLib;
use PDO;
use PDOException;
use Exception;
use Libs\ApiLog;

class QueryBuilder
{
    public $pdo;

    public function __construct()
    {
        $this->pdo = Connection::make();
    }

    private function handleSqlError($errorCode): string
    {
        switch ($errorCode) {
            case 2627: // Primary Key violation
            case 2601: // Unique constraint violation
                return "A record with the same unique value already exists. Please use a different value.";

            case 547: // Foreign Key violation
                return "This operation failed due to a missing related record. Ensure the referenced record exists before proceeding.";

            case 245: // Data type mismatch
                return "Invalid data type provided. Please check your input values.";

            case 8134: // Division by zero
                return "A mathematical error occurred: Division by zero is not allowed.";

            case 8152: // String too long
                return "Input data is too long for the specified field. Please shorten your input.";

            case 208: // Table does not exist
                return "The specified table or object does not exist. Please check the database schema.";

            case 2812: // Stored procedure does not exist
                return "The stored procedure does not exist. Verify the procedure name.";

            case 2714: // Object already exists
                return "An object with this name already exists in the database.";

            case 8115: // Arithmetic overflow
                return "An arithmetic overflow error occurred. The value is too large for the column type.";

            case 18456: // Login failure
                return "Database login failed. Please check your credentials.";

            case 1222: // Deadlock
                return "A database deadlock occurred. Try the operation again later.";

            default:
                return "A database error occurred. Please try again later or contact support.";
        }
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
//        ApiLib::handleResponse('Operation could not complete. Rolling back changes.', [], 100, __METHOD__);
        ApiApiLog::sysLog('Operation could not complete. Rolling back changes.');
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function last_id()
    {
        return $this->pdo->lastInsertId();
    }

    public function selectAll($table)
    {
        $statement = $this->pdo->prepare("select * from {$table}");
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_CLASS);
    }

    public function prepare($query)
    {
        return $this->pdo->prepare($query);
    }

    public function select($sql, $array = array(), $fetchMode = PDO::FETCH_ASSOC)
    {
        try {
            $sth = $this->pdo->prepare($sql);
            foreach ($array as $key => $value) {
                $sth->bindValue("$key", $value);
            }
            $sth->execute();
            $result = $sth->fetchAll($fetchMode);
            unset($sth);
            return $result;
        } catch (Exception $e) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    public function save($table, $data, $model = null)
    {
        ksort($data);

        try {
            $fieldNames = [];
            $fieldValues = ':' . implode(', :', array_keys($data));
            $qry = '';
            switch ($_ENV['DB_TYPE']) {
                case 'mysql':
                    $fieldNames = (new QueryBuilder)->extract_mysql_insert_fields($data);
                    $qry = (new QueryBuilder)->write_mysql_insert_statement($table, $fieldNames, $fieldValues);
                    break;
                case 'odbc':
                case 'sqlsrv':
                    $fieldNames = (new QueryBuilder)->extract_sqlsrv_insert_fields($data);
                    $qry = (new QueryBuilder)->write_sqlsrv_insert_statement($table, $fieldNames, $fieldValues);
                    break;
                default:
                    break;
            }

            $sth = $this->pdo->prepare($qry);
            foreach ($data as $key => $value) {
                $sth->bindValue(":$key", $value);
            }
            $result = $sth->execute();
            unset($sth);

            $data['id'] = $this->lastInsertId();

            ApiLog::queryLog('insert', $qry, $data);

            return $result;
        } catch (Exception $e) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    private function extract_mysql_insert_fields($data)
    {
        return implode('`, `', array_keys($data));
    }

    private function write_mysql_insert_statement($table, $names, $values)
    {
        return "INSERT INTO $table (`$names`) VALUES ($values)";
    }

    private function extract_sqlsrv_insert_fields($data)
    {
        return implode('], [', array_keys($data));
    }

    private function write_sqlsrv_insert_statement($table, $names, $values)
    {
        return "INSERT INTO $table ([$names]) VALUES ($values)";
    }

    public function update($table, $data, $where, $where_key = 'id')
    {
        ksort($data);

        try {
            $fieldDetails = '';

            switch ($_ENV['DB_TYPE']) {
                case 'mysql':
                    $fieldDetails = (new QueryBuilder)->extract_mysql_update_fields($data);
                    break;
                case 'odbc':
                case 'sqlsrv':
                    $fieldDetails = (new QueryBuilder)->extract_sqlsrv_update_fields($data);
                    break;
                default:
                    break;
            }

            $sql = "UPDATE $table SET $fieldDetails WHERE $where_key = '$where'";

            $sth = $this->pdo->prepare($sql);
            foreach ($data as $key => $value) {
                $sth->bindValue(":$key", $value);
            }

            $result = $sth->execute();
            unset($sth);
            return $result;
        } catch (PDOException $e) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
        } catch (Exception $e) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
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

    public function updateCustom($table, $data, $where)
    {
        ksort($data);
        $whereClause = '';

        try {
            $fieldDetails = '';
            switch ($_ENV['DB_TYPE']) {
                case 'mysql':
                    $fieldDetails = (new QueryBuilder)->extract_mysql_update_fields($data);
                    break;
                case 'odbc':
                case 'sqlsrv':
                    $fieldDetails = (new QueryBuilder)->extract_sqlsrv_update_fields($data);
                    break;
                default:
                    break;
            }

            foreach ($where as $value) {
                $whereClause .= ' ' . $value;
            }

            $sth = $this->pdo->prepare("UPDATE $table SET $fieldDetails WHERE $whereClause");

            foreach ($data as $key => $value) {
                $sth->bindValue(":$key", $value);
            }

            $result = $sth->execute();
            unset($sth);
            return $result;
        } catch (PDOException $e) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
        } catch (Exception $e) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

    public function updateFiltered($table, $data, $where)
    {
        ksort($data);
        $whereClause = '';

        try {
            $fieldDetails = '';
            switch ($_ENV['DB_TYPE']) {
                case 'mysql':
                    $fieldDetails = (new QueryBuilder)->extract_mysql_update_fields($data);
                    break;
                case 'sqlsrv':
                    $fieldDetails = (new QueryBuilder)->extract_sqlsrv_update_fields($data);
                    break;
                default:
                    break;
            }

            foreach ($where as $key => $value) {
                $whereClause .= "$key" . " = :$key AND ";
            }

            $whereClause = trim($whereClause, ' AND ');
            $sql = "UPDATE $table SET $fieldDetails WHERE $whereClause";
            $sth = $this->pdo->prepare($sql);
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
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
        } catch (Exception $e) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $matches);
            $errorCode = isset($matches[1]) ? (int)$matches[1] : 0;
            $msg = $this->handleSqlError($errorCode);
            ApiLib::handleResponse($msg, [], 500, __METHOD__, $e->getTraceAsString());
        }
    }

}

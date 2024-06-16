<?php


/**
 * Clase dSqlSrv2
 *
 * Esta clase se basa en el trabajo realizado por Danny Nunez.
 *
 * @version 0.2 
 * @author David Prats
 */
class dSqlSrv2
{
    public $serverName = DB_HOST;
    public $dbname = DB_NAME;
    public $user = DB_USER;
    public $password = DB_PASS;
    public $characterSet = "UTF-8";
    public $connection;
    public $statement = null;
    protected $status = null;

    function __construct($dbname = '')
    {
        if ($dbname != '') {
            $this->dbname = $dbname;
        }

        $connectionInfo = array(
            "UID" => $this->user,
            "PWD" => $this->password,
            "Database" => $this->dbname,
            "CharacterSet" => $this->characterSet,
            //"Driver" => "{ODBC Driver 18 for SQL Server}"
        );
        $this->connection = sqlsrv_connect($this->serverName, $connectionInfo);
        if ($this->connection) {
            $this->status = true;
        } else {
            $this->status = false;
        }
    }


    /**
     * Checks is the db connection is established. All queries for dynamic DB content should check is the
     * connection is established and load fallback content if the connection value is false.   
     * <code>
     * $db->getStatus(  );
     * </code>
     * 
     *
     * @return bool
     */

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Closes an open connection and releases resourses associated with the connection.
     * <code>
     * $db->close( );
     * </code>
     * @return bool TRUE on success or FALSE on failure.
     */
    public function close()
    {
        if ($this->connection) {
            $result = sqlsrv_close($this->connection);
            if ($result === false) {
                // Opcional: Puedes registrar el error o manejarlo de alguna otra manera.
                // error_log(print_r(sqlsrv_errors(), true));
                return false;
            }
        }
        //Para que no de error si ya estuviera cerrada
        return true;
    }

    /**
     * Begin the transaction.
     * <code>
     * $db->transaction( );
     * </code>
     * @return bool TRUE on success or FALSE on failure.
     */
    public function transaction()
    {
        /* Begin the transaction. */
        $result = sqlsrv_begin_transaction($this->connection);
        if ($result === false) {
            // Opcional: Puedes registrar el error o manejarlo de alguna otra manera.
            throw new Exception('transaction ::'.sqlsrv_errors(), 1);           
        }
        return true;
    }


    /**
     * If both queries were successful, commit the transaction. 
     * <code>
     * $db->commit( );
     * </code>
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commit()
    {
        $result = sqlsrv_commit($this->connection);
        if ($result === false) {
            // Opcional: Puedes registrar el error o manejarlo de alguna otra manera.
            throw new Exception(print_r(sqlsrv_errors()), 1);
            //return false;
        }
        return true;
    }


    /**
     * Rollback the transaction
     * <code>
     * $db->rollback( );
     * </code>
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollback()
    {
        $result = sqlsrv_rollback($this->connection);
        if ($result === false) {
            // Opcional: Puedes registrar el error o manejarlo de alguna otra manera.
            throw new Exception('rollback ::'.sqlsrv_errors(), 1);       
            //return false;
        }
        return true;
    }

    
    /**
     * @param $stmt - The statement resource returned by sqlsrv_query or sqlsrv_prepare.
     * @return bool TRUE on success or FALSE on failure.
     * @link http://www.php.net/manual/en/function.sqlsrv-free-stmt.php
     */
    public function freeStmt($stmt = null)
    {
        if( $stmt == null )
            return sqlsrv_free_stmt($this->statement);
        else
            return sqlsrv_free_stmt($stmt);
        
    }


    /**
     * Prepared statement
     * @param string $query sql query
     * @param array $params
     * @return resource|bool 'a statement resource on success and FALSE if an error occurred.
     *
     * @link http://www.php.net/manual/en/function.sqlsrv-prepare.php
     */
    public function prepare($query, $params= null)
    {
        return sqlsrv_prepare($this->connection, $query, $params);
    }


    /**
     * @param $prepStmt - Value of prepared statement.     * 
     * @return bool TRUE on success or FALSE on failure
     * 
     * @link http://www.php.net/manual/en/function.sqlsrv-execute.php
     */
    public function execute($prepStmt = null)
    {
        $this->statement = ($prepStmt == null) ? $this->statement : $prepStmt;
        $result = sqlsrv_execute($this->statement);
        if ($result === false) {
            // Opcional: Puedes registrar el error o manejarlo de alguna otra manera.
            error_log(print_r(sqlsrv_errors(), true));
            return false;
        }
        return true;
    }


    /**
     * Prepared statement and executes
     * @param string $query sql query
     * @param array $params
     * @link http://www.php.net/manual/en/function.sqlsrv-execute.php
     * @return 'Returns' TRUE on success FALSE on failure
     */
    public function prepareExecute($query, $params = null)
    {

        $stmt = $this->prepare($query, $params);
        if ($stmt === false) {
            return false;
        } else {
            $this->statement = $stmt;
            $result = sqlsrv_execute($stmt);
            if ($result === false) {
                // Opcional: Puedes registrar el error o manejarlo de alguna otra manera.
                error_log(print_r(sqlsrv_errors(), true));
                return false;
            }
            return true;
        }
    }


    /**
     * Prepared statement and return fetchArray
     * @param string $query sql query
     * @param array $params
     * @param string $type - The type of array to return. SQLSRV_FETCH_ASSOC or SQLSRV_FETCH_NUMERIC 
     * @return mixed arry bool - This method will return an associative or numeric array is results are returned.
     * By default an associative array will be returned. 
     */
    public function prepareFetchArray($query, $params, $type = SQLSRV_FETCH_ASSOC)
    {
        $results = array();
        $preparedStatement = sqlsrv_prepare($this->connection, $query, $params);
        if ($preparedStatement === false) {
            error_log(print_r(sqlsrv_errors(), true));
            return false;
        }
        $result = sqlsrv_execute($preparedStatement);
        $results = array();
        if ($result === true) {
            $type = ($type == SQLSRV_FETCH_ASSOC) ? 2 : 1;
            while ($row = sqlsrv_fetch_array($preparedStatement, $type)) {
                $results[] = $row;
            }
            return $results;
        }
        return false;
    }


    /**
     * @param $query string       
     * @param $params array                
     * @return mixed 'Returns data from the specified field on success. Returns FALSE otherwise.
     * @link http://www.php.net/manual/en/function.sqlsrv-get-field.php 
     */
    public function prepareFetchCol($query, $params = null)
    {
        $this->statement = sqlsrv_prepare($this->connection, $query, $params);
        $result = sqlsrv_execute($this->statement);
        if ($result === true) {
            while ($row = sqlsrv_fetch_array($this->statement, SQLSRV_FETCH_NUMERIC)) {
                return $row[0];
            }
            return false;
        } else {
            return false;
        }
    }


    /**
     * @param $query string 
     * @param $params array            
     * @return 'statement'
     * @link http://www.php.net/manual/en/function.sqlsrv-query.php
     * Tambien acepta parametros
     * 25-10-2023 se modifica para que funcione con parametros.
     * $sql = "SELECT * FROM tabla WHERE columna = ?";
     * $params = array($valor);
     * $result = sqlsrv_query($conn, $sql, $params);       
     */
    public function query($query, $params = null)
    {
        $this->statement = sqlsrv_query($this->connection, $query, $params);
        if (!$this->statement) {
                error_log(print_r(sqlsrv_errors(), true));
                return false; 
        }
        return $this->statement;
    }

    /**
     * Return Last entered ID
     * @return integer
     */
    public function lastInsertId()
    {
        $scopeId = (int) $this->fetchCol("SELECT SCOPE_IDENTITY() AS SCOPE_IDENTITY");
        return $scopeId;
    }

    /**
     * Return Last entered ID
     * @param $stmt - The statement resource returned by sqlsrv_query or sqlsrv_prepare.
     * @return integer
     * @link http://php.net/manual/en/function.sqlsrv-next-result.php
     */
    function lastId($stmt)
    {
        sqlsrv_next_result($stmt);
        sqlsrv_fetch($stmt);
        return sqlsrv_get_field($stmt, 0);
    }

    function lastId3($stmt)
    {
        // Mover al siguiente resultado si está disponible
        if (sqlsrv_next_result($stmt)) {

             if (sqlsrv_next_result($stmt)) {
                // Recuperar el resultado
                if (sqlsrv_fetch($stmt)) {
                    return sqlsrv_get_field($stmt, 0);
                }
             }
        }
        return null; // O manejar el error como prefieras
    }

    /**
     * Return Last entered ID
     * @param $stmt - The statement resource returned by sqlsrv_query or sqlsrv_prepare.
     * @return integer
     * @link http://php.net/manual/en/function.sqlsrv-next-result.php
     */
    function lastId2()
    {
        if (is_null($this->statement)) {
            return -1;
        }
        sqlsrv_next_result($this->statement);
        sqlsrv_fetch($this->statement);
        return sqlsrv_get_field($this->statement, 0);
    }



    /**
     * @return integer
     * @param $stmt - The statement resource returned by sqlsrv_query or sqlsrv_prepare.
     */
    public function getRowsAffected($smtp = null)
    {
        if( $smtp != null )
            $this->statement = $smtp;
        
        if (is_null($this->statement)) {
            return -1;
        }
        $rowsAffected = sqlsrv_rows_affected($this->statement);
        if ($rowsAffected == -1 || $rowsAffected === false) {
            return -1;
        }
        return (int) $rowsAffected;
    }



    /**
     * @param $query string           
     * @return mixed array of objects - Returns an object on success,
     * NULL if there are no more rows to return, 
     * and FALSE if an error occurs or if the specified class does not exist.
     * @link http://www.php.net/manual/en/function.sqlsrv-fetch-object.php
     */
    public function fetchObject($query, $params = null)
    {

        if ($query != null && $query != '')
            $this->query($query, $params);

        if ($this->statement) {

            $a_array = array();
            //@return Returns an object on success, null if there are no more rows to return, and false if an error occurs or if the specified class does not exist.                  
            while ($res = sqlsrv_fetch_object($this->statement)) {
                $a_array[] = $res;
            }
            return $a_array;
        } else {

            return false;
        }
    }



    /**
     * @param string $query 
     * @param string|int $type  - The type of array to return. SQLSRV_FETCH_ASSOC or SQLSRV_FETCH_NUMERIC 
     * for more info see here http://www.php.net/manual/en/function.sqlsrv-fetch-array.php 
     * @return array|false of array
     */
    public function fetchArray($query = null, $type = SQLSRV_FETCH_ASSOC, $params = null)
    {

        if ($query != null && $query != '')
            $this->query($query, $params);

        if ($this->statement) {

            $a_array = array();
            $type = ($type == SQLSRV_FETCH_ASSOC || $type == 2) ? 2 : 1;
            //@return Returns an array on success, null if there are no more rows to return, and false if an error occurs.              
            while ($res = sqlsrv_fetch_array($this->statement, $type)) {
                $a_array[] = $res;
            }
            return $a_array;
        } else {

            error_log(print_r(sqlsrv_errors(), true));
            return false;
        }
    }



    /**
     * @param $query string           
     * @return $column value - Returns data from the specified field on success. Returns FALSE otherwise.
     * @link http://www.php.net/manual/en/function.sqlsrv-get-field.php
     */
    public function fetchCol($query, $params = null)
    {

        $this->statement = $this->query($query, $params);
        sqlsrv_fetch($this->statement);
        $column = sqlsrv_get_field($this->statement, 0);

        return $column;
    }


    /**
     * @param $prepStmt
     * @param string $fetchType object|array
     * @return 'multitype:mixed'
     */
    public function executeFetch($prepStmt, $fetchType = 'object')
    {
        if (sqlsrv_execute($prepStmt)) {
            $a_array = array();
            $func = 'sqlsrv_fetch_' . $fetchType;
            while ($res = call_user_func($func, $prepStmt)) {
                $a_array[] = $res;
            }
            return $a_array;
        } else {            
            return false;
        }
    }


    /**
     * @param string $tableName - The name of the table. Returns all rows from the table requested. 
     * @param string|array $feilds - The feilds to return. By default all feilds will be returned.  
     * @param string|int $type - The return type wanted. SQLSRV_FETCH_ASSOC OR SQLSRV_FETCH_NUMERIC
     * @param string $order - Order the results either ASC or DESC. DESC is the default
     * 
     * @return array|bool - This method will return an associative or numeric array is results are returned.
     * By default an associative array will be returned. 
     */
    public function get($tableName, $feilds = '*', $type = SQLSRV_FETCH_ASSOC, $order = 'DESC')
    {
        if (is_array($feilds)) {
            $feildsString = $this->feildsBuilder($feilds);
        } else {
            $feildsString = '*';
        }
        $sql = "SELECT $feildsString FROM $tableName ORDER BY id $order";
        $preparedStatement = sqlsrv_prepare($this->connection, $sql);
        $result = sqlsrv_execute($preparedStatement);
        $results = array();
        if ($result === true) {

            $type = ($type == SQLSRV_FETCH_ASSOC || $type == 2) ? 2 : 1;
            while ($row = sqlsrv_fetch_array($preparedStatement, $type)) {
                $results[] = $row;
            }

            return $results;
        } else {
            return false;
        }
    }



    /**
     * @param string $tableName - The name of the table. Returns all rows from the table requested.
     * @param int $Id - The id of the record.   
     * @param string $type - The return type wanted. SQLSRV_FETCH_ASSOC 
     * OR SQLSRV_FETCH_NUMERIC - See http://www.php.net/manual/en/function.sqlsrv-fetch-array.php
     * @return mixed array||bool - This method will return an associative or numeric array is results are returned.
     * By default an associative array will be returned. 
     */
    public function get_by_id($tableName, $id = null)
    {
        $sql = "SELECT * FROM " . $tableName . " WHERE id = $id";
        $preparedStatement = sqlsrv_prepare($this->connection, $sql);
        $result = sqlsrv_execute($preparedStatement);
        if ($result === true) {
            while ($row = sqlsrv_fetch_array($preparedStatement, SQLSRV_FETCH_ASSOC)) {
                $results = $row;
            }
            return $results;
        } else {
            return false;
        }
    }



    /**
     * @param string $tableName - The name of the table. 
     * @param array $keyValue - An associative array. The key is the feild name and the value is the field value.   
     * @param string $type - The return type wanted. SQLSRV_FETCH_ASSOC
     * @param string $field - The field to order by.
     * @param string $order - Order the results either ASC or DESC. DESC is the default 
     * OR SQLSRV_FETCH_NUMERIC - See http://www.php.net/manual/en/function.sqlsrv-fetch-array.php
     * @return mixed - This method will return an associative or numeric array is results are returned. False if no results are returned.
     * By default an associative array will be returned.
     * Build a querry builder to handle mulitple key value pairs. Currently only handles on set of keyvalues. 
     */
    public function get_where($tableName, $keyValue, $type = 'SQLSRV_FETCH_ASSOC', $field = 'id', $order = 'DESC')
    {
        $sqlString = $this->query_builder($keyValue);
        $sql = "SELECT * FROM $tableName WHERE $sqlString ORDER BY $field $order";
        $preparedStatement = sqlsrv_prepare($this->connection, $sql);
        $result = $this->execute($preparedStatement);
        $results = array();
        if ($result === true) {
            while ($row = sqlsrv_fetch_array($preparedStatement, SQLSRV_FETCH_ASSOC)) {
                $results[] = $row;
            }
            return $results;
        } else {
            return false;
        }
    }


    public function query_builder($keyValue)
    {
        $sqlString = '';
        $numberOfKeyValues = count($keyValue);
        $count = 1;
        foreach ($keyValue as $key => $value) {
            if ($count == $numberOfKeyValues) {
                $sqlString = $sqlString . $key . ' = ' . $value . ' ';
            } else {
                $sqlString = $sqlString . $key . ' = ' . $value . ' AND ';
            }
            $count++;
        }
        return $sqlString;
    }
    public function feildsBuilder($feilds)
    {
        $feildsString = '';
        $numberOfFeilds = count($feilds);
        $count = 1;
        foreach ($feilds as $value) {
            if ($count == $numberOfFeilds) {
                $feildsString = $feildsString . $value . ' ';
            } else {
                $feildsString = $feildsString . $value . ' , ';
            }
            $count++;
        }
        return $feildsString;
    }


    //Escapa caracteres ' en mssql para insertar
    public function escape($str)
    {
        $str = stripslashes($str);
        return str_replace("'", "´", $str);
    }
}

<?php
declare(strict_types=1);

/* config.php provides the connect_db() function. */
require_once('./config.php');

/**
 * Runs the sql query on the database and returns the result
 * as single value.
 *
 * Parameters:
 * - sql: should be the structured query statement to run.
 *        Note: the underlying database is MySQL, using the
 *        InnoDb storage engine. Use the MySQL dialect.
 *        Craft the statement to return a single value. Other-
 *        wise, expect errors and crashes.
 *
 * Returns:
 * The only value that the SQL statement can return.
 */
function scalar(string $sql): any {
    $mysqli = connect_db();
    $result = $mysqli->query($sql, MYSQLI_STORE_RESULT);
    $row = $result->fetch_assoc();
    if (empty($row)) {
        return null;
    }
    return reset($row);
}

/**
 * Runs the sql query on the database and returns the result
 * as a list of dicts. Each row in the list is a row in the
 * query's resultset. Field names are used as dict names.
 *
 * Parameters:
 * - sql: should be the structured query statement to run.
 *        Note: the underlying database is MySQL, using the
 *        InnoDb storage engine. Use the MySQL dialect.
 *
 * Returns:
 * Either null, or a list of tuples (associative array). Each
 * tuple is a row of the query result, with the keys of its 
 * key-value pairs named after the field names specified in 
 * the SQL statement.
 */
function query(string $sql, ?string $param_types = null, ?array $params = null): ?array {
    if (empty($param_types) || empty($params)) {
        $mysqli = connect_db();
        $result = $mysqli->query($sql, MYSQLI_STORE_RESULT);
        return $result->fetch_all(MYSQLI_BOTH);
    }
    return db_exec($sql, $param_types, $params);
}

/**
 * Run the query statement passed in as $dml on the database
 * connection returned by connect_db(), automatically assigning
 * the parameters if present. 
 *
 * Parameters:
 * - dml: should be the data manipulation statement to run.
 *        Can run SQL as well.
 *        Note: the underlying database is MySQL, using the
 *        InnoDb storage engine. Use the MySQL dialect.
 * - param_types:
 *        May be type hints for a mysqli prepared statement.
 *        If not passing in parameters, omit this argument.
 *        Default: null.
 * - params:
 *        May be the values to pass into the mysqli statement
 *        that gets prepared based on the passed-in dml.
 *        Parameters are positional: the same position in this
 *        array is expected to replace a question mark in the 
 *        dml. If a wrong amount of parameters is given, the
 *        procedure will fail.
 *
 * Returns:
 * Either an empty list, or a list of tuples (associative array). 
 * Each tuple is a row of the query result, with the keys of its 
 * key-value pairs named after the field names specified in the 
 * SQL statement.
 */
function db_exec(string $dml, ?string $param_types = null, ?array $params = null): array {
    $mysqli = connect_db();
    $ps = $mysqli->prepare($dml);
    if (!empty($param_types) && !empty($params)) {
        /* automatically bind all parameters */
        $ps->bind_param($param_types, ...$params);
    }
    $ps->execute();
    $result = $ps->get_result();
    if (is_null($result)) {
        return [];
    }
    if (method_exists($result, 'fetch_all')) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

?>

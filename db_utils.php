<?php
/**
 * Database utilities.
 * Has a few abstractions for running custom queries, also defines 
 * a few standard queries expected to be used often.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1014.1328
 */
declare(strict_types=1);

/* config.php provides the connect_db() function. */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/config.php';

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
function scalar(?string $sql) {
    if (empty($sql)) {
        return null;
    }
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
function query(?string $sql, ?string $param_types = null, ?array $params = null): ?array {
    if (empty($sql)) {
        return null;
    }
    if (empty($param_types) || empty($params)) {
        $mysqli = connect_db();
        $result = $mysqli->query($sql, MYSQLI_STORE_RESULT);
        return $result->fetch_all(MYSQLI_ASSOC);
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
function db_exec(?string $dml, ?string $param_types = null, ?array $params = null): array {
    if (empty($dml)) {
        return [];
    }
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


/**
 * Reads the enumerations from the database. They are stored as
 * separate values for each attribute, with a language code.
 *
 * Parameters:
 * - language_code: a 2-letter ISO language code that determines
 *                  which translation to retrieve. For instance: 'en'.
 *
 * Returns: a list of tuples, each of which has the following fields:
 * - attribute_id: identifies the attribute to enumerate,
 * - enum_value: an enumerated value for the attribute, which gets
 *               stored in the database.
 * - caption: the text to display to the user.
 * The list is ordered by attribute_id and caption, ascending.
 * 
 * You can destructure like so:
 *
 * $enums = read_enumerations_from_db('en');
 * foreach($enums as list(
 *      'attribute_id' => $id,
 *      'enum_value' => $val,
 *      'caption' => $txt
 * )) { echo "$id - $val ($txt)"; }
 */
function read_enumerations_from_db(string $language_code): array {
    $sql = "select `attribute_id`, `enum_value`, `caption` from `enums` where `language_code` = ? order by `attribute_id`, `caption`";
    return query($sql, 's', [$language_code]);
}

/**
 * The current version of PHP, 7.4, returns a hash of 128 chars
 * for sha512.
 */
function hash_candidate(string $candidate): string {
    return hash(
        'sha512', 
        $candidate
    );
}

/**
 * Whether an email address is known for an existing user
 * of the system.
 * 
 * Parameters: 
 * - hashed_candidate, string: the hash of the email address
 *   to check for existence. Use the hash_candidate function
 *   in this module to hash the email address.
 * 
 * Returns:
 * True if the user is recognized by the passed-in email hash.
 */
function is_email_known(?string $email): bool {
    if (empty($email)) {
        return false;
    }
    $email_hashed = hash_candidate($email);
    $sql = 'select 
        (count(*) > 0) as `is_known` 
        from `users` 
        where `email_hash` = \'' 
        . $email_hashed
        . '\'';
    return ('1' == scalar($sql));
}

/**
 * Retrieves from the users table for the passed-in email address,
 * the hashing algorithm and version used to hash the key and secret
 * used to authenticate the user who owns that email address.
 * 
 * Parameters
 * - email: should be the email hoped to have been registered
 *
 * Returns
 * An empty array in case no user was found with that email address,
 * or an array for that user, containing these fields:
 * - hashing_algo: the name of the algorithm used to hash tokens,
 * - hashing_version: the version of the hashing algorithm.
 */
function get_hashing_algo_for_user_by_email(?string $email):?array {
    if (empty($email)) {
        return [];
    }
    $email_hash = hash_candidate($email);
    $sql = 'select `hashing_algo`, `hashing_version` 
        from `users` where `email_hash` = ?';
    $result = query($sql, 's', [$email_hash]);
    if (count($result) > 0) {
        return $result[0];
    }
    return [];
}

/**
 * Whether or not the provided credentials match a known user.
 * We hash the provided information using the same hashing algorithm
 * as used by the system when it stored the user's credentials last
 * time.
 */
function is_user_known(?string $email, ?string $key, ?string $secret):int {
    if (
        empty($email)
        || empty($key)
        || empty($secret)
    ) {
        return -1;
    }
    $h = get_hashing_algo_for_user_by_email($email);
    if (empty($h) || (count($h) < 2)) { 
        return -2;
    }
    [
        'hashing_algo' => $hashing_algo, 
        'hashing_version' => $hashing_version
    ] = $h;
    if (empty($hashing_algo) || empty($hashing_version)) {
        return -3;
    }
    $email_hash = hash_candidate($email);
    $key_hash = hash($hashing_algo, $key);
    $secret_hash = hash($hashing_algo, $secret);
    $sql = 'select
        (count(*) > 0) as `is_known`
        from `users`
        where `email_hash` = ?
        and `key_hash` = ?
        and `secret_hash` = ?';
    $result = query(
        $sql, 'sss', [
            $email_hash, 
            $key_hash, 
            $secret_hash
        ]
    );
    if (empty($result) || empty($result[0])) {
        return -4;
    }
    if ('1' == $result[0]['is_known']) {
        return 1;
    }
    return -5;
}

?>

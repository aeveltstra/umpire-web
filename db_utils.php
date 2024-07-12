<?php
/**
 * Database utilities.
 * Has a few abstractions for running custom queries, also defines a few 
 * standard queries expected to be used often.
 * 
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.708.2055
 */
declare(strict_types=1);
 
/* config.php provides the connect_db() function. */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/config.php';

// We do NOT want to treat the warnings as errors, which is the default,
// so we set our desired reporting style explicitly. The reason is that
// it will echo the authentication credentials to the public when the DB
// connection fails. That is NEVER what we want.
mysqli_report(MYSQLI_REPORT_ALL);

/**
 * Runs an sql query on the database and returns the result as single value.
 *
 * @param $sql should be the structured query statement to run.
 *             Note: the underlying database is MySQL, using the InnoDb 
 *             storage engine. Use the MySQL dialect. Craft the statement
 *             to return a single value. Otherwise, expect errors and 
 *             crashes.
 *
 * @return The only value that the SQL statement can return.
 */
function scalar(?string $sql)
{
    if (empty($sql)) {
        return null;
    }
    $mysqli = null;
    try {
        $mysqli = connect_db();
    } catch (Exception $x) {
        throw new Exception('Failed to connect to database.', 0, $x);
    }
    if (is_null($mysqli)) {
        return null;
    }
    $result = $mysqli->query($sql, MYSQLI_STORE_RESULT);
    $row = $result->fetch_assoc();
    if (empty($row)) {
        return null;
    }
    return reset($row);
}

/**
 * Runs the sql query on the database and returns the result as a list of 
 * dicts. Each row in the list is a row in the query's resultset. Field 
 * names are used as dict names.
 *
 * @param $sql   should be the structured query statement to run.
 *               Note: the underlying database is MySQL, using the InnoDb 
 *               storage engine. Use the MySQL dialect.
 * @param $types should be the SQL data types specified in the way
 *               expected by mysqli.bind_param(), as a literal string 
 *               with single-character data type indicators. Use if $sql
 *               contains ?-parameters, otherwise omit.
 * @param $vals  should be the values passed into mysqli.bind_param(),
 *               to supply for the ?-parameters specified in $sql. If 
 *               none supplied, omit.
 *
 * @return Either null, or a list of tuples (associative array). Each 
 *         tuple is a row of the query result, with the keys of its key-
 *         value pairs named after the field names specified in the SQL 
 *         statement.
 */
function query(?string $sql, ?string $types = null, ?array $vals = null): ?array
{
    if (empty($sql)) {
        return null;
    }
    if (empty($types) || empty($vals)) {
        $mysqli = null;
        try {
            $mysqli = connect_db();
        } catch (Exception $x) {
            throw new Exception(
                'Failed to connect to the database.',
                0,
                $x
            );
        }
        if (!($mysqli instanceof mysqli)) {
            return [];
        }
        $result = $mysqli->query($sql, MYSQLI_STORE_RESULT);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return db_exec($sql, $types, $vals);
}

/**
 * Run the query statement passed in as $dml on the database connection 
 * returned by connect_db(), automatically assigning the parameters if 
 * present. 
 *
 * @param $dml    should be the data manipulation statement to run. Can 
 *                run SQL too. Note: the underlying database is MySQL, 
 *                using the InnoDb storage engine. Use the MySQL dialect.
 * @param $types  may be type hints for a mysqli prepared statement. If 
 *                not passing in parameters, omit this argument. Default:
 *                null.
 * @param $params may be the values to pass into the mysqli statement 
 *                that gets prepared based on the passed-in dml. Para-
 *                meters are positional: the same position in this array 
 *                is expected to replace a question mark in the dml. If 
 *                a wrong amount of parameters is given, the procedure 
 *                will fail.
 *
 * @return Either an empty list, or a list of tuples (associative array).
 * Each tuple is a row of the query result, with the keys of its key-
 * value pairs named after the field names specified in the SQL statement.
 */
function db_exec(?string $dml, ?string $types = null, ?array $params = null): array
{
    if (empty($dml)) {
        return [];
    }
    $mysqli = null;
    try {
        $mysqli = connect_db();
    } catch (Exception $x) {
        throw new Exception(
            'Failed to connect to database.',
            0,
            $x
        );
    }
    if (is_null($mysqli)) {
        return [];
    }
    $ps = $mysqli->prepare($dml);
    if (!empty($types) && !empty($params)) {
        /* automatically bind all parameters */
        $ps->bind_param($types, ...$params);
    }
    $ps->execute();
    $result = $ps->get_result();
    if (is_null($result)) {
        $ps->reset();
        return [];
    }
    if (method_exists($result, 'fetch_all')) {
        $output = $result->fetch_all(MYSQLI_ASSOC);
        $ps->reset();
        return $output;
    }
    return [];
}

/**
 * Subscribes an email address to updates to a case.
 * 
 * @param $case_id identifies the profile / case to subscribe to.
 * @param $email   the email address to notify of case changes.
 *
 * @return True if subscribing succeeded.
 */
function db_subscribe(int $case_id, string $email): bool
{
    global $support_email;
    $params = [$case_id, $email];
    $mysqli = null;
    try {
        $mysqli = connect_db();
    } catch (Excpetion $x) {
        throw new Exception(
            'Failed to connect to database.',
            0,
            $x
        );
    }
    if (is_null($mysqli)) {
        return false;
    }
    $mysqli->query("set @success = 0");
    $sql = 'call sp_subscribe(?, ?, @success)';
    $ps = $mysqli->prepare($sql);
    $ps->bind_param('is', ...$params);
    try {
        $ps->execute();
        $result = $mysqli->query('select @success as `is_successful`;');
        $result = $result->fetch_all(MYSQLI_ASSOC);
        return ('1' == $result[0]['is_successful']);
    } catch (mysqli_sql_exception $e)  {
        error_log(
            'Failed to subscribe user with email '
            . addslashes($email)
            . ' to case '
            . addslashes($case_id)
            . ', because: '
            . addslashes($e->getMessage()),
            1,
            $support_email  
        );
        return false;
    }
    return false;
}

/**
 * Reads the enumerations from the database. They are stored as separate 
 * values for each attribute, with a language code.
 *
 * @param $language_code should be a 2-letter ISO language code that 
 *                       determines which translation to retrieve. For 
 *                       instance: 'en'.
 *
 * @return a list of tuples, each of which has the following fields:
 * - attribute_id: identifies the attribute to enumerate,
 * - enum_value: an enumerated value for the attribute, which gets stored
 *               in the database.
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
function db_read_enumerations(string $language_code): array
{
    global $support_email;
    $sql = "select `attribute_id`, 
            `enum_value`, 
            `caption` 
            from `enums` 
            where `language_code` = ? 
            order by `attribute_id`, 
            `caption`";
    try {
        return query($sql, 's', [$language_code]);
    } catch (Exception $x) {
        error_log(
            'Failed to read field enumerations from the database, '
            . 'for language code '
            . addslashes($language_code)
            . ', because: '
            . addslashes($x->getMessage()),
            1,
            $support_email  
        );
    }
    return [];
}

/**
 * Reads the database fields and their attributes, so they can be used in 
 * the strtr() function to render HTML form fields.
 *
 * @return a list of tuples. Every field is a tuple, with the field 
 * attributes being the tuple elements. The list is sorted by display 
 * sequence. Like so:
 * [
 *     ['id' => 'aliases', 'data_type' => 'shorttext'],
 *     ['id' => 'birth year', 'data_type' => 'integer']
 * ]
 *
 * Note: you will see this and wonder why we didn't use a view, instead.
 * Funny thing: PHP's MySQLi doesn't like it when we use views in a prepared
 * statement. It throws an SQL exception pretending it needs to re-prepare
 * the statement. Avoiding views seems to fix it.
 */
function db_read_form_entry_fields(string $form_id): array
{
    global $support_email;
    $sql = 'SELECT `id`, `data_type`, `caption`, `hint`, `min`, 
            `max`, `default`, `is_write_once` FROM (
                select `f`.`form` AS `form`,
                `t`.`language_code` AS `language_code`,
                `f`.`display_sequence` AS `display_sequence`,
                `a`.`id` AS `id`,
                `a`.`data_type` AS `data_type`,
                `t`.`translation` AS `caption`,
                `t`.`hint` AS `hint`,
                `a`.`min` AS `min`,
                `a`.`max` AS `max`,
                `a`.`is_write_once` AS `is_write_once`,
                `a`.`default` AS `default` 
                from (
                    (
                        `attributes` `a` 
                        join `form_attributes` `f` 
                        on((`f`.`attribute` = `a`.`id`))
                    ) 
                    join `attribute_translations` `t` 
                    on((`t`.`attribute_id` = `a`.`id`))
                ) where (`f`.`hide_on_entry` = 0)
            ) as `entry_form_attributes` 
            where `form` = ? 
            and `language_code` = ? 
            order by `display_sequence` asc'; 
    try {
        return query($sql, 'ss', [$form_id, 'en']);
    } catch (Exception $x) {
        error_log(
            'Failed to read form fields from the database, '
            . 'for form '
            . addslashes($form_id)
            . ', because: '
            . addslashes($x->getMessage()),
            1,
            $support_email  
        );
    }
    return [];
}

/**
 * The current version of PHP, 7.4, returns a hash of 128 chars for sha512.
 * Sha512 is SHA-2 with 512 bit strength. The hash function encodes it in
 * hexadecimal, lower-cased.
 * 
 * @param $candidate should be the data to hash.
 * 
 * @return the calculated hash of the passed-in data.
 */
function db_hash(string $candidate): string
{
    return hash(
        'sha512', 
        $candidate
    );
}

/**
 * Whether a hashed email address is known for an existing user of the
 * system. This is useful because the authenticated user token from the 
 * session_utils module can be passed into this function, to determine 
 * whether they still are known. They could have been deleted, for instance,
 * in the meantime between authenticating and taking some other action.)
 * 
 * @param $email_hash the hash of the email address to check for 
 *                    existence. Use the db_hash function in this module
 *                    to hash the email address.
 * 
 * @return True if the user is recognized by the passed-in email hash.
 */
function db_is_email_hash_known(?string $email_hash): bool
{
    if (empty($email_hash)) {
        return false;
    }
    $sql = 'select 
        (count(*) > 0) as `is_known` 
        from `users` 
        where `email_hash` = \'' 
        . $email_hash
        . '\'';
    return ('1' == scalar($sql));
}

/**
 * Whether an email address is known for an existing user of the system.
 * 
 * @param $email the email address to check for existence.
 * 
 * @return True if the user is recognized by the passed-in email address.
 */
function db_is_email_known(?string $email): bool
{
    if (empty($email)) {
        return false;
    }
    $email_hashed = db_hash($email);
    return db_is_email_hash_known($email_hashed);
}

/**
 * Retrieves from the users table for the passed-in email address, the 
 * hashing algorithm and version used to hash the key and secret used to 
 * authenticate the user who owns that email address.
 * 
 * Note: at the time of storing this information, the hashing algorithm
 * of PHP 7.4 is assumed. Its hash function encodes the hash as lower-
 * cased hexadecimal.
 * 
 * @param $email should be the email hoped to have been registered
 *
 * @return An empty array in case no user was found with that email 
 * address, or an array for that user, containing these fields:
 * - hashing_algo: the name of the algorithm used to hash tokens,
 * - hashing_version: the version of the hashing algorithm.
 */
function get_hashing_algo_for_user_by_email(?string $email): ?array
{
    if (empty($email)) {
        return [];
    }
    $email_hash = db_hash($email);
    $sql = 'select `hashing_algo`, `hashing_version` 
        from `users` where `email_hash` = ?';
    $result = query($sql, 's', [$email_hash]);
    if (count($result) > 0) {
        return $result[0];
    }
    return [];
}

/**
 * Whether or not the provided credentials match a known user. We hash the 
 * provided information using the same hashing algorithm as used by the 
 * system when it stored the user's credentials last time.
 * 
 * @return an integer that indicates whether the user is known, or whether 
 * anything went wrong while checking for that.
 * Known values:
 * 1:  The user is known and the passed-in key and secret match.
 * -1: One or more of the passed-in variables was empty, meaning the check 
 *     was aborted prematurely.
 * -2: No hashing algorithm was found for the user by email, effectively 
 *     meaning the email does tie to a user.
 * -3: The email does tie to a user but it does not contain a hashing 
 *     algorithm or version. That means something went wrong during entry of
 *     the user into the system.
 * -4: The email does tie to a user, and it has a hashing algorithm and a 
 *     version, but its hashed user key and secret do not match the hashes 
 *     created off the passed-in key and secret. In essence, this means: 
 *     wrong user name and password.
 * -5: Something went wrong and we don't know what.
 */
function db_is_user_known(
    ?string $email, 
    ?string $key, 
    ?string $secret
): int {
    if (empty($email)
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
    $email_hash = db_hash($email);
    $key_hash = hash($hashing_algo, $key);
    $secret_hash = hash($hashing_algo, $secret);
    $result = query(
        'call sp_is_user_known(?, ?, ?)', 
        'sss', 
        [
            $email_hash, 
            $key_hash, 
            $secret_hash
        ]
    );
    if (empty($result) || empty($result[0])) {
        return -4;
    }
    /* Strict type comparison fails.
     * TODO: figure out why.
     */
    $is_known = $result[0]['is_known'];
    if (1 == $is_known) {
        return 1;
    }
    if (0 == $is_known) {
        return 0;
    }
    return -5;
}

/**
 * Logs the specified event for the authenticated user.
 * If the user did not authenticate, this function will abort.
 * 
 * @param $name should name the event to log.
 * 
 * @return True if authentication succeeded.
 */
function db_log_user_event(string $name): bool
{
    include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
    if (!session_did_user_authenticate()) {
        return false;
    }
    $authenticated_email_hash = session_recall_user_token();
    $values = [$authenticated_email_hash, $name];
    db_exec(
        'call sp_log_user_event_by_value(?, ?)',
        'ss',
        $values
    );
    return true;
}

/**
 * Determines which of the passed-in privileges is assigned to the user.
 * Privileges are stored and assigned to users in the Umpire database.
 * This function passes the question on to a database-specific function.
 * 
 * @param $user_token    identifies the currently authenticated user,
 *                       based on their user session.
 * @param ...$privileges lists (by name) the things hoped to be allowed
 *                       for he passed-in user.
 * 
 * @return Hopefully an array of only those privileges from the ones 
 * passed in, that are assigned to the user.
 */
function db_which_of_these_privileges_does_user_hold(
    ?string $user_token,
    ?string ...$privileges
): ?array {
    if (empty($user_token) || empty($privileges)) {
        return false;
    }
    $mysqli = connect_db();
    $mysqli->query("set @result = 0");
    $sql = 'call sp_which_of_these_privileges_does_user_hold(?,?,@result)';
    $ps = $mysqli->prepare($sql);
    $x = implode(',', $privileges);
    $params = [$user_token, $x];
    $ps->bind_param('ss', ...$params);
    $ps->execute();
    $result = $mysqli->query('select @result as `found`;');
    $result = $result->fetch_all(MYSQLI_ASSOC);
    $output = [];
    for ($i = 0; $i < count($result); $i+=1) {
        $output[] = $result[$i]['found'];
    }
    return $output;
}

function db_is_user_admin(
    ?string $session_user_token
): bool {
    if (empty($session_user_token)) {
        return false;
    }
    $mysqli = connect_db();
    $mysqli->query("set @result = 0");
    $sql = 'call sp_is_user_admin(?, @result)';
    $ps = $mysqli->prepare($sql);
    $params = [$session_user_token];
    $ps->bind_param('s', ...$params);
    $ps->execute();
    $result = $mysqli->query('select @result as `is_user_admin`;');
    $result = $result->fetch_all(MYSQLI_ASSOC);
    return ('1' == $result[0]['is_user_admin']);
}

function db_may_authenticated_user_accept_access(
    ?string $session_user_token
): bool {
    return db_is_user_admin($session_user_token);
}

function db_may_authenticated_user_reject_access(
    ?string $session_user_token
): bool {
    return db_is_user_admin($session_user_token);
}

/**
 * Allows further access to a user, identified by the passed-in email
 * address. The function will check whether the current user has the 
 * privilege to accept an access application.
 * 
 * @param $current_user_hash should be the hashed email address of the
 *                           user who performs the acceptance. Usually an
 *                           administrator. Use db_hash() to hash it.
 * @param $accept_email      should be the email address to be accepted.
 *                           This will be hashed by db_hash() before it
 *                           gets sent to the DB.
 * 
 * @return True if the acceptance was successful.
 */
function db_accept_access(
    ?string $current_user_hash, 
    ?string $accept_email
): bool {
    if (!db_may_authenticated_user_accept_access($current_user_hash)) {
        return false;
    }
    $accept_email_hash = db_hash($accept_email);
    $params = [$current_user_hash, $accept_email_hash];
    $mysqli = connect_db();
    $mysqli->query("set @success = 0");
    $sql = 'call sp_accept_access_application(?, ?, @success)';
    $ps = $mysqli->prepare($sql);
    $ps->bind_param('ss', ...$params);
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);
    try {
        $ps->execute();
        $result = $mysqli->query('select @success as `is_successful`;');
        $result = $result->fetch_all(MYSQLI_ASSOC);
        return ('1' == $result[0]['is_successful']);
    } catch (mysqli_sql_exception $e)  {
        return false;
    }
}

/**
 * Deny further access to a user, identified by the passed-in email
 * address. The function will check whether the current user has the 
 * privilege to reject an access application.
 * 
 * @param $current_user_hash should be the hashed email address of the
 *                           user who performs the rejection. Usually an
 *                           administrator. Use db_hash() to hash it.
 * @param $reject_email      should be the email address to be rejected.
 *                           This will be hashed by db_hash() before it
 *                           gets sent to the DB.
 * 
 * @return True if the rejection was successful.
 */
function db_reject_access(
    ?string $current_user_hash,
    ?string $reject_email
): bool {
    if (!db_may_authenticated_user_reject_access($current_user_hash)) {
        return false;
    }
    $reject_email_hash = db_hash($reject_email);
    $params = [$current_user_hash, $reject_email_hash];
    $mysqli = connect_db();
    $mysqli->query("set @success = 0");
    $sql = 'call sp_reject_access_application(?, ?, @success)';
    $ps = $mysqli->prepare($sql);
    $ps->bind_param('ss', ...$params);
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);
    try {
        $ps->execute();
        $result = $mysqli->query('select @success as `is_successful`;');
        $result = $result->fetch_all(MYSQLI_ASSOC);
        return ('1' == $result[0]['is_successful']);
    } catch (mysqli_sql_exception $e) {
        return false;
    }
}

function db_make_user_key(): string
{
    return bin2hex(
        random_bytes(64)
    );
}

function db_make_user_secret(): string
{
    $word_list = [
        'horse',
        'green',
        'sombrero',
        'falsetto',
        'ivy',
        'farms',
        'pizza',
        'dulcimer',
        'hashbrown',
        'return',
        'thankful',
        'venerate',
        'treaty',
        'madness',
        'slicing',
        'majestic',
        'fibrous',
        'pineapple',
        'exterior',
        'individual',
        'vertices',
        'properly',
        'ringtoss',
        'cookie cutter',
        'vigorous',
        'edge',
        'corkscrew',
        'friends',
        'electricity',
        'overconfidence',
        'polarizing',
        'shameful',
        'complete',
        'sandwich',
        'tomato',
        'lettuce',
        'flimsy',
        'dainty',
        'perpendicular',
        'guacamole',
        'flavor',
        'molecule',
        'tear',
        'choice',
        'beefsteak',
        'realize',
        'artistic',
        'cooking',
        'lampshade',
        'jokingly',
        'pinboard',
        'astrophysics'
    ];
    $result = array();
    foreach (
        array_rand(
            $word_list, 
            7
        ) as $k 
    ) {
        $result[] = $word_list[$k];
    }
    return implode(
        ' ',
        $result
    );
}

/** 
 * Creates a user in the database, and returns their authentication
 * credentials as a pair of key and secret. Those get stored in the DB
 * as well, hashed with a unique salt for every individual.
 *
 * @param $hashed_candidate should be the hash of the candidate's email 
 *                          address. Use the db_hash() function to hash.
 * 
 * @return A tuple of the user's new key and secrect (in that order), if
 *         all goes well. May be empty. Otherwise, null.
 */
function db_add_user(string $hashed_candidate): ?array
{
    $key = db_make_user_key();
    $secret = db_make_user_secret();
    $hashing_algo = 'sha512';
    $hashing_version = 1;
    $key_hash = hash(
        $hashing_algo,
        $key
    );
    $secret_hash = hash(
        $hashing_algo,
        $secret
    );
    $sql = 'insert into `users` (
        `email_hash`, 
        `access_requested_on`, 
        `key_hash`, 
        `secret_hash`, 
        `hashing_algo`, 
        `hashing_version`, 
        `last_hashed_date`
    ) values ( 
        ?, now(), ?, ?, ?, ?, now()
    );';
    $mysqli = connect_db();
    $ps = $mysqli->prepare($sql);
    /* automatically bind all parameters */
    $ps->bind_param(
        'ssssi', 
        $hashed_candidate,
        $key_hash,
        $secret_hash,
        $hashing_algo,
        $hashing_version
    );
    $ps->execute();
    $seq = $mysqli->insert_id;
    if ($seq) {
        return [$key, $secret];
    }
    return null;
}

/**
 * Retrieves where to go next after a successful form entry.
 * 
 * @param $form_id should identify the form for which to retrieve
 *                 where to go next.
 * 
 * @return a URL. May be null.
 */
function db_get_next_after_form_entry_success(string $form_id): ?string
{
    if (!$form_id) { 
        return null; 
    }
    $sql = 'select `url_after_entry` from `forms` where `id` = ?';
    $result = query($sql, 's', [$form_id]);
    if (count($result) > 0) {
        return $result[0]['url_after_entry'];
    }
    return null;
}

/**
 * Creates a reset key in the user database, ties it to the user, and 
 * returns it. Use this when the user lost their authentication keys and 
 * needs a reset. This reset key must be made available to the user. It
 * has a short life: the user must respond within 30 minutes. If they
 * manage to do so, they authenticated successfully and could, for
 * instance, be allowed to reset their authentication credentials.
 * 
 * @param $user_email should identify the user by their email address.
 *                    It will be hashed before getting sent to the DB.
 */
function db_make_authentication_reset_key(string $user_email): ?string
{
    if (empty($user_email)) {
        return null;
    }
    $email_hash = db_hash($user_email);
    $mysqli = connect_db();
    $mysqli->query("set @reset_key = ''");
    $sql = 'call sp_make_user_auth_reset_key(?, @reset_key)';
    $ps = $mysqli->prepare($sql);
    $params = [$email_hash];
    $ps->bind_param('s', ...$params);
    $ps->execute();
    $result = $mysqli->query('select @reset_key as `reset_key`;');
    $result = $result->fetch_all(MYSQLI_ASSOC);
    if (isset($result[0]) && isset($result[0]['reset_key'])) {
        return $result[0]['reset_key'];
    }
    return null;
}

/**
 * Determines whether or not the user may reset their authentication
 * credentials. Usual restrictions include time and frequency of 
 * previous resets.
 */
function db_may_user_reset_authentication(string $user_email): bool
{
    if (empty($user_email)) {
        return null;
    }
    $email_hash = db_hash($user_email);
    $sql = 'call sp_may_user_reset_own_authentication(?);';
    $result = query($sql, 's', [$email_hash]);
    return (
        isset($result[0]) 
        && isset($result[0]['is_allowed'])
        && true == $result[0]['is_allowed']
    );
}

/**
 * Resets the access key and secret for a user, if the provided email
 * and reset key match one known by the database and not yet expired,
 * and various other security mechanisms handled in the database.
 * Usual restrictions include time and frequency of previous resets.
 * 
 * @param $email     should identify the user who is attempting to reset
 *                   their authentication credentials.
 * @param $reset_key should specify a key sent to the user's email, 
 *                   and stored for that user in the database.
 * 
 * @return A tuple of the user's new key and secrect (in that order), if
 *         all goes well. May be empty. Otherwise, null.
 */
function db_reset_auth_key_for_user_if_valid($email, $reset_key): ?array
{
    if (empty($email)) {
        return null;
    }
    $access_key = db_make_user_key();
    $access_secret = db_make_user_secret();
    $hashing_algo = 'sha512';
    $hashing_version = 1;
    $key_hash = hash(
        $hashing_algo,
        $access_key
    );
    $secret_hash = hash(
        $hashing_algo,
        $access_secret
    );
    $email_hash = db_hash($email);
    $sql = 'call sp_store_access_keys_if_allowed(?, ?, ?, ?);';
    $result = query(
        $sql, 
        'ssss', 
        [
            $email_hash,
            $reset_key,
            $key_hash,
            $secret_hash
        ]
    );
    echo var_dump($result);
    return [$access_key, $access_secret];

    if (isset($result[0]) 
        && isset($result[0]['got_stored'])
        && true == $result[0]['got_stored']
    ) {
        return [$access_key, $access_secret];
    }
    return null;
}

?>

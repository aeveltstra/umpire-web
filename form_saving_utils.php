<?php
/**
 * Stores a form entry. Fields are generated on
 * the fly based on the fields listed in the database.
 * @author A.E.Veltstra
 * @version 2.24.0301.2006
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);

/**
 * DB Utils contains functions to read from and store into
 * the database.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

/**
 * Session Utils contain functions to read from and store into
 * session variables, and creates related things like nonces.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

/**
 * Start with creating a new case id. This is needed later
 * to assign facts to all the dimensions.
 *
 * Returns: the case id, if creation of a new entry succeeded.
 */
function form_make_case_id(string $form_id): int {
    $sql = 'insert into `entries` (entry_id, form) values (NULL, ?);';
    /* We cannot use a function from db_utils because none
     * that exist return the automatically incremented row id.
     */
    $mysqli = connect_db();
    $ps = $mysqli->prepare($sql);
    $params = [$form_id];
    $ps->bind_param('s', ...$params);
    $ps->execute();
    $seq = $mysqli->insert_id;
    $ps->reset();
    return $seq;
}

/** TODO: rework this so it can be enveloped in a transaction, which will
 * avoid creating orphaned case records. */
function form_store_email(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if ($fact) {
        $new_value = strval($fact);
    }
    $sql = 'call sp_store_email(?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $new_value,
        $user_token
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_image(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if ($fact) {
        $new_value = strval($fact);
    }
    $sql = 'call sp_store_image(?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $new_value,
        $user_token
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_integer(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if ($fact) {
        $new_value = intval($fact);
    }
    $sql = 'call sp_store_integer(?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $new_value,
        $user_token
    ];
    db_exec($sql,
        'isis',
        $input
    );
    return true;
}
function form_store_longtext(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $sql = 'call sp_store_longtext(?,?,?,?)';
    $new_value = null;
    if ($fact) {
        $new_value = strval($fact);
    }
    $input = [
        $case_id,
        $dimension_id,
        $new_value,
        $user_token
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_shorttext(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $sql = 'call sp_store_shorttext(?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $fact,
        $user_token
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_date(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $new_year = null;
    $new_month = null;
    $new_day = null;
    if($fact) {
        $epoch = strtotime($fact);
        $new_year = date('Y', $epoch);
        $new_month = date('m', $epoch);
        $new_day = date('d', $epoch);
    }
    $sql = 'call sp_store_date(?,?,?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $new_year,
        $new_month,
        $new_day,
        $user_token
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_time(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if($fact) {
        $new_value = date('g:i a', strtotime($fact));
    }
    $sql = 'call sp_store_time(?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $new_value,
        $user_token
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_enumerated(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $sql = 'call sp_store_enumerated(?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $fact,
        $user_token
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_percent(
    string $dimension_id,
    ?string $fact,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if ($fact) {
        $new_value = intval($fact);
    }
    $sql = 'call sp_store_integer(?,?,?,?)';
    $input = [
        $case_id,
        $dimension_id,
        $new_value,
        $user_token
    ];
    db_exec($sql,
        'isis',
        $input
    );
    return true;
}

/**
 * Now we have the case id, we can start assigning the dimension facts. We
 * match a form input to a dimension, by id. The DB stores each data type in a
 * separate table, identifying the value for a case by case id.
 */
function form_store(
    string $form_id,
    int $case_id,
    string $user_token,
    string $dimension_id,
    string $data_type,
    $fact
): bool {
    $success = false;
    switch ($data_type) {
        case 'email':
            $success = form_store_email(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'image':
            $success = form_store_image(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'integer':
            $success = form_store_integer(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'longtext':
            $success = form_store_longtext(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'shorttext':
            $success = form_store_shorttext(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'date':
            $success = form_store_date(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'time':
            $success = form_store_time(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'enum':
            $success = form_store_enumerated(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
        case 'percent':
            $success = form_store_percent(
                $dimension_id,
                $fact,
                $case_id,
                $user_token
            );
            break;
    }
    return $success;
}

/**
 * Makes it so the user can view their own case entry, and case 
 * managers can manage it.
 */
function form_assign_first_case_users(string $case_id, string $user_token) {
    $sql = 'call sp_assign_first_case_users(?,?)';
    $input = [
        $case_id,
        $user_token
    ];
    db_exec($sql,
        'ss',
        $input
    );
    return true;
}

/**
 * Saves new facts for the passed-in form.
 * @param form_id: identity of the form. Supply an identity known in 
 *        the database. 
 * @param expected_dimensions: an array of dimension names read from the 
 *        database for the form identified by form_id.
 * @param posted_facts: the value of PHP's $_POST. We hope that the form
 *        it generated, was created with the same expected fields, but
 *        we aren't going to take that for granted.
 * @return a tuple containing the new entry's case id, and a list of 
 *        facts that failed to get stored, if any. Each failure is a tuple 
 *        containing: new case id, dimension id, and fact. It does not 
 *        provide an error message, in order to hide implementation details
 *        about the database.
 */
function form_enter_new(string $form_id, $expected_dimensions, $posted_facts) {
    /**
     * Yes, creating a new case id here will lead to orphans. We will have to
     * remove those, later. A way to approach this with less orphan creation,
     * is to envelope the entire database entry in a transaction. And that would
     * work specifically for this script, but it won't for the scripts that
     * store individual field changes using asynchronous javascript calls.
     */
    $fails = [];
    $new_case_id = form_make_case_id($form_id);
    session_remember('new_case_id', strval($new_case_id));
    $user_token = session_recall_user_token();
    db_log_user_event('registered_form_entry');

    form_assign_first_case_users($form_id, $user_token);

    foreach($expected_dimensions as list(
        'id' => $dimension_id,
        'data_type' => $data_type
    )) {
        if (isset($posted_facts[$dimension_id])) {
            $fact = $posted_facts[$dimension_id];
            $success = form_store(
                $form_id,
                $new_case_id,
                $user_token,
                $dimension_id,
                $data_type,
                $fact
            );
            if (!$success) {
                $fails[] = [
                    "case" => $new_case_id,
                    "dimension" => $dimension_id,
                    "fact" => $fact
                ];
            }
        }
    }
    return array('new_case_id'=>$new_case_id, 'fails'=>$fails);
}

?>
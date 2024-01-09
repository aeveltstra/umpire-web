<?php
/**
 * Stores a form entry. Fields are generated on
 * the fly based on the fields listed in the database.
 * @author A.E.Veltstra
 * @version 2.23.1214.2227
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
 * to assign all the profile fields.
 *
 * Returns: the case id, if creation of a new profile succeeded.
 */
function form_make_case_id(string $form_id): int {
    $sql = 'insert into `profiles` (case_id, form) values (NULL, ?);';
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
function form_store_integer(
    string $field_name,
    ?string $field_value,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if ($field_value) {
        $new_value = intval($field_value);
    }
    $sql = 'call sp_store_integer(?,?,?,?)';
    $input = [
        $case_id,
        $field_name,
        $new_value,
        session_recall_user_token()
    ];
    db_exec($sql,
        'isis',
        $input
    );
    return true;
}
function form_store_longtext(
    string $field_name,
    ?string $field_value,
    int $case_id,
    string $user_token
): bool {
    $sql = 'call sp_store_longtext(?,?,?,?)';
    $input = [
        $case_id,
        $field_name,
        $field_value,
        session_recall_user_token()
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_shorttext(
    string $field_name,
    ?string $field_value,
    int $case_id,
    string $user_token
): bool {
    $sql = 'call sp_store_shorttext(?,?,?,?)';
    $input = [
        $case_id,
        $field_name,
        $field_value,
        session_recall_user_token()
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_date(
    string $field_name,
    ?string $field_value,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if($field_value) {
        $new_value = date('Y-m-d', strtotime($field_value));
    }
    $sql = 'call sp_store_date(?,?,?,?)';
    $input = [
        $case_id,
        $field_name,
        $new_value,
        session_recall_user_token()
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_time(
    string $field_name,
    ?string $field_value,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if($field_value) {
        $new_value = date('g:i a', strtotime($field_value));
    }
    $sql = 'call sp_store_time(?,?,?,?)';
    $input = [
        $case_id,
        $field_name,
        $new_value,
        session_recall_user_token()
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_enumerated(
    string $field_name,
    ?string $field_value,
    int $case_id,
    string $user_token
): bool {
    $sql = 'call sp_store_enumerated(?,?,?,?)';
    $input = [
        $case_id,
        $field_name,
        $field_value,
        session_recall_user_token()
    ];
    db_exec($sql,
        'isss',
        $input
    );
    return true;
}
function form_store_percent(
    string $field_name,
    ?string $field_value,
    int $case_id,
    string $user_token
): bool {
    $new_value = null;
    if ($field_value) {
        $new_value = intval($field_value);
    }
    $sql = 'call sp_store_integer(?,?,?,?)';
    $input = [
        $case_id,
        $field_name,
        $new_value,
        session_recall_user_token()
    ];
    db_exec($sql,
        'isis',
        $input
    );
    return true;
}

/**
 * Now we have the case id, we can start assigning the field values. We
 * match a form input to a field, by name. The DB stores each data type in a
 * separate table, identifying the value for a case by case id.
 */
function form_store(
        string $form_id,
        int $case_id,
        string $user_token,
        string $field_name,
        string $data_type,
        $field_value
): bool {
    $success = false;
    switch ($data_type) {
        case 'integer':
            $success = form_store_integer(
                $field_name,
                $field_value,
                $case_id,
                $user_token
            );
            break;
        case 'longtext':
            $success = form_store_longtext(
                $field_name,
                $field_value,
                $case_id,
                $user_token
            );
            break;
        case 'shorttext':
            $success = form_store_shorttext(
                $field_name,
                $field_value,
                $case_id,
                $user_token
            );
            break;
        case 'date':
            $success = form_store_date(
                $field_name,
                $field_value,
                $case_id,
                $user_token
            );
            break;
        case 'time':
            $success = form_store_time(
                $field_name,
                $field_value,
                $case_id,
                $user_token
            );
            break;
        case 'enum':
            $success = form_store_enumerated(
                $field_name,
                $field_value,
                $case_id,
                $user_token
            );
            break;
        case 'percent':
            $success = form_store_percent(
                $field_name,
                $field_value,
                $case_id,
                $user_token
            );
            break;
    }
    return $success;
}

function form_enter_new(string $form_id, $expected_fields, $posted) {
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

    foreach($expected_fields as list(
        'id' => $field_id,
        'data_type' => $data_type
    )) {
        if (isset($_POST[$field_id])) {
            $value = $_POST[$field_id];
            $success = form_store(
                $form_id,
                $new_case_id,
                $user_token,
                $field_id,
                $data_type,
                $value
            );
            if (!$success) {
                $fails[] = [
                    "case" => $new_case_id,
                    "field" => $field_id,
                    "value" => $value
                ];
            }
        }
    }
    return array('new_case_id'=>$new_case_id, 'fails'=>$fails);
}

?>
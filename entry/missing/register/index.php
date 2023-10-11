<?php
declare(strict_types=1);
/**
 * Stores the missing person's case entry. Fields are generated on 
 * the fly based on the fields listed in the database.
 * @author A.E.Veltstra
 * @version 2.23.1010.2207
 */
error_reporting(E_ALL);

/* If this process got invoked by any method other than HTTP POST,
 * processing needs to halt and the user needs to be redirected.
 * The process requires to be invoked using HTTP POST.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    die();
}

/**
 * db_utils contains functions to read from and store into 
 * the database.
 */
require_once $SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

/**
 * Start with creating a new case id. This is needed later 
 * to assign all the profile fields. 
 * 
 * Returns: the case id, if creation of a new profile succeeded.
 */
function make_case_id() {
    $sql = 'insert into `profiles` (case_id) values (NULL);';
    /* We cannot use a function from db_utils because none
     * that exist return the automatically incremented row id.
     */
    $mysqli = connect_db();
    $ps = $mysqli->prepare($sql);
    $ps->execute();
    $seq = $mysqli->insert_id;
    return $seq;
}

/**
 * Now we have the case id, we can start assigning the field
 * values. We match a form input to a field, by name. The DB
 * stores each data type in a separate table, identifying the
 * value for a case by case id.
 */
function store(string $field_name, string $field_value, string $case_id): bool {
    $data_type = query('SELECT `data_type` FROM 
    `vw_missing_entry_form_attributes_en` where id = ?',
    's', [$field_name])[0]['data_type'];
    if (empty($data_type)) {
        /* well, obviously that didn't work. */
        return false;
    }
    match ($data_type) {
        'integer' => store_integer($field_name, $field_value, $case_id),
        'longtext' => store_longtext$field_name, $field_value, $case_id),
        'shorttext' => store_shorttext($field_name, $field_value, $case_id),
        'date' => store_date($field_name, $field_value, $case_id),
        'time' => store_time($field_name, $field_value, $case_id),
        'enum' => store_enumeration($field_name, $field_value, $case_id),
    }
}


header('Location: ./success/');
die;

?>

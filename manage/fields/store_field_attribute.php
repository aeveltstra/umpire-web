<?php
/**
 * Save the properties for an Umpire form field.
 * 
 * PHP Version 7.3
 *
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.930.2028
 */
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

if (!session_did_user_authenticate()) {
    echo '{
        "success": false,
        "errors": [
            "Access denied: you need to log in first."
        ]
    }';
    die();
}

$current_user = session_recall_user_token();
$held_privileges = db_which_of_these_privileges_does_user_hold(
    $current_user,
    'may_manage_forms'
);
if (empty($held_privileges)) {
    echo '{
        "success": false,
        "errors": [
            "Access denied: you are not allowed to manage forms."
        ]
    }';
    die();
}
$session_form_nonce = session_recall_nonce('manage_entry_form_field');
$posted_form_nonce = '';
if (isset($_POST['nonce'])) {
    $posted_form_nonce = $_POST['nonce'];
}

$field_choice = '';
if (isset($_POST['field_id'])) {
    $field_choice = $_POST['field_id'];
}

if (!$posted_form_nonce 
    || (!$session_form_nonce==$posted_form_nonce) 
    || !session_is_nonce_valid('manage_entry_form_field')
) {
    echo '{
        "success": false,
        "errors": [
            "Failed to validate field. Please reload."
        ]
    }';
    die();
}

$old_value_from_post = '';
if (isset($_POST['old_value'])) {
    $old_value_from_post = $_POST['old_value'];
}

$new_value_from_post = '';
if (isset($_POST['new_value'])) {
    $new_value_from_post = $_POST['new_value'];
}

$property_from_post = '';
if (isset($_POST['property'])) {
    $property_from_post = $_POST['property'];
}

if (!empty($field_choice)) {
    $get_existing_attribute = query(
        'select 1 
            from `attributes` 
            where `id` = ?
        ',
        's',
        [
            $field_choice
        ]
    );
    $is_attrib_found = (count($get_existing_attribute) > 0);
    if ($is_attrib_found) {
        $sql = '';
        $dt = '';
        switch ($property_from_post) {
        case 'new_data_type':
            $sql = 'update `attributes` set `data_type` = ?
                    where (`data_type` = ? or `data_type` = \'\' or `data_type` is null)
                    and `id` = ?
                   ';
            $dt = 'sss'; 
            break;
        case 'new_min':
            $sql = 'update `attributes` set `min` = ? 
                    where `min` = ? and `id` = ?';
            $dt = 'iis';
            break;
        case 'new_max':
            $sql = 'update `attributes` set `max` = ? 
                    where `max` = ? and `id` = ?';
            $dt = 'iis';
            break;
        case 'new_default':
            $sql = 'update `attributes` set `default` = ? 
                    where (`default` = ? or `default` = \'\' or `default` is null)
                    and `id` = ?
                   ';
            $dt = 'sss';
            break;
        case 'new_is_write_once':
            $sql = 'update `attributes` set `is_write_once` = ? 
                    where `is_write_once` = ? and `id` = ?';
            $dt = 'iis';
            if (($new_value_from_post == 'on')
               || ($new_value_from_post == 1)
               || ($new_value_from_post == 'true')
            ) {
                $new_value_from_post = 1;
            } else {
                $new_value_from_post = 0;
            }
            if (($old_value_from_post == 'on')
               || ($old_value_from_post == 1)
               || ($old_value_from_post == 'true')
            ) {
                $old_value_from_post = 1;
            } else {
                $old_value_from_post = 0;
            }
            break;
        default:
        }
        if (empty($sql)) {
            echo '{
                "success": false,
                "errors": [
                    "Aborting: unknown attribute received."
                ]
            }';
        } else {
            try {
                $result = db_exec(
                    $sql,
                    $dt,
                    [
                        $new_value_from_post,
                        $old_value_from_post,
                        $field_choice
                    ]
                );
                echo '{
                    "success": true,
                    "updated": {
                        "field": "' . $field_choice . '",
                        "property": "' . $property_from_post . '", 
                        "new": "' . $new_value_from_post . '",
                        "old": "' . $old_value_from_post . '"
                    },
                    "errors": []
                }';
            } catch (mysqli_sql_exception $err) {
                $e2 = addslashes($err->getMessage());
                echo '{
                    "success": false,
                    "errors": [
                        "' . $e2 . '"
                    ]
                }';
            }
        }
    } else {
        echo '{
          "success": false,
          "errors": [
              "Field attribute not found.",
              "Return to the overview and load the field from there."
           ]
        }';
    }
}
?>

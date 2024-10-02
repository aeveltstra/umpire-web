<?php
/**
 * Save the usage hint Umpire shows when displaying and mentioning an entry form field.
 *
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.1001.1925
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

$old_hint_from_post = '';
if (isset($_POST['old_hint'])) {
    $old_hint_from_post = $_POST['old_hint'];
}

$new_hint_from_post = '';
if (isset($_POST['new_hint'])) {
    $new_hint_from_post = $_POST['new_hint'];
}

$translation_language = '';
if (isset($_POST['language'])) {
    $translation_language = $_POST['language'];
}

if (!empty($field_choice)) {
    $get_existing_record = query(
        'select `hint` 
            from `attribute_translations` 
            where `language_code` = ?
            and `attribute_id` = ?',
        'ss',
        [
            $translation_language,
            $field_choice
        ]
    );
    $is_existing_record_found = (count($get_existing_record) > 0);
    if ($is_existing_record_found) {
        $existing_record_has_old_value = isset(
            $get_existing_record[0]['hint']
        );
        if ($existing_record_has_old_value) {
            $old_value_from_record = $get_existing_record[0]['hint'];
            $old_values_match = (
                $old_value_from_record == $old_hint_from_post
            );
            if ($old_values_match) {
                try {
                    $result = db_exec(
                        'update `attribute_translations` 
                            set `hint` = ?
                            where `language_code` = ?
                            and `attribute_id` = ?
                        ',
                        'sss',
                        [
                            $new_hint_from_post,
                            $translation_language,
                            $field_choice
                        ]
                    );
                    echo '{
                      "success": true,
                      "update": {
                        "language": "'. $translation_language .'",
                        "field": "'. $field_choice .'",
                        "new": "'. $new_hint_from_post .'"
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
            } else {
                echo '{
                  "success": false,
                  "errors": [
                    "Match failed on old values.",
                    "Maybe someone else changed the translation hint already.",
                    "Reload the screen to see changes."
                   ]
                }';
            }
        } else {
            try {
                $result = db_exec(
                    'update `attribute_translations` 
                        set `hint` = ?
                        where `language_code` = ?
                        and `attribute_id` = ?
                        and (
                            `hint` is null
                            or `hint` = \'\'
                        )
                    ',
                    'sss',
                    [
                        $new_hint_from_post,
                        $translation_language,
                        $field_choice
                    ]
                );
                echo '{
                  "success": true,
                  "set": {
                    "language": "'. $translation_language .'",
                    "field": "'. $field_choice .'",
                    "new": "'. $new_hint_from_post .'"
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
        try {
            $result = db_exec(
                'insert into `attribute_translations` 
                    (`hint`, `language_code`, `attribute_id`)
                    values 
                    (?, ?, ?)
                ',
                'sss',
                [
                    $new_hint_from_post,
                    $translation_language,
                    $field_choice
                ]
            );
            echo '{
              "success": true,
              "set": {
                "language": "'. $translation_language .'",
                "field": "'. $field_choice .'",
                "new": "'. $new_hint_from_post .'"
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
}
?>

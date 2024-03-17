<?php
/**
 * Save the title Umpire shows when displaying and mentioning
 * an entry form.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.24.317.1410
 */
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: application/json');

include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

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
$session_form_nonce = session_recall_nonce('manage_entry_form');
$posted_form_nonce = '';
if (isset($_POST['nonce'])) {
    $posted_form_nonce = $_POST['nonce'];
}
$form_choice = '';
if (isset($_POST['form_id'])) {
    $form_choice = $_POST['form_id'];
}

$old_caption_from_post = '';
if (isset($_POST['old_caption'])) {
    $old_caption_from_post = $_POST['old_caption'];
}

$new_caption_from_post = '';
if (isset($_POST['new_caption'])) {
    $new_caption_from_post = $_POST['new_caption'];
}

$caption_language = '';
if (isset($_POST['language'])) {
    $caption_language = $_POST['language'];
}

if (!empty($form_choice)) {
    $get_existing_record = query(
        'select `caption` 
            from `form_caption_translations` 
            where `language` = ?
            and `form` = ?'
        ,
        'ss',
        [
            $caption_language,
            $form_choice
        ]
    );
    $is_existing_record_found = (count($get_existing_record) > 0);
    if ($is_existing_record_found) {
        $existing_record_has_old_value = isset(
            $get_existing_record[0][`caption`]
        );
        if ($existing_record_has_old_value) {
            $old_value_from_record = $get_existing_record[0][`caption`];
            $old_values_match = (
                $old_value_from_record == $old_caption_from_post
            );
            if ($old_values_match) {
                try {
                    $result = db_exec(
                        'update `form_caption_translations` 
                            set `caption` = ?
                            where `language` = ?
                            and `form` = ?
                            and `caption` = ?
                        ',
                        'ssss',
                        [
                            $new_caption_from_post,
                            $caption_language,
                            $form_choice,
                            $old_caption_from_post
                        ]
                    );
                    echo '{
                      "success": true,
                      "errors": []
                    }';
                } catch (mysqli_sql_exception $err) {
                    echo '{
                      "success": false,
                      "errors": [
                        "{$err}"
                       ]
                    }';
                }
            } else {
                echo '{
                  "success": false,
                  "errors": [
                    "Match failed on old values.",
                    "Maybe someone else changed the caption already.",
                    "Reload the screen to see changes."
                   ]
                }';
            }
        } else if (!empty($old_value_from_record)) {
            echo '{
              "success": false,
              "errors": [
                   "Match failed on old values.",
                   "Maybe someone else changed the caption already.",
                   "Reload the screen to see changes."
               ]
            }';
        } else {
            try {
                $result = db_exec(
                    'update `form_caption_translations` 
                        set `caption` = ?
                        where `language` = ?
                        and `form` = ?
                        and (
                            `caption` is null
                            or `caption` = \'\'
                        )
                    ',
                    'sss',
                    [
                        $new_caption_from_post,
                        $caption_language,
                        $form_choice
                    ]
                );
                echo '{
                  "success": true,
                  "errors": []
                }';
            } catch (mysqli_sql_exception $err) {
                $e2 = addslashes($err);
                echo '{
                  "success": false,
                  "errors": [
                    "{$e2}"
                   ]
                }';
            }
        }
    } else {
        echo '{
          "success": false,
          "errors": [
              "Form not found.",
              "Return to the overview and load the form from there."
           ]
        }';
    }
}
?>
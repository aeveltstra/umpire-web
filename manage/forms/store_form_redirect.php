<?php
/**
 * Save the web address to which the Umpire application has to redirect
 * the user after successful form submission.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.24.313.1906
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

$old_form_redirect_from_post = '';
if (isset($_POST['old_value'])) {
    $old_form_redirect_from_post = $_POST['old_value'];
}

$new_form_redirect_from_post = '';
if (isset($_POST['new_value'])) {
    $new_form_redirect_from_post = $_POST['new_value'];
}

if (!empty($form_choice)) {
    $get_form_existing_record = query(
        'select `id`, `url_after_entry` 
            from `forms` 
            where `id` = ?',
        's',
        [$form_choice]
    );
    $is_form_found = (count($get_form_existing_record) > 0);
    if ($is_form_found) {
        $is_form_known = true;
        $form_record_has_old_value = isset($get_form_existing_record[0][`url_after_entry`]);
        if ($form_record_has_old_value) {
            $old_value_from_record = $get_form_existing_record[0][`url_after_entry`];
            $old_values_match = ($old_value_from_record == $old_form_redirect_from_post);
            if ($old_values_match) {
                try {
                    $result = db_exec(
                        'update `forms` 
                            set `url_after_entry` = ?
                            where `id` = ?
                            and `url_after_entry` = ?
                        ',
                        'sss',
                        [
                            $new_form_redirect_from_post,
                            $form_choice,
                            $old_value_from_record
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
            } else {
                echo '{
                  "success": false,
                  "errors": [
                      "Match failed on old values. Maybe someone else changed the form already. Reload the screen to see changes."
                   ]
                }';
            }
        } else if (!empty($old_form_redirect_from_post)) {
            echo '{
              "success": false,
              "errors": [
                   "Match failed on old values. Maybe someone else changed the form already. Reload the screen to see changes."
               ]
            }';
        } else {
            try {
                $result = db_exec(
                    'update `forms` 
                        set `url_after_entry` = ?
                        where `id` = ?
                        and (
                            `url_after_entry` is null
                            or `url_after_entry` = \'\'
                        )
                    ',
                    'ss',
                    [
                        $new_form_redirect_from_post,
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
              "Form not found. Return to the overview and load the form from there."
           ]
        }';
    }
}
?>
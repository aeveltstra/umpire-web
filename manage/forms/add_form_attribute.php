<?php
/**
 * Add field properties to an Umpire form.
 * 
 * PHP Version 7.3
 *
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.803.1705
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
$session_form_nonce = session_recall_nonce('manage_entry_form');
$posted_form_nonce = '';
if (isset($_POST['nonce'])) {
    $posted_form_nonce = $_POST['nonce'];
}

$form_choice = '';
if (isset($_POST['form_id'])) {
    $form_choice = $_POST['form_id'];
}

if (!$posted_form_nonce 
    || (!$session_form_nonce==$posted_form_nonce) 
    || !session_is_nonce_valid('manage_entry_form')
) {
    echo '{
        "success": false,
        "errors": [
            "Failed to validate form. Please reload."
        ]
    }';
    die();
}

$attributes_from_post = [];
if (isset($_POST['attributes'])) {
    $attributes_from_post = explode(',', $_POST['attributes']);
}

$errors = [];
if (empty($form_choice)) {
    $errors[] = 'Failed to recognize the form to which to append attributes. Please reload.';
} else {
    foreach ($attributes_from_post as $attribute) {
      try {
        $result = db_exec(
          'insert into `form_attributes` 
           (`form`, `attribute`)
           values (?, ?)', 
          'ss',
          [
            $form_choice,
            $attribute
          ]
        );
      } catch (mysqli_sql_exception $err) {
	$e2 = addslashes($err->getMessage());
        $errors[] = $e2;
      }
    }
}
if ($errors) {
    echo '{
        "success": false,
        "errors": '.$errors.' 
    }';
} else {
    echo '{
        "success": true,
        "updated": {
            "form": "'.$form_choice.'"
        },
        "errors": []
    }';
}
?>

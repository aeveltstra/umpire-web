<?php
/**
 * Remove the field property for an Umpire form.
 * 
 * PHP Version 7.3
 *
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.803.1628
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

$attribute_from_post = '';
if (isset($_POST['attribute'])) {
    $attribute_from_post = $_POST['attribute'];
}

if (!empty($form_choice)) {
    try {
        $result = db_exec(
            'delete 
                from `form_attributes` 
                where `form` = ?
                and `attribute` = ?',
            'ss',
            [
                $form_choice,
                $attribute_from_post
            ]
        );
        echo '{
            "success": true,
            "updated": {
                "form": "' . $form_choice . '",
                "attribute": "' . $attribute_from_post . '" 
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
?>

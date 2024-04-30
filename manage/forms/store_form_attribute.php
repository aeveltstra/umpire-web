<?php
/**
 * Save the field properties for an Umpire form.
 * 
 * PHP Version 7.3
 *
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.413.1657
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

$old_form_value_from_post = '';
if (isset($_POST['old_value'])) {
    $old_form_value_from_post = $_POST['old_value'];
}

$new_form_value_from_post = '';
if (isset($_POST['new_value'])) {
    $new_form_value_from_post = $_POST['new_value'];
}

$attribute_from_post = '';
if (isset($_POST['attribute'])) {
    $attribute_from_post = $_POST['attribute'];
}

$property_from_post = '';
if (isset($_POST['property'])) {
    $property_from_post = $_POST['property'];
}

/**
 * Evaluates the passed in property name to determine
 * whether it is attributed, and if so, removes the
 * attribute ID, to return only the property name. This is 
 * needed because the HTML form that allows modification of 
 * attribute properties needs to add the attribute ID to 
 * each property, to make them unique. This reverses it.
 * 
 * @param $attributed_prop_name should be the name of the 
 *                              property that is modified by
 *                              the HTML form. 
 * @param $attrib_id            should be the identifier 
 *                              of the attribute that is 
 *                              getting its property 
 *                              modified.
 * 
 * @return the property name, hopefully.
 */
function extract_prop_name($attributed_prop_name, $attrib_id)
{
    if (empty($attributed_prop_name)
        || empty($attrib_id)
    ) {
        return '';
    } else {
        $search = '_' . $attrib_id;
        $replace = '';
        $subject = '' . $attributed_prop_name;
        $count = 1;
        return str_replace($search, $replace, $subject, $count); 
    }
}

if (!empty($form_choice)) {
    $get_existing_attribute = query(
        'select `attribute`
            from `form_attributes` 
            where `form` = ?
            and `attribute` = ?',
        'ss',
        [
            $form_choice,
            $attribute_from_post
        ]
    );
    $is_attrib_found = (count($get_existing_attribute) > 0);
    $attrib = $get_existing_attribute[0]['attribute'];
    if ($is_attrib_found) {
        $prop = extract_prop_name(
            $property_from_post,
            $attrib
        );
        $sql = '';
        $dt = '';
        $is_form_specific = false;
        switch ($prop) {
        case 'new_data_type':
            $sql = 'update `attributes` set `data_type` = ? 
                    where `data_type` = ? and `id` = ?';
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
                    where `default` = ? and `id` = ?';
            $dt = 'sss';
            break;
        case 'new_is_write_once':
            $sql = 'update `attributes` set `is_write_once` = ? 
                    where `is_write_once` = ? and `id` = ?';
            $dt = 'iis';
            break;
        case 'new_is_write_once':
            $sql = 'update `form_attributes`
                    set `is_write_once` = ?
                    where `is_write_once` = ?
                    and `attribute` = ?
                    and `form` = ?
                ';
            $dt = 'iiss';
            $is_form_specific = true;
            break;
        case 'new_hide_on_entry':
            $sql = 'update `form_attributes`
                    set `hide_on_entry` = ?
                    where `hide_on_entry` = ?
                    and `attribute` = ?
                    and `form` = ?
                ';
            $dt = 'iiss';
            $is_form_specific = true;
            break;
        default:
            $sql = '';
        }
        if (empty($sql) || empty($dt)) {
            echo '{
                "success": false,
                "errors": [
                    "Aborting: unknown attribute received."
                ]
            }';
        } else {
            try {
                if ($is_form_specific) {
                    $result = db_exec(
                        $sql,
                        $dt,
                        [
                            $new_form_value_from_post,
                            $old_form_value_from_post,
                            $attrib,
                            $form_choice
                        ]
                    );
                } else {
                    $result = db_exec(
                        $sql,
                        $dt,
                        [
                            $new_form_value_from_post,
                            $old_form_value_from_post,
                            $attrib
                        ]
                    );
                }
                echo '{
                    "success": true,
                    "updated": {
                        "form": "' . $form_choice . '",
                        "attribute": "' . $attrib . '", 
                        "property": "' . $prop . '", 
                        "new": "' . $new_form_value_from_post . '",
                        "old": "' . $old_form_value_from_post . '"
                    },
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
              "Form attribute not found.",
              "Return to the overview and load the form from there."
           ]
        }';
    }
}
?>
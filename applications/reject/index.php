<?php
/**
 * Deny access to a user who has requested it.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1014.1030
 */

/**
 * This script expects to receive input from querystring.
 * Invoke like this: reject/?id=value
 * in which value is the email address to reject.
 * If that doesn't exist, we throw an error immediately.
 */
if (empty($_GET['id'])) {
    header('Location: ./error-missing-id-query/');
    die;
}

/**
 * Session utilities automatically start and secure the user
 * session.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';

if (!is_user_authenticated()) {
    set_session_variable('reject_user_application_email', $_GET['id']);
    set_session_variable('return_to', '/umpire/applications/reject/');
    header('Location: ../../sign-in/');
    die;
}

/**
 * db_utils.php contains db functionality like query(sql)
 * and db_exec(sql, params_typestring, params).
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';


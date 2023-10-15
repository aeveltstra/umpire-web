<?php
/**
 * Helper functions for handling sessions.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1014.1430
 */

declare(strict_types=1);
ini_set('session.use-strict-mode', 'true');
session_start();

/** Use this if running PHP on a server shared on the same server. */
function get_session_app_name():string {
    $session_umpire_app_random_seed = 'bvd67iBVDRUJ04926494720IUYTFvdfg';
    $session_umpire_app_name = 'Umpire_' . $session_umpire_app_random_seed . '_';
    return $session_umpire_app_name;
}

/** 
 * Wraps around setting session variables to include the specific
 * application name, which reduces the chance other of PHP instances 
 * on the same iron server to write session variables of the same name.
 */
function set_session_variable(string $k, string $v):bool {
    $varname = get_session_app_name() . $k;
    $_SESSION[$varname] = $v;
    return true;
}

/**
 * Wraps around getting session variables to include the specific
 * application name, which reduces the chance of other PHP instances
 * on the same iron server to read session variables of the same name.
 */
function get_session_variable(string $k):string {
    $varname = get_session_app_name() . $k;
    if (isset($_SESSION[$varname])) {
        return $_SESSION[$varname];
    }
    return '';
}

/** Determine whether the user authenticated. */
function did_user_authenticate():bool {
  return (!empty(get_session_variable('user_did_authenticate')));
}

/** Hash any value using the same algorithm every time */
function make_session_hash(string $v):string {
    return hash(
        'sha512', 
        $v
    );
}

/** 
 * Retrieves the user token stored in this session.
 * The token gets assigned to the user when they 
 * authenticate successfully. If none exists, a random
 * value is set and returned, instead.
 */
function get_session_user_token():string {
    $t = get_session_variable('user_token');
    if (empty($t)) {
        $t = random_bytes(24);
        set_session_variable('user_token', $t);
    }
    return $t;
}

/** Create a nonce to determine that, for instance, a form submission 
 *  has been received from the correct form, and not from elsewhere.
 *  These tokens will be valid for a single user, a single identity,
 *  for the duration of 12 hours. We use 12 hours because some forms
 *  are expected to take a long time to fill out. Maybe we should 
 *  make that variable per form?
 *
 *  This has been modeled after wp_create_nonce().
 *
 *  Parameters:
 *  - id, string, required: custom identifier you use to determine
 *    which nonce to read / inspect.
 */
function make_session_nonce(string $id):string {
    $time = ceil( time() / ( 60 * 60 * 12) );
    $token = get_session_user_token();
    return make_session_hash($time . '|' . $token . '|' . $id);
}

/**
 * Stores a nonce in the session variable identified by id.
 */
function store_session_nonce(string $id, string $nonce):bool {
    return set_session_variable($id . '_nonce', $nonce);
}

/**
 * Create and save a nonce for a specific use into a session
 * variable identified by id. Use this for instance to determine that
 * a form submission is received from the expected form, and not
 * from elsewhere.
 */
function make_and_store_session_nonce(string $id):string {
    $nonce = make_session_nonce($id);
    if (store_session_nonce($id, $nonce)) {
        return $nonce;
    }
    return '';
}

/**
 * Retrieve the nonce identified by id, from the session storage.
 */
function get_session_nonce(string $id):string {
    return get_session_variable($id . '_nonce');
}

/**
 * Determine whether the nonce stored for the identity is valid.
 * It will be invalid if no nonce was found for that identity,
 * if one was found but doesn't match, or if one was found but
 * timed out.
 * 
 * This has been modeled after wp_verify_nonce().
 */
function is_session_nonce_valid(string $id):bool {
    if (empty($id)) {
        return false;
    }
    $nonce = get_session_nonce($id);
    if (empty($nonce)) {
        return false;
    }
    $expected = make_session_nonce($id);
    if (empty($expected)) {
        return false;
    }
    return hash_equals($expected, $nonce);
}

?>

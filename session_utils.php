<?php
ini_set('session.use-strict-mode', true);
session_start();

/** Use this if running PHP on a server shared on the same server. */
function get_session_app_name():string {
    $session_umpire_app_random_seed = 'bvd67iBVDRUJ04926494720IUYTFvdfg';
    $session_umpire_app_name = 'Umpire_' . $session_umpire_app_random_seed . '_';
    return $session_umpire_app_name;
}

/** Determine whether the user authenticated. */
function is_user_authenticated():bool {
  $varname = $get_session_app_name() . 'user_did_authenticate';
  if (empty($_SESSION($varname)) {
    return false;
  }
  return bool($_SESSION($varname));
}

/** 
 * Wraps around setting session variables to include the specific
 * application name, which reduces the chance other of PHP instances 
 * on the same iron server to write session variables of the same name.
 */
function set_session_variable(string $k, string $v):bool {
    $_SESSION[get_session_app_name() . $k] = $v;
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


?>

<?php
/**
 * Helper functions for handling sessions.
 * 
 * PHP Version 7.5.3
 * 
 * @category Administrative
 * @package  Umpire
 * @author   A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version  2.24.526.2203
 */

declare(strict_types=1);
ini_set('session.use-strict-mode', 'true');
session_start();

/**
 * Hash any value using the same algorithm every time. 
 * 
 * @param $v should be the data to hash
 * 
 * @return the hash of the passed-in data.
 */
function session_make_hash(string $v): string
{
    return hash(
        'sha512', 
        $v
    );
}

/**
 * Use this if running PHP on a server shared on the same server. 
 * 
 * @return a unique, salted/seeded identifier that sets apart our app
 * from others that run on the same server.
 */
function session_recall_app_name(): string
{
    $session_umpire_app_random_seed = 'bvd67iBVDRUJ04926494720IUYTFvdfg';
    $session_umpire_app_name = 'Umpire_' . $session_umpire_app_random_seed . '_';
    return $session_umpire_app_name;
}

/**
 * Wraps around destroying session variables to include the specific
 * application name, which reduces the chance other of PHP instances on 
 * the same iron server to delete session variables of the same name.
 * 
 * @param $k should name the key to forget.
 * 
 * @return True if forgotten successfully.
 */
function session_forget(?string $k): bool
{
    if (empty($k)) {
        return true;
    }
    $varname = session_recall_app_name() . $k;
    unset($_SESSION[$varname]);
    return true;
}

/** 
 * Wraps around setting session variables to include the specific
 * application name, which reduces the chance other of PHP instances on 
 * the same iron server to write session variables of the same name.
 * 
 * @param $k should name the key as which to remember the $v value.
 * @param $v should be the value to remember at the $k key.
 * 
 * @return True if remembered successfully.
 */
function session_remember(string $k, ?string $v): bool
{
    if (empty($v)) {
        return session_forget($k);
    }
    $varname = session_recall_app_name() . $k;
    $_SESSION[$varname] = $v;
    return true;
}

/**
 * Wraps around session variables to include the specific application 
 * name, which reduces the chance of other PHP instances on the same iron 
 * server to read session variables of the same name.
 * 
 * @param $k should name the key that identifies the information to pull 
 *           up from the session memory.
 * 
 * @return empty string if no key $k was found in the session memory.
 * May also be empty if the data found at key $k is empty. Otherwise,
 * the remembered value. Never null.
 */
function session_recall(string $k):string
{
    $varname = session_recall_app_name() . $k;
    if (isset($_SESSION[$varname])) {
        return $_SESSION[$varname];
    }
    return '';
}

/**
 * Generates a user token based on their email address.
 * 
 * @param $email should be the user's email address. Note the function
 *               will work if this isn't an email address, but we cannot
 *               guarantee that follow-up functions will work as expected.
 * 
 * @return the user token generated based on the passed-in $email address.
 */
function session_make_user_token(string $email): string
{
    return session_make_hash($email);
}

/** 
 * Retrieves the user token stored in this session.
 * The token gets assigned to the user when they authenticate successfully, 
 * and also when a form needs a nonce if the user is anonymous.
 */
function session_recall_user_token():string
{
    return session_recall('user_token');
}
/** 
 * Deletes the user token from this session.
 * The token gets assigned to the user when they authenticate successfully, 
 * and also when a form needs a nonce if the user is anonymous.
 */
function session_forget_user_token():bool
{
    return session_forget('user_token');
}

/**
 * Store the fact that the current user authenticated by supplying their 
 * token. If their token is not supplied, or if it is empty, the session 
 * variable will reflect that the user did not authenticate.
 * Check whether a user authenticated by calling the function 
 * session_did_user_authenticate().
 * 
 * Parameters:
 * - user_token, string, optional: should contain the logged-in user's email
 *   address, hashed using the session_make_user_token() function. If any 
 *   other type of value is provided, checks on user privilege levels will 
 *   fail. Providing an empty value will make the session forget the user
 *   token.
 */
function session_remember_user_token(?string $user_token):bool
{
    if(empty($user_token)) {
        session_forget('user_token');
        return false;
    }
    session_remember('user_token', $user_token);
    return true;
}

/**
 * Determine whether the user authenticated. Set it by calling the
 * function session_remember_user_token(token).
 */
function session_did_user_authenticate():bool
{
    $token = session_recall_user_token();
    if (empty($token)) {
        return false;
    }
    if (0 === strpos($token, 'anonymous_', 0)) {
        return false;
    }
    return true;
}

/**
 * Create a nonce to determine that, for instance, a form submission has  
 * been received from the correct form, and not from elsewhere.
 * 
 * These tokens will be valid for a single user, a single identity, for
 * the duration of 12 hours. We use 12 hours because some forms are 
 * expected to take a long time to fill out. Maybe we should make that 
 * variable per form?
 * 
 * This has been modeled after wp_create_nonce().
 * 
 * We need this function separate from the storage function, because the 
 * nonce validation function uses it separately.
 *
 * @param $id string, required: custom identifier you use to determine 
 *            which nonce to read / inspect.
 * 
 * @return the created nonce.
 */
function session_make_nonce(string $id):string
{
    $time = ceil(time() / ( 60 * 60 * 12));
    $token = session_recall_user_token();
    if (empty($token)) {
        $token = 'anonymous_' . bin2hex(random_bytes(24));
        session_remember_user_token($token);
    }
    return session_make_hash($time . '|' . $token . '|' . $id);
}

/**
 * Stores a nonce in the session variable identified by id. Use this for 
 * instance to prevent cross-site request forgery also known as CSRF. 
 * Use the session_make_and_remember_nonce(id) function, as a convenience
 * method. 
 * 
 * @param $id    should be the form identifier. Choose this when generating
 *               the nonce for the form, and use it when checking it or
 *               forgetting it.
 * @param $nonce should be the nonce generated earlier, to store for the
 *               form identified by $id. Use the function 
 *               session_make_nonce() to generate the nonce.
 * 
 * @return Boolean true if remembering the nonce for that form succeeded.
 */
function session_remember_nonce(string $id, string $nonce):bool
{
    return session_remember($id . '_nonce', $nonce);
}

/**
 * Removes a session nonce stored earlier. Do this to prevent reposting 
 * of the same form, after validating a nonce seen earlier. 
 * 
 * One of the reasons to do this, is to make DDOS attempts slightly more 
 * complex, as bots will need to go back to the entry form to retrieve a 
 * new nonce. Another is to reduce CSRF attempts.
 * 
 * @param $id should be the form identifier. Choose this when generating
 *            the nonce for the form, and use it when checking it or
 *            forgetting it.
 * 
 * @return Boolean true if the form nonce was forgotten successfully.
 */
function session_forget_nonce(string $id):bool
{
    return session_forget($id . '_nonce');
}

/**
 * Create and save a nonce for a specific use into a session variable
 * identified by id. Use this for instance to determine that a form
 * submission is received from the expected form, and not from elsewhere.
 * 
 * One of the reasons to do this, is to make DDOS attempts slightly more 
 * complex, as bots will need to go back to the entry form to retrieve a 
 * new nonce. Another is to reduce CSRF attempts.
 * 
 * @param $id should be the form identifier. Choose this when generating
 *            the nonce for the form, and use it when checking it or
 *            forgetting it.
 * 
 * @return the created nonce.
 */
function session_make_and_remember_nonce(string $id):string
{
    $nonce = session_make_nonce($id);
    if (session_remember_nonce($id, $nonce)) {
        return $nonce;
    }
    return '';
}

/**
 * Retrieve the nonce identified by id, from the session storage.
 * 
 * @param $id should be the form identifier. Choose this when generating
 *            the nonce for the form, and use it when checking it or
 *            forgetting it.
 * 
 * @return the nonce created and remembered earlier. Could be empty if 
 *         no nonce was remembered with the passed-in $id.
 */
function session_recall_nonce(string $id):string
{
    return session_recall($id . '_nonce');
}

/**
 * Determine whether the nonce stored for the identity is valid. It will 
 * be invalid if no nonce was found for that identity, if one was found 
 * but doesn't match, or if one was found but timed out.
 * 
 * This has been modeled after wp_verify_nonce().
 * 
 * @param $id should be the form identifier. Choose this when generating
 *            the nonce for the form, and use it when checking it or
 *            forgetting it.
 * 
 * @return Boolean true if the nonce is valid.
 */
function session_is_nonce_valid(string $id):bool
{
    if (empty($id)) {
        return false;
    }
    $nonce = session_recall_nonce($id);
    if (empty($nonce)) {
        return false;
    }
    $expected = session_make_nonce($id);
    if (empty($expected)) {
        return false;
    }
    return hash_equals($expected, $nonce);
}

?>

<?php
declare(strict_types=1);

/**
 * Request credentials to access Umpire
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.926.2143
 */

require_once('../../../config.php');

function is_email_known($candidate) {
    $hashed_candidate = hash(
        'sha512', 
        $candidate
    );
    $sql = 'select 
        count(*) as `amount` 
        from `users` 
        where `email_hash` = \'' 
        . $hashed_candidate 
        . '\'';
    $rows = query($sql);
    if ($rows) {
        $row = $rows[0];
        if ($row) {
            $amount = $row['amount'];
            return $amount > 0;
        }
    }
    return false;
}

function make_user_key() {
    return bin2hex(
        random_bytes(64)
    );
}

function make_user_secret() {
    $word_list = [
        'horse',
        'green',
        'sombrero',
        'falsetto',
        'ivy',
        'farms',
        'pizza',
        'dulcimer',
        'hashbrown',
        'return',
        'thankful',
        'venerate',
        'treaty',
        'madness',
        'slicing',
        'majestic',
        'fibrous',
        'pineapple',
        'exterior',
        'individual',
        'vertices',
        'properly',
        'ringtoss',
        'cookie cutter',
        'vigorous',
        'edge',
        'corkscrew',
        'friends',
        'electricity',
        'overconfidence',
        'polarizing',
        'shameful',
        'complete',
        'sandwich',
        'tomato',
        'lettuce',
        'flimsy',
        'dainty',
        'perpendicular',
        'guacamole',
        'flavor',
        'molecule',
        'tear',
        'choice',
        'beefsteak'
    ],
    $result = array();
    foreach(
        array_rand(
            $word_list, 
            7
        ) as $k 
    ) {
        $result[] = $word_list[$k];
    }
    return implode(
        ' ',
        $result
    );
}

function add_user($candidate) {
    $hashed_candidate = hash(
        'sha512', 
        $candidate
    );
    $key = make_user_key();
    $secret = make_user_secret();
    $hashing_algo = 'sha512';
    $hashing_version = 1;
    $key_hash = hash(
        $hashing_algo,
        $key
    );
    $secret_hash = hash(
        $hashing_algo,
        $secret
    );
    $sql = 'insert into `users` (
            `email_hash`, 
            `access_requested_on`, 
            `key_hash`, 
            `secret_hash`, 
            `hashing_algo`, 
            `hashing_version`, 
            `last_hashed_date`
            ) values (\''
            . $hashed_candidate 
            . '\', 
            getdate(), 
            \''
            . $key_hash
            . '\', 
            \''
            . $secret_hash
            . '\', 
            \''
            . $hashing_algo
            . '\', 
            \'1\',
            getdate()
            ');';
    $result = query($sql);
    return [$key, $secret];
}

session_start();
$add_email_valid = $_SESSION['add_email_valid'];
unset($_SESSION['add_email_valid']);

$is_known = is_email_known($add_email_valid);

if ($is_known) {
    
    header('Location: ./thank-you');
} else {
    [$key, $secret] = add_user($add_email_valid);
    $success = mail(
        $admin_email,
        'Umpire access requested',
        "Hello,  
  
Special access has been requested to the Umpire database from this email address:  
${add_email_valid}  
  
Please use this link to review the application:  
https://www.umpi.re/applications/?email=${add_email_valid}
  
--
I am a robot. I cannot read your reply. For feedback and support, reach out to ${admin_email}."
    );
}

?>

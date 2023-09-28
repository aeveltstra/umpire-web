<?php
declare(strict_types=1);
error_reporting(E_ALL);
session_start();

/**
 * Request credentials to access Umpire
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.927.2028
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
    ];
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
    );';
    $result = query($sql);
    return [$key, $secret];
}

$add_email_valid = $_SESSION['add_user_email_valid'];
unset($_SESSION['add_user_email_valid']);
$add_user_reason = $_SESSION['add_user_reason_tainted'];
unset($_SESSION['add_user_reason_tainted']);
$add_user_agreed = $_SESSION['add_user_agreed_tainted'];
unset($_SESSION['add_user_agreed_tainted']);

/* $admin_email is set in config.php */
$success = mail(
    $admin_email,
    'Umpire access requested',
    "Hello,  
  
Special access has been requested to the Umpire database from this email address:  
${add_email_valid}  

Their reason is:
${add_user_reason}

Did they agree to the terms and conditions?
${add_user_agreed}
  
Use this link to accept the application:  
https://www.umpi.re/applications/accept?email=${add_email_valid}

Use this link to reject it:
https://www.umpi.re/applications/reject?email=${add_email_valid}
  
--
I am a robot. I cannot read your reply. For feedback and support, reach out to ${admin_email}."
);

$is_known = is_email_known($add_email_valid);

if ($is_known) {
    header('Location: ./sent');
} else {
    [$key, $secret] = add_user($add_email_valid);
}

?>
<!DOCTYPE html>
<html lang="en" encoding="utf-8">
<head><meta charset="utf-8" />
<title>Save this info! - Umpire</title>
<meta name="description" value="This will be shown only once."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>Save this info! - Umpire</h1>
<h2>This will be shown only once.</h2>
<p>You will be asked for these when you log in. If you lose them, you have to reset them. The Umpire operatives CANNOT retrieve them, and CANNOT send them to you.</p>
<p>Please print this page or save it as a PDF and store it somewhere else.</p>
<p>The email address you provided:</p>
<p><?php echo addslashes($add_email_valid) ?></p>
<p>This is your access key:</p>
<p><?php echo addslashes($key) ?></p>
<p>And this is your secret pass phrase:</p>
<p><?php echo addslashes($secret) ?></p>
<p>Note: the Umpire operatives will NEVER ask for your key or secret pass phrase. They may ask for your email address.</p>
</body>
</html>


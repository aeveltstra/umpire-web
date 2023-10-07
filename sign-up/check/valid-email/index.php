<?php
/**
 * Request credentials to access Umpire
 * Step 2: check whether the passed-in email is a known user,
 * and if so, set a temporary reset key.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1007.1756
 */
 error_reporting(E_ALL);

/**
 * db_utils.php contains db functionality
 * like query(sql) and 
 * db_exec(sql, params_typestring, params).
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

session_start();
if (
    !isset($_SESSION['add_user_email_valid'])
    || !isset($_SESSION['add_user_reason_tainted'])
    || !isset($_SESSION['add_user_agreed_tainted'])
) {
    header('Location: ./error-missing-input/');
    die();
}


function make_user_key(): string {
    return bin2hex(
        random_bytes(64)
    );
}

function make_user_secret(): string {
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
        'beefsteak',
        'realize',
        'artistic',
        'cooking',
        'lampshade',
        'jokingly',
        'pinboard',
        'astrophysics'
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

function add_user(string $hashed_candidate): ?array {
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
    ) values ( 
        ?, now(), ?, ?, ?, ?, now()
    );';
    $mysqli = connect_db();
    $ps = $mysqli->prepare($sql);
    /* automatically bind all parameters */
    $ps->bind_param('ssssi', 
        $hashed_candidate,
        $key_hash,
        $secret_hash,
        $hashing_algo,
        $hashing_version
    );
    $ps->execute();
    $seq = $mysqli->insert_id;
    if ($seq) {
        return [$key, $secret];
    }
    return null;
}

$add_email_valid = $_SESSION['add_user_email_valid'];
unset($_SESSION['add_user_email_valid']);
$add_user_reason = $_SESSION['add_user_reason_tainted'];
unset($_SESSION['add_user_reason_tainted']);
$add_user_agreed = $_SESSION['add_user_agreed_tainted'];
unset($_SESSION['add_user_agreed_tainted']);
if (empty($add_user_agreed)) {
    $add_user_agreed = 'no';
} else if ('on' == $add_user_agreed) {
    $add_user_agreed = 'yes';
} else {
    $add_user_agreed = 'no';
}

$candidate_hash = hash_candidate(
    $add_email_valid
);
$is_known = is_email_known(
    $candidate_hash
);

if ($is_known) {
    header('Location: ./sent/');
    die;
}

$user_added = add_user($candidate_hash);
if (empty($user_added)) {
    header('Location: ./error-storage-failure/');
    die;
} 

[$key, $secret] = $user_added;
$_SESSION['access_request_email'] = $add_email_valid;
$_SESSION['access_request_reason'] = $add_user_reason;
$_SESSION['access_request_agreed_to_terms'] = $add_user_agreed;

?>
<!DOCTYPE html>
<html lang="en" encoding="utf-8">
<head><meta charset="utf-8" />
<title>E-mail Address Accepted - Umpire</title>
<meta name="description" value="Save this info. It will be shown only once."/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>
<h1>E-mail Address Accepted - Umpire</h1>
<h2>Save this info. It will be shown only once.</h2>
<p>We created some access keys for you. You will be asked for these when you log in. If you lose them, you have to reset them. Our operatives CANNOT retrieve them, and CANNOT send them to you.</p>
<p>Note: our operatives will NEVER ask for your key or secret pass phrase. They may ask for your email address.</p>
<p>Please print this page or save it as a PDF and store it somewhere else.</p>
<form method=post action="send/">
    <fieldset><legend>The email address you provided:</legend>
        <p><?php echo addslashes($add_email_valid) ?></p>
    </fieldset>
    <fieldset><legend>This is your access key:</legend>
        <p><?php echo addslashes($key) ?></p>
    </fieldset>
    <fieldset><legend>And this is your secret pass phrase:</legend>
        <p><?php echo addslashes($secret) ?></p>
    </fieldset>
    <fieldset><legend>Did you save the keys?</legend>
        <p><label><input type=radio name=saved_how value="screenshot"/> Yes, I took a screen shot;</label></p>
        <p><label><input type=radio name=saved_how value="print"/> Yes, I printed it to pdf, paper, or similar;</label></p>
        <p><label><input type=radio name=saved_how value="manual_file"/> Yes, I copied them to a file;</label></p>
        <p><label><input type=radio name=saved_how value="not"/> No, I didn't.</label></p>
    </fieldset>
    <fieldset><legend>Last step (step 2 of 2):</legend>
        <p><label><input type=submit value="Apply for Access"/></label></p>
    </fieldset>
</form>
</body>
</html>

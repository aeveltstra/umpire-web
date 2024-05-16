<?php 
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/session_utils.php';
$last_case_id = session_recall('new_case_id');
$nonce = session_make_and_remember_nonce('subscribe');
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Subscribe to Case Updates - Umpire</title>
<meta name=description content="Receive emails for every change."/>
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../c/main.css"/>
</head>
<body>

<h1>Subscribe to Case Updates - Umpire</h1>
<?php if (!empty($last_case_id)) { ?>
    <p>This is your case ID: <?php echo $last_case_id; ?>.</p>
    <p>Please keep this with your records for future reference.</p>
    <h2>Do you want to receive an email for every change?</h2>
    <form action="../subscribe/" method=post><fieldset><legend>Yes, please!</legend>
        <p><label><input type=checkbox name=agree/> I agree to the</label> <a href="../terms/">terms and conditions</a></p>
        <p><label for=email>Send notifications to this email address:</label></p>
        <p><input type=email size=60 maxlength=256 id=email name=email /></p>
        <p><label><input type=submit value=Subscribe /></label></p>
        <input type=hidden hidden name=case value='<?php echo $last_case_id; ?>' />
        <input type=hidden hidden name=nonce value='<?php echo $nonce; ?>'/>
    </fieldset></form>
    <form action="/umpire/" method=get><fieldset><legend>No, thanks!</legend>
        <p><label><input type=submit value="I'm fine with not receiving updates."/></label></p>
    </fieldset></form>
<?php } ?>
<p>Return to <a href="/umpire/">the home page</a>.</p>
</body>
</html>

<?php
declare(strict_types=1);
/**
 * Confirm that registering the case was successful.
 * Display the new case ID.
 * Offer the user to subscribe to or own the case.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1010.2022
 */

$started = session_start();
/* These values are generated during case entry. */
/* The new case id identifies the case. That allows users to 
 * subscribe to updates and edit the case.
 */
$case_id = $_SESSION['new_case_id'];
if (empty($case_id)) {
    $case_id = 'unknown';
}

/* The owner of the case is anoynmous if the case got entered by a 
 * user who did not log in to identify themselves. If they had logged 
 * in prior to entering the case, this value will be false.
 */
$is_case_owner_anonymous = $_SESSION['new_case_owner_is_anonymous'];
if (empty($is_case_owner_anonymous)) {
    $is_case_owner_anonymous = true;
}

/* The owner of the case is added during case entry. If the case got
 * entered by an anonymous user, a temporary user id will be given. 
 * If the user had logged in prior to entering the case, that user's
 * id will be given.
 */
$case_owner = $_SESSION['new_case_owner'];
if (empty($case_owner)) {
    $case_owner = 'anonymous';
}

?>
<!DOCTYPE html>
<html lang=en>
<head><meta charset="utf-8" />
<title>Success! - Umpire</title>
<meta name=description content="Missing person's case entered successfully."/>
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css" />
</head>
<body>
<h1>Success! - Umpire</h1>
<h2>Missing person's case entered successfully</h1>
<p>Anything else we can do for you?</p>
<form action="subscribe/" method=post>
    <fieldset><legend>New Case ID</legend>
        <p>This is the identification of the case you entered. 
            Should you want to talk to us about the case, please 
            use this ID as reference.</p>
        <p><?php 
            /* We MUST NOT place the case id into a form field,
             * as doing so will imply to the receiving process
             * that this form is the source of truth for that id,
             * but it most certainly isn't, as anyone could enter
             * any case id they'd feel like. Instead, the form
             * processor must read the case id from session memory.
             */
            echo $case_id;
        ?></p>
    </fieldset>
    <fieldset><legend>Would you like to keep track?</legend>
        <p>You can subscribe to updates to this case. 
            We'll send you a notification every time 
            it changes in relevant ways. Please note that
            by subscribing, you allow us to register your 
            email address. Should you wish to remain 
            anonymous, please use an email address that 
            does not use your real name.</p>
        <p><label><input type=checkbox name=chk_yes_subscribe /> Yes,
            I want to subscribe to case changes.</label></p>
    </fieldset>
    <?php if ($is_case_owner_anonymous) { ?>
    <fieldset><legend>Need to edit your case?</legend>
        <p>Approved case owners are allowed to edit their cases. 
            We store changes and old versions of each case. 
            Changes can be undone if you make mistakes.
            Would you like to be registered as the case owner?
            We do not show to the public who owns or entered a 
            case. It will be visible to case workers and system
            administrators. You will be given a user account 
            if you don't already have one.
        </p>
        <p><label><input type=checkbox name=chk_yes_own /> Yes,
            I want to be registered as owner of this 
            case.</label></p>
    </fieldset>
    <?php } ?>
    <fieldset><legend>How can we reach you?</legend>
        <p><label for=email_sub>E-mail Address:</label></p>
        <p><input type=email name=email_sub id=email_sub size=60 /></p>
        <p><label><input type=submit value="OK"/></label></p>
    </fieldset>
</form>
<p><a href="/umpire/entry/">Enter a new case into the system.</a></p>
</body>
</html>

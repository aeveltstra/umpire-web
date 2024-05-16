<?php
/**
 * Conveys that the user subscribed successfully, and offers next
 * actions.
 * 
 * @author  A.E.Veltstra for OmegaJunior Consultancy <omegajunior@protonmail.com>
 * @version 2.24.114.1830
 */
declare(strict_types=1);

/* DB Utils contains functions to retrieve information from the DB. */
require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Subscribed Successfully - Umpire</title>
<meta name=description 
    content="You will receive case updates until you unsubscribe." />
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="../../c/main.css"/>
</head>
<body>

<h1>Subscribed Successfully - Umpire</h1>
<h2>You will receive case updates until you unsubscribe.</h2>
<p>Did you want to <a href="../../view/statistics/">view our statistics</a>?</p>
</body>
</html>

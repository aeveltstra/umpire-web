<?php
/**
 * Conveys that the user signed in successfully, and offers next
 * actions.
 * @author A.E.Veltstra for OmegaJunior Consultancy
 * @version 2.23.1024.2206
 */
declare(strict_types=1);

/* DB Utils contains functions to retrieve information from the DB. */
include_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
<title>Authenticated Successfully - Umpire</title>
<meta name=description content="Welcome back!" />
<meta name=author value="OmegaJunior Consultancy, LLC" />
<meta name=viewport content="width=device-width, initial-scale=1.0" />
<link rel=stylesheet href="/umpire/c/main.css"/>
</head>
<body>

<h1>Authenticated Successfully - Umpire</h1>
<h2>Welcome back!</h2>
<p>Did you want to <a href="/umpire/entry">enter a case</a>?</p>
</body>
</html>

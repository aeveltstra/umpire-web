<?php
declare(strict_types=1);
error_reporting(E_ALL);
$equalities = [];

include_once 'db_utils.php';

$equalities[] = [1, '1', scalar('select 1')];
$equalities[] = [2, 'en', scalar('select `language_code` from `enums` where `language_code` = \'en\' limit 1;')];
$equalities[] = [3, 'en', query('select `language_code` from `enums` where `language_code` = \'en\';')[0][0]];
$equalities[] = [4, 'en', query('select `language_code` from `enums` where `language_code` = ?;', 's', ['en'])[0][0]];

$a_equals_b = function($test):bool {
    $good = $test[1] === $test[2];
    return !$good;
};

$equality_failures = array_filter($equalities, $a_equals_b);

$make_equality_failure_output = function($test):string {
    $a = $test[1];
    $b = $test[2];
    $counter = $test[0];
    $a_type = gettype($a);
    $b_type = gettype($b);
    return "Fail: test ${counter} expected [${a}] of type ${a_type} but got [${b}] of type {$b_type}.";
};

$output = array_map($make_equality_failure_output, $equality_failures);

if (empty($output)) {
    echo "All's well.";
}
foreach($output as $fail) {
    echo "$fail\n";
}

?>

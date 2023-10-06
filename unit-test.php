<?php
declare(strict_types=1);
error_reporting(E_ALL);

/** Equalities holds values for equality tests. Equality tests are 
 *  true if their values equal. Each row should have values for a 
 *  distinct test. Each row should be defined as:
 *  - test index number: used during logging to identify the test,
 *  - expected value (string): to compare against the actual value,
 *  - actual value (string): the value derived from an operation,
 *    to compare against the expected value.
 */
$equalities = [];

/** Inequalities holds values for difference tests. Each row should have
 *  values for a distinct test. Each row should be defined the same way
 *  as equalities. Difference tests are true if their values differ.
 */
$inequalities = [];

require_once $_SERVER['DOCUMENT_ROOT'] . '/umpire/db_utils.php';
//include_once './entry/missing/index.php';

$equalities[] = [1, '1', scalar('select 1')];
$equalities[] = [2, 'en', scalar('select `language_code` from `enums` where `language_code` = \'en\' limit 1;')];
$equalities[] = [3, 'en', query('select `language_code` from `enums` where `language_code` = \'en\';')[0]['language_code']];
$equalities[] = [4, 'en', query('select `language_code` from `enums` where `language_code` = ?;', 's', ['en'])[0]['language_code']];

$inequalities[] = [5, '0', query('select count(*) from `enums` order by `attribute_id`, `caption`')];
$equalities[] = [6, 'attribute_id,enum_value,caption', join(',', array_keys(query('select `attribute_id`, `enum_value`, `caption` from `enums` where `language_code` = \'en\';')[0]))];

/* this is weird but due to how mysqli->fetch_all(MYSQLI_BOTH) does associations. It returns both the field index numbers and the field names. We use MYSQLI_ASSOC, so the result should have field names, only. */
$inequalities[] = [7, '0,attribute_id,1,enum_value,2,caption', join(',', array_keys(query('select `attribute_id`, `enum_value`, `caption` from `enums` where `language_code` = \'en\';')[0]))];
$equalities[] = [8, 'attribute_id,enum_value,caption', join(',', array_keys(db_exec('select `attribute_id`, `enum_value`, `caption` from `enums` where `language_code` = ?;', 's', ['en'])[0]))];

$enumerations = read_enumerations_from_db('en');
$equalities[] = [9, '/home/users/web/b1771/glo.omegajunior', $_SERVER['DOCUMENT_ROOT']];
$equalities[] = [10, 'enum_value', array_keys($enumerations[0])[1]];
$equalities[] = [11, 3, count(array_keys($enumerations[0]))];
echo '<!-- enumerations: ';
var_dump($enumerations);
echo '-->';

/** Equality test. Returns true if values equal. 
 *  Use this as the array_filter function.
 */
$a_equals_b = function($test):bool {
    $good = $test[1] === $test[2];
    return $good;
};

/** Inequality test. Returns true if values differ.
 *  Use this as the array_filter function.
 */
$a_differs_from_b = function($test):bool {
    $fail = !($test[1] === $test[2]);
    return $fail;
};

/** Perform the tests. */
$equality_failures = array_filter($equalities, $a_differs_from_b);
$inequality_failures = array_filter($inequalities, $a_equals_b);

/** Interpret the difference test results. 
 *  Use this as the array_map function.
 */
$make_inequality_failure_output = function($test):string {
    $a = $test[1];
    $b = $test[2];
    $counter = $test[0];
    $a_type = gettype($a);
    $b_type = gettype($b);
    return "Fail: difference test ${counter} expected value [${a}] of type ${a_type} to differ from actual value [${b}] of type {$b_type}, but they are the same.";
};

/** Interpret the equality test results. 
 *  Use this as the array_map function.
 */
$make_equality_failure_output = function($test):string {
    $a = $test[1];
    $b = $test[2];
    $counter = $test[0];
    $a_type = gettype($a);
    $b_type = gettype($b);
    return "Fail: equality test ${counter} expected value [${a}] of type ${a_type} to be the same as actual value [${b}] of type {$b_type}, but they differ.";
};

/** Gather the test result interpretations. */
$output = array_map($make_equality_failure_output, $equality_failures);
$ouput[] = array_map($make_inequality_failure_output, $inequality_failures);

/** Show the outcome(s). */
if (empty($output)) {
    echo "All's well.";
}
foreach($output as $fail) {
    echo "$fail\n";
}

?>

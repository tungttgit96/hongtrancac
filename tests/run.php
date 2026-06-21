<?php

require_once __DIR__ . '/bootstrap.php';

$filters = array_slice($argv, 1);
foreach (glob(__DIR__ . '/*Test.php') as $file) {
    if ($filters) {
        $matches = false;
        foreach ($filters as $filter) {
            if (stripos(basename($file), $filter) !== false) {
                $matches = true;
                break;
            }
        }
        if (!$matches) continue;
    }
    require $file;
}

if (!$GLOBALS['hdk_tests']) {
    $case = new HDK_TestCase();
    $case->assert_same(1, 1, 'test runner can compare identical values');
    echo "PASS test runner self-check\n";
    exit(0);
}

$failures = 0;
foreach ($GLOBALS['hdk_tests'] as $name => $test) {
    try {
        $test(new HDK_TestCase());
        echo "PASS $name\n";
    } catch (Throwable $error) {
        $failures++;
        echo "FAIL $name: {$error->getMessage()}\n";
    }
}

$total = count($GLOBALS['hdk_tests']);
echo "\n" . ($total - $failures) . "/$total tests passed\n";
exit($failures > 0 ? 1 : 0);

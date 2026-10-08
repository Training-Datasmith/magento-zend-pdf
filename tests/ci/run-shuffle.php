<?php

/**
 * Single-process PHPUnit run with Fisher–Yates shuffled test order (seed 20261008).
 */

$root = dirname(dirname(__DIR__));
chdir($root);

require $root . '/vendor/autoload.php';
require $root . '/tests/bootstrap.php';

$config = PHPUnit\Util\Configuration::getInstance($root . '/phpunit.xml.dist');
$suite = $config->getTestSuiteConfiguration();

$expected = countTests($suite);
$tests = flattenTests($suite);

mt_srand(20261008);
fisherYatesShuffle($tests);

$shuffled = new PHPUnit\Framework\TestSuite('Shuffled');
foreach ($tests as $test) {
    $shuffled->addTest($test);
}

$runner = new PHPUnit\TextUI\TestRunner();
$result = $runner->doRun($shuffled, array(
    'configuration' => $config,
    'loadedExtensions' => array(),
    'notLoadedExtensions' => array(),
), false);

$ran = $result->count();
if ($ran < $expected) {
    fwrite(STDERR, "Expected {$expected} tests, ran {$ran}\n");
    exit(2);
}

exit($result->wasSuccessful() ? 0 : 1);

/**
 * @param PHPUnit\Framework\Test $suite
 * @return int
 */
function countTests(PHPUnit\Framework\Test $suite)
{
    if ($suite instanceof PHPUnit\Framework\TestCase) {
        return 1;
    }
    if (!$suite instanceof PHPUnit\Framework\TestSuite) {
        return $suite->count();
    }
    $n = 0;
    foreach ($suite as $test) {
        $n += countTests($test);
    }
    return $n;
}

/**
 * @param PHPUnit\Framework\Test $suite
 * @return PHPUnit\Framework\TestCase[]
 */
function flattenTests(PHPUnit\Framework\Test $suite)
{
    $out = array();
    if ($suite instanceof PHPUnit\Framework\TestCase) {
        $out[] = $suite;
        return $out;
    }
    if ($suite instanceof PHPUnit\Framework\TestSuite) {
        foreach ($suite as $test) {
            foreach (flattenTests($test) as $t) {
                $out[] = $t;
            }
        }
    }
    return $out;
}

/**
 * @param PHPUnit\Framework\TestCase[] $tests
 */
function fisherYatesShuffle(array &$tests)
{
    $n = count($tests);
    for ($i = $n - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        if ($j !== $i) {
            $tmp = $tests[$i];
            $tests[$i] = $tests[$j];
            $tests[$j] = $tmp;
        }
    }
}

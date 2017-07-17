<?php
namespace exussum12\CoverageChecker;

use Exception;
use PHPUnit_Framework_AssertionFailedError as AssertionFailedError;
use PHPUnit_Framework_Test as Test;
use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_TestListener as TestListener;
use PHPUnit_Framework_TestSuite as TestSuite;

/**
 * @codeCoverageIgnore
 */
class DiffFilter implements TestListener
{
    protected $modifiedTests = null;
    protected $modifiedSuites = null;
    public function __construct($old, $diff)
    {
        try {
            $diff = new DiffFileLoader($diff);
            $matcher = new FileMatchers\EndsWith();
            $coverage = new PhpunitFilter($diff, $matcher, $old);
            $this->modifiedTests = $coverage->getTestsForRunning();
            $this->modifiedSuites = array_keys($this->modifiedTests);
            unset($coverage);
        } catch (Exception $e) {
            //Something has gone wrong, Don't filter
            echo "Missing required diff / php coverage, Running all tests\n";
        }
    }

    public function addError(Test $test, Exception $e, $time)
    {
    }
    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
    }
    public function addRiskyTest(Test $test, Exception $e, $time)
    {
    }
    public function startTestSuite(TestSuite $suite)
    {
        if (!is_array($this->modifiedTests)) {
            return;
        }

        $suiteName = $suite->getName();
        $runTests = [];
        if (empty($suiteName)) {
            return ;
        }

        $tests = $suite->tests();
                         
        foreach ($tests as $test) {
            $skipTest = 
                $test instanceof TestCase &&
                !$this->hasTestChanged(
                    $test
                );

            if ($skipTest) {
                continue;
            }
            $runTests[]= $test;
        }

        $suite->setTests($runTests);
    }
    public function startTest(Test $test)
    {
    }
    public function endTest(Test $test, $time)
    {
    }
    public function addIncompleteTest(Test $test, Exception $e, $time)
    {
    }
    public function addSkippedTest(Test $test, Exception $e, $time)
    {
    }
    public function endTestSuite(TestSuite $suite)
    {
    }
    public function onFatalError()
    {
    }
    public function onCancel()
    {
    }

    public function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    protected function shouldRunTest($modifiedTest, $currentTest, $class)
    {
        foreach ($modifiedTest as $test) {
            $testName = $currentTest->getName();
            if (strpos($class, get_class($currentTest)) !== false && (empty($test) || strpos($test, $testName) !== false)) {
                return true;
            }
        }
        return false;
    }

    private function hasTestChanged(TestCase $test)
    {
        foreach ($this->modifiedTests as $class => $modifiedTest) {
            if ($this->shouldRunTest($modifiedTest, $test, $class)) {
                return true;
            }
        }

        return false;
    }
}

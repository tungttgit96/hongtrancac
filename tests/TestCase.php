<?php

class HDK_TestCase {
    public function assert_same($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    public function assert_true($actual, $message = '') {
        $this->assert_same(true, $actual, $message);
    }

    public function assert_false($actual, $message = '') {
        $this->assert_same(false, $actual, $message);
    }

    public function assert_count($expected, $actual, $message = '') {
        $this->assert_same($expected, count($actual), $message);
    }

    public function assert_instance_of($class, $actual, $message = '') {
        if (!($actual instanceof $class)) {
            throw new RuntimeException(($message ? $message . ': ' : '') . 'expected instance of ' . $class);
        }
    }
}

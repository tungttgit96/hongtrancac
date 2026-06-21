<?php

require_once dirname(__DIR__) . '/wp-content/plugins/hdk-core/includes/class-protection.php';

hdk_test('untrusted peer cannot spoof forwarded client IP', function(HDK_TestCase $t) {
    $server = [
        'REMOTE_ADDR' => '198.51.100.20',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.44',
        'HTTP_X_REAL_IP' => '203.0.113.45',
    ];
    $t->assert_same('198.51.100.20', HDK_Protection::resolve_ip($server, []));
});

hdk_test('trusted proxy resolves first valid forwarded client', function(HDK_TestCase $t) {
    $server = [
        'REMOTE_ADDR' => '10.0.0.2',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.44, 10.0.0.1',
    ];
    $t->assert_same('203.0.113.44', HDK_Protection::resolve_ip($server, ['10.0.0.2']));
});

hdk_test('malformed trusted proxy chain falls back to remote address', function(HDK_TestCase $t) {
    $server = ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => 'not-an-ip'];
    $t->assert_same('10.0.0.2', HDK_Protection::resolve_ip($server, ['10.0.0.2']));
});

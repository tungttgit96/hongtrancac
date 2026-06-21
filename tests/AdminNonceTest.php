<?php

hdk_test('admin moderation handlers verify action-specific nonces', function(HDK_TestCase $t) {
    $source = file_get_contents(dirname(__DIR__) . '/wp-content/plugins/hdk-core/includes/class-admin.php');
    $t->assert_true(str_contains($source, "check_admin_referer('hdk_comment_' . \$cid)"), 'comment action nonce');
    $t->assert_true(str_contains($source, "check_admin_referer('hdk_report_' . \$rid)"), 'report action nonce');
});

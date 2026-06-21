<?php

require_once dirname(__DIR__) . '/wp-content/plugins/hdk-core/includes/class-comment-policy.php';

hdk_test('comment policy rejects empty and oversized content', function(HDK_TestCase $t) {
    $empty = HDK_Comment_Policy::validate(3, 9, 2, '   ', 0);
    $long = HDK_Comment_Policy::validate(3, 9, 2, str_repeat('a', 2001), 0);
    $t->assert_same('empty_comment', $empty->get_error_code());
    $t->assert_same('comment_too_long', $long->get_error_code());
});

hdk_test('comment policy rate limits the sixth comment in five minutes', function(HDK_TestCase $t) {
    $GLOBALS['hdk_transients'] = ['hdk_comment_rl_3' => 5];
    $result = HDK_Comment_Policy::validate(3, 9, 2, 'Valid', 0);
    $t->assert_same('comment_rate_limited', $result->get_error_code());
    $t->assert_same(429, $result->get_error_data()['status']);
});

hdk_test('comment reply parent must belong to the same story and chapter', function(HDK_TestCase $t) {
    $GLOBALS['hdk_transients'] = [];
    $GLOBALS['hdk_comments'] = [15 => (object)['comment_ID' => 15]];
    $GLOBALS['hdk_comment_meta'] = [15 => ['hdk_story_id' => 10, 'hdk_chapter_number' => 2]];
    $result = HDK_Comment_Policy::validate(3, 9, 2, 'Reply', 15);
    $t->assert_same('invalid_parent', $result->get_error_code());
});

hdk_test('valid comment records rate count and uses WordPress moderation', function(HDK_TestCase $t) {
    $GLOBALS['hdk_transients'] = [];
    $GLOBALS['hdk_comment_approval'] = 0;
    $result = HDK_Comment_Policy::validate(3, 9, 2, 'Valid', 0);
    $t->assert_true($result);
    HDK_Comment_Policy::record_submission(3);
    $t->assert_same(1, $GLOBALS['hdk_transients']['hdk_comment_rl_3']);
    $t->assert_same(0, HDK_Comment_Policy::approval(['comment_content' => 'Valid']));
});

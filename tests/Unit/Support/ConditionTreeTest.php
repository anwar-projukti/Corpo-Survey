<?php

use App\Support\ConditionTree;
use Illuminate\Validation\ValidationException;

test('a valid leaf condition passes', function () {
    ConditionTree::validate(['question_id' => 1, 'operator' => 'equals', 'value' => 'yes'], [1, 2, 3]);
})->throwsNoExceptions();

test('a valid nested and/or group passes', function () {
    ConditionTree::validate([
        'operator' => 'and',
        'conditions' => [
            ['question_id' => 1, 'operator' => 'gt', 'value' => 18],
            [
                'operator' => 'or',
                'conditions' => [
                    ['question_id' => 2, 'operator' => 'equals', 'value' => 'x'],
                    ['question_id' => 3, 'operator' => 'is_answered'],
                ],
            ],
        ],
    ], [1, 2, 3]);
})->throwsNoExceptions();

test('a condition referencing a question outside the given set is rejected', function () {
    ConditionTree::validate(['question_id' => 999, 'operator' => 'equals', 'value' => 'yes'], [1, 2, 3]);
})->throws(ValidationException::class);

test('an unrecognized operator is rejected', function () {
    ConditionTree::validate(['question_id' => 1, 'operator' => 'DROP TABLE', 'value' => 'x'], [1, 2, 3]);
})->throws(ValidationException::class);

test('a group with an invalid operator is rejected', function () {
    ConditionTree::validate([
        'operator' => 'xor',
        'conditions' => [['question_id' => 1, 'operator' => 'equals', 'value' => 'x']],
    ], [1, 2, 3]);
})->throws(ValidationException::class);

test('a group with no conditions is rejected', function () {
    ConditionTree::validate(['operator' => 'and', 'conditions' => []], [1, 2, 3]);
})->throws(ValidationException::class);

test('is_answered and is_not_answered do not require a value', function () {
    ConditionTree::validate(['question_id' => 1, 'operator' => 'is_answered'], [1, 2, 3]);
})->throwsNoExceptions();

test('an operator other than is_answered/is_not_answered requires a value', function () {
    ConditionTree::validate(['question_id' => 1, 'operator' => 'equals'], [1, 2, 3]);
})->throws(ValidationException::class);

test('an excessively deep tree is rejected', function () {
    $tree = ['question_id' => 1, 'operator' => 'equals', 'value' => 'x'];

    for ($i = 0; $i < 15; $i++) {
        $tree = ['operator' => 'and', 'conditions' => [$tree]];
    }

    ConditionTree::validate($tree, [1]);
})->throws(ValidationException::class);

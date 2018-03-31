<?php

use Phan\Issue;

return [
    'target_php_version' => '7.0',
    'backward_compatibility_checks' => false,
    'suppress_issue_types' => ['PhanDeprecatedFunction'],

    'exclude_file_list' => [],
    'exclude_analysis_directory_list' => [
        'vendor/',
    ],

    'plugins' => [
        'AlwaysReturnPlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'UnreachableCodePlugin',
    ],

    'directory_list' => [
        'src/',
        'vendor/sanmai/pipeline/src/',
    ],
];

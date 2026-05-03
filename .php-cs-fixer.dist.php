<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
    ])
    ->name('*.php');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        // ── Base ruleset ────────────────────────────────────────────────────
        '@PSR12' => true,

        // ── Arrays ──────────────────────────────────────────────────────────
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_whitespace_before_comma_in_array' => true,
        'trim_array_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'whitespace_after_comma_in_array' => true,

        // ── Operators & expressions ─────────────────────────────────────────
        'binary_operator_spaces' => true,
        'cast_spaces' => true,
        'concat_space' => ['spacing' => 'one'],
        'logical_operators' => true,   // 'and'/'or' → '&&'/'||'
        'not_operator_with_successor_space' => true,   // '!' → '! '
        'object_operator_without_whitespace' => true,
        'standardize_not_equals' => true,   // '<>' → '!='
        'ternary_to_null_coalescing' => true,   // '?: null' → '??'
        'unary_operator_spaces' => true,

        // ── Spacing & blank lines ────────────────────────────────────────────
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'method_chaining_indentation' => true,
        'no_extra_blank_lines' => [
            'tokens' => ['extra', 'throw', 'use', 'use_trait'],
        ],
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,

        // ── Imports ──────────────────────────────────────────────────────────
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_import_per_statement' => true,

        // ── Strings & casts ──────────────────────────────────────────────────
        'lowercase_cast' => true,
        'single_quote' => true,

        // ── Classes & methods ────────────────────────────────────────────────
        'class_attributes_separation' => [
            'elements' => ['method' => 'one'],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'no_blank_lines_after_class_opening' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait', 'constant_public', 'constant_protected', 'constant_private',
                'property_public', 'property_protected', 'property_private',
                'construct', 'destruct', 'magic', 'phpunit', 'method_public',
                'method_protected', 'method_private',
            ],
        ],
        'protected_to_private' => true,
        'self_accessor' => true,
        'single_class_element_per_statement' => true,
        'single_trait_insert_per_statement' => true,
        'visibility_required' => true,

        // ── Control flow ─────────────────────────────────────────────────────
        'elseif' => true,
        'no_alternative_syntax' => true,
        'no_unneeded_control_parentheses' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'switch_continue_to_break' => true,

        // ── PHPDoc ───────────────────────────────────────────────────────────
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,

        // ── Return types & declarations ───────────────────────────────────────
        'declare_strict_types' => false,
        'return_type_declaration' => true,
        'type_declaration_spaces' => true,
        'void_return' => true,

        // ── Misc ─────────────────────────────────────────────────────────────
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'native_function_casing' => true,
        'no_empty_statement' => true,
        'no_leading_namespace_whitespace' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_trailing_comma_in_singleline' => true,
        'psr_autoloading' => true,
        'single_blank_line_at_eof' => true,
    ])
    ->setFinder($finder);

<?php
/**
 * Test script to reproduce the capability bug
 *
 * The bug: wp_ulike_get_user_access_capability() returns the FIRST key
 * from $current_user->allcaps array, which is unpredictable.
 *
 * WordPress stores capabilities in alphabetical order internally,
 * so key($allcaps) might return something like 'activate_plugins'
 * instead of 'manage_options'.
 */

echo "=== Testing wp_ulike_get_user_access_capability Bug ===\n\n";

// Simulate WordPress user object
class MockUser {
    public $roles = ['administrator'];
    public $allcaps = [
        'switch_themes' => true,
        'edit_themes' => true,
        'activate_plugins' => true,
        'edit_plugins' => true,
        'edit_users' => true,
        'edit_files' => true,
        'manage_options' => true,
        'moderate_comments' => true,
        'manage_categories' => true,
        'edit_posts' => true,
    ];
}

// Original buggy function
function wp_ulike_get_user_access_capability_BUGGY( $type ) {
    $current_user  = new MockUser();
    $allowed_roles = ['administrator'];
    return ! empty( $allowed_roles ) && array_intersect( $allowed_roles, $current_user->roles )
        ? key($current_user->allcaps)  // BUG: returns first key
        : 'manage_options';
}

// Fixed version
function wp_ulike_get_user_access_capability_FIXED( $type ) {
    $allowed_roles = ['administrator'];
    // Always return 'manage_options' for admin users
    // This is what WordPress menu system expects
    return 'manage_options';
}

echo "Buggy version returns: " . wp_ulike_get_user_access_capability_BUGGY('stats') . "\n";
echo "Expected: manage_options\n\n";

echo "Fixed version returns: " . wp_ulike_get_user_access_capability_FIXED('stats') . "\n";
echo "Expected: manage_options\n\n";

echo "=== Root Cause ===\n";
echo "The buggy function calls key(\$current_user->allcaps) which returns\n";
echo "the FIRST key in the array. In PHP, array order is insertion order.\n";
echo "WordPress capabilities are stored alphabetically, so the first one\n";
echo "might be 'activate_plugins' or 'switch_themes', not 'manage_options'.\n\n";

echo "When WordPress checks current_user_can('switch_themes') for the\n";
echo "statistics menu, even though the user HAS that capability, WordPress\n";
echo "menu system gets confused because it expects 'manage_options' or 'read'.\n";

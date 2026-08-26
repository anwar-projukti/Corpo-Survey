<?php

return [

    // General actions, reused across the dashboard
    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'create' => 'Create',
        'close' => 'Close',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'next' => 'Next',
        'submit' => 'Submit',
        'export' => 'Export',
        'import' => 'Import',
        'filter' => 'Filter',
        'search' => 'Search',
        'view' => 'View',
        'publish' => 'Publish',
        'archive' => 'Archive',
        'log_in' => 'Log in',
        'log_out' => 'Log out',
        'register' => 'Register',
    ],

    // Primary navigation (spec §13)
    'nav' => [
        'dashboard' => 'Dashboard',
        'surveys' => 'Surveys',
        'all_surveys' => 'All Surveys',
        'create_survey' => 'Create Survey',
        'templates' => 'Templates',
        'question_library' => 'Question Library',
        'responses' => 'Responses',
        'all_responses' => 'All Responses',
        'review' => 'Review',
        'export_history' => 'Export History',
        'analytics' => 'Analytics',
        'survey_analytics' => 'Survey Analytics',
        'reports' => 'Reports',
        'respondents' => 'Respondents',
        'api' => 'API',
        'api_keys' => 'API Keys',
        'documentation' => 'Documentation',
        'webhooks' => 'Webhooks',
        'users_and_permissions' => 'Users & Permissions',
        'audit_logs' => 'Audit Logs',
        'settings' => 'Settings',
        'settings_general' => 'General',
        'settings_language' => 'Language',
        'settings_appearance' => 'Appearance',
        'settings_security' => 'Security',
        'settings_notifications' => 'Notifications',
        'settings_api' => 'API',
    ],

    // Theme switcher (spec §3.1)
    'theme' => [
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],

    // Language switcher (spec §4)
    'language' => [
        'english' => 'English',
        'bangla' => 'বাংলা',
    ],

    // System / flash messages
    'system' => [
        'generic_error' => 'Something went wrong. Please try again.',
        'saved' => 'Saved successfully.',
        'deleted' => 'Deleted successfully.',
        'no_results' => 'No results found.',
        'loading' => 'Loading...',
        'confirm_delete' => 'Are you sure you want to delete this? This action cannot be undone.',
        'session_expired' => 'Your session has expired. Please log in again.',
    ],

    'welcome' => 'Welcome, :name',

    // Auth screens (login, register, password reset, 2FA, email verification)
    'auth' => [
        'login_title' => 'Log in',
        'email' => 'Email',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'create_account' => 'Create an account',
        'already_have_account' => 'Already have an account?',

        'register_title' => 'Create your organization',
        'organization_name' => 'Organization name',
        'your_name' => 'Your name',
        'confirm_password' => 'Confirm password',

        'forgot_password_title' => 'Forgot your password?',
        'email_reset_link' => 'Email password reset link',

        'reset_password_title' => 'Reset your password',
        'new_password' => 'New password',
        'confirm_new_password' => 'Confirm new password',
        'reset_password_submit' => 'Reset password',

        'confirm_password_title' => 'Confirm your password',
        'confirm_password_body' => 'This is a sensitive action — please confirm your password to continue.',

        'two_factor_title' => 'Two-factor authentication',
        'two_factor_body' => 'Enter the code from your authenticator app, or one of your recovery codes.',
        'code' => 'Code',
        'recovery_code' => 'Or a recovery code',
        'verify' => 'Verify',

        'verify_email_title' => 'Verify your email',
        'verify_email_body' => 'We sent a verification link to your email address. Click it to continue.',
        'verify_email_resent' => 'A new verification link has been sent.',
        'resend_verification' => 'Resend verification email',
        'two_factor_required_title' => 'Two-factor authentication required',
        'two_factor_required_body' => 'Your role requires two-factor authentication. Set it up to continue to the dashboard.',

        'two_factor_setup_intro' => 'You\'ll need an authenticator app (Google Authenticator, Authy, 1Password, etc.) on your phone.',
        'two_factor_enable_button' => 'Set up two-factor authentication',
        'two_factor_scan_instructions' => 'Scan this QR code with your authenticator app, then enter the 6-digit code it shows.',
        'two_factor_manual_setup' => 'Can\'t scan? Enter the code manually',
        'two_factor_recovery_intro' => 'Save these recovery codes somewhere safe. Each one can be used once to sign in if you lose access to your authenticator app.',
        'two_factor_continue_to_dashboard' => 'Continue to dashboard',
        'two_factor_invalid_code' => 'That code is incorrect or has expired. Please try again.',
        'two_factor_generic_error' => 'Something went wrong. Please try again.',
    ],

];

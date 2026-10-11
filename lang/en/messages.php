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
        'previous' => 'Previous',
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
        'skip_to_content' => 'Skip to content',
        'remove' => 'Remove',
        'register' => 'Register',
    ],

    // Role names as shown in the UI (sidebar org label, header profile) —
    // mirrors the Spatie role names in database/seeders/RolesAndPermissionsSeeder
    // exactly, so App\Models\User::primaryRoleName() can key straight into
    // this array without a translation-key mapping layer.
    'roles' => [
        'Super Admin' => 'System Admin',
        'Org Admin' => 'Org Admin',
        'Editor' => 'Editor',
        'Field User' => 'Field User',
        'Viewer' => 'Viewer',
    ],

    // Primary navigation (spec §13)
    'nav' => [
        'primary_navigation' => 'Primary navigation',
        'dashboard' => 'Dashboard',
        'my_surveys' => 'My Surveys',
        'surveys' => 'Surveys',
        'all_surveys' => 'All Surveys',
        'create_survey' => 'Create Survey',
        'templates' => 'Templates',
        'question_library' => 'Question Library',
        'all_responses' => 'All Responses',
        'review' => 'Review',
        'export_history' => 'Export History',
        'user_productivity' => 'User Productivity',
        'import_respondents' => 'Import Respondents',
        'survey_analytics' => 'Survey Analytics',
        'reports' => 'Reports',
        'users_and_permissions' => 'Users & Permissions',
        'organizations' => 'Organizations',
        'landing_content' => 'Landing Page',
        'pricing_plans' => 'Pricing Plans',
        'trial_days_left' => '{0} Trial ends today|{1} 1 day left|[2,*] :count days left',
        'trial_expired' => 'Trial expired',
        'help' => 'Help',
        // Still used as accessibility labels on the header's working
        // language/theme dropdowns (x-language-switcher, x-theme-switcher)
        // even though there's no Settings page — kept for that reason.
        'settings_language' => 'Language',
        'settings_appearance' => 'Appearance',
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

    // Public marketing/landing page shown at "/" to signed-out visitors
    'landing' => [
        'eyebrow' => 'Built for research and field operations teams',
        'tagline' => 'Field surveys, from questionnaire to insight',
        'body' => 'Design surveys, collect responses online or door-to-door, and turn them into reports — all in one place.',
        'hero_footnote' => 'Set up your organization in minutes — no separate installation required.',
        'get_started' => 'Get started',
        'go_to_dashboard' => 'Go to dashboard',
        'feature_builder_title' => 'Flexible survey builder',
        'feature_builder_body' => 'Create multi-page surveys with branching logic, validation, and reusable question templates.',
        'feature_field_title' => 'Door-to-door field collection',
        'feature_field_body' => 'Field staff collect responses on the go, even offline, with progress synced back automatically.',
        'feature_analytics_title' => 'Real-time analytics',
        'feature_analytics_body' => 'Track response rates, drop-off points, and question-level results as they come in.',
        'features_title' => 'Everything your team needs to run a survey end to end',
        'nav_features' => 'Features',
        'nav_pricing' => 'Pricing',
        'nav_about' => 'About',
        'nav_contact' => 'Contact',
        'pricing_title' => 'Simple, transparent pricing',
        'pricing_body' => 'Start small or scale to a full organization — pick the plan that fits, or talk to us about a custom quote.',
        'pricing_cta' => 'Contact us for pricing',
        'pricing_most_popular' => 'Most popular',
        'pricing_trial_days' => '{1} :count-day free trial|[2,*] :count-day free trial',
        'about_title' => 'One system, from questionnaire to insight',
        'about_body' => 'Corpo Survey Management helps organizations design surveys, collect responses online or door-to-door, and turn them into reports — all in one place. Built for research and field operations teams who need a single system spanning survey design, in-person data collection, and analysis.',
        'about_stat_response_types_label' => 'Collection modes',
        'about_stat_response_types_value' => 'Online + field',
        'about_stat_languages_label' => 'Languages',
        'about_stat_languages_value' => 'EN + বাংলা',
        'about_stat_offline_label' => 'Field collection',
        'about_stat_offline_value' => 'Works offline',
        'contact_title' => 'Get in touch',
        'contact_body' => 'Questions about setting up your organization? Reach us by email, phone, or visit our office.',
        'contact_email_label' => 'Email',
        'contact_phone_label' => 'Phone',
        'contact_office_label' => 'Office',
        'contact_office_address' => "House 18, Road 6\nGulshan, Dhaka",
    ],

    // First-run onboarding checklist shown on the dashboard until an org
    // has its first real response.
    'onboarding' => [
        'title' => 'Get started',
        'body' => 'A few steps to get your first survey collecting real responses.',
        'step_create_survey' => 'Create your first survey',
        'step_publish_survey' => 'Publish it',
        'step_publish_survey_body' => 'A survey must be published before anyone can respond to it.',
        'step_invite_field_user' => 'Invite a field user (optional)',
        'step_invite_field_user_body' => 'Field users can collect responses door-to-door on your behalf.',
        'step_first_response' => 'Collect your first response',
        'stat_surveys' => 'Surveys',
        'stat_responses' => 'Total responses',
        'stat_organizations' => 'Organizations',
        'stat_users' => 'Users',
    ],

    // Dashboard home screen (resources/views/home.blade.php) — stat tiles,
    // recent-activity lists, and quick actions shown once an org (or, for
    // Super Admin, the whole system) has moved past onboarding.
    'dashboard' => [
        'view_all' => 'View all',
        'stat_published' => 'Published surveys',
        'stat_field_users' => 'Field users',
        'quick_actions' => 'Quick actions',
        'recent_surveys' => 'Recent surveys',
        'no_surveys_title' => 'No surveys yet',
        'no_surveys_body' => 'Create your first survey to start collecting responses.',
        'recent_organizations' => 'Recent organizations',
        'no_organizations' => 'No organizations yet.',
        'response_count' => '{0} No responses|{1} 1 response|[2,*] :count responses',
        'survey_count' => '{0} No surveys|{1} 1 survey|[2,*] :count surveys',
        'user_count' => '{0} No users|{1} 1 user|[2,*] :count users',
    ],

    // Auth screens (login, register, password reset, 2FA, email verification)
    'auth' => [
        'login_title' => 'Log in',
        'admin_login_title' => 'System Admin log in',
        'org_login_title' => 'Organization Admin log in',
        'email' => 'Email',
        'password' => 'Password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'create_account' => 'Create an account',
        'already_have_account' => 'Already have an account?',
        'login_link' => 'Log in',

        'register_title' => 'Create your organization',
        'organization_name' => 'Organization name',
        'your_name' => 'Your name',
        'confirm_password' => 'Confirm password',
        'registered_please_login' => 'Account created successfully. Please log in to continue.',
        'selected_plan' => 'Selected plan',
        'change_plan' => 'Change plan',

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

    // Impersonation banner (spec §13 "System Admin")
    'impersonation' => [
        'banner' => 'You are viewing this organization as :name.',
        'return' => 'Return to System Admin',
    ],

];

<?php

return [

    // General actions, reused across the dashboard
    'actions' => [
        'save' => 'সংরক্ষণ করুন',
        'cancel' => 'বাতিল করুন',
        'delete' => 'মুছে ফেলুন',
        'edit' => 'সম্পাদনা করুন',
        'create' => 'তৈরি করুন',
        'close' => 'বন্ধ করুন',
        'confirm' => 'নিশ্চিত করুন',
        'back' => 'পিছনে',
        'next' => 'পরবর্তী',
        'submit' => 'জমা দিন',
        'export' => 'রপ্তানি করুন',
        'import' => 'আমদানি করুন',
        'filter' => 'ফিল্টার',
        'search' => 'অনুসন্ধান',
        'view' => 'দেখুন',
        'publish' => 'প্রকাশ করুন',
        'archive' => 'সংরক্ষণাগারভুক্ত করুন',
        'log_in' => 'লগ ইন',
        'log_out' => 'লগ আউট',
        'register' => 'নিবন্ধন করুন',
    ],

    // Primary navigation (spec §13)
    'nav' => [
        'dashboard' => 'ড্যাশবোর্ড',
        'surveys' => 'জরিপসমূহ',
        'all_surveys' => 'সব জরিপ',
        'create_survey' => 'জরিপ তৈরি করুন',
        'templates' => 'টেমপ্লেট',
        'question_library' => 'প্রশ্ন লাইব্রেরি',
        'responses' => 'উত্তরসমূহ',
        'all_responses' => 'সব উত্তর',
        'review' => 'পর্যালোচনা',
        'export_history' => 'রপ্তানির ইতিহাস',
        'analytics' => 'অ্যানালিটিক্স',
        'survey_analytics' => 'জরিপ অ্যানালিটিক্স',
        'reports' => 'প্রতিবেদন',
        'respondents' => 'উত্তরদাতা',
        'api' => 'এপিআই',
        'api_keys' => 'এপিআই কী',
        'documentation' => 'ডকুমেন্টেশন',
        'webhooks' => 'ওয়েবহুক',
        'users_and_permissions' => 'ব্যবহারকারী ও অনুমতি',
        'audit_logs' => 'অডিট লগ',
        'settings' => 'সেটিংস',
        'settings_general' => 'সাধারণ',
        'settings_language' => 'ভাষা',
        'settings_appearance' => 'থিম',
        'settings_security' => 'নিরাপত্তা',
        'settings_notifications' => 'বিজ্ঞপ্তি',
        'settings_api' => 'এপিআই',
    ],

    // Theme switcher (spec §3.1)
    'theme' => [
        'light' => 'হালকা',
        'dark' => 'গাঢ়',
        'system' => 'সিস্টেম',
    ],

    // Language switcher (spec §4)
    'language' => [
        'english' => 'English',
        'bangla' => 'বাংলা',
    ],

    // System / flash messages
    'system' => [
        'generic_error' => 'কিছু একটা সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
        'saved' => 'সফলভাবে সংরক্ষিত হয়েছে।',
        'deleted' => 'সফলভাবে মুছে ফেলা হয়েছে।',
        'no_results' => 'কোনো ফলাফল পাওয়া যায়নি।',
        'loading' => 'লোড হচ্ছে...',
        'confirm_delete' => 'আপনি কি নিশ্চিত যে এটি মুছে ফেলতে চান? এই কাজটি পূর্বাবস্থায় ফেরানো যাবে না।',
        'session_expired' => 'আপনার সেশনের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে আবার লগ ইন করুন।',
    ],

    'welcome' => 'স্বাগতম, :name',

    // Auth screens (login, register, password reset, 2FA, email verification)
    'auth' => [
        'login_title' => 'লগ ইন',
        'email' => 'ইমেইল',
        'password' => 'পাসওয়ার্ড',
        'remember_me' => 'মনে রাখুন',
        'forgot_password' => 'পাসওয়ার্ড ভুলে গেছেন?',
        'create_account' => 'অ্যাকাউন্ট তৈরি করুন',
        'already_have_account' => 'ইতিমধ্যে অ্যাকাউন্ট আছে?',

        'register_title' => 'আপনার প্রতিষ্ঠান তৈরি করুন',
        'organization_name' => 'প্রতিষ্ঠানের নাম',
        'your_name' => 'আপনার নাম',
        'confirm_password' => 'পাসওয়ার্ড নিশ্চিত করুন',

        'forgot_password_title' => 'পাসওয়ার্ড ভুলে গেছেন?',
        'email_reset_link' => 'পাসওয়ার্ড রিসেট লিংক পাঠান',

        'reset_password_title' => 'আপনার পাসওয়ার্ড রিসেট করুন',
        'new_password' => 'নতুন পাসওয়ার্ড',
        'confirm_new_password' => 'নতুন পাসওয়ার্ড নিশ্চিত করুন',
        'reset_password_submit' => 'পাসওয়ার্ড রিসেট করুন',

        'confirm_password_title' => 'আপনার পাসওয়ার্ড নিশ্চিত করুন',
        'confirm_password_body' => 'এটি একটি স্পর্শকাতর কাজ — চালিয়ে যেতে অনুগ্রহ করে আপনার পাসওয়ার্ড নিশ্চিত করুন।',

        'two_factor_title' => 'দ্বি-স্তর যাচাইকরণ',
        'two_factor_body' => 'আপনার অথেনটিকেটর অ্যাপের কোড অথবা একটি রিকভারি কোড লিখুন।',
        'code' => 'কোড',
        'recovery_code' => 'অথবা একটি রিকভারি কোড',
        'verify' => 'যাচাই করুন',

        'verify_email_title' => 'আপনার ইমেইল যাচাই করুন',
        'verify_email_body' => 'আমরা আপনার ইমেইল ঠিকানায় একটি যাচাইকরণ লিংক পাঠিয়েছি। চালিয়ে যেতে সেটিতে ক্লিক করুন।',
        'verify_email_resent' => 'একটি নতুন যাচাইকরণ লিংক পাঠানো হয়েছে।',
        'resend_verification' => 'যাচাইকরণ ইমেইল আবার পাঠান',
        'two_factor_required_title' => 'দ্বি-স্তর যাচাইকরণ আবশ্যক',
        'two_factor_required_body' => 'আপনার ভূমিকার জন্য দ্বি-স্তর যাচাইকরণ প্রয়োজন। ড্যাশবোর্ডে যেতে এটি সেটআপ করুন।',
    ],

];

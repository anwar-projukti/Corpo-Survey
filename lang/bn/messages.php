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
        'previous' => 'পূর্ববর্তী',
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
        'skip_to_content' => 'মূল বিষয়বস্তুতে যান',
        'remove' => 'সরান',
        'register' => 'নিবন্ধন করুন',
    ],

    // ইউআই-তে দেখানো রোলের নাম (সাইডবার, প্রোফাইল) — কী নামগুলো
    // database/seeders/RolesAndPermissionsSeeder-এর Spatie রোল নামের সাথে
    // মিলিয়ে রাখা হয়েছে
    'roles' => [
        'Super Admin' => 'সিস্টেম অ্যাডমিন',
        'Org Admin' => 'অর্গ অ্যাডমিন',
        'Editor' => 'এডিটর',
        'Field User' => 'ফিল্ড ইউজার',
        'Viewer' => 'ভিউয়ার',
    ],

    // Primary navigation (spec §13)
    'nav' => [
        'primary_navigation' => 'প্রধান নেভিগেশন',
        'dashboard' => 'ড্যাশবোর্ড',
        'my_surveys' => 'আমার জরিপ',
        'surveys' => 'জরিপসমূহ',
        'all_surveys' => 'সব জরিপ',
        'create_survey' => 'জরিপ তৈরি করুন',
        'templates' => 'টেমপ্লেট',
        'question_library' => 'প্রশ্ন লাইব্রেরি',
        'all_responses' => 'সব উত্তর',
        'review' => 'পর্যালোচনা',
        'export_history' => 'রপ্তানির ইতিহাস',
        'user_productivity' => 'ব্যবহারকারীর উৎপাদনশীলতা',
        'import_respondents' => 'উত্তরদাতা আমদানি',
        'survey_analytics' => 'জরিপ অ্যানালিটিক্স',
        'reports' => 'প্রতিবেদন',
        'users_and_permissions' => 'ব্যবহারকারী ও অনুমতি',
        'organizations' => 'প্রতিষ্ঠানসমূহ',
        'landing_content' => 'ল্যান্ডিং পেজ',
        'pricing_plans' => 'মূল্য প্ল্যান',
        'trial_days_left' => '{0} ট্রায়াল আজ শেষ হচ্ছে|{1} ১ দিন বাকি|[2,*] :count দিন বাকি',
        'trial_expired' => 'ট্রায়াল শেষ হয়েছে',
        'help' => 'সহায়তা',
        // ভাষা/থিম হেডার ড্রপডাউনের অ্যাক্সেসিবিলিটি লেবেলে এখনও ব্যবহৃত হয়
        'settings_language' => 'ভাষা',
        'settings_appearance' => 'থিম',
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

    // "/" পাতায় লগ-আউট থাকা ভিজিটরদের জন্য পাবলিক ল্যান্ডিং পেজ
    'landing' => [
        'eyebrow' => 'গবেষণা ও মাঠপর্যায়ের কার্যক্রম দলের জন্য তৈরি',
        'tagline' => 'প্রশ্নপত্র থেকে ইনসাইট — মাঠপর্যায়ের জরিপ',
        'body' => 'জরিপ তৈরি করুন, অনলাইনে বা দ্বারে দ্বারে গিয়ে উত্তর সংগ্রহ করুন, এবং সেগুলোকে রিপোর্টে রূপান্তর করুন — সব এক জায়গায়।',
        'hero_footnote' => 'কয়েক মিনিটেই আপনার প্রতিষ্ঠান সেটআপ করুন — আলাদা কোনো ইনস্টলেশনের প্রয়োজন নেই।',
        'get_started' => 'শুরু করুন',
        'go_to_dashboard' => 'ড্যাশবোর্ডে যান',
        'feature_builder_title' => 'নমনীয় সার্ভে বিল্ডার',
        'feature_builder_body' => 'শাখা-লজিক, ভ্যালিডেশন এবং পুনঃব্যবহারযোগ্য প্রশ্ন টেমপ্লেট দিয়ে মাল্টি-পেজ জরিপ তৈরি করুন।',
        'feature_field_title' => 'দ্বারে দ্বারে মাঠ পর্যায়ের সংগ্রহ',
        'feature_field_body' => 'মাঠকর্মীরা অফলাইনেও চলার পথে উত্তর সংগ্রহ করতে পারেন, যা স্বয়ংক্রিয়ভাবে সিঙ্ক হয়ে যায়।',
        'feature_analytics_title' => 'রিয়েল-টাইম অ্যানালিটিক্স',
        'feature_analytics_body' => 'উত্তর আসার সাথে সাথে রেসপন্স রেট, ড্রপ-অফ পয়েন্ট এবং প্রশ্ন-ভিত্তিক ফলাফল দেখুন।',
        'features_title' => 'শুরু থেকে শেষ পর্যন্ত জরিপ পরিচালনার জন্য যা কিছু দরকার',
        'nav_features' => 'ফিচার',
        'nav_pricing' => 'মূল্য',
        'nav_about' => 'পরিচিতি',
        'nav_contact' => 'যোগাযোগ',
        'pricing_title' => 'সহজ, স্বচ্ছ মূল্য নির্ধারণ',
        'pricing_body' => 'ছোট করে শুরু করুন অথবা সম্পূর্ণ প্রতিষ্ঠানে স্কেল করুন — উপযুক্ত প্ল্যান বেছে নিন, অথবা কাস্টম কোটেশনের জন্য আমাদের সাথে কথা বলুন।',
        'pricing_cta' => 'মূল্যের জন্য যোগাযোগ করুন',
        'pricing_most_popular' => 'সবচেয়ে জনপ্রিয়',
        'pricing_trial_days' => '{1} :count দিনের ফ্রি ট্রায়াল|[2,*] :count দিনের ফ্রি ট্রায়াল',
        'about_title' => 'প্রশ্নপত্র থেকে ইনসাইট পর্যন্ত, একটি মাত্র সিস্টেম',
        'about_body' => 'Corpo Survey Management প্রতিষ্ঠানগুলোকে জরিপ ডিজাইন করতে, অনলাইনে বা দ্বারে দ্বারে গিয়ে উত্তর সংগ্রহ করতে এবং সেগুলোকে রিপোর্টে রূপান্তর করতে সাহায্য করে — সব এক জায়গায়। গবেষণা ও মাঠপর্যায়ের কার্যক্রম দলের জন্য তৈরি, যাদের জরিপ ডিজাইন, সরাসরি ডেটা সংগ্রহ এবং বিশ্লেষণ — সবকিছুর জন্য একটি মাত্র সিস্টেম প্রয়োজন।',
        'about_stat_response_types_label' => 'সংগ্রহের ধরন',
        'about_stat_response_types_value' => 'অনলাইন + মাঠপর্যায়',
        'about_stat_languages_label' => 'ভাষা',
        'about_stat_languages_value' => 'EN + বাংলা',
        'about_stat_offline_label' => 'মাঠপর্যায়ে সংগ্রহ',
        'about_stat_offline_value' => 'অফলাইনেও কাজ করে',
        'contact_title' => 'যোগাযোগ করুন',
        'contact_body' => 'আপনার প্রতিষ্ঠান সেটআপ নিয়ে প্রশ্ন আছে? ইমেইল, ফোন বা সরাসরি আমাদের অফিসে যোগাযোগ করুন।',
        'contact_email_label' => 'ইমেইল',
        'contact_phone_label' => 'ফোন',
        'contact_office_label' => 'অফিস',
        'contact_office_address' => "বাড়ি ১৮, রোড ৬\nগুলশান, ঢাকা",
    ],

    'onboarding' => [
        'title' => 'শুরু করুন',
        'body' => 'আপনার প্রথম জরিপে সত্যিকারের উত্তর সংগ্রহ শুরু করতে কয়েকটি ধাপ।',
        'step_create_survey' => 'আপনার প্রথম জরিপ তৈরি করুন',
        'step_publish_survey' => 'এটি প্রকাশ করুন',
        'step_publish_survey_body' => 'কেউ উত্তর দেওয়ার আগে জরিপটি অবশ্যই প্রকাশিত হতে হবে।',
        'step_invite_field_user' => 'একজন ফিল্ড ব্যবহারকারী আমন্ত্রণ করুন (ঐচ্ছিক)',
        'step_invite_field_user_body' => 'ফিল্ড ব্যবহারকারীরা আপনার পক্ষে দুয়ারে দুয়ারে গিয়ে উত্তর সংগ্রহ করতে পারেন।',
        'step_first_response' => 'আপনার প্রথম উত্তর সংগ্রহ করুন',
        'stat_surveys' => 'জরিপসমূহ',
        'stat_responses' => 'মোট উত্তর',
        'stat_organizations' => 'প্রতিষ্ঠানসমূহ',
        'stat_users' => 'ব্যবহারকারী',
    ],

    'dashboard' => [
        'view_all' => 'সব দেখুন',
        'stat_published' => 'প্রকাশিত জরিপ',
        'stat_field_users' => 'মাঠকর্মী',
        'quick_actions' => 'দ্রুত কার্যক্রম',
        'recent_surveys' => 'সাম্প্রতিক জরিপ',
        'no_surveys_title' => 'এখনও কোনো জরিপ নেই',
        'no_surveys_body' => 'উত্তর সংগ্রহ শুরু করতে আপনার প্রথম জরিপ তৈরি করুন।',
        'recent_organizations' => 'সাম্প্রতিক প্রতিষ্ঠান',
        'no_organizations' => 'এখনও কোনো প্রতিষ্ঠান নেই।',
        'response_count' => '{0} কোনো উত্তর নেই|{1} ১টি উত্তর|[2,*] :countটি উত্তর',
        'survey_count' => '{0} কোনো জরিপ নেই|{1} ১টি জরিপ|[2,*] :countটি জরিপ',
        'user_count' => '{0} কোনো ব্যবহারকারী নেই|{1} ১ জন ব্যবহারকারী|[2,*] :count জন ব্যবহারকারী',
    ],

    // Auth screens (login, register, password reset, 2FA, email verification)
    'auth' => [
        'login_title' => 'লগ ইন',
        'admin_login_title' => 'সিস্টেম অ্যাডমিন লগ ইন',
        'org_login_title' => 'অর্গানাইজেশন অ্যাডমিন লগ ইন',
        'email' => 'ইমেইল',
        'password' => 'পাসওয়ার্ড',
        'remember_me' => 'মনে রাখুন',
        'forgot_password' => 'পাসওয়ার্ড ভুলে গেছেন?',
        'create_account' => 'অ্যাকাউন্ট তৈরি করুন',
        'already_have_account' => 'ইতিমধ্যে অ্যাকাউন্ট আছে?',
        'login_link' => 'লগ ইন করুন',

        'register_title' => 'আপনার প্রতিষ্ঠান তৈরি করুন',
        'organization_name' => 'প্রতিষ্ঠানের নাম',
        'your_name' => 'আপনার নাম',
        'confirm_password' => 'পাসওয়ার্ড নিশ্চিত করুন',
        'selected_plan' => 'নির্বাচিত প্ল্যান',
        'change_plan' => 'প্ল্যান পরিবর্তন করুন',
        'registered_please_login' => 'অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে। চালিয়ে যেতে লগ ইন করুন।',

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

        'two_factor_setup_intro' => 'আপনার ফোনে একটি অথেনটিকেটর অ্যাপ (Google Authenticator, Authy, 1Password ইত্যাদি) প্রয়োজন হবে।',
        'two_factor_enable_button' => 'দ্বি-স্তর যাচাইকরণ সেটআপ করুন',
        'two_factor_scan_instructions' => 'আপনার অথেনটিকেটর অ্যাপ দিয়ে এই QR কোডটি স্ক্যান করুন, তারপর এটি যে ৬-সংখ্যার কোড দেখায় তা লিখুন।',
        'two_factor_manual_setup' => 'স্ক্যান করতে পারছেন না? কোডটি নিজে লিখুন',
        'two_factor_recovery_intro' => 'এই রিকভারি কোডগুলো নিরাপদ জায়গায় সংরক্ষণ করুন। আপনার অথেনটিকেটর অ্যাপে প্রবেশাধিকার হারালে প্রতিটি কোড একবার ব্যবহার করে সাইন ইন করতে পারবেন।',
        'two_factor_continue_to_dashboard' => 'ড্যাশবোর্ডে চালিয়ে যান',
        'two_factor_invalid_code' => 'কোডটি ভুল অথবা মেয়াদ শেষ হয়ে গেছে। আবার চেষ্টা করুন।',
        'two_factor_generic_error' => 'কিছু ভুল হয়েছে। আবার চেষ্টা করুন।',
    ],

    // Impersonation banner (spec §13 "System Admin")
    'impersonation' => [
        'banner' => 'আপনি এই প্রতিষ্ঠানটি :name হিসেবে দেখছেন।',
        'return' => 'সিস্টেম অ্যাডমিনে ফিরে যান',
    ],

];

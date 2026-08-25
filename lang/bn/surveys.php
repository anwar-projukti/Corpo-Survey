<?php

return [

    'status' => [
        'draft' => 'খসড়া',
        'published' => 'প্রকাশিত',
        'closed' => 'বন্ধ',
        'archived' => 'সংরক্ষণাগারভুক্ত',
    ],

    'response_status' => [
        'partial' => 'আংশিক',
        'complete' => 'সম্পূর্ণ',
    ],

    'collection_method' => [
        'self_serve' => 'স্ব-উত্তরদান',
        'field_agent' => 'ফিল্ড এজেন্ট',
    ],

    // Question types (spec §6.1)
    'question_types' => [
        'short_text' => 'সংক্ষিপ্ত টেক্সট',
        'long_text' => 'দীর্ঘ টেক্সট',
        'number' => 'সংখ্যা',
        'email' => 'ইমেইল',
        'phone' => 'ফোন',
        'url' => 'ইউআরএল',
        'single_choice' => 'একক পছন্দ',
        'multiple_choice' => 'একাধিক পছন্দ',
        'dropdown' => 'ড্রপডাউন',
        'searchable_dropdown' => 'অনুসন্ধানযোগ্য ড্রপডাউন',
        'yes_no' => 'হ্যাঁ/না',
        'star' => 'স্টার রেটিং',
        'numeric_rating' => 'সাংখ্যিক রেটিং',
        'emoji' => 'ইমোজি রেটিং',
        'likert' => 'লিকার্ট স্কেল',
        'nps' => 'নেট প্রোমোটার স্কোর',
        'satisfaction' => 'সন্তুষ্টি',
        'matrix' => 'ম্যাট্রিক্স',
        'ranking' => 'র‍্যাঙ্কিং',
        'slider' => 'স্লাইডার',
        'range' => 'রেঞ্জ',
        'image_choice' => 'ছবি পছন্দ',
        'date' => 'তারিখ',
        'time' => 'সময়',
        'date_time' => 'তারিখ ও সময়',
        'file_upload' => 'ফাইল আপলোড',
        'image_upload' => 'ছবি আপলোড',
        'signature' => 'স্বাক্ষর',
        'country' => 'দেশ',
        'division' => 'বিভাগ',
        'district' => 'জেলা',
        'city' => 'শহর',
        'address' => 'ঠিকানা',
        'map_location' => 'মানচিত্র অবস্থান',
    ],

    // Public survey submission access modes (spec §5.8)
    'access_mode' => [
        'multiple_responses' => 'একাধিক উত্তর দেওয়ার অনুমতি',
        'one_response_per_user' => 'প্রতি ব্যবহারকারী একটি উত্তর',
        'require_authentication' => 'লগ ইন আবশ্যক',
        'require_otp' => 'ওটিপি আবশ্যক',
        'anonymous' => 'বেনামী উত্তর',
    ],

];

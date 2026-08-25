<?php

return [

    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'closed' => 'Closed',
        'archived' => 'Archived',
    ],

    'response_status' => [
        'partial' => 'Partial',
        'complete' => 'Complete',
    ],

    'collection_method' => [
        'self_serve' => 'Self-serve',
        'field_agent' => 'Field agent',
    ],

    // Question types (spec §6.1)
    'question_types' => [
        'short_text' => 'Short Text',
        'long_text' => 'Long Text',
        'number' => 'Number',
        'email' => 'Email',
        'phone' => 'Phone',
        'url' => 'URL',
        'single_choice' => 'Single Choice',
        'multiple_choice' => 'Multiple Choice',
        'dropdown' => 'Dropdown',
        'searchable_dropdown' => 'Searchable Dropdown',
        'yes_no' => 'Yes/No',
        'star' => 'Star Rating',
        'numeric_rating' => 'Numeric Rating',
        'emoji' => 'Emoji Rating',
        'likert' => 'Likert Scale',
        'nps' => 'Net Promoter Score',
        'satisfaction' => 'Satisfaction',
        'matrix' => 'Matrix',
        'ranking' => 'Ranking',
        'slider' => 'Slider',
        'range' => 'Range',
        'image_choice' => 'Image Choice',
        'date' => 'Date',
        'time' => 'Time',
        'date_time' => 'Date & Time',
        'file_upload' => 'File Upload',
        'image_upload' => 'Image Upload',
        'signature' => 'Signature',
        'country' => 'Country',
        'division' => 'Division/State',
        'district' => 'District',
        'city' => 'City',
        'address' => 'Address',
        'map_location' => 'Map Location',
    ],

    // Public survey submission access modes (spec §5.8)
    'access_mode' => [
        'multiple_responses' => 'Allow multiple responses',
        'one_response_per_user' => 'One response per user',
        'require_authentication' => 'Require authentication',
        'require_otp' => 'Require OTP',
        'anonymous' => 'Anonymous response',
    ],

];

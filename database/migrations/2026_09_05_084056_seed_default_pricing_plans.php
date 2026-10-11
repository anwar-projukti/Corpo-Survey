<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Default content so the public pricing section isn't empty the
        // moment this feature ships — a Super Admin can edit or delete
        // these from /pricing-plans like any other plan.
        if (DB::table('pricing_plans')->exists()) {
            return;
        }

        DB::table('pricing_plans')->insert([
            [
                'name_en' => 'Basic',
                'name_bn' => 'বেসিক',
                'price_en' => '$29/mo',
                'price_bn' => '৳২,৫০০/মাস',
                'description_en' => 'For small teams getting started with survey collection.',
                'description_bn' => 'জরিপ সংগ্রহ শুরু করা ছোট দলের জন্য।',
                'features_en' => json_encode([
                    'Up to 5 active surveys',
                    '500 responses per month',
                    'Online + field collection',
                    'Email support',
                ]),
                'features_bn' => json_encode([
                    '৫টি পর্যন্ত সক্রিয় জরিপ',
                    'মাসে ৫০০টি রেসপন্স',
                    'অনলাইন + মাঠপর্যায়ে সংগ্রহ',
                    'ইমেইল সাপোর্ট',
                ]),
                'button_text_en' => 'Get started',
                'button_text_bn' => 'শুরু করুন',
                'button_target' => 'register',
                'highlighted' => false,
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_en' => 'Enterprise',
                'name_bn' => 'এন্টারপ্রাইজ',
                'price_en' => 'Contact us',
                'price_bn' => 'যোগাযোগ করুন',
                'description_en' => 'For large organizations with advanced needs.',
                'description_bn' => 'উন্নত চাহিদাসম্পন্ন বড় প্রতিষ্ঠানের জন্য।',
                'features_en' => json_encode([
                    'Unlimited surveys and responses',
                    'Unlimited field users',
                    'Dedicated support',
                    'Custom onboarding',
                ]),
                'features_bn' => json_encode([
                    'সীমাহীন জরিপ ও রেসপন্স',
                    'সীমাহীন মাঠকর্মী',
                    'ডেডিকেটেড সাপোর্ট',
                    'কাস্টম অনবোর্ডিং',
                ]),
                'button_text_en' => 'Contact sales',
                'button_text_bn' => 'সেলসের সাথে যোগাযোগ করুন',
                'button_target' => 'contact',
                'highlighted' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pricing_plans')
            ->whereIn('name_en', ['Basic', 'Enterprise'])
            ->delete();
    }
};

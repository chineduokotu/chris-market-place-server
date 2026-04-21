<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // 1. Digital Services
            ['name' => 'Web Development', 'slug' => 'web-development', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'Mobile App Development', 'slug' => 'mobile-app-development', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'UI/UX Design', 'slug' => 'ui-ux-design', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'Graphic Design', 'slug' => 'graphic-design', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'E-commerce Development', 'slug' => 'ecommerce-development', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'Website Maintenance', 'slug' => 'website-maintenance', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'Cybersecurity', 'slug' => 'cybersecurity', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'Data Analysis', 'slug' => 'data-analysis', 'group_name' => 'Digital Services', 'icon' => '💻'],
            ['name' => 'AI / Chatbot Development', 'slug' => 'ai-chatbot-development', 'group_name' => 'Digital Services', 'icon' => '💻'],

            // 2. Marketing & Sales
            ['name' => 'Social Media Management', 'slug' => 'social-media-management', 'group_name' => 'Marketing & Sales', 'icon' => '📢'],
            ['name' => 'Digital Marketing', 'slug' => 'digital-marketing', 'group_name' => 'Marketing & Sales', 'icon' => '📢'],
            ['name' => 'SEO Services', 'slug' => 'seo-services', 'group_name' => 'Marketing & Sales', 'icon' => '📢'],
            ['name' => 'Content Marketing', 'slug' => 'content-marketing', 'group_name' => 'Marketing & Sales', 'icon' => '📢'],
            ['name' => 'Email Marketing', 'slug' => 'email-marketing', 'group_name' => 'Marketing & Sales', 'icon' => '📢'],
            ['name' => 'Influencer Marketing', 'slug' => 'influencer-marketing', 'group_name' => 'Marketing & Sales', 'icon' => '📢'],

            // 3. Writing & Translation
            ['name' => 'Copywriting', 'slug' => 'copywriting', 'group_name' => 'Writing & Translation', 'icon' => '✍️'],
            ['name' => 'Blog Writing', 'slug' => 'blog-writing', 'group_name' => 'Writing & Translation', 'icon' => '✍️'],
            ['name' => 'Resume / CV Writing', 'slug' => 'resume-cv-writing', 'group_name' => 'Writing & Translation', 'icon' => '✍️'],
            ['name' => 'Proofreading & Editing', 'slug' => 'proofreading-editing', 'group_name' => 'Writing & Translation', 'icon' => '✍️'],
            ['name' => 'Translation', 'slug' => 'translation', 'group_name' => 'Writing & Translation', 'icon' => '✍️'],

            // 4. Creative & Media
            ['name' => 'Photography', 'slug' => 'photography', 'group_name' => 'Creative & Media', 'icon' => '🎥'],
            ['name' => 'Video Editing', 'slug' => 'video-editing', 'group_name' => 'Creative & Media', 'icon' => '🎥'],
            ['name' => 'Animation', 'slug' => 'animation', 'group_name' => 'Creative & Media', 'icon' => '🎥'],
            ['name' => 'Voice Over', 'slug' => 'voice-over', 'group_name' => 'Creative & Media', 'icon' => '🎥'],
            ['name' => 'Music Production', 'slug' => 'music-production', 'group_name' => 'Creative & Media', 'icon' => '🎥'],
            ['name' => 'Podcast Editing', 'slug' => 'podcast-editing', 'group_name' => 'Creative & Media', 'icon' => '🎥'],

            // 5. Home Services
            ['name' => 'Cleaning', 'slug' => 'cleaning', 'group_name' => 'Home Services', 'icon' => '🏠'],
            ['name' => 'Plumbing', 'slug' => 'plumbing', 'group_name' => 'Home Services', 'icon' => '🏠'],
            ['name' => 'Electrical', 'slug' => 'electrical', 'group_name' => 'Home Services', 'icon' => '🏠'],
            ['name' => 'AC Repair', 'slug' => 'ac-repair', 'group_name' => 'Home Services', 'icon' => '🏠'],
            ['name' => 'Generator Repair', 'slug' => 'generator-repair', 'group_name' => 'Home Services', 'icon' => '🏠'],
            ['name' => 'Carpentry / Furniture', 'slug' => 'carpentry-furniture', 'group_name' => 'Home Services', 'icon' => '🏠'],
            ['name' => 'Interior Design', 'slug' => 'interior-design', 'group_name' => 'Home Services', 'icon' => '🏠'],
            ['name' => 'Pest Control', 'slug' => 'pest-control', 'group_name' => 'Home Services', 'icon' => '🏠'],

            // 6. Logistics & Errands
            ['name' => 'Delivery Services', 'slug' => 'delivery-services', 'group_name' => 'Logistics & Errands', 'icon' => '🚚'],
            ['name' => 'Errand Running', 'slug' => 'errand-running', 'group_name' => 'Logistics & Errands', 'icon' => '🚚'],
            ['name' => 'Moving / Relocation', 'slug' => 'moving-relocation', 'group_name' => 'Logistics & Errands', 'icon' => '🚚'],

            // 7. Business Services
            ['name' => 'Business Registration', 'slug' => 'business-registration', 'group_name' => 'Business Services', 'icon' => '💼'],
            ['name' => 'Accounting / Bookkeeping', 'slug' => 'accounting-bookkeeping', 'group_name' => 'Business Services', 'icon' => '💼'],
            ['name' => 'Legal Services', 'slug' => 'legal-services', 'group_name' => 'Business Services', 'icon' => '💼'],
            ['name' => 'Virtual Assistant', 'slug' => 'virtual-assistant', 'group_name' => 'Business Services', 'icon' => '💼'],
            ['name' => 'HR / Recruitment', 'slug' => 'hr-recruitment', 'group_name' => 'Business Services', 'icon' => '💼'],

            // 8. Education & Training
            ['name' => 'Tutoring', 'slug' => 'tutoring', 'group_name' => 'Education & Training', 'icon' => '📚'],
            ['name' => 'Exam Prep (WAEC, JAMB)', 'slug' => 'exam-prep', 'group_name' => 'Education & Training', 'icon' => '📚'],
            ['name' => 'Online Courses', 'slug' => 'online-courses', 'group_name' => 'Education & Training', 'icon' => '📚'],
            ['name' => 'Career Coaching', 'slug' => 'career-coaching', 'group_name' => 'Education & Training', 'icon' => '📚'],
            ['name' => 'Interview Preparation', 'slug' => 'interview-preparation', 'group_name' => 'Education & Training', 'icon' => '📚'],

            // 9. Lifestyle & Personal
            ['name' => 'Fitness Training', 'slug' => 'fitness-training', 'group_name' => 'Lifestyle & Personal', 'icon' => '💅'],
            ['name' => 'Makeup Artist', 'slug' => 'makeup-artist', 'group_name' => 'Lifestyle & Personal', 'icon' => '💅'],
            ['name' => 'Fashion / Tailoring', 'slug' => 'fashion-tailoring', 'group_name' => 'Lifestyle & Personal', 'icon' => '💅'],
            ['name' => 'Event Planning', 'slug' => 'event-planning', 'group_name' => 'Lifestyle & Personal', 'icon' => '💅'],
            ['name' => 'Personal Coaching', 'slug' => 'personal-coaching', 'group_name' => 'Lifestyle & Personal', 'icon' => '💅'],
        ];

        Category::query()->delete();

        // Reassign all existing services to category ID 1 to prevent errors since we truncated
        // Normally this would be a bad idea, but this is a dev DB
        \App\Models\Service::query()->update(['category_id' => 1]);

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}

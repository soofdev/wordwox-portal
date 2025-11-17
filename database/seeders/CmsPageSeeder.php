<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CmsPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the default org and portal (SuperHero CrossFit)
        $orgId = 8; // SuperHero CrossFit org_id
        $portalId = 1; // Default portal

        // Clear existing CMS data first
        $this->command->info('Clearing existing CMS data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cms_sections')->delete();
        DB::table('cms_pages')->where('org_id', $orgId)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create Homepage
        $homePage = CmsPage::create([
            'uuid' => Str::uuid(),
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Welcome to SuperHero CrossFit',
            'slug' => 'home',
            'description' => 'Unleash your inner strength at SuperHero CrossFit - where everyone has the potential to achieve greatness.',
            'content' => '',
            'status' => 'published',
            'type' => 'home',
            'is_homepage' => true,
            'show_in_navigation' => false,
            'sort_order' => 1,
            'seo_title' => 'SuperHero CrossFit - Unleash Your Inner Strength',
            'seo_description' => 'Join SuperHero CrossFit and transform your fitness journey. Expert trainers, state-of-the-art facilities, and supportive community await you.',
            'seo_keywords' => 'crossfit, fitness, gym, superhero, training, strength, workout',
            'template' => 'home',
            'published_at' => time(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // Create Homepage Sections
        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $homePage->id,
            'name' => 'Hero Section',
            'type' => 'hero',
            'title' => 'Unleash Your Inner Strength: Train, Transform, Triumph!',
            'subtitle' => 'Where everyone has the potential to achieve greatness',
            'content' => 'Welcome to Superhero CrossFit, where we believe everyone has the potential to achieve greatness.',
            'settings' => json_encode([
                'background_color' => '#1f2937',
                'text_color' => '#ffffff',
                'button_style' => 'primary'
            ]),
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $homePage->id,
            'name' => 'Welcome Content',
            'type' => 'content',
            'title' => 'Transform Your Fitness Journey',
            'content' => 'Our state-of-the-art facilities, expert trainers, and supportive community are here to help you unleash your inner strength. Whether you\'re just starting your fitness journey or you\'re a seasoned athlete, our diverse range of classes and personalized training programs will empower you to train hard, transform your body, and triumph over any challenge. Join us today and experience the transformation you\'ve always dreamed of!',
            'sort_order' => 2,
            'is_active' => true,
            'is_visible' => true,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $homePage->id,
            'name' => 'Call to Action',
            'type' => 'cta',
            'title' => 'Ready to Start Your Journey?',
            'content' => 'Join our community of fitness enthusiasts and start your transformation today.',
            'data' => json_encode([
                'buttons' => [
                    ['text' => 'View All Packages', 'url' => '/packages', 'style' => 'primary'],
                    ['text' => 'Browse Classes', 'url' => '/schedule', 'style' => 'secondary'],
                    ['text' => 'Call Us', 'url' => 'tel:+1234567890', 'style' => 'outline'],
                    ['text' => 'Email Us', 'url' => 'mailto:info@superhero.wodworx.com', 'style' => 'outline']
                ]
            ]),
            'sort_order' => 3,
            'is_active' => true,
            'is_visible' => true,
        ]);

        // Create About Us Page
        $aboutPage = CmsPage::create([
            'uuid' => Str::uuid(),
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'About Us',
            'slug' => 'about-us',
            'description' => 'Learn about SuperHero CrossFit\'s mission, values, and commitment to helping you achieve your fitness goals.',
            'content' => '',
            'status' => 'published',
            'type' => 'page',
            'is_homepage' => false,
            'show_in_navigation' => true,
            'sort_order' => 2,
            'seo_title' => 'About SuperHero CrossFit - Our Story & Mission',
            'seo_description' => 'Discover the story behind SuperHero CrossFit, our mission to empower fitness transformations, and our commitment to community.',
            'template' => 'default',
            'published_at' => time(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $aboutPage->id,
            'name' => 'Our Story',
            'type' => 'content',
            'title' => 'Our Story',
            'content' => '<p>SuperHero CrossFit was founded with a simple belief: everyone has the potential to be their own superhero. Our journey began when our founders realized that traditional gyms weren\'t providing the community, support, and results that people truly needed.</p><p>We created a space where fitness meets fun, where challenges become victories, and where every member is part of a supportive family. Our state-of-the-art facility is equipped with the latest CrossFit equipment, and our certified trainers are passionate about helping you reach your goals safely and effectively.</p>',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $aboutPage->id,
            'name' => 'Our Mission',
            'type' => 'content',
            'title' => 'Our Mission',
            'content' => '<p>At SuperHero CrossFit, our mission is to empower individuals to unlock their full potential through functional fitness, community support, and personal growth. We believe that fitness is not just about physical transformation—it\'s about building confidence, resilience, and a mindset that carries over into every aspect of life.</p><p>We are committed to:</p><ul><li>Providing safe, effective, and scalable workouts for all fitness levels</li><li>Creating an inclusive and supportive community environment</li><li>Helping members achieve their personal fitness goals</li><li>Promoting overall health and wellness</li><li>Making fitness fun and engaging</li></ul>',
            'sort_order' => 2,
            'is_active' => true,
            'is_visible' => true,
        ]);

        // Create Packages Page
        $packagesPage = CmsPage::create([
            'uuid' => Str::uuid(),
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Membership Packages',
            'slug' => 'packages',
            'description' => 'Choose the perfect membership package for your fitness journey. Flexible options for every lifestyle and budget.',
            'content' => '',
            'status' => 'published',
            'type' => 'page',
            'is_homepage' => false,
            'show_in_navigation' => true,
            'sort_order' => 3,
            'seo_title' => 'Membership Packages - SuperHero CrossFit',
            'seo_description' => 'Explore our flexible membership packages designed for every fitness level and budget. Join SuperHero CrossFit today!',
            'template' => 'packages',
            'published_at' => time(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $packagesPage->id,
            'name' => 'Package Introduction',
            'type' => 'content',
            'title' => 'Choose Your SuperHero Package',
            'content' => '<p>Whether you\'re just starting your fitness journey or you\'re a seasoned athlete, we have the perfect package for you. All memberships include access to our expert coaching, supportive community, and state-of-the-art facilities.</p>',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $packagesPage->id,
            'name' => 'Membership Options',
            'type' => 'pricing',
            'title' => 'Membership Options',
            'content' => 'Choose from our flexible membership options designed to fit your schedule and budget.',
            'data' => json_encode([
                'packages' => [
                    [
                        'name' => 'Starter Hero',
                        'price' => '$99',
                        'period' => 'month',
                        'features' => ['4 classes per month', 'Basic nutrition guidance', 'Community support', 'Equipment orientation'],
                        'popular' => false
                    ],
                    [
                        'name' => 'Super Hero',
                        'price' => '$159',
                        'period' => 'month',
                        'features' => ['Unlimited classes', 'Personal training session', 'Nutrition coaching', 'Priority booking', 'Guest passes'],
                        'popular' => true
                    ],
                    [
                        'name' => 'Elite Hero',
                        'price' => '$199',
                        'period' => 'month',
                        'features' => ['Unlimited classes', 'Weekly personal training', 'Custom meal plans', 'Body composition analysis', 'Unlimited guest passes'],
                        'popular' => false
                    ]
                ]
            ]),
            'sort_order' => 2,
            'is_active' => true,
            'is_visible' => true,
        ]);

        // Create Contact Page
        $contactPage = CmsPage::create([
            'uuid' => Str::uuid(),
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Contact Us',
            'slug' => 'contact-us',
            'description' => 'Get in touch with SuperHero CrossFit. Visit us, call us, or send us a message - we\'re here to help!',
            'content' => '',
            'status' => 'published',
            'type' => 'contact',
            'is_homepage' => false,
            'show_in_navigation' => true,
            'sort_order' => 4,
            'seo_title' => 'Contact SuperHero CrossFit - Get In Touch',
            'seo_description' => 'Contact SuperHero CrossFit for membership information, class schedules, or any questions. We\'re here to help you start your fitness journey.',
            'template' => 'contact',
            'published_at' => time(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $contactPage->id,
            'name' => 'Contact Information',
            'type' => 'contact_info',
            'title' => 'Get In Touch',
            'content' => '<p>Ready to start your superhero transformation? We\'d love to hear from you! Whether you have questions about our classes, want to schedule a tour, or need help choosing the right membership package, our team is here to help.</p>',
            'data' => json_encode([
                'address' => '123 Hero Street, Fitness City, FC 12345',
                'phone' => '+1 (555) 123-HERO',
                'email' => 'info@superhero.wodworx.com',
                'hours' => [
                    'Monday - Friday: 5:00 AM - 10:00 PM',
                    'Saturday: 7:00 AM - 8:00 PM',
                    'Sunday: 8:00 AM - 6:00 PM'
                ]
            ]),
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $contactPage->id,
            'name' => 'Contact Form',
            'type' => 'contact_form',
            'title' => 'Send Us a Message',
            'content' => 'Fill out the form below and we\'ll get back to you as soon as possible.',
            'data' => json_encode([
                'fields' => [
                    ['name' => 'name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'required' => true],
                    ['name' => 'phone', 'label' => 'Phone Number', 'type' => 'tel', 'required' => false],
                    ['name' => 'interest', 'label' => 'I\'m interested in', 'type' => 'select', 'options' => ['General Information', 'Membership Options', 'Personal Training', 'Group Classes', 'Corporate Programs'], 'required' => true],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true]
                ],
                'submit_text' => 'Send Message',
                'success_message' => 'Thank you for your message! We\'ll get back to you within 24 hours.'
            ]),
            'sort_order' => 2,
            'is_active' => true,
            'is_visible' => true,
        ]);

        // Create Coaches Page
        $coachesPage = CmsPage::create([
            'uuid' => Str::uuid(),
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Our Coaches',
            'slug' => 'coaches',
            'description' => 'Meet our expert team of certified CrossFit trainers who are passionate about helping you achieve your fitness goals.',
            'content' => '',
            'status' => 'published',
            'type' => 'page',
            'is_homepage' => false,
            'show_in_navigation' => true,
            'sort_order' => 5,
            'seo_title' => 'Expert CrossFit Coaches - SuperHero CrossFit',
            'seo_description' => 'Meet our certified CrossFit trainers and fitness experts who will guide you on your transformation journey.',
            'template' => 'coaches',
            'published_at' => time(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $coachesPage->id,
            'name' => 'Coaches Introduction',
            'type' => 'content',
            'title' => 'Meet Your SuperHero Team',
            'content' => '<p>Our certified coaches are the heart of SuperHero CrossFit. Each brings unique expertise, passion, and dedication to helping you achieve your fitness goals safely and effectively. They\'re not just trainers—they\'re your partners in transformation.</p>',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $coachesPage->id,
            'name' => 'Coach Profiles',
            'type' => 'team',
            'title' => 'Our Expert Coaches',
            'content' => 'Get to know the amazing coaches who will guide your fitness journey.',
            'data' => json_encode([
                'coaches' => [
                    [
                        'name' => 'Sarah Johnson',
                        'title' => 'Head Coach & Owner',
                        'certifications' => ['CrossFit Level 3 Trainer', 'Nutrition Specialist', 'Olympic Lifting Coach'],
                        'bio' => 'Sarah founded SuperHero CrossFit with a vision to create a supportive community where everyone can achieve their fitness goals. With over 8 years of CrossFit experience and a background in sports nutrition, she specializes in helping members build sustainable healthy habits.',
                        'specialties' => ['Olympic Lifting', 'Nutrition Coaching', 'Beginner Programs']
                    ],
                    [
                        'name' => 'Mike Rodriguez',
                        'title' => 'Senior Coach',
                        'certifications' => ['CrossFit Level 2 Trainer', 'Gymnastics Specialist', 'Mobility Coach'],
                        'bio' => 'Mike brings incredible energy and expertise to every class. His background in gymnastics and focus on movement quality helps members perform exercises safely while maximizing results. He\'s passionate about helping people overcome their fitness fears.',
                        'specialties' => ['Gymnastics Movements', 'Mobility & Flexibility', 'Injury Prevention']
                    ],
                    [
                        'name' => 'Emily Chen',
                        'title' => 'Coach & Nutritionist',
                        'certifications' => ['CrossFit Level 2 Trainer', 'Registered Dietitian', 'Precision Nutrition Coach'],
                        'bio' => 'Emily combines her love for CrossFit with her expertise in nutrition to help members achieve complete wellness. She believes that fitness and nutrition go hand in hand and works with members to develop sustainable lifestyle changes.',
                        'specialties' => ['Nutrition Coaching', 'Weight Management', 'Metabolic Conditioning']
                    ]
                ]
            ]),
            'sort_order' => 2,
            'is_active' => true,
            'is_visible' => true,
        ]);

        // Create Schedule Page
        $schedulePage = CmsPage::create([
            'uuid' => Str::uuid(),
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Class Schedule',
            'slug' => 'schedule',
            'description' => 'View our weekly class schedule and find the perfect time to train. Classes for all fitness levels available throughout the day.',
            'content' => '',
            'status' => 'published',
            'type' => 'page',
            'is_homepage' => false,
            'show_in_navigation' => true,
            'sort_order' => 6,
            'seo_title' => 'Class Schedule - SuperHero CrossFit',
            'seo_description' => 'Check out our weekly CrossFit class schedule. Find the perfect time to train with classes available throughout the day.',
            'template' => 'schedule',
            'published_at' => time(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        CmsSection::create([
            'uuid' => Str::uuid(),
            'cms_page_id' => $schedulePage->id,
            'name' => 'Schedule Information',
            'type' => 'content',
            'title' => 'Find Your Perfect Class Time',
            'content' => '<p>We offer classes throughout the day to fit your busy schedule. All classes are scalable to your fitness level, and our coaches will help modify exercises as needed. New to CrossFit? Try our beginner-friendly classes or schedule a free consultation.</p><p><strong>Booking:</strong> Members can book classes through our mobile app or online portal. We recommend booking in advance as popular time slots fill up quickly!</p>',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);

        $this->command->info('CMS pages and sections created successfully!');
        $this->command->info('Created pages: Home, About Us, Packages, Contact Us, Coaches, Schedule');
        $this->command->info('Total sections created: ' . CmsSection::count());
    }
}

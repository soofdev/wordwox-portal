<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\OrgPortal;
use Illuminate\Console\Command;

class SeedCmsPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:seed-pages {--org_id=} {--portal_id=} {--migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed CMS pages with default content for portals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('migrate')) {
            $this->info('Running migrations first...');
            $this->call('migrate');
        }

        $orgId = $this->option('org_id');
        $portalId = $this->option('portal_id');

        if (!$orgId || !$portalId) {
            $this->error('Please provide --org_id and --portal_id options');
            $this->info('Available portals:');
            
            $portals = OrgPortal::active()->get();
            foreach ($portals as $portal) {
                $this->line("Portal ID: {$portal->id}, Org ID: {$portal->org_id}, Subdomain: {$portal->subdomain}");
            }
            
            return 1;
        }

        $portal = OrgPortal::where('id', $portalId)
                          ->where('org_id', $orgId)
                          ->first();

        if (!$portal) {
            $this->error("Portal not found with ID: {$portalId} and Org ID: {$orgId}");
            return 1;
        }

        $this->info("Creating CMS pages for portal: {$portal->subdomain} (ID: {$portal->id})");

        // Create default pages
        $this->createHomePage($orgId, $portalId);
        $this->createAboutPage($orgId, $portalId);
        $this->createContactPage($orgId, $portalId);
        $this->createServicesPage($orgId, $portalId);

        $this->info('CMS pages created successfully!');
        
        // Show created pages
        $pages = CmsPage::where('org_id', $orgId)
                       ->where('orgPortal_id', $portalId)
                       ->get();
                       
        $this->info("Created {$pages->count()} pages:");
        foreach ($pages as $page) {
            $this->line("- {$page->title} ({$page->type}) - /{$page->slug}");
        }

        return 0;
    }

    private function createHomePage($orgId, $portalId)
    {
        $page = CmsPage::create([
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Home',
            'slug' => 'home',
            'description' => 'Welcome to our portal',
            'content' => '<h1>Welcome to Our Portal</h1><p>This is the homepage content.</p>',
            'status' => 'published',
            'type' => 'home',
            'is_homepage' => true,
            'show_in_navigation' => true,
            'sort_order' => 1,
            'seo_title' => 'Home - Welcome to Our Portal',
            'seo_description' => 'Welcome to our portal homepage',
            'template' => 'home',
            'published_at' => now(),
        ]);

        // Create sections for home page
        CmsSection::create([
            'cms_page_id' => $page->id,
            'name' => 'Hero Section',
            'type' => 'hero',
            'title' => 'Welcome to Our Portal',
            'subtitle' => 'Your gateway to excellence',
            'content' => '<p>Discover amazing features and services.</p>',
            'settings' => [
                'background_color' => '#f8f9fa',
                'text_color' => '#333333',
                'button_text' => 'Get Started',
                'button_url' => '/about'
            ],
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);

        CmsSection::create([
            'cms_page_id' => $page->id,
            'name' => 'Features Section',
            'type' => 'features',
            'title' => 'Our Features',
            'content' => '<p>Explore what makes us unique.</p>',
            'data' => [
                'features' => [
                    ['title' => 'Feature 1', 'description' => 'Amazing feature description'],
                    ['title' => 'Feature 2', 'description' => 'Another great feature'],
                    ['title' => 'Feature 3', 'description' => 'One more fantastic feature'],
                ]
            ],
            'sort_order' => 2,
            'is_active' => true,
            'is_visible' => true,
        ]);
    }

    private function createAboutPage($orgId, $portalId)
    {
        $page = CmsPage::create([
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'About Us',
            'slug' => 'about',
            'description' => 'Learn more about our organization',
            'content' => '<h1>About Us</h1><p>We are dedicated to excellence and innovation.</p>',
            'status' => 'published',
            'type' => 'about',
            'show_in_navigation' => true,
            'sort_order' => 2,
            'seo_title' => 'About Us - Our Story',
            'seo_description' => 'Learn about our mission, vision, and values',
            'published_at' => now(),
        ]);

        CmsSection::create([
            'cms_page_id' => $page->id,
            'name' => 'About Content',
            'type' => 'content',
            'title' => 'Our Story',
            'content' => '<p>Founded with a vision to make a difference, we have been serving our community with dedication and passion.</p>',
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);
    }

    private function createContactPage($orgId, $portalId)
    {
        $page = CmsPage::create([
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Contact Us',
            'slug' => 'contact',
            'description' => 'Get in touch with us',
            'content' => '<h1>Contact Us</h1><p>We would love to hear from you.</p>',
            'status' => 'published',
            'type' => 'contact',
            'show_in_navigation' => true,
            'sort_order' => 4,
            'seo_title' => 'Contact Us - Get in Touch',
            'seo_description' => 'Contact us for inquiries and support',
            'published_at' => now(),
        ]);

        CmsSection::create([
            'cms_page_id' => $page->id,
            'name' => 'Contact Form',
            'type' => 'contact_form',
            'title' => 'Send us a message',
            'content' => '<p>Fill out the form below and we will get back to you soon.</p>',
            'settings' => [
                'fields' => ['name', 'email', 'subject', 'message'],
                'submit_text' => 'Send Message',
                'success_message' => 'Thank you for your message!'
            ],
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);
    }

    private function createServicesPage($orgId, $portalId)
    {
        $page = CmsPage::create([
            'org_id' => $orgId,
            'orgPortal_id' => $portalId,
            'title' => 'Our Services',
            'slug' => 'services',
            'description' => 'Discover our range of services',
            'content' => '<h1>Our Services</h1><p>We offer a comprehensive range of services.</p>',
            'status' => 'published',
            'type' => 'page',
            'show_in_navigation' => true,
            'sort_order' => 3,
            'seo_title' => 'Our Services - What We Offer',
            'seo_description' => 'Explore our comprehensive range of services',
            'published_at' => now(),
        ]);

        CmsSection::create([
            'cms_page_id' => $page->id,
            'name' => 'Services List',
            'type' => 'services',
            'title' => 'What We Offer',
            'content' => '<p>Our services are designed to meet your needs.</p>',
            'data' => [
                'services' => [
                    ['name' => 'Service 1', 'description' => 'Professional service description'],
                    ['name' => 'Service 2', 'description' => 'Quality service description'],
                    ['name' => 'Service 3', 'description' => 'Expert service description'],
                ]
            ],
            'sort_order' => 1,
            'is_active' => true,
            'is_visible' => true,
        ]);
    }
}

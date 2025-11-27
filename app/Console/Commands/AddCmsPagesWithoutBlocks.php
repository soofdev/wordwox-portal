<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Command to create CMS pages without blocks/sections for an organization
 * 
 * This command creates basic CMS pages (home, about, contact, etc.) for a given
 * organization without any associated sections or blocks. Useful for setting up
 * initial page structure that can be populated later.
 */
class AddCmsPagesWithoutBlocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:add-pages {org_id : The organization ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add CMS pages without blocks/sections for an organization';

    /**
     * Default pages to create if not specified
     */
    protected $defaultPages = [
        'home' => [
            'title' => 'Home',
            'slug' => 'home',
            'type' => 'home',
            'is_homepage' => true,
            'sort_order' => 1,
        ],
        'about' => [
            'title' => 'About Us',
            'slug' => 'about',
            'type' => 'about',
            'sort_order' => 2,
        ],
        'contact' => [
            'title' => 'Contact Us',
            'slug' => 'contact-us',
            'type' => 'contact',
            'sort_order' => 4,
        ],
        'services' => [
            'title' => 'Our Services',
            'slug' => 'services',
            'type' => 'page',
            'sort_order' => 3,
        ],
        'schedule' => [
            'title' => 'Schedule',
            'slug' => 'schedule',
            'type' => 'page',
            'sort_order' => 5,
        ],
        'coaches' => [
            'title' => 'Our Coaches',
            'slug' => 'coaches',
            'type' => 'page',
            'sort_order' => 6,
        ],
        'packages' => [
            'title' => 'Packages',
            'slug' => 'packages',
            'type' => 'page',
            'sort_order' => 7,
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orgId = $this->argument('org_id');
        
        // Use defaults
        $status = 'published';
        $skipExisting = true;

        $this->info("Creating CMS pages for Org ID: {$orgId}");

        // Determine which pages to create
        $pagesToCreate = $this->getPagesToCreate();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($pagesToCreate as $pageKey) {
            if (!isset($this->defaultPages[$pageKey])) {
                $this->warn("Unknown page type: {$pageKey}. Skipping.");
                continue;
            }

            $pageData = $this->defaultPages[$pageKey];

            // Check if page already exists
            if ($skipExisting) {
                $existing = CmsPage::where('org_id', $orgId)
                                    ->where('slug', $pageData['slug'])
                                    ->first();
                
                if ($existing) {
                    $this->line("  ⏭️  Skipping '{$pageData['title']}' (already exists)");
                    $skipped++;
                    continue;
                }
            }

            try {
                $page = CmsPage::create([
                    'org_id' => $orgId,
                    'title' => $pageData['title'],
                    'slug' => $pageData['slug'],
                    'description' => "{$pageData['title']} page",
                    'content' => "<h1>{$pageData['title']}</h1><p>This is the {$pageData['title']} page content.</p>",
                    'status' => $status,
                    'type' => $pageData['type'],
                    'is_homepage' => $pageData['is_homepage'] ?? false,
                    'show_in_navigation' => true,
                    'sort_order' => $pageData['sort_order'],
                    'seo_title' => "{$pageData['title']} - Portal",
                    'seo_description' => "{$pageData['title']} page description",
                    'template' => 'fitness',
                    'layout' => 'default',
                    'published_at' => $status === 'published' ? now()->timestamp : null,
                ]);

                $this->info("  ✅ Created '{$pageData['title']}' (slug: /{$pageData['slug']})");
                $created++;
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to create '{$pageData['title']}': {$e->getMessage()}");
                $errors++;
            }
        }

        // Summary
        $this->newLine();
        $this->info("Summary:");
        $this->line("  Created: {$created} pages");
        if ($skipped > 0) {
            $this->line("  Skipped: {$skipped} pages (already exist)");
        }
        if ($errors > 0) {
            $this->line("  Errors: {$errors} pages");
        }

        // Show all pages for this org
        $allPages = CmsPage::where('org_id', $orgId)
                            ->orderBy('sort_order')
                            ->get();

        if ($allPages->count() > 0) {
            $this->newLine();
            $this->info("All pages for this organization:");
            foreach ($allPages as $page) {
                $sectionsCount = $page->sections()->count();
                $statusIcon = $page->status === 'published' ? '✅' : '📝';
                $this->line("  {$statusIcon} {$page->title} ({$page->type}) - /{$page->slug} [{$sectionsCount} sections]");
            }
        }

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Get the list of pages to create
     */
    protected function getPagesToCreate(): array
    {
        // Return all default pages
        return array_keys($this->defaultPages);
    }
}


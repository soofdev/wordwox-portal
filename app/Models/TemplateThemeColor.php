<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateThemeColor extends Model
{
    use HasFactory, Tenantable;

    protected $table = 'template_theme_colors';

    protected $fillable = [
        'org_id',
        'template',
        'primary_color',
        'secondary_color',
        'text_dark',
        'text_gray',
        'text_base',
        'text_light',
        'text_footer',
        'bg_white',
        'bg_packages',
        'bg_coaches',
        'bg_footer',
        'bg_navbar',
        'text_navbar',
        'primary_hover',
        'secondary_hover',
        'button_bg_color',
        'button_text_color',
    ];

    /**
     * Get the organization that owns this theme color configuration
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Get default theme colors for fitness template
     * Colors extracted from SuperHero CrossFit website:
     * - Packages page: https://superhero.wodworx.com/org-plan/index
     * - About page: https://superhero.wodworx.com/site/about
     * Navbar uses Bootstrap's navbar-dark bg-dark (rgb(33, 37, 41) = #212529)
     */
    public static function getDefaults(): array
    {
        return [
            'primary_color' => '#4285F4',        // Google blue for primary buttons (Buy buttons on packages page)
            'secondary_color' => '#e03e2d',       // Red accent color (from "Unleash" text on homepage)
            'text_dark' => '#212529',             // Dark gray/black for headers and dark text (from CSS border colors)
            'text_gray' => '#6c757d',             // Medium gray for secondary text
            'text_base' => '#212529',             // Base text color (dark gray/black)
            'text_light' => '#ffffff',            // White text for dark backgrounds
            'text_footer' => '#6c757d',           // Gray text for footer
            'bg_white' => '#ffffff',              // White background
            'bg_packages' => '#f5f5f5',           // Light gray background for packages section (from CSS)
            'bg_coaches' => '#f5f5f5',            // Light gray background for coaches section
            'bg_footer' => '#f5f5f5',             // Light gray footer background
            'bg_navbar' => '#212529',              // Dark navbar background (Bootstrap bg-dark = rgb(33, 37, 41) from navbar-dark bg-dark on about page)
            'text_navbar' => '#ffffff',            // White text for navbar (from navbar-dark class on about page)
            'primary_hover' => '#357ABD',         // Darker blue for primary button hover (from CSS)
            'secondary_hover' => '#c02d1f',       // Darker red for secondary hover
            'button_bg_color' => '#343a40',        // Button background color (dark gray default)
            'button_text_color' => '#ffffff',      // Button text color (defaults to white)
        ];
    }

    /**
     * Get or create theme colors for an organization and template
     */
    public static function getOrCreateForOrg(int $orgId, string $template = 'fitness'): self
    {
        return static::firstOrCreate(
            ['org_id' => $orgId, 'template' => $template],
            static::getDefaults()
        );
    }
}

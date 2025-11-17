<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsSection extends BaseWWModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'cms_page_id',
        'name',
        'type',
        'title',
        'subtitle',
        'content',
        'settings',
        'data',
        'template',
        'css_classes',
        'styles',
        'sort_order',
        'is_active',
        'is_visible',
        'responsive_settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'data' => 'array',
        'styles' => 'array',
        'responsive_settings' => 'array',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the page this section belongs to
     */
    public function cmsPage(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    /**
     * Alias for cmsPage relationship for backward compatibility
     */
    public function page(): BelongsTo
    {
        return $this->cmsPage();
    }

    /**
     * Scope sections by organization through page relationship
     */
    public function scopeForOrg($query, $orgId)
    {
        return $query->whereHas('cmsPage', function($q) use ($orgId) {
            $q->where('org_id', $orgId);
        });
    }

    /**
     * Scope sections by organization and portal through page relationship
     */
    public function scopeForOrgAndPortal($query, $orgId, $portalId)
    {
        return $query->whereHas('cmsPage', function($q) use ($orgId, $portalId) {
            $q->where('org_id', $orgId)
              ->where('orgPortal_id', $portalId);
        });
    }

    /**
     * Scope for active sections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for visible sections
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope for ordered sections
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get sections by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the rendered content for this section
     */
    public function getRenderedContentAttribute(): string
    {
        // This could be extended to support different content rendering
        // based on section type (markdown, HTML, etc.)
        return $this->content ?? '';
    }
}

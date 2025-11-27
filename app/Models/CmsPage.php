<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsPage extends BaseWWModel
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'org_id',
        'title',
        'slug',
        'description',
        'content',
        'meta_data',
        'status',
        'type',
        'is_homepage',
        'show_in_navigation',
        'sort_order',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'template',
        'layout',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'is_homepage' => 'boolean',
        'show_in_navigation' => 'boolean',
        'published_at' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    /**
     * Get the sections for this page
     */
    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class)->orderBy('sort_order');
    }

    /**
     * Get the organization this page belongs to
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class);
    }

    /**
     * Get the user who created this page
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this page
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where(function($q) {
                        $q->whereNull('published_at')
                          ->orWhere('published_at', '<=', now());
                    });
    }

    /**
     * Scope for navigation pages
     */
    public function scopeInNavigation($query)
    {
        return $query->where('show_in_navigation', true)
                    ->orderBy('sort_order');
    }

    /**
     * Get the full URL for this page
     */
    public function getUrlAttribute(): string
    {
        if ($this->portal && $this->portal->baseUrl) {
            return $this->portal->baseUrl . '/' . $this->slug;
        }
        
        return url('/' . $this->slug);
    }

    /**
     * Check if page is published
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' && 
               ($this->published_at === null || $this->published_at <= now());
    }
}

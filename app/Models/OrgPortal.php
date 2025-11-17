<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgPortal extends Model
{
    use SoftDeletes;

    protected $table = 'orgPortal';

    protected $fillable = [
        'uuid',
        'org_id',
        'orgLocation_id',
        'subdomain',
        'baseUrl',
        'status',
    ];

    /**
     * Get the organization this portal belongs to
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class);
    }

    /**
     * Get the CMS pages for this portal
     */
    public function cmsPages(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'orgPortal_id');
    }

    /**
     * Get published CMS pages for this portal
     */
    public function publishedPages(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'orgPortal_id')
                    ->published()
                    ->orderBy('sort_order');
    }

    /**
     * Get navigation pages for this portal
     */
    public function navigationPages(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'orgPortal_id')
                    ->published()
                    ->inNavigation();
    }

    /**
     * Scope for active portals
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

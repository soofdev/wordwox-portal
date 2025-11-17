<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrgTerms extends Model
{
    use HasFactory, Tenantable, SoftDeletes;

    protected $table = 'org_terms';

    protected $fillable = [
        'org_id',
        'title',
        'content',
        'version',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'org_id' => 'integer',
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'version' => '1.0',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
    /**
     * Get the organization that owns these terms
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'id');
    }

    /**
     * Scope to get only active terms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get terms by version
     */
    public function scopeVersion($query, $version)
    {
        return $query->where('version', $version);
    }

    /**
     * Get the latest active terms for an organization
     */
    public static function getLatestForOrg($orgId)
    {
        return static::where('org_id', $orgId)
            ->active()
            ->orderBy('effective_date', 'desc')
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Check if these terms are the latest version for the organization
     */
    public function isLatestVersion()
    {
        $latest = static::getLatestForOrg($this->org_id);
        return $latest && $latest->id === $this->id;
    }

    /**
     * Get rendered content with variables replaced
     */
    public function getRenderedContent($variables = [])
    {
        $content = $this->content;

        // Replace common variables (single braces format)
        $defaultVariables = [
            '{org_name}' => $this->org->name ?? 'Organization',
            '{effective_date}' => $this->effective_date->format('F j, Y'),
            '{version}' => $this->version,

            // Keep double braces for backward compatibility
            '{{org_name}}' => $this->org->name ?? 'Organization',
            '{{effective_date}}' => $this->effective_date->format('F j, Y'),
            '{{version}}' => $this->version,
        ];

        $allVariables = array_merge($defaultVariables, $variables);

        foreach ($allVariables as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DateTime;

abstract class BaseWWModel extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $casts = [
        'isDeleted' => 'boolean',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->isDirty(static::CREATED_AT)) {
                $model->setCreatedAt($model->freshTimestamp());
            }
            if (!$model->isDirty(static::UPDATED_AT)) {
                $model->setUpdatedAt($model->freshTimestamp());
            }

            // Set org_id only if not already set and if we have an authenticated user
            if (empty($model->org_id) && auth()->check() && auth()->user() && auth()->user()->orgUser) {
                $model->org_id = auth()->user()->orgUser->org_id;
            }

            // Set uuid only if not already set
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });

        static::updating(function ($model) {
            if (!$model->isDirty(static::UPDATED_AT)) {
                $model->setUpdatedAt($model->freshTimestamp());
            }
        });
    }

    public function freshTimestamp()
    {
        return time();
    }

    public function fromDateTime($value)
    {
        return $value instanceof Carbon || $value instanceof DateTime ? $value->getTimestamp() : (int) $value;
    }

    public function getDateFormat()
    {
        return 'U';
    }

    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $this->fromDateTime($value);
    }

    public function setUpdatedAt($value)
    {
        $this->{static::UPDATED_AT} = $this->fromDateTime($value);
    }

    public function scopeWithTrashed($query)
    {
        return $query;
    }

    public function delete()
    {
        $this->isDeleted = true;
        $this->deleted_at = Carbon::now();
        return $this->save();
    }
    
    public function restore()
    {
        $this->isDeleted = false;
        $this->deleted_at = null;
        return $this->save();
    }

    public function setDeletedAtAttribute($value)
    {
        if ($value === null) {
            $this->attributes['isDeleted'] = false;
            $this->attributes['deleted_at'] = null;
        } else {
            $this->attributes['deleted_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $this->attributes['isDeleted'] = true;
        }
    }

    public function forceDelete()
    {
        return parent::delete();
    }

    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'id');
    }
}
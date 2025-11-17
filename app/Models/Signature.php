<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id', 
        'uuid',
        'filename',
        'document_filename',
        'certified',
        'from_ips',
    ];

    protected $casts = [
        'from_ips' => 'array',
        'certified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the signable model (polymorphic relationship)
     */
    public function signable()
    {
        return $this->morphTo('model');
    }

    /**
     * Get the signature image path
     */
    public function getSignatureImagePath()
    {
        return $this->filename ? config('sign-pad.signatures_path') . '/' . $this->filename : null;
    }

    /**
     * Get the signed document path
     */
    public function getSignedDocumentPath()
    {
        return $this->document_filename ? config('sign-pad.documents_path') . '/' . $this->document_filename : null;
    }

    /**
     * Get the signature image URL
     */
    public function getSignatureImageUrl()
    {
        $path = $this->getSignatureImagePath();
        return $path ? Storage::disk(config('sign-pad.disk_name'))->url($path) : null;
    }

    /**
     * Get the signed document URL
     */
    public function getSignedDocumentUrl()
    {
        $path = $this->getSignedDocumentPath();
        return $path ? Storage::disk(config('sign-pad.disk_name'))->url($path) : null;
    }
}
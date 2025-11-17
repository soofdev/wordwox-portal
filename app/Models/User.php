<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    // Manually set the table name to match core project
    protected $table = 'user';

    // Use timestamp integer values instead of datetime
    protected $dateFormat = 'U';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fullName',
        'email',
        'password_hash',
        'auth_key',
        'otp',
        'otp_token',
        'otp_expire',
        'orgUser_id',
        'phoneCountry',
        'phoneNumber',
        'language_preference',
        'uuid',
        'status',
        'verifiedEmail',
        'verifiedPhoneNumber',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'password_hash' => 'hashed', // Commented out to avoid conflicts
            'verifiedEmail' => 'boolean',
            'verifiedPhoneNumber' => 'boolean',
            'email_verified_at' => 'timestamp',
        ];
    }

    // Manually specify the password field name in the database
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = $value;
    }

    public function generateOTP() {
        $this->timestamps = false;

        $this->otp = rand(1111, 9999);
        $this->otp_token = md5($this->otp);
        $this->otp_expire = now()->addMinutes(15)->timestamp;
        // save
        $this->save();
    }

    public function clearOTP() {
        $this->timestamps = false;

        $this->otp = null;
        $this->otp_token = null;
        $this->otp_expire = null;
        // save
        $this->save();
    }

    /**
     * Get the user's initials (using fullName instead of name)
     */
    public function initials(): string
    {
        return Str::of($this->fullName ?: '')
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the name attribute (alias for fullName for compatibility)
     */
    public function getNameAttribute(): string
    {
        return $this->fullName ?: '';
    }

    /**
     * Get the full phone number by concatenating country code and number
     */
    public function getFullPhoneNumber(): string
    {
        if (empty($this->phoneCountry) || empty($this->phoneNumber)) {
            return '';
        }
        return $this->phoneCountry . $this->phoneNumber;
    }

    /**
     * Find a user by their phone number
     *
     * @param string $phone Phone number with or without +/00 prefix
     * @return User|null
     */
    public static function findByPhoneNumber(string $phone): ?User
    {
        // Remove any spaces from the phone number
        $phone = str_replace(' ', '', $phone);

        // Remove + or 00 prefix if present
        $phone = preg_replace('/^\+|^00/', '', $phone);

        return static::where('phoneCountry', function ($query) use ($phone) {
                $query->select('phoneCountry')
                    ->from('user')
                    ->whereRaw("CONCAT(phoneCountry, phoneNumber) = ?", [$phone]);
            })
            ->whereRaw("CONCAT(phoneCountry, phoneNumber) = ?", [$phone])
            ->first();
    }

    // Tenant relationships
    public function orgUser()
    {
        return $this->belongsTo(OrgUser::class, 'orgUser_id', 'id');
    }

    public function orgUsers()
    {
        return $this->hasMany(OrgUser::class, 'user_id', 'id');
    }

    /**
     * Check if this user has FOH access in any organization
     */
    public function hasAnyFohAccess(): bool
    {
        return $this->orgUsers()->withoutGlobalScopes()->where('isFohUser', true)->exists();
    }

    /**
     * Get all orgUsers where this user has FOH access
     */
    public function fohOrgUsers()
    {
        return $this->orgUsers()->where('isFohUser', true);
    }

    /**
     * Get the user's effective language
     * Priority: user language_preference -> org default -> 'en-US'
     * Only works if language feature is enabled for the organization
     *
     * @return string
     */
    public function getEffectiveLanguage(): string
    {
        $orgFeatures = $this->orgUser->org->orgSettingsFeatures ?? null;



        // If language feature is not enabled, always return English
        if (!$orgFeatures || !$orgFeatures->isLanguageFeatureEnabled()) {

            return 'en-US';
        }

        // If user has a language preference, use it (if it's still enabled)
        if (!empty($this->language_preference)) {
            $enabledLanguages = $orgFeatures->getEnabledLanguages();
            if (in_array($this->language_preference, $enabledLanguages)) {
                return $this->language_preference;
            }
        }

        // Fall back to first enabled language or English
        $enabledLanguages = $orgFeatures->getEnabledLanguages();
        
        // Ensure we have an array and get the first element safely
        if (!is_array($enabledLanguages) || empty($enabledLanguages)) {
            $fallbackLanguage = 'en-US';
        } else {
            $fallbackLanguage = $enabledLanguages[0];
        }



        return $fallbackLanguage;
    }

    /**
     * Set the user's language preference
     *
     * @param string|null $language
     * @return void
     */
    public function setLanguagePreference(?string $language): void
    {
        $orgFeatures = $this->orgUser->org->orgSettingsFeatures ?? null;

        // If language is null, clear the preference
        if ($language === null) {
            $this->language_preference = null;
            $this->save();
            return;
        }

        // Validate that language feature is enabled
        if (!$orgFeatures || !$orgFeatures->isLanguageFeatureEnabled()) {
            throw new \InvalidArgumentException("Language feature is not enabled for this organization.");
        }

        // Validate that the language is enabled for the organization
        $enabledLanguages = $orgFeatures->getEnabledLanguages();
        if (!in_array($language, $enabledLanguages)) {
            throw new \InvalidArgumentException("Language '{$language}' is not enabled for this organization.");
        }

        $this->language_preference = $language;
        $this->save();
    }

    /**
     * Check if user can use language features
     *
     * @return bool
     */
    public function canUseLanguageFeatures(): bool
    {
        // Users can always use language features - they'll get at least English
        // This allows the language settings page to always be accessible
        return true;
    }

    /**
     * Get available languages for this user
     *
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        $orgFeatures = $this->orgUser->org->orgSettingsFeatures ?? null;

        // If no org features exist, return default English
        if (!$orgFeatures) {
            return ['en-US' => 'English'];
        }

        // Get enabled languages from organization
        $enabledLanguages = $orgFeatures->getEnabledLanguages();

        // If enabled languages is empty, return default English
        if (empty($enabledLanguages)) {
            return ['en-US' => 'English'];
        }

        // Return the enabled languages with their display names
        $availableLanguages = $orgFeatures->getEnabledLanguagesWithNames();



        return $availableLanguages;
    }
}

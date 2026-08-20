<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Traits\HasAuditTrail;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles,HasAuditTrail;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'can_access_erp',
        'can_access_admin',
        'theme_mode',
        'theme_accent',
        'theme_sidebar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function modules()
    {
        return $this->belongsToMany(\App\Models\Module::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }
    
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
    
    /**
     * Create an audit log entry tied to this user.
     */
    public function audit(
        string $module,
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $meta = []
    ): AuditLog {
        return $this->auditLogs()->create([
            'module'       => $module,
            'action'       => $action,
            'description'  => $description,
    
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
    
            'route'        => optional(request()->route())->getName(),
            'url'          => request()->fullUrl(),
            'method'       => request()->method(),
            'ip'           => request()->ip(),
            'user_agent'   => substr((string) request()->userAgent(), 0, 500),
    
            'meta'         => $meta,
        ]);
    }
}

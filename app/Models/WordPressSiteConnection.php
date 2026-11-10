<?php

namespace App\Models;

use App\Enums\WordPressConnectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'brand_id',
    'created_by_user_id',
    'site_url',
    'rest_api_endpoint',
    'username',
    'application_password',
    'status',
    'site_name',
    'wordpress_version',
    'last_verified_at',
    'last_error',
])]
class WordPressSiteConnection extends Model
{
    use HasFactory;

    protected $table = 'wordpress_site_connections';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'application_password',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<WordPressPublishedPost, $this> */
    public function publications(): HasMany
    {
        return $this->hasMany(WordPressPublishedPost::class, 'wordpress_site_connection_id');
    }

    public function isConnected(): bool
    {
        return $this->status === WordPressConnectionStatus::Connected;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'application_password' => 'encrypted',
            'status' => WordPressConnectionStatus::class,
            'last_verified_at' => 'datetime',
        ];
    }
}

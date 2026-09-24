<?php

namespace App\Models;

use App\Domain\Shared\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $birth_date
 * @property string $status
 * @property UserRole $role
 * @property string $password
 * @property string|null $remember_token
 * @property Profile|null $profile
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'birth_date', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'role' => UserRole::class,
            'password' => 'hashed',
        ];
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [UserRole::Moderator, UserRole::Admin], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** @return HasOne<Profile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /** @return HasMany<Novel, $this> */
    public function novels(): HasMany
    {
        return $this->hasMany(Novel::class);
    }

    /** @return HasMany<Follow, $this> */
    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    /** @return HasMany<SavedNovel, $this> */
    public function savedNovels(): HasMany
    {
        return $this->hasMany(SavedNovel::class);
    }

    /** @return HasMany<ReadingProgress, $this> */
    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    /** @return HasMany<Bookmark, $this> */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
}

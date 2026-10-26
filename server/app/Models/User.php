<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Ai\Concerns\HasConversations;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasConversations, HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Поиск по имени, email и телефону — то, по чему администратор
     * ищет человека в списке.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('phones', fn (Builder $phones) => $phones->where('phone', 'like', $like));
        });
    }

    /**
     * Телефоны, основной первым: именно он подставляется в заявку,
     * а остальные человек выбирает сам.
     *
     * @return HasMany<UserPhone, $this>
     */
    public function phones(): HasMany
    {
        return $this->hasMany(UserPhone::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function primaryPhone(): ?string
    {
        return $this->phones->firstWhere('is_primary', true)?->phone
            ?? $this->phones->first()?->phone;
    }

    /**
     * Витрина этого человека. Null — роль мастера есть, а страницы ещё нет:
     * профиль заводится отдельно и может отсутствовать.
     *
     * @return HasOne<Master, $this>
     */
    public function master(): HasOne
    {
        return $this->hasOne(Master::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    /**
     * Человек, заведённый через соцсеть, пароля не имеет — экран профиля
     * по этому признаку показывает «задать пароль» вместо «сменить пароль».
     */
    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim((string) $this->name), 0, 1));
    }
}

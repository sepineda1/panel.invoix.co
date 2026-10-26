<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'status', 'app_url', 'database_name', 'database_user', 'database_password',
    'redis_prefix', 'app_key', 'image_ref', 'admin_name', 'admin_email', 'admin_password',
    'password_shown_at', 'checkpoint', 'last_error', 'image_updated_at',
])]
#[Hidden(['database_password', 'app_key', 'admin_password'])]
class Tenant extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'database_password' => 'encrypted',
            'app_key' => 'encrypted',
            'admin_password' => 'encrypted',
            'password_shown_at' => 'datetime',
            'image_updated_at' => 'datetime',
        ];
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    public function imageTag(): ?string
    {
        if (! $this->image_ref) {
            return null;
        }

        $pos = strrpos($this->image_ref, ':');

        return $pos === false ? $this->image_ref : substr($this->image_ref, $pos + 1);
    }

    /** @return array<string, mixed> */
    public function present(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'app_url' => $this->app_url,
            'image_ref' => $this->image_ref,
            'image_tag' => $this->imageTag(),
            'admin_name' => $this->admin_name,
            'admin_email' => $this->admin_email,
            'database_name' => $this->database_name,
            'database_user' => $this->database_user,
            'redis_prefix' => $this->redis_prefix,
            'last_error' => $this->last_error,
            'checkpoint' => $this->checkpoint,
            'created_at' => $this->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'image_updated_at' => $this->image_updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ];
    }
}

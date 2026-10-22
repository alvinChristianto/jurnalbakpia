<?php

namespace App\Models;

use App\Enums\FlashSaleStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class OlFlashSale extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'banner_path',
        'banner_alt',
        'start_at',
        'duration_minutes',
        'end_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'duration_minutes' => 'integer',
        'status' => FlashSaleStatus::class,
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OlFlashSaleItem::class, 'flash_sale_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function syncEnd(): self
    {
        $this->end_at = $this->start_at->copy()->addMinutes($this->duration_minutes);

        return $this;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner_path
            ? Storage::disk('public')->url($this->banner_path)
            : null;
    }

    public function isActive(?CarbonInterface $now = null): bool
    {
        $now = $now ?: Carbon::now();

        return $this->status === FlashSaleStatus::PUBLISHED
            && $this->start_at !== null
            && $this->end_at !== null
            && $this->start_at->lte($now)
            && $this->end_at->gt($now);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookFeaturedEntry extends Model
{
    /**
     * @var iterable<int, string>
     */
    protected $fillable = [
        'import_batch',
        'work_id',
        'position',
        'source',
        'list_name',
        'payload',
        'imported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Work, $this>
     */
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    /**
     * @param  Builder<BookFeaturedEntry>  $query
     * @return Builder<BookFeaturedEntry>
     */
    public function scopeForLatestImport(Builder $query): Builder
    {
        $latestImportedAt = static::query()->max('imported_at');

        if ($latestImportedAt === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('imported_at', $latestImportedAt);
    }
}

<?php

namespace App\Models;

use App\Services\OpenLibrary\OpenLibraryBookNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'open_library_key',
        'work_id',
        'title',
        'subtitle',
        'by_statement',
        'edition_name',
        'physical_format',
        'publishers',
        'publish_date',
        'number_of_pages',
        'cover_id',
        'languages',
        'subjects',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publishers' => 'array',
            'languages' => 'array',
            'subjects' => 'array',
            'number_of_pages' => 'integer',
            'cover_id' => 'integer',
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
     * @return HasMany<EditionIsbn, $this>
     */
    public function isbns(): HasMany
    {
        return $this->hasMany(EditionIsbn::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return OpenLibraryBookNormalizer::coverUrlFromCoverId($this->cover_id, 'M');
    }

    public function resolveCoverUrl(string $size = 'M'): ?string
    {
        return OpenLibraryBookNormalizer::coverUrlFromCoverId($this->cover_id, $size);
    }
}

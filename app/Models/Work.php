<?php

namespace App\Models;

use App\Services\OpenLibrary\OpenLibraryBookNormalizer;
use Database\Factories\WorkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Work extends Model
{
    /** @use HasFactory<WorkFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'open_library_key',
        'title',
        'subtitle',
        'cover_id',
        'first_publish_year',
        'description',
        'subjects',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cover_id' => 'integer',
            'first_publish_year' => 'integer',
            'subjects' => 'array',
        ];
    }

    /**
     * @return HasMany<BookFeaturedEntry, $this>
     */
    public function featuredEntries(): HasMany
    {
        return $this->hasMany(BookFeaturedEntry::class);
    }

    /**
     * @return BelongsToMany<Author, $this>
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'author_works')
            ->withPivot(['position', 'role'])
            ->orderByPivot('position');
    }

    /**
     * @return HasMany<ReadingLog, $this>
     */
    public function readingLogs(): HasMany
    {
        return $this->hasMany(ReadingLog::class);
    }

    /**
     * @return HasMany<Edition, $this>
     */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class);
    }

    /**
     * Medium cover URL for templates and API-shaped fields that expect a string URL.
     */
    public function getCoverUrlAttribute(): ?string
    {
        return OpenLibraryBookNormalizer::coverUrlFromCoverId($this->cover_id, 'M');
    }

    public function getPublishYearAttribute(): ?int
    {
        return $this->first_publish_year;
    }

    public function resolveCoverUrl(string $size = 'M'): ?string
    {
        return OpenLibraryBookNormalizer::coverUrlFromCoverId($this->cover_id, $size);
    }

    public function displayAuthor(): ?string
    {
        $names = $this->authors
            ->pluck('name')
            ->filter(fn (mixed $name): bool => is_string($name) && $name !== '')
            ->implode(', ');

        return $names !== '' ? $names : null;
    }
}

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
        'goodbooks_book_id',
        'title',
        'subtitle',
        'cover_id',
        'first_publish_year',
        'description',
        'subjects',
        'open_library_search_doc',
        'open_library_match_source',
        'open_library_enriched_at',
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
            'goodbooks_book_id' => 'integer',
            'open_library_search_doc' => 'array',
            'open_library_enriched_at' => 'datetime',
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

    public function primaryAuthorName(): ?string
    {
        $name = $this->authors
            ->firstWhere('pivot.position', 1)?->name;

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function displayAuthor(): ?string
    {
        return $this->primaryAuthorName();
    }
}

<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'open_library_id',
        'title',
        'author',
        'cover_url',
        'publish_year',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish_year' => 'integer',
        ];
    }

    /**
     * @return HasMany<BookFeaturedEntry, $this>
     */
    public function featuredEntries(): HasMany
    {
        return $this->hasMany(BookFeaturedEntry::class);
    }
}

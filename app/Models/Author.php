<?php

namespace App\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'open_library_id',
        'goodbooks_author_id',
        'name',
        'bio',
        'birth_date',
        'death_date',
        'wikipedia',
        'alternate_names',
        'open_library_author_search_doc',
        'open_library_author_enriched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alternate_names' => 'array',
            'open_library_author_search_doc' => 'array',
            'open_library_author_enriched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Work, $this>
     */
    public function works(): BelongsToMany
    {
        return $this->belongsToMany(Work::class, 'author_works')
            ->withPivot(['position', 'role'])
            ->orderByPivot('position');
    }
}

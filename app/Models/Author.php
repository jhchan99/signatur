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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alternate_names' => 'array',
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

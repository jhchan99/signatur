<?php

namespace App\Models;

use Database\Factories\ReadingLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingLog extends Model
{
    /** @use HasFactory<ReadingLogFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'rating',
        'review_text',
        'is_spoiler',
        'is_private',
        'date_started',
        'date_finished',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'is_spoiler' => 'boolean',
            'is_private' => 'boolean',
            'date_started' => 'date',
            'date_finished' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}

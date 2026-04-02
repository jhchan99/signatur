<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditionIsbn extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'edition_id',
        'isbn',
    ];

    /**
     * @return BelongsTo<Edition, $this>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}

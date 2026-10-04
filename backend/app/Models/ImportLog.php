<?php

namespace App\Models;

use Database\Factories\ImportLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_id
 * @property string|null $transaction_id
 * @property string $error_message
 */
class ImportLog extends Model
{
    /** @use HasFactory<ImportLogFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'import_id',
        'transaction_id',
        'error_message',
    ];

    /**
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}

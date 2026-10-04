<?php

namespace App\Models;

use App\Domain\Import\Enums\ImportStatus;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $file_name
 * @property int $total_records
 * @property int $successful_records
 * @property int $failed_records
 * @property ImportStatus $status
 */
class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'file_name',
        'total_records',
        'successful_records',
        'failed_records',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'total_records' => 0,
        'successful_records' => 0,
        'failed_records' => 0,
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_records' => 'integer',
            'successful_records' => 'integer',
            'failed_records' => 'integer',
            'status' => ImportStatus::class,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ImportLog
 */
class ImportLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at,
        ];
    }
}

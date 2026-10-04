<?php

namespace App\Http\Controllers;

use App\Http\Resources\ImportLogResource;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImportLogController extends Controller
{
    /**
     * Paginated error logs, for imports too large to return in one response.
     */
    public function index(Request $request, Import $import): AnonymousResourceCollection
    {
        $logs = $import->logs()
            ->orderBy('id')
            ->paginate($this->perPage($request, 50));

        return ImportLogResource::collection($logs);
    }
}

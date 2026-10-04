<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessImport;
use App\Models\Import;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class ImportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $imports = Import::query()
            ->orderByDesc('id')
            ->paginate($this->perPage($request, 20));

        return ImportResource::collection($imports);
    }

    /**
     * Stores the file and queues it; the client polls GET /api/imports/{id} for the result.
     */
    public function store(StoreImportRequest $request): JsonResponse
    {
        $file = $request->importFile();
        $path = $file->store('imports', 'local');
        if ($path === false) {
            throw new RuntimeException('Could not store the uploaded file.');
        }

        $import = Import::create(['file_name' => $file->getClientOriginalName()]);
        ProcessImport::dispatch($import, $path, $request->fileFormat());

        return ImportResource::make($import->refresh())
            ->response()
            ->setStatusCode(JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * Import details with its error logs, as required by the brief.
     */
    public function show(Import $import): ImportResource
    {
        return ImportResource::make($import->load(['logs' => fn ($query) => $query->orderBy('id')]));
    }
}

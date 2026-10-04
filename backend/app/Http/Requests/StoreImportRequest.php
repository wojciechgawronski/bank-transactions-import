<?php

namespace App\Http\Requests;

use App\Domain\Import\Enums\FileFormat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreImportRequest extends FormRequest
{
    /**
     * Upload limit in kilobytes (10 MB).
     */
    public const MAX_SIZE_KB = 10_240;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $extensions = implode(',', array_column(FileFormat::cases(), 'value'));

        return [
            'file' => ['required', 'file', "extensions:{$extensions}", 'max:'.self::MAX_SIZE_KB],
        ];
    }

    public function importFile(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('file');
    }

    public function fileFormat(): FileFormat
    {
        return FileFormat::fromExtension($this->importFile()->getClientOriginalExtension());
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Page size from ?per_page=, clamped to 1..100.
     */
    protected function perPage(Request $request, int $default): int
    {
        return max(1, min(100, $request->integer('per_page', $default)));
    }
}

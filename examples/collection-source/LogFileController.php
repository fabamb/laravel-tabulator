<?php

namespace App\Http\Controllers;

use App\Tabulator\LogFileTabulatorTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogFileController
{
    public function index()
    {
        return view('logs.index');
    }

    public function data(Request $request): JsonResponse
    {
        $files = collect(Storage::disk('logs')->files())->map(fn (string $path) => [
            'id' => $path,
            'name' => basename($path),
            'size' => Storage::disk('logs')->size($path),
        ]);

        return (new LogFileTabulatorTable())->of($files)->toResponse($request);
    }
}

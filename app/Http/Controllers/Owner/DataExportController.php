<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\CenterExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    use ResolvesCurrentCenter;

    public function export(Request $request, CenterExporter $exporter): StreamedResponse
    {
        $center = $this->currentCenter($request, true);
        $data = $exporter->export($center);

        $filename = 'mam-'.\Illuminate\Support\Str::slug($center->name).'-'.now()->format('Ymd').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}

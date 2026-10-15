<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** Excel-friendly CSV download (UTF-8 BOM so Bangla shows correctly). */
class Csv
{
    public static function download(string $filename, array $header, iterable $rows): StreamedResponse
    {
        abort_unless(Tenancy::branch() === null || Tenancy::can('reports.export'), 403);

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            foreach ($rows as $r) {
                fputcsv($out, array_map(fn ($v) => is_float($v) ? round($v, 2) : $v, array_values((array) $r)));
            }
            fclose($out);
        }, $filename.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

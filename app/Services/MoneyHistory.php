<?php

namespace App\Services;

use App\Support\LedgerPresentation;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MoneyHistory
{
    /** @param array<int, string> $statuses
     * @return array{from: ?string, to: ?string, status: string}
     */
    public function filters(Request $request, array $statuses, string $default): array
    {
        $data = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'status' => ['nullable', Rule::in($statuses)], 'export' => ['nullable', Rule::in(['csv'])]]);

        return ['from' => $data['from'] ?? null, 'to' => $data['to'] ?? null, 'status' => $data['status'] ?? $default];
    }

    /** @param array{from: ?string, to: ?string, status: string} $filters */
    public function apply(Builder $query, array $filters, string $dateColumn): Builder
    {
        return $query->when($filters['from'], fn (Builder $query, string $date) => $query->where($dateColumn, '>=', CarbonImmutable::parse($date, config('money.timezone'))->startOfDay()->setTimezone(config('app.timezone'))))
            ->when($filters['to'], fn (Builder $query, string $date) => $query->where($dateColumn, '<', CarbonImmutable::parse($date, config('money.timezone'))->startOfDay()->addDay()->setTimezone(config('app.timezone'))))
            ->when($filters['status'] !== 'all', fn (Builder $query) => $filters['status'] === 'open'
                ? $query->whereIn('p.status', ['pending', 'processing']) : $query->where('p.status', $filters['status']));
    }

    /** @param array<int, string> $columns */
    public function csv(Builder $query, array $columns, Closure $values, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns, $values): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $columns, ',', '"', '');
            foreach ($query->cursor() as $record) {
                $row = array_map(function (mixed $value): string {
                    $value = LedgerPresentation::text((string) $value);

                    return ! is_numeric($value) && preg_match('/^\s*[=+@\-\t\r\n]/', $value) ? "'".$value : $value;
                }, $values($record));
                fputcsv($output, $row, ',', '"', '');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}

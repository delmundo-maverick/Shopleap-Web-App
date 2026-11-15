<?php

namespace App\Support;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class MoneyReferences
{
    /** @param array<string, mixed> $values */
    public static function insert(string $table, string $prefix, array $values): object
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                return DB::transaction(function () use ($table, $prefix, $values): object {
                    $id = DB::table($table)->insertGetId($values + ['reference_no' => OrderCodes::generate($prefix, 10)]);

                    return DB::table($table)->where('id', $id)->firstOrFail();
                });
            } catch (UniqueConstraintViolationException $exception) {
                $databaseMessage = (string) ($exception->errorInfo[2] ?? '');
                if (! str_contains($databaseMessage, $table.'_reference_no_unique')
                    && ! str_contains($databaseMessage, $table.'.reference_no')) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Could not allocate a transaction reference. Please retry.');
    }
}

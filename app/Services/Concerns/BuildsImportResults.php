<?php

namespace App\Services\Concerns;

trait BuildsImportResults
{
    /**
     * @param  array<int, array{row: int, column: string, message: string}>  $errors
     * @return array{
     *     status: 'warning',
     *     title: string,
     *     body: ?string,
     *     errors: array<int, array{row: int, column: string, message: string}>,
     *     imported_count: int
     * }
     */
    protected function warningResult(string $title, ?string $body = null, array $errors = [], int $importedCount = 0): array
    {
        return [
            'status' => 'warning',
            'title' => $title,
            'body' => $body,
            'errors' => $errors,
            'imported_count' => $importedCount,
        ];
    }

    /**
     * @return array{
     *     status: 'success',
     *     title: string,
     *     body: ?string,
     *     errors: array<int, array{row: int, column: string, message: string}>,
     *     imported_count: int
     * }
     */
    protected function successResult(string $title, int $importedCount): array
    {
        return [
            'status' => 'success',
            'title' => $title,
            'body' => null,
            'errors' => [],
            'imported_count' => $importedCount,
        ];
    }

    /**
     * @return array{
     *     status: 'danger',
     *     title: string,
     *     body: ?string,
     *     errors: array<int, array{row: int, column: string, message: string}>,
     *     imported_count: int
     * }
     */
    protected function dangerResult(string $title, ?string $body = null): array
    {
        return [
            'status' => 'danger',
            'title' => $title,
            'body' => $body,
            'errors' => [],
            'imported_count' => 0,
        ];
    }
}

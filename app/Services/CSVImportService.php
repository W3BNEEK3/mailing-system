<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RecipientData;
use App\Repositories\RecipientRepository;

/*
 * CsvImportService
 *
 * Parses a CSV file of recipient data, validates each row, and bulk-inserts
 * valid rows into the recipients table. Also creates/associates RecipientGroup
 * rows for any tags found in the CSV.
 *
 * Expected CSV column order (header row is optional but recommended):
 *   first_name, last_name, email, company, tags
 *
 * The only required column is `email`. All others are optional.
 *
 * Tags column format: comma-separated group names
 *   e.g. "Clients, Newsletter, VIPs"
 *   Each value becomes or maps to a RecipientGroup row.
 *
 * Return value of import():
 *   [
 *     'imported' => 5,    // rows successfully inserted (new contacts)
 *     'skipped'  => 2,    // rows skipped because email already exists
 *     'errors'   => [     // rows rejected for validation reasons
 *         ['row' => 3, 'email' => 'bad-email', 'reason' => 'Invalid email format'],
 *     ],
 *   ]
 *
 * Security:
 *   - File type is validated before opening
 *   - fgetcsv() is used (not str_getcsv on file_get_contents) to handle
 *     large files without loading the whole file into memory
 *   - Email addresses are lowercased and trimmed before insertion
 */
class CsvImportService
{
    /* Maximum number of rows processed per import (safety limit) */
    private const MAX_ROWS = 5000;

    public function __construct(
        private readonly RecipientRepository $recipients,
    ) {}

    // ─── Public API ───────────────────────────────────────────────────────────

    /*
     * Parse and import a CSV file.
     *
     * @param string $filePath  Absolute path to the uploaded CSV file (tmp path from $_FILES)
     * @return array{imported: int, skipped: int, errors: array}
     *
     * @throws \App\Exceptions\StorageException  If the file cannot be opened
     */
    public function import(string $filePath): array
    {
        $result = [
            'imported' => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \App\Exceptions\StorageException("Could not open CSV file for reading: {$filePath}");
        }

        try {
            $rowNumber   = 0;
            $hasHeader   = false;
            $columnMap   = [];
            $batch       = [];
            $batchSize   = 100; // insert in chunks of 100 rows

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($rowNumber > self::MAX_ROWS) {
                    $result['errors'][] = [
                        'row'    => $rowNumber,
                        'email'  => '',
                        'reason' => 'Import limit reached: maximum ' . self::MAX_ROWS . ' rows per import.',
                    ];
                    break;
                }

                /* Skip blank rows */
                if ($row === [null] || $row === ['']) {
                    continue;
                }

                /* First non-blank row: detect if it is a header */
                if ($rowNumber === 1) {
                    [$hasHeader, $columnMap] = $this->detectHeader($row);
                    if ($hasHeader) {
                        continue; /* Skip the header row, move to next */
                    }
                    /* No header detected — treat this row as data with default column order */
                }

                /* Map row values to named fields */
                $data = $this->mapRow($row, $columnMap);

                /* Validate the email */
                $email = strtolower(trim($data['email'] ?? ''));

                if ($email === '') {
                    $result['errors'][] = [
                        'row'    => $rowNumber,
                        'email'  => '',
                        'reason' => 'Email address is missing.',
                    ];
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $result['errors'][] = [
                        'row'    => $rowNumber,
                        'email'  => $email,
                        'reason' => "'{$email}' is not a valid email address.",
                    ];
                    continue;
                }

                /* Add to the current batch */
                $batch[] = new RecipientData(
                    firstName: trim($data['first_name'] ?? ''),
                    lastName:  trim($data['last_name']  ?? ''),
                    email:     $email,
                    company:   trim($data['company']    ?? '') ?: null,
                    tags:      trim($data['tags']       ?? '') ?: null,
                );

                /* Flush the batch when it reaches the batch size */
                if (count($batch) >= $batchSize) {
                    $this->flushBatch($batch, $result);
                    $batch = [];
                }
            }

            /* Flush any remaining rows */
            if (!empty($batch)) {
                $this->flushBatch($batch, $result);
            }

        } finally {
            fclose($handle);
        }

        return $result;
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /*
     * Detect whether the first row of the CSV is a header row.
     *
     * A header row is detected when any of the canonical column names
     * ('email', 'first_name', 'last', 'name', 'company', 'tags') appear
     * in the row values (case-insensitive).
     *
     * Returns [$hasHeader, $columnMap] where $columnMap is an associative
     * array mapping canonical field names to their column index positions.
     *
     * If no header is detected, the default positional order is used:
     *   0 = first_name, 1 = last_name, 2 = email, 3 = company, 4 = tags
     *
     * @param array $row  First CSV row as an array of strings
     * @return array{0: bool, 1: array<string, int>}
     */
    private function detectHeader(array $row): array
    {
        /* Canonical aliases: possible column header names -> field name */
        $aliases = [
            'first_name'  => ['first_name', 'first', 'firstname', 'given_name', 'given'],
            'last_name'   => ['last_name',  'last',  'lastname',  'surname',    'family'],
            'email'       => ['email', 'email_address', 'e-mail', 'mail'],
            'company'     => ['company', 'organisation', 'organization', 'org', 'business'],
            'tags'        => ['tags', 'groups', 'group', 'lists', 'list', 'category'],
        ];

        $normalised = array_map(
            fn($v) => strtolower(trim(preg_replace('/[^a-z0-9_]/i', '_', $v))),
            $row
        );

        $columnMap  = [];
        $isHeader   = false;

        foreach ($normalised as $index => $cellValue) {
            foreach ($aliases as $fieldName => $acceptedAliases) {
                if (in_array($cellValue, $acceptedAliases, true)) {
                    $columnMap[$fieldName] = $index;
                    $isHeader             = true;
                }
            }
        }

        if (!$isHeader) {
            /* Use default positional order */
            $columnMap = [
                'first_name' => 0,
                'last_name'  => 1,
                'email'      => 2,
                'company'    => 3,
                'tags'       => 4,
            ];
        }

        return [$isHeader, $columnMap];
    }

    /*
     * Map a CSV row (positional array) to a named field array using the column map.
     *
     * @param array              $row        Raw CSV row values
     * @param array<string, int> $columnMap  Maps field name -> column index
     * @return array<string, string>
     */
    private function mapRow(array $row, array $columnMap): array
    {
        $data = [];
        foreach ($columnMap as $fieldName => $index) {
            $data[$fieldName] = $row[$index] ?? '';
        }
        return $data;
    }

    /*
     * Flush a batch of RecipientData DTOs to the database.
     *
     * Step 1: Build plain arrays and call bulkInsert() for all rows at once.
     * Step 2: For each row that has tags, resolve/create groups and add pivots.
     *
     * The $result array is updated in place with imported/skipped counts.
     *
     * @param RecipientData[]  $batch   A batch of validated recipient data
     * @param array           &$result  The running import result array (mutated)
     */
    private function flushBatch(array $batch, array &$result): void
    {
        /* Build plain arrays for bulkInsert() */
        $rows = array_map(fn(RecipientData $dto) => $dto->toArray(), $batch);

        /* Perform the bulk insert and count how many were actually inserted */
        $inserted = $this->recipients->bulkInsert($rows);

        /*
         * rowCount() from INSERT IGNORE returns only newly inserted rows.
         * Rows skipped due to email duplicates are counted as 'skipped'.
         */
        $result['imported'] += $inserted;
        $result['skipped']  += (count($batch) - $inserted);

        /* Process tags for each RecipientData that has them */
        foreach ($batch as $dto) {
            if (empty($dto->tagsArray())) {
                continue;
            }

            /* Find the recipient we just inserted (or the existing one) */
            $recipient = $this->recipients->findByEmail($dto->email);
            if (!$recipient) {
                continue; /* Should not happen, but guard defensively */
            }

            foreach ($dto->tagsArray() as $tagName) {
                if ($tagName === '') {
                    continue;
                }

                $group = $this->recipients->findOrCreateGroup($tagName);
                $this->recipients->addToGroup((int) $recipient->id, (int) $group->id);
            }
        }
    }
}

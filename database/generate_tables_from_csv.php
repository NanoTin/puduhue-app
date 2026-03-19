<?php
/**
 * You are working on the Puduhue App project (see AGENT and README.md).
 *
 * Goal:
 *   Create a PHP CLI script that reads the CSV specs and generates the
 *   CREATE TABLE .sql files for ALL tables, in order, based on Tables.csv.
 *
 * Files:
 *   - database/spec/Tables.csv
 *   - database/spec/Columns.csv
 *   - database/spec/Audit Columns.csv
 *   - database/spec/Generic Log Table.csv
 *
 * Output:
 *   - One file per table in:
 *       database/tables/01_table_<TableName>.sql
 *
 * CSV assumptions:
 *   - All CSV files are delimited by semicolons (;).
 *   - The first row of each CSV is the header.
 *
 * Tables.csv:
 *   - Contains one row per logical table.
 *   - Must have at least:
 *       "TableName"                -> logical table name (e.g. empresas, menus, prodleche)
 *       "Audit Columns"            -> "TRUE" or "FALSE"
 *       "Generic Log Table"        -> "TRUE" or "FALSE"
 *   - Add or use a column "SqlGenerated" (TRUE/FALSE) to track if the .sql
 *     for that table has already been generated.
 *   - The script must only generate tables where:
 *       SqlGenerated is empty or FALSE.
 *
 * Columns.csv:
 *   - Contains one row per column.
 *   - Must have at least:
 *       "TableName"                -> to relate to Tables.csv
 *       "ColumnName"
 *       "DataType"                 -> e.g. INT, VARCHAR(50), DATETIME, etc.
 *       "IsPK"                     -> "TRUE"/"FALSE"
 *       "IsFK"                     -> "TRUE"/"FALSE"
 *       "Nullable"                 -> "TRUE"/"FALSE"
 *       "Default"                  -> default value expression or empty
 *       "Comment"                  -> column comment
 *
 * Audit Columns.csv:
 *   - Defines the standard audit columns to be added when "Audit Columns" is TRUE
 *     in Tables.csv.
 *   - Same structure as Columns.csv but only for audit columns.
 *
 * Generic Log Table.csv:
 *   - Defines the structure of the generic log table when "Generic Log Table" is TRUE.
 *   - Use it to generate a <TableName>log table for each table that requires it.
 *
 * Requirements:
 *   - For each table in Tables.csv that needs generation:
 *       1) Read its column definitions from Columns.csv.
 *       2) If "Audit Columns" = TRUE, append the audit columns from Audit Columns.csv.
 *       3) Build the CREATE TABLE statement for MariaDB 10.11.15:
 *          - Engine = InnoDB
 *          - Charset = utf8mb4
 *          - Set PRIMARY KEY based on IsPK columns.
 *          - Create INDEX or UNIQUE INDEX where appropriate (example: if a column has
 *            a flag like "IsUnique" or similar in Columns.csv; if not present, skip).
 *          - Add column comments from the CSV.
 *          - Add a TABLE comment using the table description if available.
 *       4) Write the resulting SQL into:
 *            database/tables/01_table_<TableName>.sql
 *          Overwrite the file if it already exists.
 *       5) If "Generic Log Table" = TRUE for that table:
 *          - Generate also a CREATE TABLE for "<TableName>log" using the
 *            structure defined in Generic Log Table.csv, adjusting names as needed.
 *          - Write that to:
 *            database/tables/01_table_<TableName>log.sql
 *       6) Update Tables.csv setting "SqlGenerated" = "TRUE" for that table.
 *
 *   - The script must:
 *       - Be idempotent: running it again should not break anything.
 *       - Only regenerate files for tables where SqlGenerated is not TRUE.
 *       - Echo to the console the name of each table processed.
 *
 *   - Do NOT invent columns. Only use what appears in the CSV files.
 *   - If the CSV contains additional fields (like "Length", "Precision", etc.),
 *     use them to build proper column definitions (e.g. VARCHAR(50), DECIMAL(10,2), etc.).
 *
 * Implementation notes:
 *   - Use built-in PHP functions to parse CSV (fopen, fgetcsv, etc.).
 *   - Use associative arrays indexed by header names for clarity.
 *   - Separate the logic into small functions:
 *       - loadCsv(string $path): array
 *       - buildCreateTableSql(array $tableRow, array $columns, array $auditColumns): string
 *       - buildLogTableSql(array $tableRow, array $genericLogColumns): string
 *       - writeSqlFile(string $tableName, string $sql, bool $isLogTable = false): void
 *       - updateTablesCsvSqlGenerated(...): void
 *
 * Now implement the full script body below, including a CLI entry point, so that:
 *   php database/generate_tables_from_csv.php
 * runs the generation for all pending tables.
 */
// === IMPLEMENTATION STARTS HERE ===

declare(strict_types=1);

$baseDir = __DIR__;
$specDir = $baseDir . '/spec';
$tablesPath = $specDir . '/Tables.csv';
$columnsPath = $specDir . '/Columns.csv';
$auditColumnsPath = $specDir . '/Audit Columns.csv';
$genericLogPath = $specDir . '/Generic Log Table.csv';
$outputDir = $baseDir . '/tables';

// Utility to load a semicolon-delimited CSV into an array of associative rows.
function loadCsv(string $path): array
{
    $rows = [];
    if (!is_file($path)) {
        throw new RuntimeException("CSV not found: {$path}");
    }
    if (($handle = fopen($path, 'r')) === false) {
        throw new RuntimeException("Cannot open CSV: {$path}");
    }
    $headers = fgetcsv($handle, 0, ';');
    if ($headers === false) {
        fclose($handle);
        return [];
    }
    $headers = array_map('trim', $headers);
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        // Skip empty lines
        if (count($data) === 1 && $data[0] === null) {
            continue;
        }
        $row = [];
        foreach ($headers as $idx => $key) {
            $row[$key] = array_key_exists($idx, $data) ? trim((string)$data[$idx]) : '';
        }
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function saveCsv(string $path, array $rows, array $headers): void
{
    if (($handle = fopen($path, 'w')) === false) {
        throw new RuntimeException("Cannot write CSV: {$path}");
    }
    fputcsv($handle, $headers, ';');
    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $key) {
            $line[] = $row[$key] ?? '';
        }
        fputcsv($handle, $line, ';');
    }
    fclose($handle);
}

function boolFromString(string $value): bool
{
    return strtoupper(trim($value)) === 'TRUE';
}

function getField(array $row, array $keys, string $default = ''): string
{
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

function formatDefault(?string $default): ?string
{
    if ($default === null || $default === '') {
        return null;
    }
    $default = trim($default);
    if (strcasecmp($default, 'NULL') === 0) {
        return 'DEFAULT NULL';
    }
    if (preg_match('/^(CURRENT_TIMESTAMP|NOW\\(\\)|CURRENT_DATE|CURRENT_TIME)/i', $default)) {
        return 'DEFAULT ' . $default;
    }
    if (preg_match('/^[0-9]+(\\.[0-9]+)?$/', $default)) {
        return 'DEFAULT ' . $default;
    }
    return "DEFAULT '" . str_replace("'", "''", $default) . "'";
}

function buildColumnDefinition(array $col): string
{
    $name = getField($col, ['Column Name', 'ColumnName', 'Name']);
    $dataType = getField($col, ['Data Type', 'DataType', 'Type']);
    $nullable = boolFromString(getField($col, ['Null', 'Nullable'], 'FALSE'));
    $default = formatDefault(getField($col, ['Default'], ''));
    $extra = getField($col, ['Extra'], '');
    $comment = getField($col, ['Description', 'Comment', 'Note'], '');

    $parts = [];
    $parts[] = "  `{$name}`";
    $parts[] = $dataType;
    $parts[] = $nullable ? 'NULL' : 'NOT NULL';
    if ($default !== null) {
        $parts[] = $default;
    }
    if ($extra !== '') {
        $parts[] = $extra;
    }
    if ($comment !== '') {
        $parts[] = "COMMENT '" . str_replace("'", "''", $comment) . "'";
    }
    return implode(' ', $parts);
}

function buildCreateTableSql(array $tableRow, array $columns): string
{
    $tableName = getField($tableRow, ['Table Name', 'TableName']);
    $tableComment = getField($tableRow, ['Description'], '');

    $columnLines = array_map('buildColumnDefinition', $columns);

    $constraints = [];
    $pkCols = [];
    foreach ($columns as $col) {
        if (boolFromString(getField($col, ['PK', 'IsPK'], 'FALSE'))) {
            $pkCols[] = $col['Column Name'];
        }
    }
    if (!empty($pkCols)) {
        $pkEscaped = array_map(fn($c) => "`{$c}`", $pkCols);
        $constraints[] = '  PRIMARY KEY (' . implode(', ', $pkEscaped) . ')';
    }

    foreach ($columns as $col) {
        $colName = $col['Column Name'];
        if (boolFromString(getField($col, ['Unique', 'IsUnique'], 'FALSE'))) {
            $constraints[] = "  UNIQUE KEY `uq_{$tableName}_{$colName}` (`{$colName}`)";
        }
    }

    foreach ($columns as $col) {
        if (boolFromString(getField($col, ['FK', 'IsFK'], 'FALSE'))) {
            $colName = $col['Column Name'];
            $ref = getField($col, ['FK Table.column', 'FKTable.column', 'FKTableColumn'], '');
            if ($ref !== '' && strpos($ref, '.') !== false) {
                [$refTable, $refCol] = explode('.', $ref, 2);
                $constraints[] = "  KEY `idx_{$tableName}_{$colName}` (`{$colName}`)";
                $constraints[] = "  CONSTRAINT `fk_{$tableName}_{$colName}` FOREIGN KEY (`{$colName}`) REFERENCES `{$refTable}` (`{$refCol}`)";
            }
        }
    }

    if (!empty($constraints)) {
        $columnLines = array_merge($columnLines, $constraints);
    }

    $ddl = [];
    $ddl[] = "CREATE TABLE `{$tableName}` (";
    $ddl[] = implode(",\n", $columnLines);
    $ddl[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' .
        ($tableComment !== '' ? " COMMENT '" . str_replace("'", "''", $tableComment) . "'" : '') . ';';

    return implode("\n", $ddl);
}

function buildLogTableSql(array $tableRow, array $genericLogColumns): string
{
    $tableName = getField($tableRow, ['Table Name', 'TableName']) . 'log';
    $columnLines = array_map('buildColumnDefinition', $genericLogColumns);
    $columnLines[] = '  PRIMARY KEY (`logid`)';

    $ddl = [];
    $ddl[] = "CREATE TABLE `{$tableName}` (";
    $ddl[] = implode(",\n", $columnLines);
    $ddl[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs genericos para " . getField($tableRow, ['Table Name', 'TableName']) . "';";
    return implode("\n", $ddl);
}

function writeSqlFile(string $outputDir, string $tableName, string $sql, bool $isLogTable = false): void
{
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    $fileName = $isLogTable ? $tableName . 'log' : $tableName;
    $path = rtrim($outputDir, '/\\') . '/01_table_' . $fileName . '.sql';
    file_put_contents($path, $sql . PHP_EOL);
}

function filterColumnsForTable(array $columns, string $tableName): array
{
    return array_values(array_filter($columns, static function ($col) use ($tableName) {
        $matchesTable = (isset($col['Table']) && $col['Table'] === $tableName);
        $matchesTableName = (isset($col['Table Name']) && $col['Table Name'] === $tableName);
        $matchesTableNameAlt = (isset($col['TableName']) && $col['TableName'] === $tableName);
        return $matchesTable || $matchesTableName || $matchesTableNameAlt;
    }));
}

function markTableGenerated(array &$tablesRows, array $headers, string $tableName): void
{
    foreach ($tablesRows as &$row) {
        $name = getField($row, ['Table Name', 'TableName']);
        if ($name === $tableName) {
            $row['SqlGenerated'] = 'TRUE';
        }
    }
    unset($row);
    if (!in_array('SqlGenerated', $headers, true)) {
        $headers[] = 'SqlGenerated';
    }
}

// Main CLI flow
$tablesRows = loadCsv($tablesPath);
$columnsRows = loadCsv($columnsPath);
$auditRows = loadCsv($auditColumnsPath);
$genericLogRows = loadCsv($genericLogPath);

$tableHeaders = [];
if (($handle = fopen($tablesPath, 'r')) !== false) {
    $tableHeaders = fgetcsv($handle, 0, ';') ?: [];
    $tableHeaders = array_map('trim', $tableHeaders);
    fclose($handle);
}
if (!in_array('SqlGenerated', $tableHeaders, true)) {
    $tableHeaders[] = 'SqlGenerated';
}

foreach ($tablesRows as $tableRow) {
    $tableName = getField($tableRow, ['Table Name', 'TableName']);
    if ($tableName === '') {
        continue;
    }
    $already = boolFromString($tableRow['SqlGenerated'] ?? '');
    if ($already) {
        continue;
    }

    $hasAudit = boolFromString(getField($tableRow, ['Audit Columns'], 'FALSE'));
    $hasLog = boolFromString(getField($tableRow, ['Generic Log Table'], 'FALSE'));

    $tableColumns = filterColumnsForTable($columnsRows, $tableName);

    if ($hasAudit) {
        foreach ($auditRows as $auditCol) {
            $exists = false;
            foreach ($tableColumns as $col) {
                if (($col['Column Name'] ?? '') === ($auditCol['Column Name'] ?? '')) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $clone = $auditCol;
                $clone['Table'] = $tableName;
                $clone['Table Name'] = $tableName;
                $tableColumns[] = $clone;
            }
        }
    }

    $createSql = buildCreateTableSql($tableRow, $tableColumns);
    writeSqlFile($outputDir, $tableName, $createSql, false);

    if ($hasLog) {
        $logSql = buildLogTableSql($tableRow, $genericLogRows);
        writeSqlFile($outputDir, $tableName, $logSql, true);
    }

    markTableGenerated($tablesRows, $tableHeaders, $tableName);
    echo "Generated: {$tableName}\n";
    if ($hasLog) {
        echo "Generated: {$tableName}log\n";
    }
}

// Persist Tables.csv with SqlGenerated updates
saveCsv($tablesPath, $tablesRows, $tableHeaders);

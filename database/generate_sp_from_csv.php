<?php
/**
 * You are working on the Puduhue App project (see AGENT and README.md).
 *
 * Goal:
 *   Create a PHP CLI script that reads the CSV specs and generates the
 *   Stored Procedures (.sql) for ALL tables, based on Tables.csv and Columns.csv.
 *
 * Files (input):
 *   - database/spec/Tables.csv
 *   - database/spec/Columns.csv
 *   - database/spec/Audit Columns.csv
 *
 * Output:
 *   - One .sql file per table in:
 *       database/sp/02_sp_<TableName>.sql
 *     This file should contain ALL the procedures for that table that are flagged as TRUE.
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
 *       "sp_insertar"              -> "TRUE"/"FALSE"
 *       "sp_editar"                -> "TRUE"/"FALSE"
 *       "sp_anular"                -> "TRUE"/"FALSE"
 *       "sp_eliminar"              -> "TRUE"/"FALSE"
 *       "sp_listar_resumen"        -> "TRUE"/"FALSE"
 *       "sp_listar_detalle"        -> "TRUE"/"FALSE"
 *       "sp_consultar_por_id_resumen"  -> "TRUE"/"FALSE"
 *       "sp_consultar_por_id_detalle"  -> "TRUE"/"FALSE"
 *
 *   - The script must use a tracking column:
 *       "SpGenerated" -> "TRUE"/"FALSE" (or empty)
 *
 *   - Behavior for "SpGenerated":
 *       - If the column does not exist yet, the script must add it to the header
 *         and initialize it as empty for all rows when rewriting the CSV.
 *       - The script must only generate SPs for tables where:
 *           SpGenerated is empty or "FALSE"
 *           AND at least one of the sp_* columns is "TRUE".
 *       - After generating the .sql for a table, set:
 *           SpGenerated = "TRUE"
 *         and rewrite Tables.csv.
 *
 * Columns.csv:
 *   - Contains one row per column.
 *   - Must have at least:
 *       "TableName"                -> to relate to Tables.csv
 *       "ColumnName"
 *       "DataType"                 -> e.g. INT, VARCHAR(50), DECIMAL(10,2), DATETIME, etc.
 *       "IsPK"                     -> "TRUE"/"FALSE"
 *       "Nullable"                 -> "TRUE"/"FALSE"
 *       "Default"                  -> default value expression or empty
 *       "Comment"                  -> column comment
 *
 * Audit Columns.csv:
 *   - Defines the standard audit columns that exist when "Audit Columns" = TRUE in Tables.csv.
 *   - Same structure as Columns.csv but only for audit columns (e.g. auditcreacionusuarioid, etc.).
 *
 * Stored Procedure conventions (from AGENT):
 *
 *   - All SPs must use the standard parameters:
 *       IN  p_in_json      JSON,
 *       IN  p_in_usuarioid INT,
 *       IN  p_in_dispositivo VARCHAR(50),
 *       IN  p_in_ip        VARCHAR(50),
 *       OUT p_out_json     JSON
 * 
 *   - Audit columns must NOT be read from p_in_json.
 *      Creation fields (auditcreacion*) are assigned ONLY in INSERT.
 *      Edition fields (auditedicion*) are assigned ONLY in EDIT/ANULAR/ELIMINAR.
 *      Values come from p_in_usuarioid, p_in_dispositivo, p_in_ip, and NOW().
 * 
 *   - SPs for maintenance (insert, edit, annul, delete):
 *       - Names:
 *           sp_<TableName>_insertar
 *           sp_<TableName>_editar
 *           sp_<TableName>_anular
 *           sp_<TableName>_eliminar
 *         (only generate those where the corresponding sp_* flag is TRUE in Tables.csv)
 *
 *       - Responsibilities (skeleton-level, do NOT invent business rules):
 *           * Parse p_in_json to read the fields that map to the table columns.
 *           * At the beginning of the stored procedure, start with: "sp_main: BEGIN"
 *           * Validate that p_in_json is not empty. Like this:
 *              IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
 *                 SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
 *                 LEAVE sp_main;  
 *              END IF; 
 *           * Validate that the key (or PK columns) are not duplicated on insert.
 *           * Validate that the record exists on edit/anular/eliminar.
 *           * Handle audit columns when present:
 *               - On insert:
 *                   auditcreacionusuarioid      = p_in_usuarioid
 *                   auditcreaciondispositivo    = p_in_dispositivo
 *                   auditcreacionip             = p_in_ip
 *                   auditcreacionfechahora      = NOW()
 *               - On edit/anular/eliminar:
 *                   auditedicionusuarioid       = p_in_usuarioid
 *                   auditediciondispositivo     = p_in_dispositivo
 *                   auditedicionip              = p_in_ip
 *                   auditedicionfechahora       = NOW()
 *           * sp_<TableName>_anular:
 *              * p_in_json MUST contain only the PK fields of the table.
 *              * The procedure MUST:
 *               - validate that the record exists,
 *               - NOT perform a physical DELETE,
 *               - update the "active"/"vigency" column (e.g. <TableName>vig or <TableName>activo)
 *                 to FALSE or 0,
 *               - set edition audit fields (auditedicion*) from:
 *                   auditedicionusuarioid       = p_in_usuarioid
 *                   auditediciondispositivo     = p_in_dispositivo
 *                   auditedicionip              = p_in_ip
 *                   auditedicionfechahora       = NOW()
 *              * Audit fields MUST NOT be read from p_in_json.
 *           * LOG insert for maintenance SP:
 *              * For tables that have a LOG table defined (see "Generic Log Table" in Tables.csv), the generator MUST include an INSERT into <TableName>log inside:
 *               - sp_<TableName>_insertar
 *               - sp_<TableName>_editar
 *               - sp_<TableName>_anular
 *               - sp_<TableName>_eliminar
 *              * This INSERT should:
 *               - record operation type (e.g. 'INSERT', 'EDIT', 'ANULAR', 'ELIMINAR'),
 *               - record user/device/ip/timestamp from the procedure parameters,
 *               - store a JSON backup of the affected row according to Generic Log Table.csv,
 *                 even if it is initially implemented as a TODO with a basic placeholder.
 *               * The generator must NOT read logregbkpjson from p_in_json; it is always computed inside the Stored Procedure.
 *            * Special case for sp_<TableName>_eliminar when a HIST table exists:
 *               - If Tables.csv defines another table named "<TableName>hist",
 *                 the generator must:
 *                   1) In the body of sp_<TableName>_eliminar, INSERT the current row
 *                      from the main table into "<TableName>hist" before deletion,
 *                      copying all relevant columns (ori_*) and setting the
 *                      historical audit columns (hist_audit...).
 *                   2) Then perform the physical DELETE on the main table "<TableName>"
 *                      using the PK from p_in_json.
 *
 *           * Set p_out_json with a JSON object like:
 *               {
 *                 "status": 200 or 400,
 *                 "message": "OK" or error description
 *               }
 *           * Do NOT return DATA inside p_out_json (no record arrays).
 *
 *       - Primary key behavior on INSERT:
 *           * The auto-increment PK column (e.g. <TableName>id INT AUTO_INCREMENT) MUST NOT be
 *             read from p_in_json.
 *           * The INSERT statement must omit the PK column so that the database generates it.
 *           * The procedure may use LAST_INSERT_ID() to get the new ID and include it in p_out_json
 *             if needed.
 *
 *       - LOG behavior for logregbkpjson:
 *           * In sp_<TableName>_insertar:
 *               - When inserting into the LOG table (<TableName>log), the column logregbkpjson
 *                 MUST be set to NULL, because there is no previous state to backup.
 *           * In sp_<TableName>_editar:
 *               - Before updating the main table, the procedure MUST:
 *                   1) Read the current row using the PK.
 *                   2) Convert that row to a JSON representation.
 *                   3) Insert a record into <TableName>log setting logregbkpjson to that JSON
 *                      (previous state).
 *               - Then perform the UPDATE on the main table. 
 *      - Transaction:
 *           * Stored Procedures MUST NOT contain BEGIN/COMMIT/ROLLBACK.
 *           * Transactions are controlled in PHP (Database class currently consolidated in src/Config/Database.php),
 *             which:
 *               - opens the transaction,
 *               - calls the Stored Procedure,
 *               - decides to COMMIT or ROLLBACK based on the result or exception.
 *           * The maintenance SPs generated by this script must assume they run inside
 *             an outer transaction and only implement:
 *               - validations,
 *               - DML operations (INSERT/UPDATE/DELETE),
 *               - assignment of p_out_json with status and message.
 *
 *   - SPs for queries (listar / consultar_por_id):
 *       - Names:
 *           sp_<TableName>_listar
 *           sp_<TableName>_listar_detalle
 *           sp_<TableName>_consulta_por_id
 *           sp_<TableName>_consulta_por_id_detalle
 *         (only generate those where the corresponding sp_* flag is TRUE in Tables.csv)
 *
 *       - Responsibilities (skeleton-level) using Columns.csv metadata:
 *           * Use p_in_json for filters when applicable.
 *           * Build the SELECT list using Columns.csv:
 *               - Include all columns where spListar_Select_Column = "TRUE".
 *               - Include PK columns even if spListar_Select_Column is not set.
 *               - If the table has audit columns (Audit Columns = TRUE in Tables.csv),
 *                 include the audit columns as well.
 *           * Use spListar_Filter_Column from Columns.csv to define optional filters:
 *               - For each column with spListar_Filter_Column = "TRUE":
 *                   - Read an optional filter from p_in_json named "filtro<ColumnName>"
 *                     (e.g. filtroRazonSocial, filtroUsuarioNombre, filtroInvItemDsc).
 *                   - For VARCHAR/TEXT columns, apply:
 *                         LIKE CONCAT('%', vFiltro, '%')
 *                     wrapped in a condition that ignores NULL/empty values.
 *           * Use spListar_Select_JOIN_Column_Name to generate controlled JOINs:
 *               - If spListar_Select_JOIN_Column_Name is not empty for a column
 *                 (e.g. "empresas.razonsocial"):
 *                   - Add the corresponding JOIN between the main table and the FK table
 *                     (table and join condition derived from the FK definition).
 *                   - Include that expression in the SELECT with a clear alias.
 *           * For transactional header tables with a date column:
 *               - LIST procedures must support:
 *                   filtroFechaDesde
 *                   filtroFechaHasta
 *                 read from p_in_json.
 *               - Only include these date filters when the table has at least one DATE/DATETIME/TIMESTAMP column.
 *               - Implement a BETWEEN filter:
 *                   - If filtroFechaDesde is NULL, use '1900-01-01'.
 *                   - If filtroFechaHasta is NULL, use CURRENT_DATE (or NOW()).
 *           * No data is returned in p_out_json.
 *           * The result set is the tabular SELECT output, to be consumed by PDO on the PHP side.
 *       - Query SPs (listar_*):
 *           * MUST follow the "filtroXxx" naming convention for filters coming from p_in_json.
 *           * Filters and JOINs must always be derived from Columns.csv metadata
 *             (spListar_Select_Column, spListar_Filter_Column, spListar_Select_JOIN_Column_Name)
 *             and must not invent table or column names that do not exist in the CSVs.

 *
 * General SP syntax requirements:
 *   - Target: MariaDB 10.11.15
 *   - Use DROP PROCEDURE IF EXISTS <sp_name> before each CREATE PROCEDURE.
 *   - Use DELIMITER // and DELIMITER ; around each CREATE PROCEDURE block.
 *   - Use consistent indentation and comments.
 *   - Do NOT hardcode table or column names; always use the CSV content.
 *   - Do NOT invent business-specific logic; the result is a generic skeleton following the conventions of the Puduhue App.
 *   - Exception: the DELETE + HIST copy pattern for tables that have a matching
 *     "<TableName>hist" entry in Tables.csv must always be included in sp_<TableName>_eliminar.
 *
* Output file format:
 *   - For each table (row in Tables.csv), if:
 *       * SpGenerated is not TRUE
 *       * and at least one sp_* flag is TRUE
 *     then:
 *       1) Read the table's columns from Columns.csv.
 *       2) Check the value of "Audit Columns" in Tables.csv:
 *            - If "Audit Columns" = TRUE:
 *                read the audit column definitions from Audit Columns.csv
 *                and append them to the table column list.
 *            - If FALSE:
 *                do not include audit columns.
 *       3) Build the full .sql file containing ALL requested SPs for that table:
 *          - sp_<TableName>_insertar          (if sp_insertar = TRUE)
 *          - sp_<TableName>_editar            (if sp_editar = TRUE)
 *          - sp_<TableName>_anular            (if sp_anular = TRUE)
 *          - sp_<TableName>_eliminar          (if sp_eliminar = TRUE)
 *          - sp_<TableName>_listar            (if sp_listar_resumen = TRUE)
 *          - sp_<TableName>_listar_detalle    (if sp_listar_detalle = TRUE)
 *          - sp_<TableName>_consulta_por_id   (if sp_consultar_por_id_resumen = TRUE)
 *          - sp_<TableName>_consulta_por_id_detalle (if sp_consultar_por_id_detalle = TRUE)
 *
 *       4) Write the resulting SQL into:
 *            database/sp/02_sp_<TableName>.sql
 *          Overwriting any existing file.
 *       5) Update Tables.csv, setting:
 *            SpGenerated = "TRUE" for that table.
 *
 *   - The script must be idempotent:
 *       * Running it multiple times should not regenerate SPs for tables already
 *         marked with SpGenerated = TRUE.
 *       * If a new table is added to Tables.csv, running the script again
 *         should generate the missing 02_sp_<TableName>.sql.
 *
 * Implementation notes:
 *   - Use built-in PHP functions to parse CSV (fopen, fgetcsv, etc.).
 *   - Represent each row as an associative array keyed by header names.
 *   - Create helper functions, for example:
 *       - loadCsv(string $path): array
 *       - saveCsv(string $path, array $rows): void
 *       - ensureSpGeneratedColumn(array &$tables): void
 *       - getColumnsForTable(array $columnsCsv, string $tableName): array
 *       - getAuditColumnsIfNeeded(array $auditCsv, array $tableRow): array
 *       - buildMaintenanceProceduresSql(array $tableRow, array $columns, array $auditColumns): string
 *       - buildQueryProceduresSql(array $tableRow, array $columns): string
 *       - writeSpSqlFile(string $tableName, string $sql): void
 *       - markTableAsGenerated(array &$tables, string $tableName): void
 *
 *   - Do NOT hardcode table or column names; always use the CSV content.
 *   - Do NOT invent business-specific logic; the result is a generic skeleton
 *     following the conventions of the Puduhue App.
 *
 * CLI entry point:
 *   - Implement a section at the bottom so that running:
 *       php database/generate_sp_from_csv.php
 *     will:
 *       1) Load Tables.csv and Columns.csv (and Audit Columns.csv).
 *       2) Ensure "SpGenerated" column exists in Tables.csv.
 *       3) Iterate tables in order.
 *       4) Generate 02_sp_<TableName>.sql for each pending table.
 *       5) Update Tables.csv.
 *
 * Now implement the full script body below this comment, according to these rules.
 */
declare(strict_types=1);

/**
 * Stored Procedure generator for Puduhue App.
 * Follows rules from AGENT, README.md, and the header spec.
 */

$baseDir = __DIR__;
$specDir = $baseDir . '/spec';
$tablesPath = $specDir . '/Tables.csv';
$columnsPath = $specDir . '/Columns.csv';
$auditColumnsPath = $specDir . '/Audit Columns.csv';
$outDir = $baseDir . '/sp';

// --- CSV helpers ------------------------------------------------------------
function loadCsv(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("CSV not found: {$path}");
    }
    $rows = [];
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
        if ($data === [null]) {
            continue;
        }
        $row = [];
        foreach ($headers as $idx => $key) {
            $row[$key] = $data[$idx] ?? '';
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

// --- Utility helpers --------------------------------------------------------
function getField(array $row, array $keys, string $default = ''): string
{
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

function getColumnName(array $column): string
{
    return getField($column, ['Column Name', 'ColumnName', 'Name']);
}

function getColumnDataType(array $column): string
{
    return getField($column, ['Data Type', 'DataType', 'Type'], 'VARCHAR(255)');
}

function getCsvFlag(array $column, string $key): bool
{
    return boolFlag($column[$key] ?? '');
}

function isTextType(string $dataType): bool
{
    $t = strtolower($dataType);
    return str_contains($t, 'char') || str_contains($t, 'text') || str_contains($t, 'json');
}

function isDateType(string $dataType): bool
{
    $t = strtolower($dataType);
    return str_contains($t, 'date') || str_contains($t, 'time');
}

function boolFlag(string $value): bool
{
    $v = strtoupper(trim($value));
    if ($v === '' || $v === 'FALSE' || $v === 'N/A') {
        return false;
    }
    return true;
}

function ensureSpGeneratedColumn(array &$rows, array &$headers): void
{
    if (!in_array('SpGenerated', $headers, true)) {
        $headers[] = 'SpGenerated';
        foreach ($rows as &$row) {
            $row['SpGenerated'] = '';
        }
        unset($row);
    }
}

function getTableName(array $row): string
{
    return getField($row, ['Table Name', 'TableName', 'Table']);
}

function getColumnsForTable(array $columnsCsv, string $tableName): array
{
    return array_values(array_filter($columnsCsv, static function ($column) use ($tableName) {
        return ($column['Table'] ?? '') === $tableName
            || ($column['Table Name'] ?? '') === $tableName
            || ($column['TableName'] ?? '') === $tableName;
    }));
}

function mergeAuditColumns(array $columns, array $auditCols): array
{
    $existing = array_map(static fn($col) => getColumnName($col), $columns);
    foreach ($auditCols as $audit) {
        $name = getColumnName($audit);
        if ($name !== '' && !in_array($name, $existing, true)) {
            $columns[] = $audit;
        }
    }
    return $columns;
}

function isHistTable(string $tableName): bool
{
    return str_ends_with(strtolower($tableName), 'hist');
}

function hasHistSibling(array $tables, string $tableName): bool
{
    $histName = $tableName . 'hist';
    foreach ($tables as $row) {
        if (getTableName($row) === $histName) {
            return true;
        }
    }
    return false;
}

function isAuditCreationColumn(string $name): bool
{
    return str_starts_with(strtolower($name), 'auditcreacion');
}

function isAuditEditionColumn(string $name): bool
{
    return str_starts_with(strtolower($name), 'auditedicion');
}

function isAuditColumn(string $name): bool
{
    return isAuditCreationColumn($name) || isAuditEditionColumn($name);
}

function isAutoIncrement(array $column): bool
{
    $extra = strtolower(getField($column, ['Extra'], ''));
    return str_contains($extra, 'auto_increment');
}

function findActiveColumn(array $columns): ?string
{
    foreach ($columns as $column) {
        $name = strtolower(getColumnName($column));
        if ($name === '') {
            continue;
        }
        if (str_contains($name, 'activo') || str_contains($name, 'vig')) {
            return getColumnName($column);
        }
    }
    return null;
}

function isPkColumn(array $column): bool
{
    return boolFlag(getField($column, ['PK', 'IsPK'], 'FALSE'));
}

function getPkColumns(array $columns): array
{
    return array_values(array_filter($columns, static fn($col) => isPkColumn($col)));
}

function dataTypeCastTarget(string $dataType): ?string
{
    $type = strtolower(trim($dataType));
    if ($type === '') {
        return null;
    }
    if (str_contains($type, 'int')) {
        return 'SIGNED';
    }
    if (str_starts_with($type, 'decimal') || str_starts_with($type, 'numeric')) {
        return strtoupper($dataType);
    }
    if (str_contains($type, 'float')) {
        return 'DECIMAL(20,6)';
    }
    if (str_contains($type, 'double')) {
        return 'DOUBLE';
    }
    if (str_starts_with($type, 'datetime') || str_starts_with($type, 'timestamp')) {
        return 'DATETIME';
    }
    if (str_starts_with($type, 'date')) {
        return 'DATE';
    }
    if (str_starts_with($type, 'time')) {
        return 'TIME';
    }
    if (str_contains($type, 'json')) {
        return 'JSON';
    }
    return null;
}

function jsonValueExpression(string $columnName, string $dataType): string
{
    $jsonExpr = "JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.{$columnName}'))";
    $cast = dataTypeCastTarget($dataType);
    if ($cast === null) {
        return $jsonExpr;
    }
    return "CAST({$jsonExpr} AS {$cast})";
}

function buildVariableDeclarations(array $columns): string
{
    $lines = [];
    foreach ($columns as $column) {
        $name = getColumnName($column);
        $type = getColumnDataType($column);
        if ($name === '') {
            continue;
        }
        $lines[] = "  DECLARE v_{$name} {$type};";
    }
    return implode("\n", $lines);
}

function buildJsonAssignments(array $columns, bool $skipAutoInc): string
{
    $lines = [];
    foreach ($columns as $column) {
        $name = getColumnName($column);
        if ($name === '') {
            continue;
        }
        if ($skipAutoInc && isAutoIncrement($column)) {
            continue;
        }
        $expr = jsonValueExpression($name, getColumnDataType($column));
        $lines[] = "    v_{$name} = {$expr}";
    }
    if (empty($lines)) {
        return '';
    }
    return '  SET ' . implode(",\n", $lines) . ';';
}

function buildPkCondition(array $pkColumns): string
{
    $parts = [];
    foreach ($pkColumns as $pk) {
        $name = getColumnName($pk);
        if ($name === '') {
            continue;
        }
        $parts[] = "`{$name}` = v_{$name}";
    }
    return implode(' AND ', $parts);
}

function logPkValue(array $pkColumn, string $operation): string
{
    $name = getColumnName($pkColumn);
    if ($name === '') {
        return 'NULL';
    }
    if ($operation === 'insertar' && isAutoIncrement($pkColumn)) {
        return 'LAST_INSERT_ID()';
    }
    return "v_{$name}";
}

function buildPrevStateSelect(string $tableName, array $columns, array $pkColumns): string
{
    $pkCondition = buildPkCondition($pkColumns);
    if ($pkCondition === '') {
        return "  -- TODO: set previous state JSON once PK condition is defined";
    }
    $jsonFields = [];
    foreach ($columns as $col) {
        $colName = getColumnName($col);
        if ($colName === '') {
            continue;
        }
        $jsonFields[] = "'" . $colName . "', `" . $tableName . "`.`" . $colName . "`";
    }
    $jsonExpr = 'JSON_OBJECT(' . implode(', ', $jsonFields) . ')';
    return <<<SQL
  SELECT {$jsonExpr}
  INTO v_prev_bkpjson
  FROM `{$tableName}`
  WHERE {$pkCondition}
  LIMIT 1;
SQL;
}

function auditValueForColumn(string $columnName, string $operation): ?string
{
    $lower = strtolower($columnName);
    $isInsert = $operation === 'insertar';
    $isEditAction = in_array($operation, ['editar', 'anular', 'eliminar'], true);

    if (isAuditCreationColumn($lower)) {
        if (!$isInsert) {
            return null;
        }
        if (str_contains($lower, 'usuarioid')) {
            return 'p_in_usuarioid';
        }
        if (str_contains($lower, 'dispositivo')) {
            return 'p_in_dispositivo';
        }
        if (str_contains($lower, 'ip')) {
            return 'p_in_ip';
        }
        if (str_contains($lower, 'fechahora')) {
            return 'NOW()';
        }
    }

    if (isAuditEditionColumn($lower)) {
        if (!$isEditAction) {
            return null;
        }
        if (str_contains($lower, 'usuarioid')) {
            return 'p_in_usuarioid';
        }
        if (str_contains($lower, 'dispositivo')) {
            return 'p_in_dispositivo';
        }
        if (str_contains($lower, 'ip')) {
            return 'p_in_ip';
        }
        if (str_contains($lower, 'fechahora')) {
            return 'NOW()';
        }
    }

    return null;
}

function columnsForOperation(array $columns, array $pkColumns, string $operation): array
{
    $selected = [];
    $pkNames = array_map(static fn($pk) => getColumnName($pk), $pkColumns);
    if ($operation === 'anular') {
        // Only PKs are needed for p_in_json parsing on anular.
        foreach ($pkColumns as $pk) {
            $name = getColumnName($pk);
            if ($name !== '') {
                $selected[$name] = $pk;
            }
        }
        return array_values($selected);
    }
    foreach ($columns as $column) {
        $name = getColumnName($column);
        if ($name === '') {
            continue;
        }
        if ($operation === 'insertar' && isAutoIncrement($column)) {
            continue;
        }
        if ($operation === 'eliminar') {
            if (in_array($name, $pkNames, true)) {
                $selected[$name] = $column;
            }
            continue;
        }
        if (isAuditColumn($name)) {
            continue;
        }
        $selected[$name] = $column;
    }
    foreach ($pkColumns as $pk) {
        $name = getColumnName($pk);
        if ($name !== '' && !isset($selected[$name])) {
            if ($operation === 'insertar' && isAutoIncrement($pk)) {
                continue;
            }
            $selected[$name] = $pk;
        }
    }
    return array_values($selected);
}

function buildInsertSql(string $tableName, array $columns, string $operation): string
{
    $insertColumns = [];
    $insertValues = [];
    foreach ($columns as $column) {
        $name = getColumnName($column);
        if ($name === '') {
            continue;
        }
        // On insert we ignore edition audit columns; they are set only on edit/anular/eliminar.
        if ($operation === 'insertar' && isAuditEditionColumn($name)) {
            continue;
        }
        $auditValue = auditValueForColumn($name, $operation);
        if ($auditValue !== null) {
            $insertColumns[] = "`{$name}`";
            $insertValues[] = $auditValue;
            continue;
        }
        if (isAutoIncrement($column)) {
            continue;
        }
        $insertColumns[] = "`{$name}`";
        $insertValues[] = "v_{$name}";
    }

    if (empty($insertColumns)) {
        return '';
    }

    $cols = implode(",\n    ", $insertColumns);
    $vals = implode(",\n    ", $insertValues);

    return "  INSERT INTO `{$tableName}` (\n    {$cols}\n  )\n  VALUES (\n    {$vals}\n  );";
}

function buildUpdateSql(string $tableName, array $columns, array $pkColumns, string $operation): string
{
    $setParts = [];
    $pkNames = array_map(static fn($pk) => getColumnName($pk), $pkColumns);

    foreach ($columns as $column) {
        $name = getColumnName($column);
        if ($name === '' || in_array($name, $pkNames, true)) {
            continue;
        }
        if (isAuditCreationColumn($name)) {
            continue;
        }
        $auditValue = auditValueForColumn($name, $operation);
        if ($auditValue !== null) {
            $setParts[] = "`{$name}` = {$auditValue}";
            continue;
        }
        $setParts[] = "`{$name}` = v_{$name}";
    }

    if (empty($setParts)) {
        return '';
    }

    $pkCondition = buildPkCondition($pkColumns);
    $setClause = implode(",\n    ", $setParts);

    return "  UPDATE `{$tableName}`\n  SET {$setClause}\n  WHERE {$pkCondition};";
}

function buildHistInsertSql(string $tableName, array $histColumns, array $mainColumns, array $pkColumns): string
{
    if (empty($histColumns)) {
        return '';
    }

    $mainNames = array_map(static fn($c) => getColumnName($c), $mainColumns);
    $mainLookup = array_combine($mainNames, $mainColumns);

    $insertCols = [];
    $selectCols = [];
    foreach ($histColumns as $histCol) {
        $histName = getColumnName($histCol);
        if ($histName === '' || isAutoIncrement($histCol)) {
            continue;
        }

        $src = null;
        $lower = strtolower($histName);

        if (isAuditEditionColumn($histName)) {
            $src = auditValueForColumn($histName, 'eliminar');
        } elseif (str_starts_with($lower, 'histaudit')) {
            $candidate = substr($histName, 4); // drop "hist"
            if (isset($mainLookup[$candidate])) {
                $src = "`{$tableName}`.`{$candidate}`";
            }
        } elseif (str_starts_with($lower, 'hist')) {
            $candidate = substr($histName, 4);
            if (isset($mainLookup[$candidate])) {
                $src = "`{$tableName}`.`{$candidate}`";
            }
        } elseif (str_starts_with($lower, 'ori_')) {
            $candidate = substr($histName, 4);
            if (isset($mainLookup[$candidate])) {
                $src = "`{$tableName}`.`{$candidate}`";
            }
        }

        if ($src === null && isset($mainLookup[$histName])) {
            $src = "`{$tableName}`.`{$histName}`";
        }

        if ($src === null) {
            $src = 'NULL';
        }

        $insertCols[] = "`{$histName}`";
        $selectCols[] = $src;
    }

    if (empty($insertCols)) {
        return '';
    }

    $pkCondition = buildPkCondition($pkColumns);
    $cols = implode(",\n    ", $insertCols);
    $vals = implode(",\n    ", $selectCols);

    return "  INSERT INTO `{$tableName}hist` (\n    {$cols}\n  )\n  SELECT\n    {$vals}\n  FROM `{$tableName}`\n  WHERE {$pkCondition};";
}

// --- SP builders ------------------------------------------------------------
function buildMaintenanceProcedure(
    string $operation,
    string $tableName,
    array $columns,
    array $pkColumns,
    bool $hasHist,
    array $histColumns,
    bool $hasLog
): string {
    $spName = "sp_{$tableName}_{$operation}";
    $columnsForJson = columnsForOperation($columns, $pkColumns, $operation);
    $declarations = buildVariableDeclarations($columnsForJson);
    $needsPrevJson = $hasLog && in_array($operation, ['editar', 'anular', 'eliminar'], true);
    if ($needsPrevJson) {
        $declarations .= ($declarations !== '' ? "\n" : '') . "  DECLARE v_prev_bkpjson JSON;";
    }
    $assignments = buildJsonAssignments($columnsForJson, $operation === 'insertar');
    $pkCondition = buildPkCondition($pkColumns);
    if ($pkCondition === '') {
        $fallbackPk = [];
        if (!empty($pkColumns)) {
            $fallbackPk = $pkColumns;
        } elseif (!empty($columns)) {
            $fallbackPk[] = $columns[0];
        }
        if (!empty($fallbackPk)) {
            $pkColumns = $fallbackPk;
            $pkCondition = buildPkCondition($pkColumns);
        }
    }
    $insertPkCondition = '';
    if ($operation === 'insertar') {
        $nonAutoPk = array_values(array_filter($pkColumns, static fn($pk) => !isAutoIncrement($pk)));
        $insertPkCondition = buildPkCondition($nonAutoPk);
    }

    $validation = <<<SQL
  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;
SQL;

    $dupCheck = '';
    if ($operation === 'insertar' && $insertPkCondition !== '') {
        $dupCheck = <<<SQL
  IF EXISTS (SELECT 1 FROM `{$tableName}` WHERE {$insertPkCondition}) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Duplicate primary key');
    LEAVE sp_main;
  END IF;
SQL;
    }

    $existenceCheck = '';
    if (in_array($operation, ['editar', 'anular', 'eliminar'], true) && $pkCondition !== '') {
        $existenceCheck = <<<SQL
  IF NOT EXISTS (SELECT 1 FROM `{$tableName}` WHERE {$pkCondition}) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;
SQL;
    }

    $dml = '';
    if ($operation === 'insertar') {
        $dml = buildInsertSql($tableName, $columns, $operation);
    } elseif ($operation === 'eliminar') {
        $histSql = $hasHist ? buildHistInsertSql($tableName, $histColumns, $columns, $pkColumns) : '';
        $deleteSql = $pkCondition !== '' ? "  DELETE FROM `{$tableName}` WHERE {$pkCondition};" : '';
        $dml = trim($histSql . "\n\n" . $deleteSql);
    } elseif ($operation === 'anular') {
        $activeColumn = findActiveColumn($columns);
        $setParts = [];
        if ($activeColumn !== null) {
            $setParts[] = "`{$activeColumn}` = 0";
        } else {
            $setParts[] = "-- TODO: set active/vigency column to 0";
        }
        $setParts[] = "`auditedicionusuarioid` = p_in_usuarioid";
        $setParts[] = "`auditediciondispositivo` = p_in_dispositivo";
        $setParts[] = "`auditedicionip` = p_in_ip";
        $setParts[] = "`auditedicionfechahora` = NOW()";
        $setClause = implode(",\n    ", $setParts);
        $dml = "  UPDATE `{$tableName}`\n  SET {$setClause}\n  WHERE {$pkCondition};";
    } else {
        $dml = buildUpdateSql($tableName, $columns, $pkColumns, $operation);
    }

    $prevStateSql = '';
    $logInsertBefore = '';
    $logInsertAfter = '';
    if ($hasLog) {
        $opCode = match ($operation) {
            'insertar' => 'INS',
            'editar' => 'EDT',
            'anular' => 'ANL',
            'eliminar' => 'DEL',
            default => 'OPR',
        };
        $logColumns = [];
        $logValues = [];
        foreach ($pkColumns as $pkCol) {
            $pkName = getColumnName($pkCol);
            if ($pkName === '') {
                continue;
            }
            $logColumns[] = "`{$pkName}`";
            $logValues[] = logPkValue($pkCol, $operation);
        }

        $logColumns = array_merge($logColumns, [
            '`logusuarioid`',
            '`logdispositivo`',
            '`logip`',
            '`logtipo`',
            '`logparamjson`',
            '`logregbkpjson`'
        ]);

        $logValues = array_merge($logValues, [
            'p_in_usuarioid',
            'NULL', // TODO: set usuariocod from usuarios
            'NULL', // TODO: set usuarionombre from usuarios
            'p_in_dispositivo',
            'p_in_ip',
            "'{$opCode}'",
            'p_in_json',
            $operation === 'insertar' ? 'NULL' : 'v_prev_bkpjson'
        ]);

        $logInsertStmt = "  -- Generic log insert\n  INSERT INTO `{$tableName}log` (\n    " .
            implode(",\n    ", $logColumns) . "\n  ) VALUES (\n    " .
            implode(",\n    ", $logValues) . "\n  );";

        if ($operation === 'insertar') {
            $logInsertAfter = $logInsertStmt;
        } else {
            $prevStateSql = buildPrevStateSelect($tableName, $columns, $pkColumns);
            $logInsertBefore = $logInsertStmt;
        }
    }

    $bodyParts = array_filter([
        $declarations,
        $validation,
        $assignments,
        $dupCheck,
        $existenceCheck,
        $prevStateSql,
        $logInsertBefore,
        $dml,
        $logInsertAfter,
        "  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');"
    ], static fn($part) => trim((string)$part) !== '');

    $body = implode("\n\n", $bodyParts);

    return <<<SQL
DROP PROCEDURE IF EXISTS {$spName}//
CREATE PROCEDURE {$spName}(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
{$body}
END//
SQL;
}

function buildQueryProcedure(string $suffix, string $tableName, array $columns): string
{
    $spName = "sp_{$tableName}_{$suffix}";

    // Build SELECT columns based on CSV flags
    $selectCols = [];
    $joins = [];
    $joinAdded = [];
    foreach ($columns as $col) {
        $colName = getColumnName($col);
        if ($colName === '') {
            continue;
        }
        $isPk = isPkColumn($col);
        $includeSelect = $isPk || getCsvFlag($col, 'spListar_Select_Column');
        $joinExpr = trim($col['spListar_Select_JOIN_Column_Name'] ?? '');

        if ($includeSelect) {
            if ($joinExpr !== '') {
                $alias = str_replace(['.', '`'], '_', $joinExpr);
                $selectCols[] = "{$joinExpr} AS `{$alias}`";
            } else {
                $selectCols[] = "t.`{$colName}`";
            }
        }

        if ($joinExpr !== '' && getCsvFlag($col, 'FK')) {
            $fkRef = $col['FK Table.column'] ?? $col['FKTable.column'] ?? '';
            if ($fkRef !== '' && str_contains($fkRef, '.')) {
                [$refTable, $refCol] = explode('.', $fkRef, 2);
                $joinKey = "{$refTable}.{$refCol}";
                if (!isset($joinAdded[$joinKey])) {
                    $joins[] = "  LEFT JOIN `{$refTable}` ON t.`{$colName}` = `{$refTable}`.`{$refCol}`";
                    $joinAdded[$joinKey] = true;
                }
            }
        }
    }

    // Ensure we always have at least one column
    if (empty($selectCols) && !empty($columns)) {
        $selectCols[] = 't.`' . getColumnName($columns[0]) . '`';
    }
    $selectList = implode(",\n    ", $selectCols);

    // Filters
    $filterDecls = [];
    $filterSets = [];
    $filterConditions = [];
    $dateDecls = [];
    $dateSets = [];
    $dateCondition = '';
    foreach ($columns as $col) {
        if (!getCsvFlag($col, 'spListar_Filter_Column')) {
            continue;
        }
        $colName = getColumnName($col);
        $dataType = getColumnDataType($col);
        $varName = 'v_filtro' . ucfirst($colName);
        $filterDecls[] = "  DECLARE {$varName} VARCHAR(255);";
        $filterSets[] = "  SET {$varName} = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtro" . ucfirst($colName) . "'));";
        if (isTextType($dataType)) {
            $filterConditions[] = "    AND ({$varName} IS NULL OR {$varName} = '' OR t.`{$colName}` LIKE CONCAT('%', {$varName}, '%'))";
        } else {
            $filterConditions[] = "    AND ({$varName} IS NULL OR {$varName} = '' OR t.`{$colName}` = {$varName})";
        }
    }

    // Date range (optional; only first date/time column)
    foreach ($columns as $col) {
        $colName = getColumnName($col);
        if (!isDateType(getColumnDataType($col))) {
            continue;
        }
        if (isAuditCreationColumn($colName) || isAuditEditionColumn($colName)) {
            continue;
        }
        $dateDecls = [
            "  DECLARE v_filtroFechaDesde DATE;",
            "  DECLARE v_filtroFechaHasta DATE;",
        ];
        $dateSets = [
            "  SET v_filtroFechaDesde = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), '1900-01-01');",
            "  SET v_filtroFechaHasta = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), CURRENT_DATE());",
        ];
        $dateCondition = "    AND (t.`{$colName}` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta)";
        break;
    }

    $filtersBlock = implode("\n", array_merge($filterDecls, $dateDecls));
    $filterSetBlock = implode("\n", array_merge($filterSets, $dateSets));
    $whereConditions = [];
    if ($dateCondition !== '') {
        $whereConditions[] = $dateCondition;
    }
    $whereConditions = array_merge($whereConditions, $filterConditions);
    $whereBlock = "  WHERE 1=1";
    if (!empty($whereConditions)) {
        $whereBlock .= "\n" . implode("\n", $whereConditions);
    }

    $joinBlock = empty($joins) ? '' : "\n" . implode("\n", $joins);

    return <<<SQL
DROP PROCEDURE IF EXISTS {$spName}//
CREATE PROCEDURE {$spName}(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
{$filtersBlock}
{$filterSetBlock}
  SELECT
    {$selectList}
  FROM `{$tableName}` t{$joinBlock}
{$whereBlock};
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
SQL;
}

function buildProceduresSql(array $tableRow, array $columns, array $auditColumns, array $allTables, array $allColumnsCsv): string
{
    $tableName = getTableName($tableRow);
    $hasAudit = boolFlag(getField($tableRow, ['Audit Columns'], 'FALSE'));
    $hasHist = hasHistSibling($allTables, $tableName);
    $hasLog = boolFlag(getField($tableRow, ['Generic Log Table'], 'FALSE'));
    if ($hasAudit) {
        $columns = mergeAuditColumns($columns, $auditColumns);
    }
    $pkCols = getPkColumns($columns);

    $flags = [
        'insertar' => boolFlag(getField($tableRow, ['sp_insertar'], '')),
        'editar' => boolFlag(getField($tableRow, ['sp_editar'], '')),
        'anular' => boolFlag(getField($tableRow, ['sp_anular'], '')),
        'eliminar' => boolFlag(getField($tableRow, ['sp_eliminar'], '')),
        'listar_resumen' => boolFlag(getField($tableRow, ['sp_listar_resumen'], '')),
        'listar_detalle' => boolFlag(getField($tableRow, ['sp_listar_detalle'], '')),
        'consulta_por_id_resumen' => boolFlag(getField($tableRow, ['sp_consultar_por_id_resumen'], '')),
        'consulta_por_id_detalle' => boolFlag(getField($tableRow, ['sp_consultar_por_id_detalle'], '')),
    ];

    $blocks = [];
    if ($flags['insertar']) {
        $blocks[] = buildMaintenanceProcedure('insertar', $tableName, $columns, $pkCols, false, [], $hasLog);
    }
    if ($flags['editar']) {
        $blocks[] = buildMaintenanceProcedure('editar', $tableName, $columns, $pkCols, false, [], $hasLog);
    }
    if ($flags['anular']) {
        $blocks[] = buildMaintenanceProcedure('anular', $tableName, $columns, $pkCols, false, [], $hasLog);
    }
    if ($flags['eliminar']) {
        $histCols = $hasHist ? getColumnsForTable($allColumnsCsv, $tableName . 'hist') : [];
        $blocks[] = buildMaintenanceProcedure('eliminar', $tableName, $columns, $pkCols, $hasHist, $histCols, $hasLog);
    }
    if ($flags['listar_resumen']) {
        $blocks[] = buildQueryProcedure('listar', $tableName, $columns);
    }
    if ($flags['listar_detalle']) {
        $blocks[] = buildQueryProcedure('listar_detalle', $tableName, $columns);
    }
    if ($flags['consulta_por_id_resumen']) {
        $blocks[] = buildQueryProcedure('consulta_por_id', $tableName, $columns);
    }
    if ($flags['consulta_por_id_detalle']) {
        $blocks[] = buildQueryProcedure('consulta_por_id_detalle', $tableName, $columns);
    }

    if (empty($blocks)) {
        return '';
    }

    return "DELIMITER //\n" . implode("\n\n", $blocks) . "\nDELIMITER ;\n";
}

function writeSpSqlFile(string $outDir, string $tableName, string $sql): void
{
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }
    $path = rtrim($outDir, '/\\') . '/02_sp_' . $tableName . '.sql';
    file_put_contents($path, $sql);
}

// --- Main CLI flow ----------------------------------------------------------
$tables = loadCsv($tablesPath);
$columnsCsv = loadCsv($columnsPath);
$auditCsv = loadCsv($auditColumnsPath);

$tableHeaders = [];
if (($h = fopen($tablesPath, 'r')) !== false) {
    $tableHeaders = fgetcsv($h, 0, ';') ?: [];
    fclose($h);
}
$tableHeaders = array_map('trim', $tableHeaders);
ensureSpGeneratedColumn($tables, $tableHeaders);

$forceGenerate = boolFlag((string)getenv('FORCE_GENERATE_SP'));

foreach ($tables as &$tableRow) {
    $tableName = getTableName($tableRow);
    if ($tableName === '' || isHistTable($tableName)) {
        continue;
    }

    $already = $forceGenerate ? false : boolFlag($tableRow['SpGenerated'] ?? '');
    $hasAnySp = array_reduce(
        ['sp_insertar', 'sp_editar', 'sp_anular', 'sp_eliminar', 'sp_listar_resumen', 'sp_listar_detalle', 'sp_consultar_por_id_resumen', 'sp_consultar_por_id_detalle'],
        fn($carry, $key) => $carry || boolFlag($tableRow[$key] ?? ''),
        false
    );

    if ($already || !$hasAnySp) {
        continue;
    }

    $cols = getColumnsForTable($columnsCsv, $tableName);
    $sql = buildProceduresSql($tableRow, $cols, $auditCsv, $tables, $columnsCsv);
    if ($sql === '') {
        continue;
    }

    writeSpSqlFile($outDir, $tableName, $sql);
    $tableRow['SpGenerated'] = 'TRUE';
    echo "Generated SPs for {$tableName}\n";
}
unset($tableRow);

saveCsv($tablesPath, $tables, $tableHeaders);

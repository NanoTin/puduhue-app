<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExcelExporter
{
    /**
     * Exporta un array asociativo a XLSX.
     * $columns => ['empresaid', 'empresarut', 'empresarazonsocial']
     * $data    => array de filas
     */
    public static function export(
        string $filename,
        array $columns,
        array $data,
        array $formatters = [],
        array $dateFields = []
    ): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Soportar:
        // 1) ['campo1', 'campo2']  (compatibilidad)
        // 2) ['campo1' => 'Header 1', 'campo2' => 'Header 2'] (recomendado)
        $isAssoc = array_keys($columns) !== range(0, count($columns) - 1);

        $fieldNames = $isAssoc ? array_keys($columns) : $columns;
        $headers    = $isAssoc ? array_values($columns) : $columns;
        $dateFormatByField = [];
        foreach ($dateFields as $key => $value) {
            if (is_int($key)) {
                $dateFormatByField[(string)$value] = 'dd-mm-yyyy';
            } else {
                $dateFormatByField[(string)$key] = (string)$value;
            }
        }

        // Encabezados
        $colIndex = 1;
        foreach ($headers as $headerText) {
            $cell = Coordinate::stringFromColumnIndex($colIndex) . 1;
            $sheet->setCellValue($cell, $headerText);
            $colIndex++;
        }

        // Filas
        $rowIndex = 2;
        foreach ($data as $row) {
            $colIndex = 1;
            foreach ($fieldNames as $field) {
                $cell = Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
                $value = $row[$field] ?? '';

                if (isset($formatters[$field]) && is_callable($formatters[$field])) {
                    $value = $formatters[$field]($value, $row);
                }

                if (isset($dateFormatByField[$field])) {
                    $dateTime = null;
                    if ($value instanceof \DateTimeInterface) {
                        $dateTime = $value;
                    } elseif (is_string($value) && trim($value) !== '') {
                        try {
                            $dateTime = new \DateTime($value);
                        } catch (\Exception $e) {
                            $dateTime = null;
                        }
                    }

                    if ($dateTime !== null) {
                        $sheet->setCellValue($cell, ExcelDate::PHPToExcel($dateTime));
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($dateFormatByField[$field]);
                        $colIndex++;
                        continue;
                    }
                }

                $sheet->setCellValue($cell, $value);
                $colIndex++;
            }
            $rowIndex++;
        }

        // Auto-ajustar ancho de columnas
        for ($i = 1; $i <= count($headers); $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }


        // Descargar archivo
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename={$filename}.xlsx");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

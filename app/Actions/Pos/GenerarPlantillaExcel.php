<?php

namespace App\Actions\Pos;

use App\Enums\TipoImportacion;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerarPlantillaExcel
{
    /**
     * Filas con desplegable y formato en la hoja de datos.
     */
    public const FILAS_MAXIMAS = 500;

    /**
     * Descarga la plantilla de Excel del tipo de venta: encabezados, formato por columna, desplegables
     * para las listas y una hoja con las instrucciones.
     */
    public function ejecutar(TipoImportacion $tipo): StreamedResponse
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet()->setTitle('Datos');
        $ultima = self::FILAS_MAXIMAS + 1;

        foreach ($tipo->columnas() as $indice => $columna) {
            $letra = Coordinate::stringFromColumnIndex($indice + 1);
            $hoja->setCellValue("{$letra}1", $columna['encabezado']);
            $hoja->getColumnDimension($letra)->setWidth(max(16, mb_strlen($columna['encabezado']) + 4));

            $formato = match ($columna['tipo']) {
                'fecha' => 'dd/mm/yyyy',
                'hora' => 'hh:mm',
                'dinero' => '#,##0',
                'entero' => NumberFormat::FORMAT_NUMBER,
                default => NumberFormat::FORMAT_TEXT,
            };
            $hoja->getStyle("{$letra}2:{$letra}{$ultima}")->getNumberFormat()->setFormatCode($formato);

            if ($columna['tipo'] === 'lista') {
                $lista = '"'.implode(',', array_column($columna['opciones'] ?? [], 'etiqueta')).'"';

                for ($fila = 2; $fila <= $ultima; $fila++) {
                    $validacion = $hoja->getCell("{$letra}{$fila}")->getDataValidation();
                    $validacion->setType(DataValidation::TYPE_LIST)
                        ->setAllowBlank(true)
                        ->setShowDropDown(false)
                        ->setShowErrorMessage(true)
                        ->setErrorTitle('Valor no válido')
                        ->setError('Elija un valor de la lista.')
                        ->setFormula1($lista);
                }
            }
        }

        $ultimaColumna = Coordinate::stringFromColumnIndex(count($tipo->columnas()));
        $hoja->getStyle("A1:{$ultimaColumna}1")->getFont()->setBold(true);
        $hoja->getStyle("A1:{$ultimaColumna}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDE9A9');
        $hoja->freezePane('A2');

        $this->escribirInstrucciones($libro, $tipo);
        $libro->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($libro): void {
            (new Xlsx($libro))->save('php://output');
        }, "plantilla-{$tipo->value}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Hoja con una línea por columna: qué se escribe y qué valores acepta.
     */
    private function escribirInstrucciones(Spreadsheet $libro, TipoImportacion $tipo): void
    {
        $hoja = $libro->createSheet()->setTitle('Instrucciones');
        $hoja->setCellValue('A1', "Cada fila de la hoja Datos es un registro de {$tipo->etiqueta()}. No cambie los encabezados ni su orden.");
        $hoja->setCellValue('A2', 'Cuando el pago es Mixto, los valores por medio deben sumar el valor total. En los demás pagos deje esas celdas vacías.');
        $hoja->setCellValue('A4', 'Columna');
        $hoja->setCellValue('B4', 'Qué escribir');
        $hoja->getStyle('A4:B4')->getFont()->setBold(true);

        foreach ($tipo->columnas() as $indice => $columna) {
            $hoja->setCellValue('A'.($indice + 5), $columna['encabezado']);
            $hoja->setCellValue('B'.($indice + 5), trim(($columna['tipo'] === 'lista' ? 'Una de: '.implode(', ', array_column($columna['opciones'] ?? [], 'etiqueta')).'. ' : '').$columna['ayuda']));
        }

        $hoja->getColumnDimension('A')->setWidth(28);
        $hoja->getColumnDimension('B')->setWidth(90);
    }
}

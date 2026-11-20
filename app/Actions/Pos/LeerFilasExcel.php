<?php

namespace App\Actions\Pos;

use App\Enums\TipoImportacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class LeerFilasExcel
{
    /**
     * Lee la hoja de datos del Excel y devuelve las filas con datos, indexadas por su número en el
     * archivo, con cada celda convertida al formato que esperan las reglas de registro. Las filas
     * completamente vacías se ignoran.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws ValidationException Si el archivo no se puede leer o no usa los encabezados de la plantilla.
     */
    public function ejecutar(string $ruta, TipoImportacion $tipo): array
    {
        $hoja = $this->abrirHoja($ruta);
        $columnas = $tipo->columnas();
        $posiciones = $this->ubicarColumnas($hoja, $columnas);
        $filas = [];

        for ($numero = 2; $numero <= $hoja->getHighestDataRow(); $numero++) {
            $fila = [];
            $conDatos = false;

            foreach ($columnas as $columna) {
                $celda = $hoja->getCell([$posiciones[$columna['campo']], $numero]);
                $bruto = $celda->getValue();

                if ($bruto !== null && trim((string) $bruto) !== '') {
                    $conDatos = true;
                }

                $fila[$columna['campo']] = $this->convertir($columna, $celda->getValue(), $celda->getFormattedValue());
            }

            if ($conDatos) {
                $filas[$numero] = $fila;
            }
        }

        if ($filas === []) {
            throw ValidationException::withMessages(['importacion' => 'El archivo no tiene filas con datos.']);
        }

        if (count($filas) > GenerarPlantillaExcel::FILAS_MAXIMAS) {
            throw ValidationException::withMessages(['importacion' => 'El archivo tiene más de '.GenerarPlantillaExcel::FILAS_MAXIMAS.' filas. Divídalo en varios.']);
        }

        return $filas;
    }

    private function abrirHoja(string $ruta): Worksheet
    {
        try {
            $lector = IOFactory::createReader('Xlsx');
            $lector->setReadDataOnly(false);
            $libro = $lector->load($ruta);

            return $libro->getSheetByName('Datos') ?? $libro->getSheet(0);
        } catch (Throwable) {
            throw ValidationException::withMessages(['importacion' => 'No se pudo leer el archivo. Use la plantilla de Excel (.xlsx).']);
        }
    }

    /**
     * Ubica cada columna esperada por su encabezado, sin importar el orden.
     *
     * @param  list<array<string, mixed>>  $columnas
     * @return array<string, int>
     */
    private function ubicarColumnas(Worksheet $hoja, array $columnas): array
    {
        $encabezados = [];

        for ($indice = 1; $indice <= Coordinate::columnIndexFromString($hoja->getHighestDataColumn(1)); $indice++) {
            $encabezados[$this->normalizar((string) $hoja->getCell([$indice, 1])->getValue())] = $indice;
        }

        $posiciones = [];
        $faltantes = [];

        foreach ($columnas as $columna) {
            $posicion = $encabezados[$this->normalizar($columna['encabezado'])] ?? null;

            if ($posicion === null) {
                $faltantes[] = $columna['encabezado'];
            } else {
                $posiciones[$columna['campo']] = $posicion;
            }
        }

        if ($faltantes !== []) {
            throw ValidationException::withMessages(['importacion' => 'Faltan columnas en el archivo: '.implode(', ', $faltantes).'. Descargue la plantilla y no cambie los encabezados.']);
        }

        return $posiciones;
    }

    /**
     * Convierte una celda según el tipo de su columna. Un valor que no se puede interpretar se deja
     * como texto para que la validación lo rechace con su mensaje.
     *
     * @param  array<string, mixed>  $columna
     */
    private function convertir(array $columna, mixed $valor, string $formateado): mixed
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        return match ($columna['tipo']) {
            'fecha' => $this->aFecha($valor),
            'hora' => $this->aHora($valor),
            'dinero' => $this->aNumero($valor),
            'entero' => $this->aNumero($valor),
            'lista' => $this->aOpcion($columna['opciones'], $valor),
            default => $this->aTexto($valor, $formateado, $columna['campo']),
        };
    }

    private function aTexto(mixed $valor, string $formateado, string $campo): string
    {
        $texto = is_float($valor) || is_int($valor) ? $formateado : (string) $valor;
        $texto = trim($texto);

        return $campo === 'placa' ? Str::upper(str_replace([' ', '-'], '', $texto)) : $texto;
    }

    private function aFecha(mixed $valor): string
    {
        if (is_numeric($valor)) {
            return CarbonImmutable::instance(Date::excelToDateTimeObject((float) $valor))->toDateString();
        }

        $texto = trim((string) $valor);

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            $fecha = CarbonImmutable::createFromFormat('!'.$formato, $texto);

            if ($fecha instanceof CarbonImmutable && $fecha->format($formato) === $texto) {
                return $fecha->toDateString();
            }
        }

        return $texto;
    }

    private function aHora(mixed $valor): string
    {
        if (is_numeric($valor)) {
            $segundos = (int) round(((float) $valor - floor((float) $valor)) * 86400);

            return sprintf('%02d:%02d', intdiv($segundos, 3600) % 24, intdiv($segundos % 3600, 60));
        }

        $texto = trim((string) $valor);

        return preg_match('/^(\d{1,2}):(\d{2})(:\d{2})?$/', $texto, $partes) === 1
            ? sprintf('%02d:%s', (int) $partes[1], $partes[2])
            : $texto;
    }

    private function aNumero(mixed $valor): string
    {
        if (is_int($valor) || is_float($valor)) {
            return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
        }

        $texto = trim(str_replace(['$', ' '], '', (string) $valor));

        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $texto) === 1 || preg_match('/^\d+,\d+$/', $texto) === 1) {
            $texto = str_replace(',', '.', str_replace('.', '', $texto));
        }

        return $texto;
    }

    /**
     * @param  list<array{valor: string, etiqueta: string}>  $opciones
     */
    private function aOpcion(array $opciones, mixed $valor): string
    {
        $buscado = $this->normalizar((string) $valor);

        foreach ($opciones as $opcion) {
            if ($this->normalizar($opcion['valor']) === $buscado || $this->normalizar($opcion['etiqueta']) === $buscado) {
                return $opcion['valor'];
            }
        }

        return trim((string) $valor);
    }

    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }
}

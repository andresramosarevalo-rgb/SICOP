<?php

namespace App\Actions\Pos;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EscribirCsv
{
    /**
     * Separador de columnas: Excel en español separa por punto y coma.
     */
    private const SEPARADOR = ';';

    /**
     * Descarga un CSV que Excel abre con doble clic: UTF-8 con marca de orden de bytes para las tildes,
     * punto y coma como separador y coma decimal en los números.
     *
     * @param  list<string>  $encabezados
     * @param  iterable<list<int|float|string|null>>  $filas
     */
    public function ejecutar(string $nombreArchivo, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas): void {
            $salida = fopen('php://output', 'w') ?: throw new RuntimeException('No se pudo abrir la salida del archivo.');

            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $encabezados, self::SEPARADOR);

            foreach ($filas as $fila) {
                fputcsv($salida, array_map($this->formatearCelda(...), $fila), self::SEPARADOR);
            }

            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Formatea una celda. Los textos que Excel podría tomar por una fórmula (empiezan por =, +, - o @)
     * se neutralizan para que un dato digitado no se ejecute al abrir el archivo.
     */
    private function formatearCelda(int|float|string|null $celda): string
    {
        if ($celda === null) {
            return '';
        }

        if (is_int($celda) || is_float($celda)) {
            return str_replace('.', ',', (string) $celda);
        }

        return preg_match('/^[=+\-@\t\r]/', $celda) === 1 ? "'".$celda : $celda;
    }
}

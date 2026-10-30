<?php
/** Aritmética monetaria exacta en centavos, sin float para cálculos de cobro. */
final class CotizadorMontos
{
    public static function centavos($decimal): int
    {
        $texto = (string)$decimal;
        if (!preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/D', $texto, $m)) {
            throw new InvalidArgumentException('Importe decimal inválido.');
        }
        return (int)$m[1] * 100 + (int)str_pad($m[2] ?? '0', 2, '0');
    }

    public static function moneda(int $centavos): string
    {
        if ($centavos < 0 || $centavos > 999999999999) {
            throw new InvalidArgumentException('Importe fuera de rango.');
        }
        return intdiv($centavos, 100) . '.' . str_pad((string)($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @param array $lineas Cada línea contiene cantidad y precios en formato DECIMAL de MySQL. */
    public static function totalizar(array $lineas): array
    {
        $armazones = 0;
        $micas = 0;
        foreach ($lineas as $linea) {
            $cantidad = $linea['cantidad'] ?? null;
            if (!is_int($cantidad) || $cantidad < 1 || $cantidad > 10) {
                throw new InvalidArgumentException('Cantidad incorrecta en la cotización.');
            }
            $armazones += self::centavos($linea['precio_unitario_armazon']) * $cantidad;
            $micas += self::centavos($linea['precio_unitario_mica']) * $cantidad;
        }
        return [
            'subtotal_armazones' => self::moneda($armazones),
            'subtotal_micas' => self::moneda($micas),
            'descuento' => '0.00',
            'impuestos' => '0.00',
            'total' => self::moneda($armazones + $micas),
        ];
    }
}

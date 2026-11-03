<?php
/** IVC 4.9.5.3.1 · Validación de identidad básica para clientes presenciales. */
final class PosClientes
{
    private static function nombre($valor, string $campo, bool $opcional = false): ?string
    {
        if (!is_string($valor)) throw new InvalidArgumentException('Campo inválido: '.$campo.'.');
        $texto = preg_replace('/\s+/u', ' ', trim($valor));
        if ($texto === null) throw new InvalidArgumentException('Caracteres inválidos en '.$campo.'.');
        if ($texto === '' && $opcional) return null;
        if (!preg_match("/\A[\p{L}][\p{L}\p{M} .'’-]{0,99}\z/uD", $texto)) {
            throw new InvalidArgumentException('Revisa '.$campo.' (letras y máximo 100 caracteres).');
        }
        return $texto;
    }

    public static function validar(array $datos): array
    {
        $nombre = self::nombre($datos['nombre'] ?? '', 'nombre');
        $apellidoP = self::nombre($datos['apellidoP'] ?? '', 'apellido paterno');
        $apellidoM = self::nombre($datos['apellidoM'] ?? '', 'apellido materno', true);
        $correo = $datos['correo'] ?? '';
        if (!is_string($correo)) throw new InvalidArgumentException('Correo inválido.');
        $correo = strtolower(trim($correo));
        if ($correo !== '' && (strlen($correo)>100 || !filter_var($correo,FILTER_VALIDATE_EMAIL))) {
            throw new InvalidArgumentException('Ingresa un correo electrónico válido o deja el campo vacío.');
        }
        $telefono = $datos['telefono'] ?? '';
        if (!is_string($telefono)) throw new InvalidArgumentException('Teléfono inválido.');
        $telefono = trim($telefono);
        if ($telefono !== '') {
            if (!preg_match('/\A[+0-9 ()-]{7,30}\z/D', $telefono)) {
                throw new InvalidArgumentException('El teléfono sólo puede contener números, espacios, guiones y paréntesis.');
            }
            $telefono = preg_replace('/[^0-9]/', '', $telefono);
            if (strlen($telefono)<7 || strlen($telefono)>15) {
                throw new InvalidArgumentException('El teléfono debe tener de 7 a 15 dígitos.');
            }
        }
        $obs = $datos['observaciones_pos'] ?? '';
        if (!is_string($obs) || strlen($obs)>1000) throw new InvalidArgumentException('Observaciones inválidas.');
        $obs = trim(preg_replace('/\s+/u', ' ', $obs) ?? '');
        if (preg_match('/[\x00-\x1f\x7f]/u', $obs) || preg_match_all('/./us', $obs)>255) {
            throw new InvalidArgumentException('Observaciones demasiado largas o inválidas (máximo 255 caracteres).');
        }
        return [
            'nombre'=>$nombre,'apellidoP'=>$apellidoP,'apellidoM'=>$apellidoM,
            'correo'=>$correo!==''?$correo:null,'telefono'=>$telefono!==''?$telefono:null,
            'observaciones_pos'=>$obs!==''?$obs:null,
        ];
    }
}

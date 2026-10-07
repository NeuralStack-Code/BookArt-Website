<?php
/**
 * Model Cuenta de BookArt (+ los datos personales del registro).
 * Lo carga Cuenta::fromRow() desde sp_cuenta_autenticar / sp_cuenta_por_correo, y Cuenta::fromForm() desde el registro.
 */
class Cuenta extends Model
{
    public ?int    $IdCuenta  = null;
    public ?int    $IdUsuario = null;
    public string  $Usuario   = '';
    public string  $Correo    = '';
    public int     $IdPermiso = 2;      // 1 = administrador · 2 = usuario
    public string  $Nombre    = '';
    public string  $Paterno   = '';
    public ?string $Materno   = null;   // nunca es obligatorio
    public string  $Telefono  = '';
    public int     $Dia       = 0;
    public int     $Mes       = 0;
    public int     $Anio      = 0;

    public function isAdmin(): bool
    {
        return $this->IdPermiso === 1;
    }

    /** Pantalla que le toca después de entrar. */
    public function homePath(): string
    {
        return $this->isAdmin() ? '/administrador' : '/catalogo';
    }
}

<?php
/** Entidad Cuenta de BookArt (tablas usuario + cuenta). Procedures: webService/sql/sp/sp_auth.sql. */
class CuentaBusiness extends Entity
{
    public string  $Usuario    = '';     // nombre de usuario; al entrar también puede ser el correo
    public string  $Correo     = '';
    public string  $Contrasena = '';     // hash sha512 (la base nunca recibe la contraseña)
    public int     $IdPermiso  = 2;      // 1 = administrador · 2 = usuario
    public string  $Nombre     = '';
    public string  $Paterno    = '';
    public ?string $Materno    = null;
    public string  $Telefono   = '';
    public int     $Dia        = 0;
    public int     $Mes        = 0;
    public int     $Anio       = 0;

    /** La cuenta si el usuario (o correo) y el hash coinciden; sin filas = credenciales incorrectas. */
    public function authenticate(): array
    {
        $this->db->command('sp_cuenta_autenticar');
        $this->db->addParameter('Usuario',    $this->Usuario);
        $this->db->addParameter('Contrasena', $this->Contrasena);
        return $this->db->execute();
    }

    /** La cuenta de un correo (sin validar contraseña); sin filas = no existe. */
    public function byEmail(): array
    {
        $this->db->command('sp_cuenta_por_correo');
        $this->db->addParameter('Correo', $this->Correo);
        return $this->db->execute();
    }

    /** Alta de usuario + cuenta (una transacción). Regresa el Id nuevo; 'dup' si el correo o el usuario ya existen. */
    public function insert(): array
    {
        $this->db->command('sp_cuenta_crear');
        $this->db->addParameter('Nombre',     $this->Nombre);
        $this->db->addParameter('Paterno',    $this->Paterno);
        $this->db->addParameter('Materno',    $this->Materno);
        $this->db->addParameter('Telefono',   $this->Telefono);
        $this->db->addParameter('Dia',        $this->Dia);
        $this->db->addParameter('Mes',        $this->Mes);
        $this->db->addParameter('Anio',       $this->Anio);
        $this->db->addParameter('Usuario',    $this->Usuario);
        $this->db->addParameter('Correo',     $this->Correo);
        $this->db->addParameter('Contrasena', $this->Contrasena);
        $this->db->addParameter('IdPermiso',  $this->IdPermiso);
        return $this->db->execute();
    }
}

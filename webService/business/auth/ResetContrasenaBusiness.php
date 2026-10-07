<?php
/** Entidad ResetContrasena: enlaces para restablecer la contraseña (tabla reset_contrasena). Procedures: webService/sql/sp/sp_auth.sql. */
class ResetContrasenaBusiness extends Entity
{
    public int    $IdCuenta   = 0;
    public string $TokenHash  = '';   // sha256 del token: el token en claro solo viaja en el correo
    public int    $Minutos    = 60;
    public string $Contrasena = '';   // hash sha512 de la contraseña nueva

    /** El enlace sin usar que la cuenta pidió en los últimos minutos; sin filas = no hay. */
    public function recent(): array
    {
        $this->db->command('sp_reset_reciente');
        $this->db->addParameter('IdCuenta', $this->IdCuenta);
        $this->db->addParameter('Minutos',  $this->Minutos);
        return $this->db->execute();
    }

    /** Crea el enlace (y borra los anteriores de la cuenta). */
    public function insert(): array
    {
        $this->db->command('sp_reset_crear');
        $this->db->addParameter('IdCuenta',  $this->IdCuenta);
        $this->db->addParameter('TokenHash', $this->TokenHash);
        $this->db->addParameter('Minutos',   $this->Minutos);
        return $this->db->execute();
    }

    /** Usa el enlace: lo marca como usado y cambia la contraseña. 'invalido' si no existe, venció o ya se usó. */
    public function redeem(): array
    {
        $this->db->command('sp_reset_usar');
        $this->db->addParameter('TokenHash',  $this->TokenHash);
        $this->db->addParameter('Contrasena', $this->Contrasena);
        return $this->db->execute();
    }
}

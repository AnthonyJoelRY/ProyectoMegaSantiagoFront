<?php
// Model/Entity/Usuario.php

class Usuario {
    public int $id_usuario;
    public int $id_rol;
    public string $rol_nombre = "";
    public string $nombre = "";
    public string $apellido = "";
    public string $email;
    public string $telefono = "";
    public string $clave_hash;
    public int $activo;

    public static function fromRow(array $row): self {
        $u = new self();
        $u->id_usuario = (int)($row["id_usuario"] ?? 0);
        $u->id_rol     = (int)($row["id_rol"] ?? 0);
        $u->rol_nombre = (string)($row["rol_nombre"] ?? "");
        $u->nombre     = (string)($row["nombre"] ?? "");
        $u->apellido   = (string)($row["apellido"] ?? "");
        $u->email      = (string)($row["email"] ?? "");
        $u->telefono   = (string)($row["telefono"] ?? "");
        $u->clave_hash = (string)($row["clave_hash"] ?? "");
        $u->activo     = (int)($row["activo"] ?? 0);
        return $u;
    }

    public function toArray(): array {
        return [
            "id_usuario"  => $this->id_usuario,
            "id_rol"      => $this->id_rol,
            "rol_nombre"  => $this->rol_nombre,
            "nombre"      => $this->nombre,
            "apellido"    => $this->apellido,
            "email"       => $this->email,
            "telefono"    => $this->telefono,
            "clave_hash"  => $this->clave_hash,
            "activo"      => $this->activo,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Grupo.php
class Grupo extends Model {
    protected $fillable = ['nome', 'lider_id'];
    public function membros() { return $this->belongsToMany(User::class, 'grupo_membros'); }
}

// Relacionamento em User.php
public function grupos() { return $this->belongsToMany(Grupo::class, 'grupo_membros'); }
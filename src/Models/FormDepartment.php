<?php

namespace SatuForm\FormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormDepartment extends Model
{
    use HasFactory;
    protected $connection = 'ais';
    protected $table = 'FORM.form_departments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'code',
    ];
}

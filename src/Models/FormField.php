<?php

namespace SatuForm\FormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    use HasFactory;
    protected $connection = 'ais';
    protected $table = 'FORM.form_fields';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'template_id',
        'type',
        'label',
        'required',
        'options',
        'formula',
        'table_columns',
        'table_rows',
        'sort_order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
        'table_columns' => 'array',
        'table_rows' => 'integer',
    ];
}

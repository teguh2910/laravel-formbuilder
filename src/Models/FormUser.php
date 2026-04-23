<?php

namespace SatuForm\FormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormUser extends Model
{
    use HasFactory;

    protected $table = 'FORM.form_users';

    protected $fillable = [
        'username',
        'password',
        'role',
        'name',
        'email',
        'department_id',
    ];
}

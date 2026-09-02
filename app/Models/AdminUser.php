<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class AdminUser extends Authenticatable
{

    use HasRoles;


    protected $guard_name = 'admin';



    protected $fillable = [

        'name',
        'email',
        'password',
        'is_active',
        'last_login_at'

    ];



    protected $hidden = [

        'password',
        'remember_token'

    ];



    protected $casts = [

        'is_active'=>'boolean',
        'last_login_at'=>'datetime'

    ];

}
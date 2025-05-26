<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Esputnik extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'esputnik';
    }
}

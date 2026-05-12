<?php

namespace App\Http\Controllers;

class PlaceholderController extends Controller
{
    public function __call(string $name, array $arguments)
    {
        return response(static::class . '::' . $name . ' pendiente de restaurar', 501);
    }
}
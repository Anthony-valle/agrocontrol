<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;

class PlaceholderReportController extends Controller
{
    public function __call(string $name, array $arguments)
    {
        return response(static::class . '::' . $name . ' pendiente de restaurar', 501);
    }
}

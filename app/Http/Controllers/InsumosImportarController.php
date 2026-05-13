<?php

namespace App\Http\Controllers;

use App\Imports\Insumos_importar;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InsumosImportarController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new Insumos_importar, $request->file('file'));

        return redirect()->route('insumos.index')->with('success', 'Insumos importados correctamente');
    }
}

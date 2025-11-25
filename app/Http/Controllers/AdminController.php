<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController 
{
    public function index()
    {
        return response()->json([
            'message' => 'Bienvenido al panel de administrador',
        ]);
    }
}

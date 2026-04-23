<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lista de roles',
            'data' => $roles
        ]);
    }
}
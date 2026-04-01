<?php

namespace App\Features\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Auth\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getAll();
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    public function login(Request $request)
    {
        return response()->json(['message' => 'Login attempt', 'data' => $request->all()]);
    }
}

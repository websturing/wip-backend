<?php

namespace App\Features\User\Controllers;

use App\Http\Controllers\Controller;
use App\Features\User\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getAll();
        return response()->json(['message' => 'Success', 'data' => $data]);
    }
}

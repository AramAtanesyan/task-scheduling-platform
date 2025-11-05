<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TaskStatus;

class UserController extends Controller
{
    /**
     * Get all users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = User::select('id', 'name', 'email')->get();
        return response()->json($users);
    }

    /**
     * Get all task statuses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statuses()
    {
        $statuses = TaskStatus::all();
        return response()->json($statuses);
    }
}

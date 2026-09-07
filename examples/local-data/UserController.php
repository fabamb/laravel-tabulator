<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController
{
    public function index()
    {
        // Whole dataset sent once; table then filters/sorts/paginates in the browser.
        return view('users.index', ['rows' => User::query()->select('id', 'name', 'email')->get()]);
    }
}

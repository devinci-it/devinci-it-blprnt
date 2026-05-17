<?php

namespace App\Controllers;

use DevinciIT\Blprnt\Core\Controller;
use DevinciIT\Blprnt\Auth\Auth;
class AdminController extends Controller
{
    public function index()
    {
        /*
        Sample gated admin dashboard view. In a real app, you'd fetch data here and pass it to the view.
        */
        return view('auth/admin', [
            'title' => 'Admin Dashboard',
            'isAuthenticated' => Auth::make()->check(),
            'user' => Auth::make()->user(),
        ]);
    }
}

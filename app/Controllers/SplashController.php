<?php
namespace App\Controllers;

use DevinciIT\Blprnt\Core\Controller;
use DevinciIT\Blprnt\Core\View;

class SplashController extends Controller
{
    public function index()
    {
        return View::render('splash', ['message' => 'Welcome to Blprnt!']);
    }
}

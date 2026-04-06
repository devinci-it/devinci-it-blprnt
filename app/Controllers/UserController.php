<?php
namespace App\Controllers;

use DevinciIT\Blprnt\Core\Controller;
use DevinciIT\Blprnt\Core\Response;

class UserController extends Controller
{
    public function index()
    {
        return Response::json([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ]);
    }
}

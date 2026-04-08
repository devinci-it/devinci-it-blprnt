<?php
namespace App\Controllers;

use DevinciIT\Blprnt\Core\Controller;

/**
 * SplashController - Welcome/Home Page Controller
 * 
 * @important ASSET INJECTION CAVEAT:
 * 
 * Always use the view() helper function instead of View::render() directly
 * for automatic default asset injection:
 * 
 *   ✅ CORRECT:  return view('splash', $data);
 *   ❌ WRONG:    return View::render('splash', $data);
 * 
 * Why? The view() helper automatically includes all default CSS/JS registered
 * via set_default_assets() in bootstrap/app.php. Direct View::render() calls
 * bypass this system.
 * 
 * Default assets are managed globally in bootstrap/app.php:
 * 
 *   set_default_assets(
 *       ['assets/css/reset.css', 'assets/css/theme.css'],
 *       ['assets/js/common.js']
 *   );
 * 
 * All views automatically include these assets when using view() helper.
 * Page-specific assets can be added as additional parameters:
 * 
 *   view('splash', $data, ['assets/css/splash.css'], ['assets/js/splash.js']);
 */
class SplashController extends Controller
{
    /**
     * Display the splash/welcome page
     * 
     * Uses view() helper to ensure default assets are automatically included.
     */
    public function index()
    {
        // Use view() helper for automatic default asset injection
        return view('splash', ['message' => 'Welcome to Blprnt!']);
    }
}


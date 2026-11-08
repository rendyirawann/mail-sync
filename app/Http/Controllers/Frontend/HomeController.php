<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Beranda publik — satu-satunya halaman yang bisa diindeks mesin pencari.
 */
class HomeController extends Controller
{
    /**
     * Tampilkan beranda yang menjelaskan aplikasi.
     */
    public function __invoke(): View
    {
        return view('frontend.home');
    }
}

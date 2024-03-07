<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Redirect to API documentation.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        return redirect()->to('/api');
    }
}

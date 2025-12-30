<?php

namespace App\Http\Controllers;

use App\Models\Water;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UitlegController extends Controller
{
    public function index()
    {
        return Inertia::render('Uitleg/Index');
    }

    public function kaart()
    {
        $waters = Water::with('nachtviszones')->get();

        return Inertia::render('Uitleg/Kaart', [
            'waters' => $waters,
        ]);
    }
}
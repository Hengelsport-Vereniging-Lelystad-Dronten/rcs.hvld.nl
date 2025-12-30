<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\OvertredingType;
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

    public function overtredingen()
    {
        $types = OvertredingType::with(['defaultStrafmaat', 'recidiveStrafmaat'])
            ->orderBy('code')
            ->get();

        return Inertia::render('Uitleg/Overtredingen', [
            'types' => $types,
        ]);
    }

    public function faq()
    {
        $faqs = Faq::all();
        return Inertia::render('Uitleg/Faq', ['faqs' => $faqs]);
    }

    public function handleidingen()
    {
        return Inertia::render('Uitleg/Handleidingen');
    }
}
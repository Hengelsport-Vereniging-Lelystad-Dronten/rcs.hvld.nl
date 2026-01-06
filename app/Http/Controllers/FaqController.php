<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * Toont de publieke FAQ pagina.
     */
    public function index()
    {
        $faqs = Faq::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();

        return Inertia::render('Uitleg/Faq', [
            'faqs' => $faqs,
        ]);
    }
}
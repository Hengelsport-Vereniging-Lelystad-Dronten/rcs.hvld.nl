<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('order')->get();
        return Inertia::render('Beheer/Faqs/Index', ['faqs' => $faqs]);
    }

    public function create()
    {
        return Inertia::render('Beheer/Faqs/CreateEdit', ['faq' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'order' => 'required|integer',
        ]);

        Faq::create($validated);

        return redirect()->route('beheer.faqs.index')->with('success', 'FAQ succesvol aangemaakt.');
    }

    public function edit(Faq $faq)
    {
        return Inertia::render('Beheer/Faqs/CreateEdit', ['faq' => $faq]);
    }

    public function update(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'order' => 'required|integer',
        ]);

        $faq->update($validated);

        return redirect()->route('beheer.faqs.index')->with('success', 'FAQ succesvol bijgewerkt.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->route('beheer.faqs.index')->with('success', 'FAQ verwijderd.');
    }
}
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
        return Inertia::render('Beheer/Faqs/Index', [
            'faqs' => $faqs
        ]);
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
            'is_active' => 'boolean',
        ]);

        $faq = Faq::create($validated);

        activity()
            ->performedOn($faq)
            ->log('FAQ item aangemaakt');

        return redirect()->route('beheer.faqs.index')->with('success', 'FAQ item succesvol aangemaakt.');
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
            'is_active' => 'boolean',
        ]);

        $oldData = $faq->only(['question', 'answer', 'order', 'is_active']);

        $faq->update($validated);

        activity()
            ->performedOn($faq)
            ->withProperties(['old' => $oldData, 'new' => $validated])
            ->log('FAQ item bijgewerkt');

        return redirect()->route('beheer.faqs.index')->with('success', 'FAQ item succesvol bijgewerkt.');
    }

    public function destroy(Faq $faq)
    {
        activity()->performedOn($faq)->log('FAQ item verwijderd');
        $faq->delete();
        return redirect()->route('beheer.faqs.index')->with('success', 'FAQ item verwijderd.');
    }
}
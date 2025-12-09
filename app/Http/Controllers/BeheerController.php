<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User; 
use App\Models\Water; // Voorbereiding voor wateren

class BeheerController extends Controller
{
    /**
     * Controller: BeheerController
     *
     * Algemene beheerpagina's en hulpfuncties voor gebruikers met beheerrechten.
     * Deze controller bevat vaak overzicht- en dashboardmethods voor beheerders.
     */
    /**
     * Toon de hoofd Beheerder Dashboard pagina.
     */
    public function index()
    {
        return Inertia::render('Beheer/Index');
    }

    /**
     * Toon de Gebruikers Beheer pagina (Overzicht).
     */
    public function usersIndex()
    {
        // Haal alle gebruikers op, gesorteerd op rol en naam
        $users = User::orderByRaw("FIELD(role, 'Beheerder', 'Coordinator', 'Controleur')")->get();
        
        return Inertia::render('Beheer/Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Toon de Wateren Beheer pagina (Overzicht).
     */
    public function watersIndex()
    {
        // Haal alle wateren op
        $waters = Water::orderBy('naam')->get();
        
        return Inertia::render('Beheer/Waters/Index', [
            'waters' => $waters
        ]);
    }
}

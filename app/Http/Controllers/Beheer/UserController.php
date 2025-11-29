<?php

namespace App\Http\Controllers\Beheer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::orderByRaw("FIELD(role, 'Beheerder', 'Coordinator', 'Controleur')")->get();
        
        return Inertia::render('Beheer/Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = ['Beheerder', 'Coördinator', 'Controleur']; // Beschikbare rollen
        return Inertia::render('Beheer/Users/CreateEdit', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:Beheerder,Coördinator,Controleur',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('beheer.users.index')
            ->with('message', 'Gebruiker "' . $request->name . '" succesvol toegevoegd.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // HAAL DE RESOURCE OP VOORDAT U DEZE GEBRUIKT
        $user = User::findOrFail($id);

        $roles = ['Beheerder', 'Coördinator', 'Controleur']; // Beschikbare rollen
        return Inertia::render('Beheer/Users/CreateEdit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // HAAL DE RESOURCE OP VOORDAT U DEZE GEBRUIKT
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8', // Wachtwoord is optioneel bij update
            'role' => 'required|in:Beheerder,Coördinator,Controleur',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        // Voeg wachtwoord alleen toe als het is ingevuld
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->route('beheer.users.index')
            ->with('message', 'Gebruiker "' . $request->name . '" succesvol bijgewerkt.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // HAAL DE RESOURCE OP VOORDAT U DEZE GEBRUIKT
        $user = User::findOrFail($id);
        
        // Voorkom dat de gebruiker zichzelf verwijdert (optionele beveiliging)
        if (auth()->id() === $user->id) {
            return redirect()->back()
                ->with('error', 'U kunt uw eigen account niet verwijderen.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('beheer.users.index')
            ->with('message', 'Gebruiker "' . $userName . '" succesvol verwijderd.');
    }
}

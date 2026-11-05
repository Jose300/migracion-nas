<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->name = trim("{$request->first_name} {$request->last_name}");
        $user->email = $request->email;
        $user->country_code = $request->country_code;
        $user->area_code = $request->area_code;
        $user->phone = $request->phone;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('perfil.index')->with('success', 'Perfil actualizado correctamente.');
    }
}

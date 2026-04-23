<?php

namespace SatuForm\FormBuilder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SatuForm\FormBuilder\Models\FormUser;

class FormBuilderPageController extends Controller
{
    private function render(string $initialView)
    {
        return view('formbuilder::formbuilder', [
            'formbuilderInitialView' => $initialView,
        ]);
    }

    public function landing()
    {
        return $this->render('landing');
    }

    public function login()
    {
        return $this->render('login');
    }

    public function forms()
    {
        return $this->render('fillList');
    }

    public function formsFill()
    {
        return $this->render('fillForm');
    }

    public function track()
    {
        return $this->render('track');
    }

    public function admin()
    {
        return $this->render('admin');
    }

    public function mySubmissions()
    {
        return $this->render('mySubmissions');
    }

    public function authenticate(Request $request)
    {
        $payload = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = FormUser::query()
            ->where('username', trim((string) $payload['username']))
            ->where('password', (string) $payload['password'])
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department_id,
            ],
        ]);
    }
}

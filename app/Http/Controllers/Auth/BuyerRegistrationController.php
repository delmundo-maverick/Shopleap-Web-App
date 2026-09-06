<?php


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BuyerProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuyerRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register.buyer');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_initial' => ['nullable', 'string', 'max:5'],
            'sex' => ['required', 'in:male,female'],
            'birthday' => ['required', 'date', 'before:today'],
            'contact_no' => ['required', 'string', 'max:20'],

            'province' => ['required', 'string'],
            'municipality' => ['required', 'string'],
            'barangay' => ['required', 'string'],
            'house_number' => ['required', 'string', 'max:100'],
            'street' => ['required', 'string', 'max:150'],
            'additional_address' => ['nullable', 'string', 'max:255'],

            'id_upload' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            'terms' => ['required', 'accepted'],
        ]);

        // Server-side age calculation — the form's age field is readonly/client-computed,
        // never trust that value directly, recompute it here.
        $age = Carbon::parse($validated['birthday'])->age;

        DB::transaction(function () use ($request, $validated, $age) {
            $user = User::create([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => $validated['password'], // hashed automatically via model cast
                'role' => 'buyer',
            ]);

            $idPath = $request->file('id_upload')->store('buyer_uploads/ids', 'r2');

            BuyerProfile::create([
                'user_id' => $user->id,
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'middle_initial' => $validated['middle_initial'] ?? null,
                'sex' => $validated['sex'],
                'contact_no' => $validated['contact_no'],
                'birthday' => $validated['birthday'],
                'age' => $age,
                'province' => $validated['province'],
                'municipality' => $validated['municipality'],
                'barangay' => $validated['barangay'],
                'house_number' => $validated['house_number'],
                'street' => $validated['street'],
                'additional_address' => $validated['additional_address'] ?? null,
                'id_upload_path' => $idPath,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('login')->with(
            'status',
            'Registration submitted! Please wait for the administrator\'s approval — you\'ll be notified by email.'
        );
    }
}

<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueRoleEmail implements ValidationRule
{
    public function __construct(public string $role) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (User::withEmail((string) $value)->where('role', $this->role)->exists()) {
            $fail("This email is already registered as a {$this->role}.");
        }
    }
}

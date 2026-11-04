<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\SaveAddressRequest;
use App\Models\BuyerAddress;
use App\Models\User;
use App\Services\BuyerAddressLocations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', BuyerAddress::class);

        return $this->listing($request);
    }

    public function locations(Request $request, BuyerAddressLocations $locations): JsonResponse
    {
        $data = $request->validate(['level' => ['required', 'in:province,municipality,barangay'], 'code' => ['required_unless:level,province', 'nullable', 'regex:/^[0-9]{9,10}$/']]);

        return response()->json($locations->places($data['level'], $data['code'] ?? null));
    }

    public function store(SaveAddressRequest $request, BuyerAddressLocations $locations): JsonResponse
    {
        Gate::authorize('create', BuyerAddress::class);
        $data = $locations->normalize($request->validated());
        $address = DB::transaction(function () use ($request, $data): BuyerAddress {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $default = ! $request->user()->addresses()->exists() || ($data['is_default'] ?? false);
            if ($default) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            return $request->user()->addresses()->create(array_merge($data, ['is_default' => $default]));
        });

        return $this->listing($request, $address->id);
    }

    public function update(SaveAddressRequest $request, BuyerAddress $address, BuyerAddressLocations $locations): JsonResponse
    {
        Gate::authorize('update', $address);
        $data = $locations->normalize($request->validated());
        DB::transaction(function () use ($request, $address, $data): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $address = $request->user()->addresses()->whereKey($address->id)->firstOrFail();
            $default = $address->is_default || ($data['is_default'] ?? false);
            if ($default) {
                $request->user()->addresses()->update(['is_default' => false]);
            }
            $address->update(array_merge($data, ['is_default' => $default]));
        });

        return $this->listing($request, $address->id);
    }

    public function destroy(Request $request, BuyerAddress $address): JsonResponse
    {
        Gate::authorize('delete', $address);
        DB::transaction(function () use ($request, $address): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $address = $request->user()->addresses()->whereKey($address->id)->firstOrFail();
            $replacement = $request->user()->addresses()->whereKeyNot($address->id)->latest('id')->first();
            if (! $replacement) {
                throw ValidationException::withMessages(['address' => 'Keep at least one delivery address. Add another before deleting this address.']);
            }
            $default = $address->is_default;
            $address->update(['is_default' => false]);
            $address->delete();
            if ($default) {
                $replacement->update(['is_default' => true]);
            }
        });

        return $this->listing($request);
    }

    public function setDefault(Request $request, BuyerAddress $address): JsonResponse
    {
        Gate::authorize('update', $address);
        DB::transaction(function () use ($request, $address): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $request->user()->addresses()->update(['is_default' => false]);
            $request->user()->addresses()->whereKey($address->id)->firstOrFail()->update(['is_default' => true]);
        });

        return $this->listing($request);
    }

    private function listing(Request $request, ?int $savedId = null): JsonResponse
    {
        return response()->json(['addresses' => $request->user()->addresses()->orderByDesc('is_default')->latest('id')->get(), 'saved_id' => $savedId]);
    }
}

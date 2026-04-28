<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();

        return response()->json(['data' => $addresses]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'city_id' => 'nullable|integer|exists:cities,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'postal_code' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            if ($validated['is_default'] ?? false) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            $address = $request->user()->addresses()->create($validated);

            // If this is the only address, make it default automatically
            if ($request->user()->addresses()->count() === 1) {
                $address->update(['is_default' => true]);
            }

            return response()->json([
                'message' => 'Adres başarıyla eklendi',
                'data' => $address,
            ], 201);
        });
    }

    public function update(Request $request, UserAddress $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'string|max:255',
            'name' => 'string|max:255',
            'phone' => 'string|max:20',
            'address' => 'string',
            'city' => 'string',
            'district' => 'string',
            'city_id' => 'nullable|integer|exists:cities,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'postal_code' => 'nullable|string',
            'is_default' => 'boolean',
        ]);

        return DB::transaction(function () use ($validated, $address, $request) {
            if ($validated['is_default'] ?? false) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update($validated);

            return response()->json([
                'message' => 'Adres güncellendi',
                'data' => $address,
            ]);
        });
    }

    public function destroy(Request $request, UserAddress $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $address->delete();

        return response()->json(['message' => 'Adres silindi']);
    }
}

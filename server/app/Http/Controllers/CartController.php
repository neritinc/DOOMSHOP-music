<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'ok',
            'data' => Cart::with(['user', 'items.track'])->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
        ]);

        $cart = Cart::create($validated);
        return response()->json(['message' => 'Cart created', 'data' => $cart], 201);
    }

    public function indexSelf(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'ok',
            'data' => Cart::with(['items.track'])
                ->where('user_id', $request->user()->id)
                ->get(),
        ]);
    }

    public function storeSelf(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $cart = Cart::create([
            'user_id' => $request->user()->id,
            'date' => $validated['date'],
        ]);

        return response()->json(['message' => 'Cart created', 'data' => $cart], 201);
    }

    public function show(int $id): JsonResponse
    {
        $cart = Cart::with(['user', 'items.track'])->find($id);
        if (! $cart) {
            return response()->json(['message' => 'Cart not found', 'data' => null], 404);
        }
        return response()->json(['message' => 'ok', 'data' => $cart]);
    }

    public function destroy(int $id): JsonResponse
    {
        $cart = Cart::find($id);
        if (! $cart) {
            return response()->json(['message' => 'Cart not found', 'data' => null], 404);
        }
        $cart->delete();
        return response()->json(['message' => 'Cart deleted', 'data' => ['id' => $id]]);
    }

    public function destroySelf(Request $request, int $id): JsonResponse
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $cart) {
            return response()->json(['message' => 'Cart not found', 'data' => null], 404);
        }

        $cart->delete();
        return response()->json(['message' => 'Cart deleted', 'data' => ['id' => $id]]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartItemController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'ok',
            'data' => CartItem::with(['cart', 'track', 'album'])->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => 'required|integer|exists:carts,id',
            'track_id' => 'nullable|integer|exists:tracks,id',
            'album_id' => 'nullable|integer|exists:albums,id',
            'pcs' => 'required|integer|min:1',
        ]);
        if (empty($validated['track_id']) && empty($validated['album_id'])) {
            return response()->json(['message' => 'Track or album is required', 'data' => null], 422);
        }
        if (! empty($validated['track_id']) && ! empty($validated['album_id'])) {
            return response()->json(['message' => 'Provide either track or album, not both', 'data' => null], 422);
        }

        $item = CartItem::create($validated);
        return response()->json(['message' => 'Cart item created', 'data' => $item], 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = CartItem::with(['cart', 'track', 'album'])->find($id);
        if (! $item) {
            return response()->json(['message' => 'Cart item not found', 'data' => null], 404);
        }
        return response()->json(['message' => 'ok', 'data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = CartItem::find($id);
        if (! $item) {
            return response()->json(['message' => 'Cart item not found', 'data' => null], 404);
        }

        $validated = $request->validate([
            'pcs' => 'required|integer|min:1',
        ]);

        $item->update($validated);
        return response()->json(['message' => 'Cart item updated', 'data' => $item]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = CartItem::find($id);
        if (! $item) {
            return response()->json(['message' => 'Cart item not found', 'data' => null], 404);
        }
        $item->delete();
        return response()->json(['message' => 'Cart item deleted', 'data' => ['id' => $id]]);
    }

    public function indexSelf(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = CartItem::with(['cart', 'track'])
            ->with(['album'])
            ->whereIn('cart_id', function ($query) use ($userId) {
                $query->select('id')->from('carts')->where('user_id', $userId);
            })
            ->get();

        return response()->json(['message' => 'ok', 'data' => $data]);
    }

    public function storeSelf(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => 'required|integer|exists:carts,id',
            'track_id' => 'nullable|integer|exists:tracks,id',
            'album_id' => 'nullable|integer|exists:albums,id',
            'pcs' => 'required|integer|min:1',
        ]);
        if (empty($validated['track_id']) && empty($validated['album_id'])) {
            return response()->json(['message' => 'Track or album is required', 'data' => null], 422);
        }
        if (! empty($validated['track_id']) && ! empty($validated['album_id'])) {
            return response()->json(['message' => 'Provide either track or album, not both', 'data' => null], 422);
        }

        $isOwnCart = DB::table('carts')
            ->where('id', $validated['cart_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isOwnCart) {
            return response()->json(['message' => 'Forbidden', 'data' => null], 403);
        }

        $item = CartItem::create($validated);
        return response()->json(['message' => 'Cart item created', 'data' => $item], 201);
    }

    public function updateSelf(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'pcs' => 'required|integer|min:1',
        ]);

        $item = CartItem::where('id', $id)
            ->whereIn('cart_id', function ($query) use ($request) {
                $query->select('id')->from('carts')->where('user_id', $request->user()->id);
            })
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Cart item not found', 'data' => null], 404);
        }

        $item->update($validated);
        return response()->json(['message' => 'Cart item updated', 'data' => $item]);
    }

    public function destroySelf(Request $request, int $id): JsonResponse
    {
        $item = CartItem::where('id', $id)
            ->whereIn('cart_id', function ($query) use ($request) {
                $query->select('id')->from('carts')->where('user_id', $request->user()->id);
            })
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Cart item not found', 'data' => null], 404);
        }

        $item->delete();
        return response()->json(['message' => 'Cart item deleted', 'data' => ['id' => $id]]);
    }
}

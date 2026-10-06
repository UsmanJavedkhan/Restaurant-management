<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '';
        $query = User::where('role', 'customer')->withCount('orders')->withSum(['orders' => fn ($query) => $query->whereIn('order_status', ['completed', 'delivered'])], 'total');
        if ($search !== '') {
            $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
        }

        return response()->json($query->latest('id')->paginate(20));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($user->role === 'customer', 403);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->forceFill($data)->save();

        return response()->json(['data' => $user]);
    }
}

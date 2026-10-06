<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:255'], 'message' => ['required', 'string', 'min:10', 'max:3000']]);
        ContactMessage::create($data);

        return response()->json(['message' => 'Your message has been sent to the restaurant.'], 201);
    }

    public function index(): JsonResponse
    {
        return response()->json(ContactMessage::latest('id')->paginate(20));
    }

    public function update(ContactMessage $message): JsonResponse
    {
        $message->update(['read_at' => now()]);

        return response()->json(['data' => $message]);
    }
}

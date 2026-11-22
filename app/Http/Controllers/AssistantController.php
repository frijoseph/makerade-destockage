<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Assistant\Assistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    /**
     * One customer message in, the assistant's reply (and product cards) out.
     */
    public function message(Request $request): JsonResponse
    {
        abort_unless(Setting::get('ai_enabled'), 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'conversation' => ['nullable', 'string', 'max:64'],
        ], ['message.required' => 'Écrivez votre message.', 'message.max' => 'Votre message est trop long (1 000 caractères maximum).']);

        return response()->json(Assistant::reply($data['message'], $data['conversation'] ?? null, $request->user()));
    }
}

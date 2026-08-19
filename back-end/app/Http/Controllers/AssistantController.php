<?php

namespace App\Http\Controllers;

use App\Services\SchemeAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The help widget's endpoint.
 *
 * Answers come from a curated knowledge base rather than a generative model,
 * so the endpoint is cheap, works offline, and cannot invent a legal position.
 */
class AssistantController extends Controller
{
    public function __construct(
        private readonly SchemeAssistantService $assistant,
    ) {
    }

    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        return response()->json($this->assistant->ask($data['question']));
    }

    public function topics(): JsonResponse
    {
        return response()->json([
            'greeting' => $this->assistant->greeting(),
            'topics'   => $this->assistant->topics(),
        ]);
    }
}

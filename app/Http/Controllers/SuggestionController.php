<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** A suggestion from the Suggest button on every page (owner, 17 Nov 2026): staff, borrowers and partners alike. */
class SuggestionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['kind' => 'required|in:idea,problem', 'body' => 'required|string|min:5|max:2000', 'page' => 'nullable|string|max:255']);
        DB::table('suggestions')->insert([
            'user_id' => $request->user()->id, 'kind' => $data['kind'], 'body' => trim($data['body']),
            'page' => isset($data['page']) ? \Illuminate\Support\Str::limit(parse_url($data['page'], PHP_URL_PATH) ?: $data['page'], 250, '') : null,
            'status' => 'new', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Thanks, we got it.']);
    }
}

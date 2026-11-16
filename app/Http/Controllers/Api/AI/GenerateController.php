<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\GenerateRequest;
use App\Http\Resources\GenerationDetailResource;
use App\Services\AI\ToolRunner;

/** One thin controller for all single-shot generator tools; logic lives in ToolRunner. */
class GenerateController extends Controller
{
    public function __construct(private readonly ToolRunner $runner) {}

    public function store(GenerateRequest $request, string $tool)
    {
        $user = $request->user();
        $generation = $this->runner->run($user, $tool, $request->validated());

        return (new GenerationDetailResource($generation))
            ->additional(['credits_balance' => $user->credits_balance])
            ->response()->setStatusCode(201);
    }
}

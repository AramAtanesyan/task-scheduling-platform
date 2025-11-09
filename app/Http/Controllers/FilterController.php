<?php

namespace App\Http\Controllers;

use App\Services\FilterService;
use Illuminate\Http\JsonResponse;

class FilterController extends Controller
{
    protected $filterService;

    public function __construct(FilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Get all available filter options
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'due_date_filters' => $this->filterService->getDueDateFilters(),
            ]
        ]);
    }
}


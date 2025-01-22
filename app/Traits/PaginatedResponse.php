<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use App\Helpers\ResponseHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait PaginatedResponse
{
    protected function respondWithPagination(
        LengthAwarePaginator $paginator,
        $resourceCollection,
        string $resourceKey = 'data',
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return ResponseHelper::success(
            data: [
                $resourceKey => $resourceCollection,
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage()
                ]
            ],
            message: $message
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SourceController extends Controller
{
    /**
     * List Sources
     *
     * Get a list of sources with optional pagination.
     *
     * @queryParam page integer The page number. Example: 1
     * @queryParam per_page integer Number of items per page. Example: 15
     *
     * @response scenario=success status=200 {
     *   "status": "success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Website",
     *       "created_at": "2024-02-08 10:00:00",
     *       "updated_at": "2024-02-08 10:00:00"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 10,
     *     "per_page": 15
     *   }
     * }
     * @response status=401 scenario="unauthenticated" {
     *   "status": "error",
     *   "message": "Invalid API token"
     * }
     */
    public function index(Request $request)
    {
        try {
            $sources = Source::paginate($request->per_page ?? 15);

            return response()->json([
                'status' => 'success',
                'data'   => $sources,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => t('failed_to_fetch_sources'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create Source
     *
     * Create a new source in the system.
     *
     * @bodyParam name string required The name of the source. Example: Referral
     *
     * @response scenario=success status=201 {
     *   "status": "success",
     *   "message": "Source created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Referral",
     *     "created_at": "2024-02-08 10:00:00"
     *   }
     * }
     * @response status=422 scenario="validation error" {
     *   "status": "error",
     *   "message": "Validation failed",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   }
     * }
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:sources',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $source = Source::create($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Source created successfully',
                'data'    => $source,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create source',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Source Details
     *
     * Get detailed information about a specific source.
     *
     * @urlParam id integer required The ID of the source. Example: 1
     *
     * @response scenario=success status=200 {
     *   "status": "success",
     *   "data": {
     *     "id": 1,
     *     "name": "Website",
     *     "created_at": "2024-02-08 10:00:00",
     *     "updated_at": "2024-02-08 10:00:00"
     *   }
     * }
     * @response status=404 scenario="not found" {
     *   "status": "error",
     *   "message": "Source not found"
     * }
     */
    public function show($id)
    {
        try {
            $source = Source::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $source,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Source not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update Source
     *
     * Update an existing source's information.
     *
     * @urlParam id integer required The ID of the source. Example: 1
     *
     * @bodyParam name string required The name of the source. Example: Referral Program
     *
     * @response scenario=success {
     *   "status": "success",
     *   "message": "Source updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Referral Program",
     *     "updated_at": "2024-02-08 11:00:00"
     *   }
     * }
     */
    public function update(Request $request, $id)
    {
        try {
            $source = Source::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:sources,name,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $source->update($request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Source updated successfully',
                'data'    => $source,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update source',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Source
     *
     * Remove a source from the system.
     *
     * @urlParam id integer required The ID of the source. Example: 1
     *
     * @response scenario=success {
     *   "status": "success",
     *   "message": "Source deleted successfully"
     * }
     * @response status=404 {
     *   "status": "error",
     *   "message": "Source not found"
     * }
     */
    public function destroy($id)
    {
        try {
            $source = Source::findOrFail($id);
            $source->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Source deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete source',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

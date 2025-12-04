<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FavoriteBuild;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * Get user's favorite builds
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');
            
            $favorites = FavoriteBuild::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'favorites' => $favorites
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a build to favorites
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'build_data' => 'required|array',
                'build_data.buildId' => 'required|integer',
                'build_data.total_price' => 'required|numeric',
                'build_data.parts' => 'required|array',
                'build_data.timestamp' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');

            // Check if build already exists in favorites
            $existingFavorite = FavoriteBuild::where('user_id', $userId)
                ->where('build_id', $request->build_data['buildId'])
                ->where('total_price', $request->build_data['total_price'])
                ->first();

            if ($existingFavorite) {
                // Remove from favorites (toggle off)
                $existingFavorite->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Build removed from favorites',
                    'data' => null
                ], 200);
            }

            // Add to favorites
            $favorite = FavoriteBuild::create([
                'user_id' => $userId,
                'build_id' => $request->build_data['buildId'],
                'total_price' => $request->build_data['total_price'],
                'parts_data' => json_encode($request->build_data['parts']),
                'build_data' => json_encode($request->build_data)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Build added to favorites',
                'data' => [
                    'favorite' => $favorite
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a build from favorites
     */
    public function destroy(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');

            $favorite = FavoriteBuild::where('user_id', $userId)
                ->where('id', $id)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Favorite not found'
                ], 404);
            }

            $favorite->delete();

            return response()->json([
                'success' => true,
                'message' => 'Favorite removed successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all favorites
     */
    public function clear(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');

            FavoriteBuild::where('user_id', $userId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All favorites cleared'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
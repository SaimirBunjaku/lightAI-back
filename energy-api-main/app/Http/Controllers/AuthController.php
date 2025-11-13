<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'property_ownership' => 'required|in:own,rent',
                'house_type' => 'required|in:detached,semi_detached,terraced,apartment,flat,bungalow,other',
                'number_of_occupants' => 'required|integer|min:1|max:20',
                'number_of_bedrooms' => 'required|integer|min:1|max:20',
                'heating_type' => 'required|in:gas,electric,oil,solar,heat_pump,biomass,district_heating,other',
                'property_age' => 'required|in:new_build,modern,established,older,historic',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'property_ownership' => $validated['property_ownership'],
                'house_type' => $validated['house_type'],
                'number_of_occupants' => $validated['number_of_occupants'],
                'number_of_bedrooms' => $validated['number_of_bedrooms'],
                'heating_type' => $validated['heating_type'],
                'property_age' => $validated['property_age'],
            ]);

            // Create API token for the user
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'household' => [
                            'property_ownership' => $user->property_ownership,
                            'house_type' => $user->house_type,
                            'number_of_occupants' => $user->number_of_occupants,
                            'number_of_bedrooms' => $user->number_of_bedrooms,
                            'heating_type' => $user->heating_type,
                            'property_age' => $user->property_age,
                        ],
                        'created_at' => $user->created_at->toIso8601String(),
                    ],
                    'token' => $token,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Login user and create token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Revoke all existing tokens (optional - for single device login)
            // $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'token' => $token,
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Logout user (revoke token)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'household' => [
                        'property_ownership' => $user->property_ownership,
                        'house_type' => $user->house_type,
                        'number_of_occupants' => $user->number_of_occupants,
                        'number_of_bedrooms' => $user->number_of_bedrooms,
                        'heating_type' => $user->heating_type,
                        'property_age' => $user->property_age,
                    ],
                    'created_at' => $user->created_at->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'current_password' => 'required_with:new_password',
                'new_password' => 'sometimes|string|min:8|confirmed',
                'property_ownership' => 'sometimes|in:own,rent',
                'house_type' => 'sometimes|in:detached,semi_detached,terraced,apartment,flat,bungalow,other',
                'number_of_occupants' => 'sometimes|integer|min:1|max:20',
                'number_of_bedrooms' => 'sometimes|integer|min:1|max:20',
                'heating_type' => 'sometimes|in:gas,electric,oil,solar,heat_pump,biomass,district_heating,other',
                'property_age' => 'sometimes|in:new_build,modern,established,older,historic',
            ]);

            // Update basic info
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }

            // Update household info
            if (isset($validated['property_ownership'])) {
                $user->property_ownership = $validated['property_ownership'];
            }

            if (isset($validated['house_type'])) {
                $user->house_type = $validated['house_type'];
            }

            if (isset($validated['number_of_occupants'])) {
                $user->number_of_occupants = $validated['number_of_occupants'];
            }

            if (isset($validated['number_of_bedrooms'])) {
                $user->number_of_bedrooms = $validated['number_of_bedrooms'];
            }

            if (isset($validated['heating_type'])) {
                $user->heating_type = $validated['heating_type'];
            }

            if (isset($validated['property_age'])) {
                $user->property_age = $validated['property_age'];
            }

            // Update password if provided
            if (isset($validated['new_password'])) {
                if (!Hash::check($validated['current_password'], $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect',
                    ], 401);
                }

                $user->password = Hash::make($validated['new_password']);
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'household' => [
                            'property_ownership' => $user->property_ownership,
                            'house_type' => $user->house_type,
                            'number_of_occupants' => $user->number_of_occupants,
                            'number_of_bedrooms' => $user->number_of_bedrooms,
                            'heating_type' => $user->heating_type,
                            'property_age' => $user->property_age,
                        ],
                    ],
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}

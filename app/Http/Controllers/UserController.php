<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }

    public function index(Request $request): Response
    {
        try {
            $data = $this->userRepository->getIndexData($request);
            
            return Inertia::render('Dashboard', $data);
        } catch (\Exception $e) {
            Log::error('Failed to load dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return error view instead of redirecting to avoid loops
            return Inertia::render('Dashboard', [
                'users' => ['data' => [], 'current_page' => 1, 'total' => 0],
                'search' => $request->get('search', ''),
                'notifications' => [],
                'error' => 'Failed to load dashboard. Please refresh the page.',
            ]);
        }
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Users/Create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $user = $this->userRepository->createFromRequest($request);

            // Clear relevant caches
            CacheHelper::flushTags(['users', 'user-search', 'index']);

            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation']),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create user. Please try again.']);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse|Response|RedirectResponse
    {
        try {
            $user->load('address');
            
            if ((request()->wantsJson() || request()->ajax()) && !request()->header('X-Inertia')) {
                return response()->json([
                    'user' => new UserResource($user),
                ]);
            }
            
            return Inertia::render('Users/Show', [
                'user' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to show user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['error' => 'Failed to load user.'], 500);
            }

            return redirect()->route('users.index')
                ->with('error', 'Failed to load user details.');
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response|RedirectResponse
    {
        try {
            $user->load('address');
            
            return Inertia::render('Users/Edit', [
                'user' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load edit form', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('users.index')
                ->with('error', 'Failed to load edit form.');
        }
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->userRepository->updateFromRequest($request, $user);

            // Clear relevant caches (notifications not cached - fetched fresh)
            CacheHelper::flushTags(['users', 'user-search', 'index']);

            return redirect()->route('users.index')->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation']),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update user. Please try again.']);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        try {
            $userId = $user->id;
            $this->userRepository->delete($user);

            // Clear relevant caches (notifications not cached - fetched fresh)
            CacheHelper::flushTags(['users', 'user-search', 'index']);

            return redirect()->route('users.index')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete user. Please try again.']);
        }
    }
}

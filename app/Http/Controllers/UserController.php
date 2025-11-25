<?php

namespace App\Http\Controllers;

use App\Events\UserUpdated;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\UserSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserSearchService $userSearchService
    ) {
    }

    /**
     * Display the dashboard with user search.
     */
    public function index(Request $request): Response
    {
        $query = $request->get('search', '');
        $perPage = (int) $request->get('per_page', 15);

        $users = $this->userSearchService->paginated($query, $perPage);
        
        // Get recent unread notifications (last 10)
        $notifications = Notification::with('user')
            ->where('read', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return Inertia::render('Dashboard', [
            'users' => $users,
            'search' => $query,
            'notifications' => $notifications,
        ]);
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
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        $user->address()->create([
            'country' => $validated['address']['country'],
            'city' => $validated['address']['city'],
            'post_code' => $validated['address']['post_code'],
            'street' => $validated['address']['street'],
        ]);

        // Clear search cache after creating new user
        $this->userSearchService->clearCache();

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse|Response
    {
        $user->load('address');
        
        // Return JSON for AJAX requests (modal) - check if it's NOT an Inertia request
        if ((request()->wantsJson() || request()->ajax()) && !request()->header('X-Inertia')) {
            return response()->json([
                'user' => $user,
            ]);
        }
        
        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $user->load('address');
        
        return Inertia::render('Users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        if ($user->address) {
            $user->address->update([
                'country' => $validated['address']['country'],
                'city' => $validated['address']['city'],
                'post_code' => $validated['address']['post_code'],
                'street' => $validated['address']['street'],
            ]);
        } else {
            $user->address()->create([
                'country' => $validated['address']['country'],
                'city' => $validated['address']['city'],
                'post_code' => $validated['address']['post_code'],
                'street' => $validated['address']['street'],
            ]);
        }

        // Dispatch event for user update
        event(new UserUpdated($user));

        // Clear search cache after updating user
        $this->userSearchService->clearCache();

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        
        // Clear search cache after deleting user
        $this->userSearchService->clearCache();
        
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}

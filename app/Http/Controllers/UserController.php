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
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function __construct(
        protected UserSearchService $userSearchService
    ) {
    }

    public function index(Request $request) // remove Response type to avoid mismatch
    {
        $query = (string) $request->get('search', '');
        $perPage = max(10, min((int) $request->get('per_page', 15), 50)); // safe bounds

        // Cache key short TTL to hide spikes while debugging (optional)
        $cacheKey = 'users:list:'.md5($query).':per:'.$perPage.':page:'.request()->get('page', 1);

        $users = Cache::remember($cacheKey, 20, function () use ($query, $perPage) {
            return $this->userSearchService->paginated($query, $perPage);
        });
        
        $notifications = Notification::select('id','user_id','type','message','read','created_at')
            ->where('read', false)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
        
        // withQueryString keeps search/per_page in paginator urls
        return Inertia::render('Dashboard', [
            'users' => $users->withQueryString(),
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

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse|Response
    {
        $user->load('address');
        
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

        event(new UserUpdated($user));

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        
        // Don't clear cache here - the redirect will fetch fresh data
        // The short cache TTL (20s) ensures data stays fresh enough
        // The frontend will reload the table after deletion
        
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}

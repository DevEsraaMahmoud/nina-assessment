<?php

namespace App\Repositories;

use App\Events\UserUpdated;
use App\Helpers\CacheHelper;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\UserSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserRepository
{
    public function __construct(
        protected UserSearchService $userSearchService
    ) {
    }
    /**
     * Create a new user with address.
     *
     * @param array<string, mixed> $userData
     * @param array<string, mixed> $addressData
     * @return User
     */
    public function createWithAddress(array $userData, array $addressData): User
    {
        return DB::transaction(function () use ($userData, $addressData) {
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
            ]);

            $user->address()->create([
                'country' => $addressData['country'],
                'city' => $addressData['city'],
                'post_code' => $addressData['post_code'],
                'street' => $addressData['street'],
            ]);

            return $user->load('address');
        });
    }

    /**
     * Update user and address.
     *
     * @param User $user
     * @param array<string, mixed> $userData
     * @param array<string, mixed> $addressData
     * @return User
     */
    public function updateWithAddress(User $user, array $userData, array $addressData): User
    {
        return DB::transaction(function () use ($user, $userData, $addressData) {
            $user->update([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
            ]);

            if ($user->address) {
                $user->address->update([
                    'country' => $addressData['country'],
                    'city' => $addressData['city'],
                    'post_code' => $addressData['post_code'],
                    'street' => $addressData['street'],
                ]);
            } else {
                $user->address()->create([
                    'country' => $addressData['country'],
                    'city' => $addressData['city'],
                    'post_code' => $addressData['post_code'],
                    'street' => $addressData['street'],
                ]);
            }

            event(new UserUpdated($user));

            return $user->load('address');
        });
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            return $user->delete();
        });
    }

    /**
     * Get all data needed for the index/dashboard page.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function getIndexData(Request $request): array
    {
        try {
            $query = (string) $request->get('search', '');
            $perPage = max(10, min((int) $request->get('per_page', 10), 20));
            $page = (int) $request->get('page', 1);

            // Cache key includes all parameters
            $cacheKey = 'index_data:' . md5($query . '|' . $perPage . '|' . $page);

            return CacheHelper::rememberWithTags(
                ['index', 'users'],
                $cacheKey,
                60,
                function () use ($query, $perPage, $page) {
                    $users = $this->userSearchService->paginated($query, $perPage, $page);
                    
                    // Notifications not cached - they change frequently and query is optimized
                    $notifications = $this->getCachedNotifications();

                    return [
                        'users' => $users->withQueryString(),
                        'search' => $query,
                        'notifications' => $notifications,
                    ];
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to get index data', [
                'query' => $query ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to non-cached data
            $users = $this->userSearchService->paginated($query ?? '', $perPage ?? 20, $page ?? 1);
            $notifications = $this->getCachedNotifications();

            return [
                'users' => $users->withQueryString(),
                'search' => $query ?? '',
                'notifications' => $notifications,
            ];
        }
    }

    /**
     * Get unread notifications.
     * Not cached because notifications change frequently and the query is already optimized.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCachedNotifications()
    {
        return Notification::select('id', 'user_id', 'type', 'message', 'read', 'created_at')
            ->where('read', false)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }

    /**
     * Create a user with address from a validated request.
     *
     * @param StoreUserRequest $request
     * @return User
     */
    public function createFromRequest(StoreUserRequest $request): User
    {
        $validated = $request->validated();

        return $this->createWithAddress(
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
            ],
            [
                'country' => $validated['address']['country'],
                'city' => $validated['address']['city'],
                'post_code' => $validated['address']['post_code'],
                'street' => $validated['address']['street'],
            ]
        );
    }

    /**
     * Update a user with address from a validated request.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return User
     */
    public function updateFromRequest(UpdateUserRequest $request, User $user): User
    {
        $validated = $request->validated();

        return $this->updateWithAddress(
            $user,
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
            ],
            [
                'country' => $validated['address']['country'],
                'city' => $validated['address']['city'],
                'post_code' => $validated['address']['post_code'],
                'street' => $validated['address']['street'],
            ]
        );
    }
}


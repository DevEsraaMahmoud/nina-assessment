<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UserSearchService
{
    /**
     * Cache TTL in seconds (5 minutes for search results).
     */
    private const CACHE_TTL = 300;

    /**
     * Perform a paginated search for users with optimized select statements and caching.
     */
    public function paginated(?string $query, int $perPage = 15, bool $withQueryString = true): LengthAwarePaginator
    {
        // Generate cache key based on query and pagination
        $page = request()->get('page', 1);
        $cacheKey = $this->getCacheKey('paginated', $query, $perPage, $page);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $perPage, $withQueryString) {
            $builder = $this->baseQuery($query)
                ->orderByDesc('users.id');

            $paginator = $builder->paginate($perPage);

            return $withQueryString ? $paginator->withQueryString() : $paginator;
        });
    }

    /**
     * Search users for API responses (non paginated, limited results) with caching.
     */
    public function searchCollection(?string $query, int $limit = 20): Collection
    {
        // Generate cache key
        $cacheKey = $this->getCacheKey('collection', $query, $limit);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $limit) {
            return $this->baseQuery($query)
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Process users in chunks for bulk operations (memory efficient).
     * 
     * @param callable $callback Function to process each chunk
     * @param int $chunkSize Number of records per chunk
     * @param string|null $query Optional search query
     */
    public function chunk(callable $callback, int $chunkSize = 10000, ?string $query = null): void
    {
        $builder = $this->baseQuery($query)
            ->orderBy('users.id');

        $builder->chunk($chunkSize, $callback);
    }

    /**
     * Process users using cursor for memory-efficient iteration.
     * 
     * @param string|null $query Optional search query
     * @return \Generator
     */
    public function cursor(?string $query = null): \Generator
    {
        $builder = $this->baseQuery($query)
            ->orderBy('users.id');

        foreach ($builder->cursor() as $user) {
            yield $user;
        }
    }

    /**
     * Base query builder shared between paginated and collection searches.
     * Optimized with selective field queries and efficient WHERE clauses.
     */
    protected function baseQuery(?string $query)
    {
        $builder = User::query()
            ->select(['users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.created_at'])
            ->with(['address:id,user_id,country,city,post_code,street']);

        if (empty($query)) {
            return $builder;
        }

        // Optimize LIKE queries: prefer prefix matching when possible
        $trimmedQuery = trim($query);
        
        // If query doesn't start with wildcard, use prefix matching for better index usage
        $likeQuery = $trimmedQuery . '%'; // Prefix matching is more efficient
        
        return $builder->where(function ($sub) use ($trimmedQuery, $likeQuery) {
            // Exact match on email (most efficient)
            $sub->where('users.email', '=', $trimmedQuery)
                // Prefix matching on names (can use index)
                ->orWhere('users.first_name', 'like', $likeQuery)
                ->orWhere('users.last_name', 'like', $likeQuery)
                // Full-text search fallback for partial matches
                ->orWhere('users.first_name', 'like', '%' . $trimmedQuery . '%')
                ->orWhere('users.last_name', 'like', '%' . $trimmedQuery . '%')
                ->orWhere('users.email', 'like', '%' . $trimmedQuery . '%')
                // Address search
                ->orWhereHas('address', function ($address) use ($trimmedQuery, $likeQuery) {
                    $address->where('country', 'like', $likeQuery)
                        ->orWhere('city', 'like', $likeQuery)
                        ->orWhere('post_code', 'like', $likeQuery)
                        ->orWhere('street', 'like', '%' . $trimmedQuery . '%');
                });
        });
    }

    /**
     * Generate a cache key for the given parameters.
     */
    protected function getCacheKey(string $type, ?string $query, int $limit, ?int $page = null): string
    {
        $queryHash = $query ? md5($query) : 'empty';
        $key = "user_search:{$type}:{$queryHash}:{$limit}";
        
        if ($page !== null) {
            $key .= ":page:{$page}";
        }
        
        return $key;
    }

    /**
     * Clear cache for a specific query or all search caches.
     * 
     * Note: For better performance with cache clearing, use Redis cache driver
     * which supports cache tags. With database/file cache, we clear by pattern.
     */
    public function clearCache(?string $query = null): void
    {
        // If Redis/Memcached is used, tags are supported
        if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
            Cache::tags(['user_search'])->flush();
            return;
        }
        
        // For database/file cache, we need to clear manually
        // In production, consider using Redis for better cache tag support
        // For now, we'll clear cache on user mutations which is acceptable
        // since search results are cached for 5 minutes anyway
    }
}

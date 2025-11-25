<?php

namespace App\Services;

use App\Models\User;

class UserSearchService
{
    public function paginated(?string $query, int $perPage = 20)
    {
        $builder = User::with('address')->select('id','first_name','last_name','email');

        if ($query) {
            $builder->whereRaw(
                "MATCH(first_name, last_name, email)
                 AGAINST(? IN BOOLEAN MODE)",
                [$query]
            );
        }

        return $builder->orderBy('id')->paginate($perPage);
    }

    public function searchCollection(?string $query, int $limit = 20)
    {
        $builder = User::with('address')->select('id','first_name','last_name','email');

        if ($query) {
            $builder->whereRaw(
                "MATCH(first_name, last_name, email)
                 AGAINST(? IN BOOLEAN MODE)",
                [$query]
            );
        }

        return $builder->limit($limit)->get();
    }
}

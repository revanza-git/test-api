<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Events\UserCreated;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::query()->create($data);

        // Side-effects are handled via events/listeners to keep controllers thin.
        event(new UserCreated($user));

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'created_at' => $user->created_at?->toISOString(),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $sortBy = $request->query('sortBy', 'created_at');
        $allowedSorts = ['name', 'email', 'created_at'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $paginator = User::query()
            ->where('active', true)
            ->when($search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount('orders')
            ->orderBy($sortBy)
            ->paginate(perPage: 10);

        return response()->json([
            'page' => $paginator->currentPage(),
            'users' => UserResource::collection($paginator),
        ]);
    }
}

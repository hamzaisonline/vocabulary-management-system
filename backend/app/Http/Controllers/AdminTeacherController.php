<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetAdminTeacherPasswordRequest;
use App\Http\Requests\StoreAdminTeacherRequest;
use App\Http\Requests\UpdateAdminTeacherRequest;
use App\Http\Resources\AdminTeacherResource;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminTeacherController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $teachers = Teacher::query()
            ->with('user:id,name,email,created_at')
            ->withCount('schoolClasses')
            ->orderByDesc('id')
            ->get();

        return AdminTeacherResource::collection($teachers);
    }

    public function store(StoreAdminTeacherRequest $request): JsonResponse
    {
        $teacher = DB::transaction(function () use ($request) {
            $role = Role::where('name', 'teacher')->firstOrFail();
            $data = $request->validated();

            $user = User::create([
                'role_id' => $role->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return Teacher::create(['user_id' => $user->id]);
        });

        return (new AdminTeacherResource($teacher->load('user')->loadCount('schoolClasses')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Teacher $teacher): AdminTeacherResource
    {
        $this->authorizeAdmin($request);

        return new AdminTeacherResource($teacher->load('user')->loadCount('schoolClasses'));
    }

    public function update(UpdateAdminTeacherRequest $request, Teacher $teacher): AdminTeacherResource
    {
        $teacher->user()->update($request->validated());

        return new AdminTeacherResource($teacher->load('user')->loadCount('schoolClasses'));
    }

    public function updatePassword(ResetAdminTeacherPasswordRequest $request, Teacher $teacher): JsonResponse
    {
        $teacher->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json(['message' => 'Teacher password updated successfully.']);
    }

    public function destroy(Request $request, Teacher $teacher): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($teacher->schoolClasses()->exists()) {
            return response()->json([
                'message' => 'Teacher cannot be deleted while they own classes.',
            ], 409);
        }

        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Teacher deleted successfully.']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role?->name === 'admin', 403);
    }
}

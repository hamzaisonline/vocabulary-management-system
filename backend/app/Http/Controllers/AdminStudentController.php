<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetAdminStudentPasswordRequest;
use App\Http\Requests\StoreAdminStudentRequest;
use App\Http\Requests\UpdateAdminStudentRequest;
use App\Http\Resources\AdminStudentResource;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return AdminStudentResource::collection(Student::query()
            ->with('user:id,name,email,created_at')
            ->withCount('schoolClasses')
            ->orderByDesc('id')->get());
    }

    public function store(StoreAdminStudentRequest $request): JsonResponse
    {
        $student = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $user = User::create([
                'role_id' => Role::where('name', 'student')->firstOrFail()->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return Student::create(['user_id' => $user->id]);
        });

        return (new AdminStudentResource($student->load('user')->loadCount('schoolClasses')))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, Student $student): AdminStudentResource
    {
        $this->authorizeAdmin($request);

        return new AdminStudentResource($student->load([
            'user',
            'schoolClasses:id,name,language',
        ])->loadCount('schoolClasses'));
    }

    public function update(UpdateAdminStudentRequest $request, Student $student): AdminStudentResource
    {
        $student->user()->update($request->validated());

        return new AdminStudentResource($student->load('user')->loadCount('schoolClasses'));
    }

    public function updatePassword(ResetAdminStudentPasswordRequest $request, Student $student): JsonResponse
    {
        $student->user()->update(['password' => Hash::make($request->validated('password'))]);

        return response()->json(['message' => 'Student password updated successfully.']);
    }

    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($student->schoolClasses()->exists() || $student->wordProgress()->exists() || $student->practiceSessions()->exists()) {
            return response()->json([
                'message' => 'Student cannot be deleted while enrollments or learning history exist.',
            ], 409);
        }

        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Student deleted successfully.']);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role?->name === 'admin', 403);
    }
}

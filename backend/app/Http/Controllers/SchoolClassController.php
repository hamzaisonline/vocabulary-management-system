<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollStudentRequest;
use App\Http\Requests\StoreSchoolClassRequest;
use App\Http\Requests\UpdateSchoolClassRequest;
use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = SchoolClass::query()->with(['teacher.user', 'students']);

        if ($user->role?->name === 'admin') {
            $classes = $query->get();
        } elseif ($user->role?->name === 'teacher') {
            $classes = $query->where('teacher_id', $user->teacher?->id)->get();
        } elseif ($user->role?->name === 'student') {
            $classes = $query->whereHas('students', function ($q) use ($user) {
                $q->where('students.id', $user->student?->id);
            })->get();
        } else {
            $classes = collect();
        }

        return response()->json([
            'success' => true,
            'data' => SchoolClassResource::collection($classes),
        ]);
    }

    public function show(Request $request, SchoolClass $schoolClass)
    {
        $this->authorize('view', $schoolClass);

        $schoolClass->load(['teacher.user', 'students.user']);

        return response()->json([
            'success' => true,
            'data' => new SchoolClassResource($schoolClass),
        ]);
    }

    public function store(StoreSchoolClassRequest $request)
    {
        $this->authorize('create', SchoolClass::class);

        $teacherId = $request->teacher_id;

        if ($request->user()->role?->name === 'teacher') {
            if ($request->filled('teacher_id') && (int) $request->teacher_id !== (int) $request->user()->teacher?->id) {
                abort(403, 'You can only create classes for your own teacher profile.');
            }

            $teacherId = $request->user()->teacher?->id;
        }

        $class = SchoolClass::create([
            'teacher_id' => $teacherId,
            'name' => $request->name,
            'description' => $request->description,
            'language' => $request->language,
        ]);

        $class->load(['teacher.user']);

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully.',
            'data' => new SchoolClassResource($class),
        ], 201);
    }

    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass)
    {
        $this->authorize('update', $schoolClass);

        $data = $request->only(['name', 'description', 'language']);

        if ($request->user()->role?->name === 'teacher' && $request->filled('teacher_id')) {
            if ((int) $request->teacher_id !== (int) $request->user()->teacher?->id) {
                abort(403, 'You can only update your own class.');
            }
        }

        if ($request->user()->role?->name === 'admin' && $request->has('teacher_id')) {
            $data['teacher_id'] = $request->teacher_id;
        }

        $schoolClass->fill($data);
        $schoolClass->save();

        $schoolClass->load(['teacher.user']);

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully.',
            'data' => new SchoolClassResource($schoolClass),
        ]);
    }

    public function destroy(Request $request, SchoolClass $schoolClass)
    {
        $this->authorize('delete', $schoolClass);

        $schoolClass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully.',
        ]);
    }

    public function enrollStudent(EnrollStudentRequest $request, SchoolClass $schoolClass)
    {
        $this->authorize('enrollStudent', $schoolClass);

        $student = Student::findOrFail($request->student_id);

        if ($schoolClass->students()->where('students.id', $student->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Student is already enrolled in this class.',
            ], 422);
        }

        $schoolClass->students()->attach($student->id, [
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $schoolClass->load(['teacher.user', 'students.user']);

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled successfully.',
            'data' => [
                'class_id' => $schoolClass->id,
                'student_id' => $student->id,
                'status' => 'active',
                'enrolled_at' => now()->toISOString(),
            ],
        ], 201);
    }

    public function removeStudent(Request $request, SchoolClass $schoolClass, $studentId)
    {
        $this->authorize('removeStudent', $schoolClass);

        $student = Student::findOrFail($studentId);
        $schoolClass->students()->detach($student->id);

        return response()->json([
            'success' => true,
            'message' => 'Student removed from class.',
            'data' => [
                'class_id' => $schoolClass->id,
                'student_id' => $student->id,
            ],
        ]);
    }
}

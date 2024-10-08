<?php

namespace App\Http\Controllers\Api\Classes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Absent\AbsentRequest;
use App\Http\Requests\Classes\ClassesRequest;
use App\Models\Absence;
use App\Models\Classes;
use App\Models\Kids;
use App\Models\Meals;
use Carbon\Carbon;

class ClassesController extends Controller
{
    /**
     * Construct a instance of the resource.
     */
    public function __construct()
    {
        $this->middleware(['role:nursery_Owner|permission:Manage-Classes']);
    }

    /**
     * Display a listing of the classes.
     */
    public function index()
    {
        $classes = Classes::where('nursery_id', nursery_id())->get();
        return contentResponse($classes, 'Fetches Classes Successfully');
    }

    /**
     * Store a newly created class.
     */
    public function store(ClassesRequest $request)
    {
        $data = $request->validated();
        $data['nursery_id'] = nursery_id();
        Classes::create($data);
        return messageResponse('Created Class Successfully');
    }

    /**
     * Display the specified class.
     */
    public function show(string $id)
    {
        $class = Classes::with('kids')->where('nursery_id', nursery_id())->findOrFail($id);

        foreach ($class->kids as $kid) {
            $kid->media = $kid->getMedia();
        }

        return contentResponse($class, fetchOne($class->name));
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(string $id)
    {
        $classes = Classes::where('nursery_id', nursery_id())->find($id);
        return contentResponse($classes, fetchOne($classes->name));
    }

    /**
     * Update the specified class.
     */
    public function update(ClassesRequest $request, Classes $class)
    {
        $data = $request->validated();
        $class->update($data);
        return messageResponse('Updated Class Successfully');
    }

    /**
     * Remove the specified class.
     */
    public function destroy(Classes $class)
    {
        $class->forceDelete();
        return messageResponse('Deleted Class Successfully');
    }

    /**
     * Remove the specified class.
     */
    public function kidsClassFetch($date = null, $class_id)
    {
        $date = Carbon::parse($date);
        $day = $date->shortDayName; // Gets the short name of the day (e.g., "Mon" for Monday)

        $kids = Kids::select('id', 'kid_name')->with([
            'absent' => function ($query) use ($date) {
                $query->select('kid_id', 'absent', 'created_at')->whereDate('created_at', $date); // Make sure to include created_at if you need to filter by it later
            },
            'meal_amount' => function ($query) use ($date, $day) {
                $query->whereDate('created_at', $date)
                    ->whereHas('meal', function ($query) use ($day) {
                        $query->where('days', $day);
                    });
            },
            'activites' => function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            }
        ])->where('class_id', $class_id)->get();
        $mealsClass = Meals::where('class_id', $class_id)->where('days', $day)->get();
        $kids = $kids->map(function ($kid) use ($date, $mealsClass) {
            $absence = $kid->absent;
            return [
                'id' => $kid->id,
                'kid_name' => $kid->kid_name,
                'absent' => $absence?->whereDate('created_at', $date) ? $absence->absent : 0,
                'meal_Amount' => $kid->meal_amount,
                'activites' => $kid->activites,
            ];
        });

        $data = [
            'kid' => $kids,
            'meal_class' => $mealsClass,
        ];

        return contentResponse($data, fetchAll('Class Activity'));
    }

    public function absent(AbsentRequest $request)
    {
        $date = Carbon::today()->format('Y-m-d');
        $requestValidated = $request->validated();
        $requestValidated['nursery_id'] = nursery_id();
        $absent = Absence::where('kid_id', $requestValidated['kid_id'])->whereDate('created_at', $date)->first();

        if ($absent) {
            $absent->update(['absent' => !$absent->absent]);
        } else {
            $requestValidated['absent'] = 1;
            $absent = Absence::create($requestValidated);
        }
        return messageResponse('Absent Taken Sucessfully');
    }
}

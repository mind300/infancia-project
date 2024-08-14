<?php

namespace App\Http\Controllers\Api\Schedules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\ScheduleRequest;
use App\Models\Classes;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedulesController extends Controller
{
    // Variables
    private $nursery_id;

    /**
     * Construct a instance of the resource.
     */
    public function __construct()
    {
        $this->nursery_id = auth()->user()->nursery->id ?? auth()->user()->parent->nursery_id;
    }

    /**
     * Display a listing of the resource.
     */
    public function index() 
    {
        $schedule = Schedule::where('nursery_id', $this->nursery_id)->get();
        return contentResponse($schedule, fetchAll('Schedules'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ScheduleRequest $request)
    {
        DB::beginTransaction();
        try {
            $requestValidated = $request->validated();
            $requestValidated['nursery_id'] = $this->nursery_id;
            $schedule = Schedule::create($requestValidated);
            DB::commit();
            return messageResponse('Added Schedule Successfully');
        } catch (\Throwable $error) {
            DB::rollBack();
            return messageResponse($error->getMessage(), 403);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Classes $class)
    {
        $class->schedules;
        return contentResponse($class, fetchOne('Schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        return contentResponse($schedule, fetchOne('Schedule'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ScheduleRequest $request, Schedule $schedule)
    {
        DB::beginTransaction();
        try {
            $requestValidated = $request->validated();
            $requestValidated['nursery_id'] = $this->nursery_id;
            $schedule->update($requestValidated);
            DB::commit();
            return messageResponse('Updated Schedule Successfully');
        } catch (\Throwable $error) {
            DB::rollBack();
            return messageResponse($error->getMessage(), 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        $schedule->forceDelete();
        return messageResponse('Deleted Schedule Successfully');
    }
}

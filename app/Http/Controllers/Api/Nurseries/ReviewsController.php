<?php

namespace App\Http\Controllers\Api\Nurseries;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\ReviewsRequest;
use App\Models\Nurseries;
use App\Models\Reviews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewsController extends Controller
{
    // Variables

    public function __construct()
    {
        $this->middleware(['role:nursery_Owner|teacher|parent|permission:Nursery-Profile']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(string $nursery_id)
    {
        $reviews = Reviews::with('user')->where('nursery_id', $nursery_id)->get();
        $reviews = $reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'review' => $review->review,
                'rate' => $review->rate,
                'user_name' => $review->user->name,
                'user_id' => $review->user->name,
                'nursery_id' => $review->nursery_id,
            ];
        });
        return contentResponse($reviews, fetchAll('Reviews'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReviewsRequest $request)
    {
        DB::beginTransaction();
        try {
            $requestValidated = $request->validated();
            $requestValidated['nursery_id'] = nursery_id();
            $reviews = Reviews::create($requestValidated);

            // Recalculate the average rating after the new review is added
            $avgRate = Reviews::where('nursery_id', nursery_id())
                ->avg(DB::raw('rate / 10 * 5')); // Assuming 'rate' is out of 10

            $nurseryRate = Nurseries::find(nursery_id())->update(['rateing' => $avgRate]);

            DB::commit();
            return messageResponse('Created Review Successfully');
        } catch (\Throwable $error) {
            DB::rollBack();
            return messageResponse($error->getMessage(), 403);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reviews $review)
    {
        return contentResponse($review, fetchAll('Review'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReviewsRequest $request, Reviews $review)
    {
        DB::beginTransaction();
        $requestValidated = $request->validated();
        $requestValidated['nursery_id'] = nursery_id();
        try {
            $reviews = $review->update($requestValidated);
            DB::commit();
            return messageResponse('Updated Review Successfully');
        } catch (\Throwable $error) {
            DB::rollBack();
            return messageResponse($error->getMessage(), 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reviews $review)
    {
        $review->forceDelete();
        return messageResponse('Review Deleted Successfully');
    }
}

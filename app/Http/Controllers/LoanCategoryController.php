<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoanCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoanCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $loan_categories = LoanCategory::all();

        if ($loan_categories->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        // Convert id fields to string explicitly
        $loan_categories = $loan_categories->map(function ($category) {
            $category->id = (string) $category->id; // Ensure id is a string
            $category->category_id = (string) $category->category_id; // Convert category_id to string if needed
            return $category;
        });

        return response()->json(['message' => $loan_categories], 200);
    }

    


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $validateData = $request->validate([

                'category_id' => 'required',
                'category_name' => 'required|string|max:100',
                'category_type' => 'required|in:weekly,daily,monthly',
                'duration' => 'required|integer',
                'interest_rate' => 'required',
                'status' => 'required|in:active,inactive',
            ]);

            $loan_category = LoanCategory::create([

                'category_id' => $validateData['category_id'],
                'category_name' => $validateData['category_name'],
                'category_type' => $validateData['category_type'],
                'duration' => $validateData['duration'],
                'interest_rate' => $validateData['interest_rate'],
                'status' => $validateData['status'],
            ]);


            return response()->json(['message' => 'Loan Category added successfully!'], 201);
        }
        catch(ValidationException $e){
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }
        catch (\Exception $e) {
            Log::error('Error creating Loan Category: ' . $e->getMessage());
            return response()->json(['message' => 'Error created Loan Category'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $loan_category = LoanCategory::find($id);

        if(!$loan_category) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return response()->json(['message' => $loan_category], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $loan_category = LoanCategory::find($id);

        if(!$loan_category) {
            return response()->json(['message' => 'No data found'], 404);
        }
        else{
        $loan_category->delete();
        return response()->json(['message' => 'Loan Category deleted successfully'], 200);
        }
    }
}

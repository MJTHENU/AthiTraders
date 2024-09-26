<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\LoanCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    // Join the loans table with the users table on user_id
    $loans = DB::table('loan')
        ->join('users', 'loan.user_id', '=', 'users.user_id')
        ->select('loan.*', 'users.user_name', 'users.email','users.profile_photo', 'users.sign_photo')  // Add other user fields as needed
        ->get();

    if ($loans->isEmpty()) {
        return response()->json(['message' => 'Data not found'], 404);
    } else {
        return response()->json(['loans' => $loans], 200);
    }
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

                'loan_id' => 'required',
                'user_id' => 'required|integer',
                'employee_id' => 'required',
                'category_id' => 'required|integer',
                'loan_amount' => 'required|integer',
                'loan_date' => 'required|date',
                'image' => 'nullable|string',
                'loan_closed_date' => 'required|date',
                'status' => 'required|in:pending,inprogress,completed,cancelled',
            ]);

            $loanCategory = LoanCategory::find($validateData['category_id']);
            Log::info('Loan category ID: ' . $loanCategory->category_type);

            $loan = Loan::create([

                'loan_id' => $validateData['loan_id'],
                'user_id' => $validateData['user_id'],
                'employee_id' => $validateData['employee_id'],
                'category_id' => $validateData['category_id'],
                'loan_amount' => $validateData['loan_amount'],
                'loan_category' => $loanCategory->category_type,
                'loan_date' => $validateData['loan_date'],
                'image' => $validateData['image'],
                'loan_closed_date' => $validateData['loan_closed_date'],
                'status' => $validateData['status'],
            ]);


            return response()->json(['message' => 'Loan added successfully!'], 201);
        }
        catch(ValidationException $e){
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        }
        catch (\Exception $e) {
            Log::error('Error creating Loan Category: ' . $e->getMessage());
            return response()->json(['message' => 'Error creating Loan '], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }
}

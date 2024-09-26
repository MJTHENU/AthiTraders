<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoanDue;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoanDueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $loan_due = LoanDue::all();

        if ($loan_due->isEmpty()) {
            return response()->json(['message' => 'No loan due found'], 404);
        }

        return response()->json(['message' => $loan_due], 200);
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
        try {
            $validated = $request->validate([

                'loan_id' => 'required|string',
                'user_id' => 'required|string|max:255',
                'due_amount' => 'required|numeric',
                'due_date' => 'required|date',
                'paid_date' => 'nullable|date',
                'collection_by' => 'required|string|max:225',

            ]);

            $loan_due = LoanDue::create([

                'loan_id' => $validated['loan_id'],
                'user_id' => $validated['user_id'],
                'due_amount' => $validated['due_amount'],
                'due_date' => $validated['due_date'],
                'paid_on' => $validated['paid_date'] ?? null,
                'collection_by' => $validated['collection_by'],
            ]);

            return response()->json(['message' => 'Loan Due added successfully!'], 201);
        } catch (ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error registering user: ' . $e->getMessage());
            return response()->json(['message' => 'Error added Loan Due'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $loan_due = LoanDue::find($id);

        if (!$loan_due) {
            return response()->json(['message' => 'Loan due not found'], 404);
        }

        return response()->json(['message' => $loan_due], 200);
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
        $loan_due = LoanDue::find($id);

        if(!$loan_due) {
            return response()->json(['message' => 'Loan Due not found'], 404);
        }
        else{
            $loan_due->delete();
        return response()->json([ 'message' => 'Loan Due deleted successfully'], 200);
        }
    }
}

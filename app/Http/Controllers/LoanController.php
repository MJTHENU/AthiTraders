<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanCategory;
use App\Models\LoanDue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index()
{
    // Join the loans table with the users table on user_id
    $loans = DB::table('loan')
        ->join('users as loan_user', 'loan.user_id', '=', 'loan_user.user_id')  // Join for the loan's user_id
        ->join('users as employee_user', 'loan.employee_id', '=', 'employee_user.user_id')  // Join for the loan's employee_id
        ->select(
            'loan.*', 
            'loan_user.user_name as customer_name',  // The user_name for the user who took the loan
            'loan_user.city', 
            'loan_user.email', 
            'loan_user.profile_photo', 
            'loan_user.sign_photo',
            'employee_user.user_name as employee_name'  // The user_name for the employee (employee_id)
        )
        // Order by loan ID in descending order (or use `created_at` if available)
          // Limit the results to the last 5 records
        ->get();

    if ($loans->isEmpty()) {
        return response()->json(['message' => 'Data not found'], 404);
    } else {
        return response()->json(['loans' => $loans], 200);
    }
}

public function indexweb()
{
    // Join the loans table with the users table on user_id
    $loans = DB::table('loan')
        ->join('users as loan_user', 'loan.user_id', '=', 'loan_user.user_id')  // Join for the loan's user_id
        ->join('users as employee_user', 'loan.employee_id', '=', 'employee_user.user_id')  // Join for the loan's employee_id
        ->select(
            'loan.*', 
            'loan_user.user_name as customer_name',  // The user_name for the user who took the loan
            'loan_user.city', 
            'loan_user.email', 
            'loan_user.profile_photo', 
            'loan_user.sign_photo',
            'employee_user.user_name as employee_name'  // The user_name for the employee (employee_id)
        )
        ->orderBy('loan.id', 'desc')  // Order by loan ID in descending order (or use `created_at` if available)
        ->limit(3)  // Limit the results to the last 5 records
        ->get();

    if ($loans->isEmpty()) {
        return response()->json(['message' => 'Data not found'], 404);
    } else {
        return response()->json(['loans' => $loans], 200);
    }
}

public function fetchLoansWithDetails()
{
    // Join the loans table with the users table on user_id and employee_id,
    // and join with the loandue table on loan_id
    $loans = DB::table('loan')
        ->join('users AS user', 'loan.user_id', '=', 'user.user_id')  // Join for user_id
        ->join('users AS employee', 'loan.employee_id', '=', 'employee.user_id')  // Join for employee_id
        ->leftJoin('loan_due', 'loan.loan_id', '=', 'loan_due.loan_id')  // Left join for due_date using loan_id
        ->select(
            'loan.loan_id',                  // Assuming loan_id is the primary key in the loan table
            'loan.loan_amount',              // Include other relevant loan fields
            'loan.status',                   // Include loan status
            'loan_due.due_date',             // Fetch due date from loandue table
            'user.user_name AS borrower_name',   // Include user name (borrower)
            'employee.user_name AS employee_name', // Include employee name
            'user.city'                      // Include user city
        )
        ->distinct()                       // Filter out duplicates
        ->get();                           // Fetch all results

    // Check if any loans exist
    if ($loans->isEmpty()) {
        return response()->json(['message' => 'Data not found'], 404);
    }

    // Return the list of loans with a success message
    return response()->json(['loans' => $loans], 200);
}








public function countPendingAndInProgressLoans()
{
    // Count loans with statuses pending and inprogress
    $count = DB::table('loan')
        ->whereIn('loan.status', ['pending', 'inprogress'])
        ->count();

    return response()->json(['count' => $count], 200);
}



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

public function store(Request $request)
{
    DB::beginTransaction(); // Start transaction

    try {
        // Log the incoming request data
        Log::info('Incoming request data: ', $request->all());

        // Validate the incoming request data
        $validateData = $request->validate([
            'user_id' => 'required|string',
            'employee_id' => 'required',
            'category_id' => 'required|integer',
            'loan_amount' => 'required|integer',
            'loan_date' => 'required|date',
            'image' => 'nullable|string',
            'loan_closed_date' => 'required|date',
            'status' => 'required|in:pending,inprogress,preclose,completed,cancelled',
        ]);

        Log::info('Validated data: ', $validateData);

        // Fetch the loan category based on category_id
        $loanCategory = LoanCategory::find($validateData['category_id']);
        if (!$loanCategory) {
            Log::error('Loan category not found for category_id: ' . $validateData['category_id']);
            return response()->json(['message' => 'Loan category not found.'], 404);
        }

        // Get the interest_rate and duration from the loan category
        Log::info('Loan category found: ', ['category_type' => $loanCategory->category_type, 'interest_rate' => $loanCategory->interest_rate]);

        $interest_rate = $loanCategory->interest_rate;
        $duration = $loanCategory->duration;

        // Calculate the total amount
        $loan_amount = $validateData['loan_amount'];
        $total_amount = $loan_amount + ($loan_amount * $interest_rate / 100);

        // Generate a unique loan_id
        $loan_id = $this->generateLoanId();
        Log::info('Generated loan_id: ' . $loan_id);

        // Create the loan
        $loan = Loan::create([
            'loan_id' => $loan_id,
            'user_id' => $validateData['user_id'],
            'employee_id' => $validateData['employee_id'],
            'category_id' => $validateData['category_id'],
            'loan_amount' => $loan_amount,
            'loan_category' => $loanCategory->category_type,
            'loan_date' => $validateData['loan_date'],
            'total_amount' => $total_amount,
            'image' => $validateData['image'],
            'loan_closed_date' => $validateData['loan_closed_date'],
            'status' => $validateData['status'],
        ]);

        Log::info('Loan created successfully: ', ['loan_id' => $loan->loan_id]);

        // Calculate the due amount for each installment
        $due_amount = $total_amount / $duration;
        $loan_due_date = Carbon::parse($loan->loan_date);

        // Log due amount calculation
        Log::info('Loan due amount calculated: ', ['due_amount' => $due_amount, 'duration' => $duration]);

        // Insert loan_due records
        for ($i = 1; $i <= $duration; $i++) {
            Log::info('Inserting loan due record: iteration ' . $i);

            // Prepare the due date
            $due_date = $loan_due_date->copy()->addDays(($i - 1) * 7);

            // Log the data being inserted
            Log::info('LoanDue Data: ', [
                'loan_id' => $loan->loan_id,
                'user_id' => $loan->user_id,
                'due_amount' => $due_amount,
                'status' => 'unpaid',
                'due_date' => $due_date,
            ]);

            LoanDue::create([
                'loan_id' => $loan->loan_id,
                'user_id' => $loan->user_id,
                'due_amount' => $due_amount,
                'status' => 'unpaid',
                'due_date' => $due_date,
            ]);
        }

        DB::commit(); // Commit transaction
        Log::info('Transaction committed successfully');

        return response()->json(['message' => 'Loan and installments added successfully!', 'loan_id' => $loan->loan_id, 'total_amount' => $total_amount], 201);

    } catch (ValidationException $e) {
        DB::rollBack(); // Rollback transaction in case of validation error
        Log::error('Validation error: ', $e->errors());
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollBack(); // Rollback transaction in case of database error
        Log::error('Database error: ' . $e->getMessage());
        return response()->json(['message' => 'Error creating Loan'], 500);
    } catch (\Exception $e) {
        DB::rollBack(); // Rollback transaction in case of other errors
        Log::error('Error creating Loan: ' . $e->getMessage());
        return response()->json(['message' => 'Error creating Loan'], 500);
    }
}



/**
 * Generate a unique loan_id with the format athi_XXXX
 *
 * @return string
 */
private function generateLoanId()
{
    // Initialize loan_id
    Log::info('Generating Loan ID...');
    
    $loan_id = '';
    
     $lastLoan = Loan::orderBy('loan_id', 'desc')->first();
 Log::info('last loan_id : ' . $lastLoan);
 
  if ($lastLoan) {
            $lastLoanId = $lastLoan->loan_id;
            $numberPart = (int)substr($lastLoanId, 2); // Strip prefix (e.g., AT)
            $newNumber = $numberPart + 1;
               Log::info('lastloan id: ' . $lastLoanId);
               Log::info('numberPart: ' . $numberPart);
               Log::info('newNumber: ' . $newNumber);
        } else {
            $newNumber = 1;
        }

    do {
        // Get the latest loan_id in the loan table
       
       

        // Format the new loan_id with leading zeros
        $loan_id = 'AT' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        Log::info('Checking if loan_id is unique: ' . $loan_id);

    } while (Loan::where('loan_id', $loan_id)->exists());

    Log::info('Final generated loan_id: ' . $loan_id);
    return $loan_id;
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
    public function updateStatus(Request $request, $loan_id)
{
    // Log entry to confirm method execution
    Log::info('Reached updateStatus with loan_id: ' . $loan_id);

    DB::beginTransaction(); // Start transaction

    try {
        // Validate the incoming request data
        $validateData = $request->validate([
            'status' => 'required|in:pending,inprogress,preclose,completed,cancelled',
        ]);

        // Find the loan by loan_id
        $loan = Loan::where('loan_id', $loan_id)->first();
        if (!$loan) {
            Log::error('Loan not found for loan_id: ' . $loan_id);
            return response()->json(['message' => 'Loan not found.'], 404);
        }

        // Update only the status field
        $loan->status = $validateData['status'];
        $loan->save();

        DB::commit(); // Commit transaction
        Log::info('Loan status updated successfully: ', ['loan_id' => $loan->loan_id, 'status' => $loan->status]);

        return response()->json(['message' => 'Loan status updated successfully!', 'loan_id' => $loan->loan_id, 'status' => $loan->status], 200);

    } catch (ValidationException $e) {
        DB::rollBack(); // Rollback transaction in case of validation error
        Log::error('Validation error: ', $e->errors());
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollBack(); // Rollback transaction in case of database error
        Log::error('Database error: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Loan status'], 500);
    } catch (\Exception $e) {
        DB::rollBack(); // Rollback transaction in case of other errors
        Log::error('Error updating Loan status: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Loan status'], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

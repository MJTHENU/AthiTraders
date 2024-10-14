<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoanDue;
use App\Models\Loan;
use App\Models\User;
use App\Models\LoanCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB; 


class LoanDueController extends Controller
{
    /**
     * Display a listing of the resource.
     */



public function index()
{
    // Step 1: Paginate the LoanDue entries directly (10 entries per page)
    $loan_due_entries = LoanDue::orderBy('loan_id') // Sort by loan_id
                               ->paginate(100); // Pagination, 10 entries per page

    // Step 2: Return paginated LoanDue entries (includes pagination metadata)
    return response()->json($loan_due_entries, 200);
}


public function getAllLoans()
{
    // Step 1: Fetch all loans from the loan table
    $loans = Loan::all(); // Get all loans from the Loan model

    // Step 2: Get the current date
    $currentDate = now()->toDateString();

    // Step 3: Initialize an array to hold the loan IDs and their category details
    $matchedLoans = [];

    // Step 4: Iterate over the loans to calculate due dates
    foreach ($loans as $loan) {
        // Step 4.1: Fetch category_id from the loan object
        $categoryId = $loan->category_id; // Assuming category_id is directly available on the loan object
        
        // Step 4.2: Fetch user details from the user table
        $userid = $loan->user_id;
        // Log::info('User ID: ' . $userid);
      
        
        if ($userid) {
            // Use where method to find the user
            $user = User::where('user_id', $userid)->first(); // Fetch user details by user_id
            if ($user) {
                $userid = $user->user_id;
                $username = $user->user_name;
                $usercity= $user->city;
                $useraddress= $user->address;
                
            }
        }

        // Step 4.3: Fetch category details from loan_category table
        $category = LoanCategory::find($categoryId); // Fetching category details using category_id

        // Step 4.4: Check if the category exists
        if ($category) {
            // Fetch category details into separate variables
            $categoryType = $category->category_type; // Assign category_type to a variable
            $duration = $category->duration; // Assign duration to a variable
            $interest_rate = $category->interest_rate;

            // Step 4.5: Generate due dates for the specified duration (duration * 7 days)
            $dueDates = [];
            for ($i = 1; $i <= $duration; $i++) {
                $dueDate = Carbon::parse($loan->loan_date)->addDays(7 * $i)->toDateString();
                $dueDates[] = $dueDate;
            }

            // Step 4.6: Check if the current date is in the array of due dates
            if (in_array($currentDate, $dueDates)) {
                // Step 4.7: Store the loan_id, user details, and category details if the current date matches
                $matchedLoans[] = [
                    'loan_id' => $loan->loan_id,
                    'userid'=>$userid,
                    'user_name' => $username,
                    'user_city'=>$usercity,
                    'user_address'=>$useraddress,
                    'category_details' => [
                        'category_type' => $categoryType, // Using the separate variable for category_type
                        'duration' => $duration, // Using the separate variable for duration
                        'interest_rate' => $interest_rate
                    ]
                ];
            }
        }
    }

    // Step 5: Return the matched loan IDs, user details, and category details as a JSON response
    return response()->json(['matched_loans' => $matchedLoans], 200); // Returning matched loans as JSON
}
public function fetchLoanById($loan_id)
{
    try {
        // Log the incoming request
        Log::info('Fetching loan dues for loan_id: ' . $loan_id);

        // Fetch all loan dues by loan_id
        $loan_dues = LoanDue::where('loan_id', $loan_id)->get();

        // Check if any loan dues exist
        if ($loan_dues->isEmpty()) {
            Log::warning('No loan dues found for loan_id: ' . $loan_id);
            return response()->json(['message' => 'No loan dues found for the provided loan ID'], 404);
        }

        // Log the found loan dues
        Log::info('Loan dues retrieved successfully: ', ['loan_dues' => $loan_dues]);

        // Return the list of loan dues
        return response()->json(['loan_dues' => $loan_dues], 200);

    } catch (\Exception $e) {
        // Log the error message
        Log::error('Error fetching loan dues: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while fetching loan dues.'], 500);
    }
}

public function fetchLoanByPaidDate()
{
    // Get the current date in Y-m-d format
    $currentDate = now()->format('Y-m-d');

    // Fetch all loan dues where the paid_on date matches the current date
    $loan_dues = LoanDue::whereDate('paid_on', $currentDate)->get();
 $total_income=0;
    // Check if any loan dues exist for the current date
    if ($loan_dues->isEmpty()) {
        
        return response()->json(['loan_dues' => '0',
        'total_income' => $total_income,]);
    }

    // Sum the paid_amount fields (total income)
    $total_income = $loan_dues->sum('paid_amount');

    // Sum the due_amount fields (total due amount)
    // $total_due_amount = $loan_dues->sum('due_amount');

    // Return the total income and total due amount along with the loan dues data
    return response()->json([
        'message' => 'Total income and due amount calculated',
        'total_income' => $total_income,
        
        
    ], 200);
}

public function TotalLoanDues()
{
    // Get the current date in Y-m-d format
    $currentDate = now()->format('Y-m-d');

    // Fetch all loan dues where the paid_on date matches the current date
    $loan_dues = LoanDue::whereDate('paid_on', $currentDate)->get();
    
     
$total_due_amount =0;
    // Check if any loan dues exist for the current date
    if ($loan_dues->isEmpty()) {
        return response()->json(['loan_dues' => '0',
         'total_due_amount' => $total_due_amount,]);
    }

 $total_due_amount = $loan_dues->sum('due_amount');
 
    // Return the loan dues data
    return response()->json([
        'message' => 'Loan dues fetched successfully',
        'loan_dues' => $loan_dues,
        'total_due_amount' => $total_due_amount,
    ], 200);
}


//Emp Loan_due Paid Amount 
public function fetchLoanByEmpPaidDate(Request $request)
{
    // Log the entire request payload for debugging
    Log::info("Request payload:", $request->all());

    // Retrieve collection_by from request body
    $collection_by = $request->input('collection_by');  // Correctly accessing POST data
    Log::info("Collection_by user ID: " . $collection_by);  // Log to verify

    if (!$collection_by) {
        return response()->json(['message' => 'User ID is required.'], 400);  // Respond with error if not found
    }

    // Proceed with your logic
    $currentDate = now()->format('Y-m-d');
    
    // Fetch loan dues where 'paid_on' matches current date and 'collection_by' matches
    $loan_dues = LoanDue::whereDate('paid_on', $currentDate)
                        ->where('collection_by', $collection_by)
                        ->get();

    if ($loan_dues->isEmpty()) {
        return response()->json(['message' => 'Loan dues achievable amount for user ' . $collection_by . ' is 0'], 200);
    }

    $total_income = $loan_dues->sum('due_amount');
    $total_customers = $loan_dues->unique('user_id')->count();

    return response()->json([
        'message' => 'Emp Total income calculated',
        'total_income' => $total_income,
        'total_customers' => $total_customers,
        'loan_dues' => $loan_dues,
    ], 200);
}

//Loan_id Pass Status[pending,unpaid] top 1
public function getLoanByLoanid($loan_id)
    {
        // Fetch the loan records with the given loan_id and status of 'pending' or 'unpaid'
        $loanRecords = LoanDue::where('loan_id', $loan_id)
            ->whereIn('status', ['pending', 'unpaid']) // Use whereIn for multiple statuses
            ->first(); // Use first() to get a single record or get() for multiple records

        // Check if records found
        if (!$loanRecords) {
            return response()->json(['message' => 'No loan records found for the given loan ID and status.'], 404);
        }

        // Return the records in JSON format
        return response()->json($loanRecords, 200);
    }

//Current_date Show All loan_due list Array Formet 
public function fetchCitiesWithDueLoansArray()
{
    try {
        // Get the current date and day
        $currentDate = now()->format('Y-m-d');
        $currentDay = now()->format('l');  // Day of the week (e.g., 'Monday')

        // Log the current date and day for debugging
        \Log::info('Current date: ' . $currentDate . ', Day: ' . $currentDay);

        // Fetch cities with users who have unpaid loans due today
        $cities = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')
            ->whereDate('ld.due_date', '=', $currentDate)
            ->where('ld.status', '=', 'unpaid')  // Filter for unpaid loans
            ->distinct()
            ->pluck('u.city');  // Fetch distinct city names

        // Check if any cities are found
        if ($cities->isEmpty()) {
            return response()->json(['message' => 'No cities found for users with due loans on the current date'], 404);
        }

        // Count the total number of distinct cities
        $totalCities = $cities->count();

        // Return the cities, total count, current day, and current date
        return response()->json([
            'message' => 'Cities fetched successfully',
            'totalCities' => $totalCities,  // Total number of cities
            'currentDay' => $currentDay,    // Current day of the week
            'currentDate' => $currentDate,  // Current date
            'cities' => $cities             // List of cities
        ], 200);

    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Error fetching cities: ' . $e->getMessage());

        return response()->json([
            'message' => 'An error occurred while fetching cities'
        ], 500);
    }
}

//Current_date Show All loan_due list JSON Formet
public function fetchCitiesWithDueLoansJson()
{
    try {
        // Get the current date and day
        $currentDate = now()->format('Y-m-d');
        $currentDay = now()->format('l');  // Day of the week (e.g., 'Monday')

        // Log the current date and day for debugging
        \Log::info('Current date: ' . $currentDate . ', Day: ' . $currentDay);

        // Fetch cities with users who have unpaid loans due today
        $cities = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')
            ->whereDate('ld.due_date', '=', $currentDate)
            ->where('ld.status', '=', 'unpaid')  // Filter for unpaid loans
            ->distinct()
            ->pluck('u.city');  // Fetch distinct city names

        // Check if any cities are found
        if ($cities->isEmpty()) {
            return response()->json(['message' => 'No cities found for users with due loans on the current date'], 404);
        }

        // Count the total number of distinct cities
        $totalCities = $cities->count();

        // Dynamically assign city keys (city1, city2, etc.)
        $formattedCities = [];
        foreach ($cities as $index => $city) {
            $formattedCities['city' . ($index + 1)] = $city;
        }

        // Return the cities, total count, current day, and current date
        return response()->json([
            'message' => 'Cities fetched successfully',
            'totalCities' => $totalCities,  // Total number of cities
            'currentDay' => $currentDay,    // Current day of the week
            'currentDate' => $currentDate,  // Current date
            'cities' => $formattedCities    // List of cities in 'cityN' format
        ], 200);

    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Error fetching cities: ' . $e->getMessage());

        return response()->json([
            'message' => 'An error occurred while fetching cities'
        ], 500);
    }
}

//Current_date & city  Show All loan_due & Customer list
 public function fetchCitiesWithDueLoansAndDetails($city)
    {
        try {
            // Get the current date in Y-m-d format
            $currentDate = now()->format('Y-m-d');

            // Perform the JOIN query between loan_due, users, and loan tables, filtering by city
            $loanDetails = DB::table('loan_due as ld')
                ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
                ->select(
                    'u.user_id',
                    'u.user_name',      // Ensure this is the correct column name for user names
                    'u.address',
                    'ld.loan_id',
                    'ld.due_amount',
                    'ld.due_date',
                    'ld.status'
                )
                ->whereDate('ld.due_date', '=', $currentDate)
                //   ->whereIn('ld.status', ['unpaid', 'pending'])// Filter by current date
                // ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                
                ->where('u.city', '=', $city)                  // Filter by the passed city parameter
                ->get();

            // Check if any loan details are found
            if ($loanDetails->isEmpty()) {
                return response()->json(['message' => 'No users or loans found for due loans in the specified city on the current date'], 404);
            }

            // Prepare the response data
            $responseData = [
                'message' => 'Users and loan details fetched successfully',
                'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
                'customers' => $loanDetails // Each customer details as an array
            ];

            // Return the fetched data in the response
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // Log the exception message for debugging
            \Log::error('Error fetching loan details: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
        }
    }
    
//Current_date & city  Show Particular loan_due Customer 
public function fetchCitiesWithDueLoansAndDetailsSingle($city, $loan_id)
    {
        try {
            // Get the current date in Y-m-d format
            $currentDate = now()->format('Y-m-d');

            // Perform the JOIN query between loan_due, users, and loan tables, filtering by city and loan_id
            $loanDetails = DB::table('loan_due as ld')
                ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
                ->join('loan as l', 'l.loan_id', '=', 'ld.loan_id')    // Join with the loan table
                ->select(
                    'u.user_id',
                    'u.user_name', 
                    'u.address', 
                    'u.mobile_number',
                    'u.profile_photo',
                    'u.sign_photo',
                    'u.ref_name',
                    'u.ref_user_id',
                    'u.nominee_photo',
                    'u.nominee_sign',
                    'ld.due_amount', 
                    'ld.paid_amount', 
                    'ld.status', 
                    'l.image'          // Include the image field from the loan table
                )
                ->whereDate('ld.due_date', '=', $currentDate)  // Filter by current date
                // ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                ->where('u.city', '=', $city)                  // Filter by the passed city parameter
                ->where('ld.loan_id', '=', $loan_id)           // Filter by the passed loan_id parameter
                ->get();

            // Check if any loan details are found
            if ($loanDetails->isEmpty()) {
                return response()->json(['message' => 'No users or loans found for the specified criteria'], 404);
            }

            // Prepare the response data
            $responseData = [
                'message' => 'Users and loan details fetched successfully',
                'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
                'customers' => $loanDetails // Each customer details as an array
            ];

            // Return the fetched data in the response
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // Log the exception message for debugging
            \Log::error('Error fetching loan details: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
        }
    }
    
// Update Current_date & city  Show Particular loan_due Customer 
public function updateEntryLoanDue(Request $request, $city, $loan_id)
{
    try {
        // Validate the incoming request data
        $request->validate([
            'paid_amount' => 'required|numeric',  // Ensure paid_amount is provided and is numeric
            'status' => 'required|string'          // Ensure status is provided and is a string
        ]);

        // Get the current date for the 'paid_on' field
        $currentDate = now()->format('Y-m-d');

        // Get the user_id from the session (assuming Auth is set up correctly)
        // Uncomment the line below if you're using authentication
        // $collection_by = Auth::user()->user_id;

        // Update the loan_due records where the city and loan_id match
        $updated = DB::table('loan_due')
            ->where('loan_id', '=', $loan_id)   // Filter by loan_id
            ->whereExists(function ($query) use ($city) {
                $query->select(DB::raw(1))
                      ->from('users as u')
                      ->whereRaw('u.user_id = loan_due.user_id')
                      ->where('u.city', '=', $city); // Ensure the user belongs to the specified city
            })
            ->update([
                'paid_amount' => $request->input('paid_amount'), // Update the paid_amount
                'paid_on' => $currentDate,                        // Set the current date for paid_on
                // 'collection_by' => $collection_by,             // Uncomment if you want to set collection_by
                'status' => $request->input('status')             // Update the status
            ]);

        // Check if any rows were updated
        if ($updated) {
            return response()->json([
                'message' => 'Loan due entry updated successfully',
                'city' => $city,
                'loan_id' => $loan_id
            ], 200);
        } else {
            return response()->json([
                'message' => 'No matching loan entries found for the specified city and loan ID',
                'city' => $city,
                'loan_id' => $loan_id
            ], 404);
        }
    } catch (\Exception $e) {
        // Log the exception message for debugging
        \Log::error('Error updating loan due entry: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while updating loan due entry'], 500);
    }
}

//Total Emp getLoanDueData Details
public function getLoanDueData()
    {
        // Get current date
        $currentDate = date('Y-m-d');

        // Fetch total employee count
        $totalEmployees = DB::table('users')->where('user_type', 'employee')->count();

        // Fetch loan details
        $loanDetails = DB::select("
            SELECT 
                collection_by, 
                COUNT(*) AS loan_count, 
                SUM(due_amount) AS total_due_amount, 
                SUM(paid_amount) AS total_paid_amount
            FROM 
                loan_due 
            WHERE 
                due_date = DATE(CONVERT_TZ(NOW(), '+00:00', '+05:30')) 
            GROUP BY 
                collection_by
        ");

        // Initialize totals
        $totalDueAmount = 0;
        $totalPaidAmount = 0;
        $totalCustomers = 0; // Initialize total customers

        // Process loan details and calculate pending amounts
        $updatedLoanDetails = array_map(function ($loan) use (&$totalDueAmount, &$totalPaidAmount) {
            // Convert amounts to float for accurate calculations
            $loan->total_due_amount = (float)$loan->total_due_amount;
            $loan->total_paid_amount = (float)$loan->total_paid_amount;

            // Calculate total pending due amount
            $totalPendingDueAmount = $loan->total_due_amount - $loan->total_paid_amount;

            // Add to overall totals
            $totalDueAmount += $loan->total_due_amount;
            $totalPaidAmount += $loan->total_paid_amount;

            // Determine status
            $status = $totalPendingDueAmount > 0 ? "pending" : "paid";
            
            $username = DB::table('users')->where('user_id', $loan->collection_by)->value('user_name');

            return [
                'collection_by' => $loan->collection_by,
             'username'=>$username,

                'loan_count' => $loan->loan_count,
                'total_due_amount' => number_format($loan->total_due_amount, 2, '.', ''),
                'total_paid_amount' => number_format($loan->total_paid_amount, 2, '.', ''),
                'total_pending_due_amount' => number_format($totalPendingDueAmount, 2, '.', ''),
                'status' => $status,
            ];
        }, $loanDetails);

        // Calculate total pending due amount for the response
        $totalPendingDueAmountOverall = $totalDueAmount - $totalPaidAmount;

        // Calculate total customers as the sum of loan_count from loan details
        $totalCustomers = array_reduce($updatedLoanDetails, function ($carry, $loan) {
            return $carry + $loan['loan_count'];
        }, 0);

        // Prepare the final response
        $response = [
            'current_date' => $currentDate,
            'total_employees' => $totalEmployees,
            'total_customers' => $totalCustomers,
            'total_due_amount' => number_format($totalDueAmount, 2, '.', ''),
            'total_paid_amount' => number_format($totalPaidAmount, 2, '.', ''),
            'total_pending_due_amount' => number_format($totalPendingDueAmountOverall, 2, '.', ''),
            'loan_details' => $updatedLoanDetails,
        ];

        return response()->json($response);
    }
//Each Single Emp getLoanDueData Details    
public function getLoanDueByCollection($collection_by)
    {
        // Fetch loan details based on collection_by
        $loanDetails = LoanDue::where('collection_by', $collection_by)
            ->where('due_date', date('Y-m-d')) // Check for today's date
            ->get();

        // Prepare the response
        $response = [
            'collection_by' => $collection_by,
            'loan_details' => []
        ];

        foreach ($loanDetails as $loan) {
            // Calculate total pending due amount
            $totalPendingDueAmount = $loan->due_amount - $loan->paid_amount;

            // Get next due details if the status is unpaid
            if ($totalPendingDueAmount > 0) {
                $nextLoan = LoanDue::where('user_id', $loan->user_id)
                    ->where('status', 'unpaid')
                    ->orderBy('due_date', 'ASC')
                    ->first();

                $nextDueDate = $nextLoan ? $nextLoan->due_date : null;
                $nextDueAmount = $nextLoan ? ($nextLoan->due_amount + $totalPendingDueAmount) : 0;
            } else {
                $nextDueDate = null;
                $nextDueAmount = 0;
            }

            // Push loan details into response
            $response['loan_details'][] = [
                'loan_id' => $loan->loan_id,
                'user_id' => $loan->user_id,
                'due_date' => $loan->due_date,
                'due_amount' => $loan->due_amount,
                'paid_amount' => $loan->paid_amount,
                'total_pending_due_amount' => $totalPendingDueAmount,
                'status' => $totalPendingDueAmount === 0 ? 'paid' : 'pending',
                // 'next_due_date' => $nextDueDate,
                'next_due_amount' => $nextDueAmount
            ];
        }

        return response()->json($response);
    }
//Update Customer Loan_Due Amount based on Loan_id     
// public function updateCustLoanPayment(Request $request, $loan_id)
// {
//     // Validate incoming request data
//     $request->validate([
//         'collection_by' => 'required|string',
//         'paid_amount' => 'required|numeric|min:0',
//         'status' => 'required|in:paid,pending,unpaid'
//     ]);

//     // Fetch loan record using the provided loan_id
//     $loan = LoanDue::where('loan_id', $loan_id)->first();

//     if (!$loan) {
//         // If loan is not found, return an error
//         return response()->json(['message' => 'No loan found with the provided ID.'], 404);
//     }

//     // Get today's due loan if status is "pending"
//     if ($request->status === 'pending') {
//         // Fetch today's due loan
//         $todayDueLoan = LoanDue::where('due_date', date('Y-m-d'))->where('loan_id', $loan_id)->first();

//         if ($todayDueLoan) {
//             // First update for today's due loan
//             $todayDueLoan->collection_by = $request->collection_by;
//             $todayDueLoan->paid_amount = $request->paid_amount;
//             $todayDueLoan->paid_on = now(); // Set current date for payment
//             $todayDueLoan->status = 'pending';
//             $todayDueLoan->save(); // Save today's due loan

//             // Calculate pending due amount
//             $pending_due_amount = $todayDueLoan->due_amount - $request->paid_amount;

//             // Fetch the next unpaid loan for the user
//             $nextLoan = LoanDue::where('loan_id', $loan_id)->where('status', 'unpaid')->first();

//             if ($nextLoan) {
//                 // Update the next due loan's due amount
//                 $nextLoan->due_amount += $pending_due_amount;
//                 $nextLoan->save(); // Save the next unpaid loan

//                 return response()->json([
//                     'message' => "Today's Due is pending. Next Due on {$nextLoan->due_date}.",
//                     'pending_due_amount' => $pending_due_amount,
//                     'next_due_amount' => $nextLoan->due_amount,
//                     'next_due_date' => $nextLoan->due_date
//                 ]);
//             } else {
//                 // No next unpaid loan found
//                 return response()->json(['message' => 'No next unpaid loan found for the user.'], 404);
//             }
//         }
//     } elseif ($request->status === 'paid') {
//         // If status is "paid", update today's due loan
//         $todayDueLoan = LoanDue::where('due_date', date('Y-m-d'))->where('loan_id', $loan_id)->first();

//         if ($todayDueLoan) {
//             // First update for today's due loan
//             $todayDueLoan->collection_by = $request->collection_by;
//             $todayDueLoan->paid_amount = $request->paid_amount;
//             $todayDueLoan->paid_on = now(); // Set current date for payment
//             $todayDueLoan->status = 'paid';
//             $todayDueLoan->save(); // Save today's due loan

//             return response()->json([
//                 'message' => "Today's Due Paid on {$todayDueLoan->due_date} with amount {$todayDueLoan->due_amount}",
//                 'today_due_amount' => 0
//             ]);
//         }
//     } else {
//         // Handle invalid or unknown status cases
//         return response()->json(['message' => 'Invalid status or request.'], 400);
//     }

//     // If no case matches, return an error
//     return response()->json(['message' => 'Error in processing the request.'], 500);
// }
public function updateCustLoanPayment(Request $request, $loan_id)
{
    // Validate the incoming request
    $request->validate([
        'collection_by' => 'required|string',
        'paid_amount' => 'required|numeric|min:0',
        'status' => 'required|in:paid,pending'
    ]);

    // Assign request variables
    $collection_by = $request->collection_by;
    $paid_amount = $request->paid_amount;
    $status = $request->status;

    // Fetch today's loan due record for the given loan_id
    $todayLoan = LoanDue::where('loan_id', $loan_id)
        ->where('due_date', now()->toDateString()) // Check for today's date
        ->first();

    if (!$todayLoan) {
        return response()->json(['message' => 'No due record found for today\'s date.'], 404);
    }

    // Calculate pending amount for today
    $pending_amount = $todayLoan->due_amount - $paid_amount;

    // Case when status is "pending"
    if ($status == 'pending') {
        // Update the loan as pending
        $todayLoan->collection_by = $collection_by;
        $todayLoan->paid_amount = $paid_amount; // set paid_amount to 0 as per the request
        $todayLoan->pending_amount = $pending_amount; // Store the calculated pending amount
        $todayLoan->paid_on = now();
        $todayLoan->status = 'pending';
        $todayLoan->save();

        // Fetch the next unpaid loan record
        $nextLoan = LoanDue::where('loan_id', $loan_id)
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'ASC')
            ->first();

        if ($nextLoan) {
            // Calculate next due amount
            $next_due_amount = $todayLoan->due_amount + $pending_amount; // due_amount + pending_amount

            // Update next_amount in the next unpaid loan record
            $nextLoan->next_amount = $next_due_amount; // Set next_amount
            $nextLoan->pending_amount = $pending_amount; // Store today's pending amount for next due
            $nextLoan->save();

            // Return the response for pending status with correct calculations
            return response()->json([
                'message' => "Today's Due Pending now()",
                'loan' => [
                    'id' => $todayLoan->id,
                    'collection_by' => $collection_by,
                    'due_amount' => $todayLoan->due_amount,
                    'paid_amount' => $paid_amount,
                    'paid_on' => now(),
                    'status' => 'pending',
                    'pending_due_amount' => $pending_amount
                ],
                'next_due_date' => $nextLoan->due_date,
                'next_due_amount' => $next_due_amount // Correct calculation here
            ]);
        } else {
            return response()->json(['message' => 'No next unpaid loan found.'], 404);
        }
    }

    // Case when status is "paid"
    if ($status == 'paid') {
        // Update the loan as paid
        $todayLoan->collection_by = $collection_by;
        $todayLoan->paid_amount = $paid_amount; // Set the paid amount
        $todayLoan->pending_amount = 0; // No pending amount when fully paid
        $todayLoan->paid_on = now();
        $todayLoan->status = 'paid';
        $todayLoan->save();

        // Return the response for the paid case
        return response()->json([
            'message' => "Today's Due Paid $next_due_date & $next_due_amount",
            'loan' => [
                    'id' => $todayLoan->id,
                    'collection_by' => $collection_by,
                    'due_amount' => $todayLoan->due_amount,
                    'paid_amount' => $paid_amount,
                    'paid_on' => now(),
                    'status' => 'pending',
                    'pending_due_amount' => $pending_amount
                ],
                'next_due_date' => $nextLoan->due_date,
                'next_due_amount' => $next_due_amount // Correct calculation here
            ]);
    }

    // Fallback for invalid status
    return response()->json(['message' => 'Invalid status.'], 400);
}

//**  Future
//Future date from the request Loan_due All Details
public function getLoanDueByFutureDate(Request $request)
    {
        // Validate that 'future_date' is required and is a valid date
        $request->validate([
            'future_date' => 'required|date|after:today', // Ensures the date is in the future
        ]);

        // Get the future date from the request
        $future_date = $request->input('future_date');

        // Fetch loan_due records for the given future date
        $loanDues = LoanDue::where('due_date', $future_date)->get();

        // Check if any loan due records are found
        if ($loanDues->isEmpty()) {
            return response()->json(['message' => 'No loan dues found for the given future date.'], 404);
        }

        // Return the loan due records in the response
        return response()->json([
            'message' => 'Loan dues for the future date retrieved successfully.',
            'loan_dues' => $loanDues
        ], 200);
    }
    
//Future date Only Cities List
public function fetchCitiesWithDueLoansFutureDate(Request $request)
{
    try {
        // Validate the future date provided by the user
        $request->validate([
            'future_date' => 'required|date|after_or_equal:today'
        ]);

        // Get the user-provided future date
        $futureDate = $request->input('future_date');
        $futureDay = Carbon::parse($futureDate)->format('l');  // Day of the week (e.g., 'Monday')

        // Log the future date and day for debugging
        \Log::info('Future date: ' . $futureDate . ', Day: ' . $futureDay);

        // Fetch cities with users who have unpaid loans due on the selected date
        $cities = DB::table('loan_due as ld')
            ->join('users as u', 'u.user_id', '=', 'ld.user_id')
            ->whereDate('ld.due_date', '=', $futureDate)
            ->where('ld.status', '=', 'unpaid')  // Filter for unpaid loans
            ->distinct()
            ->pluck('u.city');  // Fetch distinct city names

        // Check if any cities are found
        if ($cities->isEmpty()) {
            return response()->json(['message' => 'No cities found for users with due loans on the selected date'], 404);
        }

        // Count the total number of distinct cities
        $totalCities = $cities->count();

        // Dynamically assign city keys (city1, city2, etc.)
        $formattedCities = [];
        foreach ($cities as $index => $city) {
            $formattedCities['city' . ($index + 1)] = $city;
        }

        // Return the cities, total count, future day, and future date
        return response()->json([
            'message' => 'Cities fetched successfully',
            'totalCities' => $totalCities,   // Total number of cities
            'futureDay' => $futureDay,       // Day of the week for the selected date
            'futureDate' => $futureDate,     // Selected future date
            'cities' => $formattedCities     // List of cities in 'cityN' format
        ], 200);

    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Error fetching cities: ' . $e->getMessage());

        return response()->json([
            'message' => 'An error occurred while fetching cities'
        ], 500);
    }
}

//Future date Only Cities use Customer List
public function fetchCitiesfutureDetails(Request $request, $city)
    {
        try {
            // Validate the future date provided by the user
            $request->validate([
                'future_date' => 'required|date|after_or_equal:today',
            ]);

            // Get the user-provided future date
            $futureDate = $request->input('future_date');

            // Log the future date and city for debugging
            \Log::info('Future date: ' . $futureDate . ', City: ' . $city);

            // Perform the JOIN query between loan_due and users tables, filtering by city and future date
            $loanDetails = DB::table('loan_due as ld')
                ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
                ->select(
                    'u.user_id',
                    'u.user_name',      // Ensure this is the correct column name for user names
                    'u.address',
                    'ld.loan_id',
                    'ld.due_amount',
                    'ld.due_date',
                    'ld.status'
                )
                ->whereDate('ld.due_date', '=', $futureDate)  // Filter by future date
                ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                ->where('u.city', '=', $city)                  // Filter by the passed city parameter
                ->get();

            // Check if any loan details are found
            if ($loanDetails->isEmpty()) {
                return response()->json(['message' => 'No users or loans found for due loans in the specified city on the selected date'], 404);
            }

            // Prepare the response data
            $responseData = [
                'message' => 'Users and loan details fetched successfully',
                'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
                'customers' => $loanDetails // Each customer details as an array
            ];

            // Return the fetched data in the response
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // Log the exception message for debugging
            \Log::error('Error fetching loan details: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
        }
    }
    
//Future date Only Cities use Single Customer Details  
public function fetchCityFutureDetailsSingle(Request $request, $city, $loan_id)
    {
        try {
            // Validate the future date provided by the user
            $request->validate([
                'future_date' => 'required|date|after_or_equal:today',
            ]);

            // Get the user-provided future date
            $futureDate = $request->input('future_date');

            // Log the future date, city, and loan ID for debugging
            \Log::info('Future date: ' . $futureDate . ', City: ' . $city . ', Loan ID: ' . $loan_id);

            // Perform the JOIN query between loan_due, users, and loan tables, filtering by city and loan_id
            $loanDetails = DB::table('loan_due as ld')
                ->join('users as u', 'u.user_id', '=', 'ld.user_id')  // Join with the users table
                ->join('loan as l', 'l.loan_id', '=', 'ld.loan_id')    // Join with the loan table
                ->select(
                    'u.user_id',
                    'u.user_name', 
                    'u.address', 
                    'u.mobile_number',
                    'u.profile_photo',
                    'u.sign_photo',
                    'u.ref_name',
                    'u.ref_user_id',
                    'u.nominee_photo',
                    'u.nominee_sign',
                    'ld.due_amount', 
                    'ld.paid_amount', 
                    'ld.status', 
                    'l.image'  // Include the image field from the loan table
                )
                ->whereDate('ld.due_date', '=', $futureDate)  // Filter by future date
                ->where('ld.status', '=', 'unpaid')            // Filter for unpaid loans
                ->where('u.city', '=', $city)                  // Filter by the passed city parameter
                ->where('ld.loan_id', '=', $loan_id)           // Filter by the passed loan_id parameter
                ->get();

            // Check if any loan details are found
            if ($loanDetails->isEmpty()) {
                return response()->json(['message' => 'No users or loans found for the specified criteria'], 404);
            }

            // Prepare the response data
            $responseData = [
                'message' => 'User and loan details fetched successfully',
                'total_customer_count' => $loanDetails->count(), // Count of customers with due loans
                'customers' => $loanDetails // Each customer details as an array
            ];

            // Return the fetched data in the response
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // Log the exception message for debugging
            \Log::error('Error fetching loan details: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
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
    try {
        // Validate the incoming request
        $validated = $request->validate([
            'loan_id' => 'required|string',
            'user_id' => 'required|string|max:255',
            'due_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'due_date' => 'required|date',
            'paid_on' => 'nullable|date',
            'collection_by' => 'required|integer',
            'status' => 'nullable|string|in:paid,unpaid,pending', // Added status validation with default option
        ]);

        // Retrieve collector by collection_by
        $collector = User::where('user_id', $validated['collection_by'])->first();

        // Check if collector exists
        if (!$collector) {
            return response()->json(['message' => 'Collector not found.'], 404);
        }

        // Determine the status, defaulting to 'unpaid'
        $status = $validated['status'] ?? 'unpaid';

        // Create the loan_due record
        $loan_due = LoanDue::insert([
            'loan_id' => $validated['loan_id'], // use loan_id from validated data
            'user_id' => $validated['user_id'], 
            'due_amount' => $validated['due_amount'],
            'paid_amount' => $validated['paid_amount'],
            'due_date' => $validated['due_date'],
            'paid_on' => $validated['paid_on'] ?? null, // Set paid_on to null if not provided
            'collection_by' => $collector->id, // Use the valid collector ID
            'status' => $status, // Save the status field
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Return a success response
        return response()->json(['message' => 'Loan Due added successfully!', 'status' => $status], 201);
        
    } catch (ValidationException $e) {
        // Handle validation errors
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Handle any other errors
        Log::error('Error adding Loan Due: ' . $e->getMessage());
        return response()->json(['message' => 'Error adding Loan Due'], 500);
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
    public function update(Request $request, $loan_id)
{
    try {
        // Validate the incoming request
        $validated = $request->validate([
            'paid_amount' => 'required|numeric',
            'paid_on' => 'nullable|date',
            'status' => 'nullable|string|in:paid,unpaid,pending', // Optional status
        ]);

        // Retrieve the first loan_due record with the specified loan_id
        $loan_due = LoanDue::where('loan_id', $loan_id)->orderBy('due_date')->first();

        // Check if the loan_due record exists
        if (!$loan_due) {
            return response()->json(['message' => 'Loan Due record not found.'], 404);
        }

        // Fetch collection_by from the session
        $collection_by = session('user_id'); // Assuming the user_id is stored in the session as 'user_id'

        // Check if collection_by exists
        if (!$collection_by) {
            return response()->json(['message' => 'Collector not found in session.'], 404);
        }

        // Update the loan_due record with the new validated data
        $loan_due->paid_amount = $validated['paid_amount'];
        $loan_due->paid_on = $validated['paid_on'] ?? null; // Keep it null if not provided
        $loan_due->collection_by = $collection_by; // Use the user_id from session
        $loan_due->status = $validated['status'] ?? 'unpaid'; // Default to unpaid if status not provided
        $loan_due->updated_at = now();

        // Save the changes
        $loan_due->save();

        // Return a success response
        return response()->json(['message' => 'Loan Due updated successfully!', 'status' => $loan_due->status], 200);

    } catch (ValidationException $e) {
        // Handle validation errors
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Handle any other errors
        Log::error('Error updating Loan Due: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Loan Due'], 500);
    }
}

public function updateID(Request $request, $loan_id, $due_date, $status)
{
    Log::info('Incoming request data', [
        'loan_id' => $loan_id,
        'due_date' => $due_date,
        'status' => $status,
        'request_data' => $request->all(),
    ]);

    try {
        // Validate the incoming request for paid_amount
        $validated = $request->validate([
            'paid_amount' => 'required|numeric',
        ]);

        // Retrieve the specific loan_due record based on loan_id and due_date
        $loan_due = LoanDue::where('loan_id', $loan_id)
                           ->where('due_date', $due_date)
                           ->first();

        // Check if the loan_due record exists
        if (!$loan_due) {
            Log::warning('Loan Due record not found', [
                'loan_id' => $loan_id,
                'due_date' => $due_date,
            ]);
            return response()->json(['message' => 'Loan Due record not found.'], 404);
        }

        // Fetch collection_by from the session
        $collection_by = session('user_id');

        // Check if collection_by exists
        if (!$collection_by) {
            return response()->json(['message' => 'Collector not found in session.'], 404);
        }

        // Update the loan_due record with the new validated data
        $loan_due->paid_amount = $validated['paid_amount'];
        $loan_due->collection_by = $collection_by; // Use the user_id from session
        $loan_due->status = $status; // Update to the provided status
        $loan_due->updated_at = now();

        // Save the changes
        $loan_due->save();

        // Return a success response
        return response()->json(['message' => 'Loan Due updated successfully!', 'status' => $loan_due->status], 200);

    } catch (ValidationException $e) {
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        Log::error('Error updating Loan Due: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Loan Due'], 500);
    }
}

public function updateLoanDue(Request $request)
{
    // Validate the request only for loan_id and due_date
    $validatedData = $request->validate([
        'loan_id' => 'required|string',
        'due_date' => 'required|date',
    ]);

    // Find the loan due record by loan_id and due_date
    $loanDue = LoanDue::where('loan_id', $validatedData['loan_id'])
                      ->where('due_date', $validatedData['due_date'])
                      ->first();

    if (!$loanDue) {
        return response()->json(['message' => 'Loan due record not found'], 404);
    }

    // Only update fields that are present in the request
    if ($request->has('paid_amount')) {
        $loanDue->paid_amount = $request->input('paid_amount');
    }
    if ($request->has('paid_on')) {
        $loanDue->paid_on = $request->input('paid_on');
    }
    if ($request->has('collection_by')) {
        $loanDue->collection_by = $request->input('collection_by');
    }
    if ($request->has('status')) {
        $loanDue->status = $request->input('status');
    }

    // Save the updated loan due record
    $loanDue->save();

    return response()->json(['message' => 'Loan due updated successfully', 'loan_due' => $loanDue], 200);
}

    /**
     * Remove the specified resource from storage.
     */
public function destroy(string $loan_id)
{
    // Find the loan due record by loan_id
    $loan_due = LoanDue::where('loan_id', $loan_id)->first();

    // Check if the loan due record exists
    if (!$loan_due) {
        // Return a 404 response if the loan due is not found
        return response()->json([
            'message' => 'Loan Due not found'
        ], 404);
    }

    try {
        // Attempt to delete the loan due record
        $loan_due->delete();

        // Return a success response after deletion
        return response()->json([
            'message' => 'Loan Due deleted successfully'
        ], 200);
    } catch (\Exception $e) {
        // Return an error response if an exception occurs during deletion
        return response()->json([
            'message' => 'Failed to delete Loan Due',
            'error' => $e->getMessage()
        ], 500);
    }
}


}

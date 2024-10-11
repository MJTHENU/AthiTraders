<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoanDueController;
use App\Http\Controllers\LoanCategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\Auth\ForgotPasswordController;


//Forgot-Password
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
// User Registration
Route::post('/register', [AuthController::class, 'register']);
Route::put('/user-update/{id}', [AuthController::class, 'update']);

// User Login
Route::post('/login', [AuthController::class, 'login']);
Route::get('/profile/{id}', [AuthController::class, 'profile']);
Route::get('/all-users', [AuthController::class, 'allUsers']);
Route::get('/employees', [AuthController::class, 'getEmployees']);
Route::put('/employees/{id}', [AuthController::class, 'update']);
Route::delete('/user/{id}', [AuthController::class, 'delete']);
Route::get('/customer-count', [AuthController::class, 'getCustomersCount']);
Route::get('/employee-count', [AuthController::class, 'getEmployeesCount']);
Route::get('/customer', [AuthController::class, 'getcustomers']);
Route::middleware('auth:api')->get('/me', [AuthController::class, 'me']);
Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
Route::get('/test-token', [AuthController::class, 'testTokenGeneration']);


Route::resource('/loan-due', LoanDueController::class);
Route::get('/totalloandue', [LoanDueController::class, 'TotalLoanDues']);
Route::get('/fetch-loan-by-current-date', [LoanDueController::class,'fetchLoanByPaidDate']);
//Emp Loan_due Paid amount
Route::get('fetchLoanByEmpPaidDate/{user_id}', [LoanDueController::class, 'fetchLoanByEmpPaidDate']);
Route::post('fetchLoanByEmpPaidDate', [LoanDueController::class, 'fetchLoanByEmpPaidDate']);

//Current_date Show All loan_due list Array Format
Route::get('fetchCitiesWithDueLoansArray', [LoanDueController::class, 'fetchCitiesWithDueLoansArray']);
//Current_date Show All loan_due list Json Format
Route::get('fetchCitiesWithDueLoansJson', [LoanDueController::class, 'fetchCitiesWithDueLoansJson']);
//Current_date & city  Show All loan_due & Customer list
Route::get('fetchCitiesWithDueLoans/{city}', [LoanDueController::class, 'fetchCitiesWithDueLoansAndDetails']);
//Current_date & city  Show Particular loan_due Customer 
Route::get('fetchCitiesWithDueLoans/{city}/{loan_id}', [LoanDueController::class, 'fetchCitiesWithDueLoansAndDetailsSingle']);
//Entry Current_date & city  Show Particular loan_due Customer 
Route::put('updateEntryLoanDue/{city}/{loan_id}', [LoanDueController::class, 'updateEntryLoanDue']);
//Total Emp getLoanDueData Details
Route::get('/loan-due', [LoanDueController::class, 'getLoanDueData']);
//Each Single Emp getLoanDueData Details    
Route::get('get-loan-due/{collection_by}', [LoanDueController::class, 'getLoanDueByCollection']);
//Update Customer Loan_Due Amount  
Route::put('/update-loan-pay/{loan_id}', [LoanDueController::class, 'updateCustLoanPayment']);

//Show all Loans
Route::get('/alltodayloans', [LoanDueController::class, 'getAllLoans']);
//Loan-due Particular Loan id Show all Due
Route::get('/loan/{loan_id}/dues', [LoanDueController::class, 'fetchLoanById']);
//Change Paid_amount,paid_on,status
Route::put('/loan-due/{loanDue}', [LoanDueController::class, 'update']);
//Single Record Updated
Route::put('/loan_due/{loan_id}/{due_date}/{status}', [LoanDueController::class, 'updateID']);
//
Route::put('/update-loan', [LoanController::class, 'updateLoanDue']);





//Auto Generate Loan-id
Route::resource('/loan', LoanController::class);
Route::get('/indexweb', [LoanController::class, 'showloan-topthreee']);
Route::get('/loans/count-pending-inprogress', [LoanController::class, 'countPendingAndInProgressLoans']);
Route::get('/autoloanid', [LoanController::class, 'generateLoanId']);
Route::post('/loans', [LoanController::class, 'store']);
Route::put('/loan/{loan_id}/status', [LoanController::class, 'updateStatus']);



Route::get('/loans/details', [LoanController::class, 'fetchLoansWithDetails']);



Route::resource('/loan-category', LoanCategoryController::class);






<?php

namespace App\Http\Controllers;

use App\Models\LoanDue;
use Illuminate\Http\Request;
use App\Models\Login;
use App\Models\User;
use App\Models\Loan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function getEmployees()
    {
        $employees = User::where('user_type', 'employee')->get();
        return response()->json($employees);
    }
    public function getcustomers()
    {
        $users = User::where('user_type', 'user')->get();
        return response()->json(['message' => $users], 200);
    }
    
    public function getCustomersCount()
{
    $customerCount = User::where('user_type', 'user')->count(); // Count users with user_type 'user'
    return response()->json([
        'message' => 'Customer count retrieved successfully',
        'customer_count' => $customerCount
    ], 200);
}

public function getEmployeesCount()
{
    // Count users with 'employee' user type
    $employeeCount = User::where('user_type', 'employee')->count();

    // Return the count as a JSON response
    return response()->json([
        'message' => 'Employee count retrieved successfully',
        'employee_count' => $employeeCount
    ], 200);
}


    public function allUsers()
    {
        $data =DB::select('select * from users');
        return response()->json(['message'=>$data]);
    }

    public function profile($id)
    {
       $login = User::where('user_id', $id)->first();


        if(!$login) {
            return response()->json(['message' => 'no data found'], 404);
        }

        return response()->json(['message'=>$login], 200);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

public function register(Request $request)
{
    DB::beginTransaction();

    try {
        // Initial validation rules for all users
        $rules = [
            'user_id' => 'required|string|max:255|unique:users,user_id',
            'user_name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'aadhar_number' => 'required|string|size:12|regex:/^\d{12}$/|unique:users,aadhar_number',
            'address' => 'required|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'pincode' => 'required|string|regex:/^[0-9]{6}$/|max:255',
            'district' => 'required|string|max:255',
            'user_type' => 'required|in:employee,user',
            'status' => 'nullable|in:active,inactive',
            'mobile_number' => 'required|string|regex:/^[6-9][0-9]{9}$/|max:255',
            'email' => 'nullable|string|email|unique:users|max:255',
            'alter_mobile_number' => 'nullable|string|regex:/^[0-9]{10}$/|max:255',
           
          
            //  'profile_photo' => 'nullable', // Updated for file
            // 'sign_photo' => 'nullable',    // Updated for file
           
            'ref_user_id' => 'nullable|string|max:255',
            // 'ref_sign_photo' => 'nullable|string',
            'ref_aadhar_number' => 'nullable|string|size:12|regex:/^\d{12}$/',
            'qualification' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'added_by' => 'nullable|string',
           
        ];

        // Conditional validation for 'employee'
        if ($request->input('user_type') === 'employee') {
            $rules['email'] = 'required|string|email|unique:users|max:255';
            $rules['qualification'] = 'required|string|max:255';
              $rules['password'] = 'required|string|min:4';
               $rules[ 'profile_photo'] = 'required|string';
             $rules['sign_photo'] = 'required|string';
        }

        // Conditional validation for 'user'
        if ($request->input('user_type') === 'user') {
               $rules[ 'ref_name'] = 'required|string|max:255';
            $rules['alter_mobile_number'] = 'required|string|regex:/^[0-9]{10}$/|max:255';
            $rules['profile_photo'] = 'required|string';  
            $rules['sign_photo'] = 'required|string';     
            $rules['ref_aadhar_number'] = 'required|string|size:12|regex:/^\d{12}$/';
             $rules['password'] = 'nullable';
                $rules['nominee_photo'] = 'required|string'; 
             $rules['nominee_sign'] ='required|string';   
        }

        // Now validate the request based on the rules
        $validatedData = $request->validate($rules, [
            'user_name.regex' => 'The user name must contain only letters and spaces.',
            'pincode.regex' => 'The pincode must be exactly 6 digits.',
            'mobile_number.regex' => 'The phone number must be exactly 10 digits and must be a valid number',
            'alter_mobile_number.regex' => 'The alternate phone number must be exactly 10 digits.',
        ]);

       

        // Create the user
$user = User::create([
    'user_id' => $validatedData['user_id'],
    'user_name' => $validatedData['user_name'],
    'aadhar_number' => $validatedData['aadhar_number'],
    'address' => $validatedData['address'],
    'landmark' => $validatedData['landmark'],
    'city' => $validatedData['city'],
    'pincode' => $validatedData['pincode'],
    'district' => $validatedData['district'],
    'user_type' => $validatedData['user_type'],
    'status' => $validatedData['status'],
    'mobile_number' => $validatedData['mobile_number'],
    'email' => $validatedData['email'],
    'alter_mobile_number' => $validatedData['alter_mobile_number'],
    'profile_photo' => $validatedData['profile_photo'],
    'sign_photo' => $validatedData['sign_photo'],
    'ref_name' => $validatedData['ref_name'] ?? null, // Use null coalescing here
    'ref_user_id' => $validatedData['ref_user_id'] ?? null,
    'ref_sign_photo' => $validatedData['sign_photo'] ?? null,
    'ref_aadhar_number' => $validatedData['ref_aadhar_number'] ?? null,
    'qualification' => $validatedData['qualification'] ?? null,
    'designation' => $validatedData['designation'] ?? null,
    'added_by' => $validatedData['ref_user_id'] ?? null,
    'password' => bcrypt($validatedData['password']),
]);


        // Create the login entry
        $login = Login::create([
            'user_id' => $validatedData['user_id'],
            'user_name' => $validatedData['user_name'],
            'user_type' => $validatedData['user_type'],
            'status' => $validatedData['status'],
            'mobile_number' => $validatedData['mobile_number'],
            'email' => $validatedData['email'],
            'added_by' => $validatedData['ref_user_id'] ?? null,
            'password' => bcrypt($validatedData['password']),
            'security_password' => $request->security_password ?? null,
        ]);

        DB::commit();

        return response()->json(['message' => 'User registered successfully!'], 201);
    } catch (ValidationException $e) {
        DB::rollBack();
        Log::error('Validation error: ' . json_encode($e->errors()));
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error registering user: ' . $e->getMessage());
        return response()->json(['message' => $e->getMessage()], 500);
    }
}



    
//     public function update(Request $request, $id)
// {
//     DB::beginTransaction();

//     try {
//         // Validate the request
//         $request->validate([
//             'user_id' => 'required',
//             'user_name' => 'required|string|max:255',
//             'aadhar_number' => 'required|string|max:255',
//             'address' => 'required|string|max:255',
//             'landmark' => 'nullable|string|max:255',
//             'city' => 'required|string|max:255',
//             'pincode' => 'required|string|max:10',
//             'district' => 'required|string|max:255',
//             'user_type' => 'required|in:admin,employee,user',
//             'status' => 'required|in:active,inactive',
//             'mobile_number' => 'required|string|max:15',
//             'email' => 'required|string|email|max:255|unique:users,email,' . $id,
//             'alter_mobile_number' => 'nullable|string|max:15',
//         ]);

//         // Retrieve the user based on the provided ID
//         $user = User::find($id);

//         // Check if the user exists
//         if (!$user) {
//             return response()->json(['message' => 'User not found'], 404);
//         }

//         // Update the user with the validated data
//         $user->update([
//             'user_name' => $request->input('user_name'),
//             'aadhar_number' => $request->input('aadhar_number'),
//             'address' => $request->input('address'),
//             'landmark' => $request->input('landmark'),
//             'city' => $request->input('city'),
//             'pincode' => $request->input('pincode'),
//             'district' => $request->input('district'),
//             'user_type' => $request->input('user_type'),
//             'status' => $request->input('status'),
//             'mobile_number' => $request->input('mobile_number'),
//             'email' => $request->input('email'),
//             'alter_mobile_number' => $request->input('alter_mobile_number'),
//         ]);

//         DB::commit();

//         return response()->json(['message' => 'User updated successfully']);
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'Update failed', 'error' => $e->getMessage()], 500);
//     }
// }


public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {
        // Validate the request
        $request->validate([
            'user_id' => 'nullable',
            'user_name' => 'nullable|string|max:255',
            'aadhar_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'district' => 'nullable|string|max:255',
            'user_type' => 'nullable|in:admin,employee,user',
            'status' => 'nullable|in:active,inactive',
            'mobile_number' => 'nullable|string|max:15',
            // 'email' => 'nullable|string|email|max:255|unique:users,email,' . $id,
            'email' => 'nullable|string|email|max:255',
            'alter_mobile_number' => 'nullable|string|max:15',
            'profile_photo' => 'nullable|string',
            'sign_photo' => 'nullable|string',
             'nominee_photo' => 'nullable|string',
              'nominee_sign' => 'nullable|string',
            'ref_name' => 'nullable|string|max:255',
           'ref_user_id' => 'nullable|string|max:255',
           
            'ref_aadhar_number' => 'nullable|string|max:255',
            'updated_by' => 'nullable|integer',
            'password' => 'nullable|string|min:4',
        ]);

        // Find the user by ID
        // $user = User::find(id);
        $user = User::where('user_id', $id)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update user data, specifically for status changes
        $user->update($request->only([
            'user_id', 'user_name', 'aadhar_number', 'address', 'landmark', 
            'city', 'pincode', 'district', 'user_type', 'status', 
            'mobile_number', 'email', 'alter_mobile_number', 
            'profile_photo', 'sign_photo', 'ref_name', 
            'ref_user_id', 'nominee_photo','nominee_sign', 'ref_aadhar_number', 
            'qualification', 'designation', 'updated_by',
        ]));

        // If a password is provided, hash it and update the user
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
            $user->save(); // Save the updated password
        }

        // Update the login table if it exists
        $login = Login::where('user_id', $user->user_id)->first();
        if ($login) {
            $login->update([
                'user_id' => $request->user_id,
                'user_name' => $request->user_name,
                'user_type' => $request->user_type,
                'status' => $request->status,
                'mobile_number' => $request->mobile_number,
                'email' => $request->email,
                'updated_by' => $request->updated_by,
                'password' => $request->filled('password') ? bcrypt($request->password) : $login->password,
                'security_password' => $request->security_password ?? $login->security_password,
            ]);
        }

        DB::commit();

        return response()->json(['message' => 'Employee updated successfully!'], 200);
    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating employee: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating employee', 'error' => $e->getMessage()], 500);
    }
}



// public function delete($id)
// {
//     // Fetch the user using the user_id (which is passed as $id)
//     $user = User::where('user_id', $id)->first();

//     // Fetch the corresponding login entry using the user_id
//     $login = Login::where('user_id', $id)->first();

//     // Check if both the user and login are found
//     if ($user && $login) {
//         DB::transaction(function () use ($user, $login) {
//             // Delete both user and login in a transaction
//             $user->delete();
//             $login->delete();
//         });

//         return response()->json(['message' => 'Employee deleted successfully!']);
//     } else {
//         // Return a 404 if user or login not found
//         return response()->json(['message' => 'Employee not found!'], 404);
//     }
// }

public function delete($id)
{
    // Fetch the user using the user_id (which is passed as $id)
    $user = User::where('user_id', $id)->first();

    // Fetch the corresponding login entry using the user_id
    $login = Login::where('user_id', $id)->first();

    // Fetch the loan entry using the user_id
    $loan = Loan::where('user_id', $id)->first();

    // Logging for debugging
    if (!$user) {
        \Log::info("User with ID $id not found.");
    }

    if (!$login) {
        \Log::info("Login for user ID $id not found.");
    }

    if (!$loan) {
        \Log::info("Loan for user ID $id not found.");
    }

    // Check if all entities are found
    if ($user && $login && $loan) {
        // Check the loan status
        if ($loan->status === 'Completed') {
            DB::transaction(function () use ($user, $login) {
                // Delete both user and login in a transaction
                $user->delete();
                $login->delete();
            });

            return response()->json(['message' => 'Employee deleted successfully!']);
        } else {
            // If the loan status is not 'Completed', prevent deletion and return an error message
            return response()->json([
                'message' => "Please check the loan Status '$loan->status' does not allow deletion."
            ], 400);  // Return a 400 Bad Request error
        }
    } else {
        // Return a 404 if user, login, or loan not found
        return response()->json(['message' => 'Employee or related records not found!'], 404);
    }
}




    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|string|max:255',
    //         'password' => 'required|string',
    //     ]);

    //     $credentials = $request->only('user_id', 'password');

    //     $user = Login::where('user_id', $credentials['user_id'])->first();

    //     if (!$user || !Hash::check($credentials['password'], $user->password)) {
    //         return response()->json(['error' => 'Wrong Password or User Id'], 401);
    //     }

    //     try {
    //         $token = JWTAuth::fromUser($user);

    //         return response()->json([
    //             'message' => 'Login successful',
    //             'name' => $user->user_name,
    //             'role' => $user->user_type,
    //             'user_id' => $user->user_id,
    //             'id' => $user->id,
    //             'token' => $token,
    //         ], 200);
    //     } catch (JWTException $e) {
    //         return response()->json(['error' => 'Could not create token'], 500);
    //     }
    // }

    public function login(Request $request)
    {
        // Check if the user provided an email or a user_id
        $loginField = filter_var($request->input('user_id') ?? $request->input('email'), FILTER_VALIDATE_EMAIL) ? 'email' : 'user_id';
        
        // Adjust validation to check for either email or user_id
        $request->validate([
            $loginField => 'required|string|max:255', // Validate email or user_id depending on input
            'password' => 'required|string',
        ]);
    
        // Find user by the appropriate field (email or user_id)
        $user = Login::where($loginField, $request->input('user_id') ?? $request->input('email'))->first();
    
        if (!$user) {
            \Log::error('User not found: ' . $request->input('user_id') ?? $request->input('email'));
            return response()->json(['error' => 'User not found'], 401);
        }
    
        // Check if the password matches
        if (!Hash::check($request->input('password'), $user->password)) {
            \Log::error('Password mismatch for user: ' . $request->input('user_id') ?? $request->input('email'));
            return response()->json(['error' => 'Wrong Password or User Id/Email'], 401);
        }
    
        try {
            // Generate the JWT token
            $token = JWTAuth::fromUser($user);
    
            return response()->json([
                'message' => 'Login successful',
                'name' => $user->user_name,
                'role' => $user->user_type,
                'user_id' => $user->user_id,
                'id' => $user->id,
                'token' => $token,
            ], 200);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }
    
    

    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json(['message' => 'User logged out successfully']);
    }

    public function testTokenGeneration()
    {
        $user = Login::first();

        if (!$user) {
            return response()->json(['error' => 'No users found'], 404);
        }

        try {
            $token = JWTAuth::fromUser($user);
            return response()->json(['token' => $token]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }


}

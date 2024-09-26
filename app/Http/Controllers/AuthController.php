<?php

namespace App\Http\Controllers;

use App\Models\LoanDue;
use Illuminate\Http\Request;
use App\Models\Login;
use App\Models\User;
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
    public function getUsers()
    {
        $users = User::where('user_type', 'user')->get();
        return response()->json(['message' => $users], 200);
    }

    public function allUsers()
    {
        $data =DB::select('select * from users');
        return response()->json(['message'=>$data]);
    }

    public function profile($id)
    {
        $login = User::find($id);

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
            $validatedData = $request->validate([

                'user_id' => 'required|string|max:255',
                'user_name' => 'required|string|max:255',
                'aadhar_number' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'landmark' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'pincode' => 'required|string|max:10',
                'district' => 'required|string|max:255',
                'user_type' => 'required|in:admin,employee,user',
                'status' => 'required|in:active,inactive',
                'mobile_number' => 'required|string|max:15',
                'email' => 'required|string|email|unique:users|max:255',
                'alter_mobile_number' => 'nullable|string|max:15',
                'profile_photo' => 'nullable|string',
                'sign_photo' => 'nullable|string',
                'ref_name' => 'nullable|string|max:255',
                'ref_user_id' => 'nullable|integer',
                'ref_sign_photo' => 'nullable|string',
                'ref_aadhar_number' => 'nullable|string|max:255',
                'qualification' => 'required|string|max:255',
                'designation' => 'nullable|string|max:255',
                'added_by' => 'nullable|string',
                'password' => 'required|string|min:4',
            ]);

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
                'ref_name' => $validatedData['ref_name'],
                'ref_user_id' => $validatedData['ref_user_id'],
                'ref_sign_photo' => $validatedData['sign_photo'],
                'ref_aadhar_number' => $validatedData['ref_aadhar_number'],
                'qualification' => $validatedData['qualification'],
                'designation' => $validatedData['designation'],
                'added_by' => $validatedData['ref_user_id'] ?? null,
                'password' => bcrypt($validatedData['password']),
            ]);

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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $request->validate([

                'user_id' => 'required',
                'user_name' => 'required|string|max:255',
                'aadhar_number' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'landmark' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'pincode' => 'required|string|max:10',
                'district' => 'required|string|max:255',
                'user_type' => 'required|in:admin,employee,user',
                'status' => 'required|in:active,inactive',
                'mobile_number' => 'required|string|max:15',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'alter_mobile_number' => 'nullable|string|max:15',
                'profile_photo' => 'nullable|string',
                'sign_photo' => 'nullable|string',
                'ref_name' => 'nullable|string|max:255',
                'ref_user_id' => 'nullable|string',
                'ref_sign_photo' => 'nullable|string',
                'ref_aadhar_number' => 'nullable|string|max:255',
                'updated_by' => 'nullable|integer',
                'password' => 'nullable|string|min:4',
            ]);

            $user = User::find($id);
            $user_id = Login::where('user_id', $user->user_id);
            $user->update($request->only([
                'user_id', 'user_name', 'aadhar_number', 'address', 'landmark', 'city',
                'pincode', 'district', 'user_type', 'status', 'mobile_number',
                'email', 'alter_mobile_number', 'profile_photo', 'sign_photo',
                'ref_name', 'ref_user_id', 'ref_sign_photo', 'ref_aadhar_number', 'qualification', 'designation',
                'updated_by', 'password',
            ]));

            $login = Login::where('user_id', $user->user_id)->first();
            $login->update([
                'user_id' => $request->user_id,
                'user_name' => $request->user_name,
                'user_type' => $request->user_type,
                'status' => $request->status,
                'mobile_number' => $request->mobile_number,
                'email' => $request->email,
                'updated_by' => $request->updated_by,
                'password' => $request->password ? bcrypt($request->password) : $login->password,
                'security_password' => $request->security_password ?? $login->security_password,
            ]);

            DB::commit();

            return response()->json(['message' => 'Employee updated successfully!'], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the actual exception message
            Log::error('Error updating employee: ' . $e->getMessage());

            return response()->json(['message' => 'Error updating employee', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        $user = User::find($id);
        $login = Login::find($id);

        if ($user && $login) {
            DB::transaction(function () use ($user, $login) {
                $user->delete();
                $login->delete();
            });

            return response()->json(['message' => 'Employee deleted successfully!']);
        } else {
            return response()->json(['message' => 'Employee not found!'], 404);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('user_id', 'password');

        $user = Login::where('user_id', $credentials['user_id'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Wrong Password or User Id'], 401);
        }

        try {
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

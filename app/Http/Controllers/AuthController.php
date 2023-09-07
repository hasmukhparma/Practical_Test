<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;

class AuthController extends Controller
{
	public function register(Request $request)
    {
    	try
    	{
    		$validator = Validator::make($request->all(), [
	            'name' => 'required',
	            'email' => 'required|email',
	            'password' => 'required',
	            'c_password' => 'required|same:password',
	        ]);
	   
	        if($validator->fails()){
	        	
	        	$response = [
		            'success' => false,
		            'message' => $validator->errors(),
		        ];


		        if(!empty($errorMessages)){
		            $response['data'] = $errorMessages;
		        }

	        	return response()->json($response, 404);
	        }
	   
	        $input = $request->all();
	        $input['password'] = bcrypt($input['password']);
	        $user = User::create($input);
	        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
	        $success['name'] =  $user->name;
	   
	   		$response = [
	            'success' => true,
	            'message' => 'User register successfully.',
	            'data'    => $success,
	        ];


	        return response()->json($response, 200);
    	}
    	catch (\Illuminate\Database\QueryException $e) {
		    if ($e->errorInfo[1] === 1062) {
		        return response()->json(['error' => 'Email address already exists.'], 422);
		    } else {
		        return response()->json(['error' => 'Database error.'], 500);
		    }
		}
    }


    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')->plainTextToken; 
            $success['name'] =  $user->name;
   
   			$response = [
	            'success' => true,
	            'message' => 'User login successfully.',
	            'data'    => $success,
        	];

        	return response()->json($response, 200);
        } 
        else{ 
            return response()->json(['message' => 'Unauthorized'], 401);
        } 
    }
}

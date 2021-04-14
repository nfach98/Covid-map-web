<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\models\User;
use Illuminate\Support\Facades\Auth;
use Validator;

class UserController extends Controller
{
    public $successStatus = 200;

    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json(['error'=>'Unauthorized'], 401);
        }

        else{
            $query = User::select('name', 'username', 'api_token AS token')
            ->where([
                'username' => $request->username, 
                'password' => $request->password
            ]);
            $user = $query->first();

            if($user) {
                Auth::login($user);
                $query->update([
                    'api_token' => $user->createToken('nApp')->accessToken
                ]);

                $query = User::select('name', 'username', 'api_token AS token')
                ->where([
                    'username' => $request->username, 
                    'password' => $request->password
                ]); 
                return $query->first();
            } else {
                return response()->json(['error'=>'Unauthorized'], 401);
            }
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'username' => 'required',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        $input = $request->all();
        $user = User::create($input);
        $user->api_token = $user->createToken('nApp')->accessToken;
        $user->save();
        $success['token'] = $user->api_token;
        $success['name'] =  $user->name;

        return response()->json(['success'=>$success], $this->successStatus);
    }

    public function logout(Request $request)
    {
        if($request->bearerToken()){
            $query = User::select('name', 'username', 'api_token AS token')
            ->where('api_token', $request->bearerToken());
            $user = $query->first();

            if($user) {
                $query->update([
                    'api_token' => null
                ]);
                return response()->json(['logout' => 'success'], $this->successStatus);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
        else{
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        
    }

    /*public function details()
    {
        $user = Auth::user();
        return response()->json(['success' => $user], $this->successStatus);
    }*/
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public $module_title;

    public $module_name;

    public $module_path;

    public $module_icon;

    public $module_model;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Users';

        // module name
        $this->module_name = 'users';

        // directory path of the module
        $this->module_path = 'api';

        // module icon
        $this->module_icon = 'fa-solid fa-user-group';

        // module model name, path
        $this->module_model = "App\Models\User";
    }

    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'List';

        try {
            $users = $module_model::select([
                'id',
                'name',
                'username',
                'email',
                'email_verified_at',
                'status',
                'created_at',
                'updated_at',
            ])->latest()->paginate(10);

            return response()->json([
                'status' => 'success',
                'data' => $users,
                'message' => "$module_title retrieved successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        logUserAccess($module_title.' '.$module_action);
    }

    /**
     * Store a newly created user in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Store';

        // Validate request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:3|max:191',
            'last_name' => 'required|min:3|max:191',
            'email' => 'required|email:rfc,dns|regex:/(.+)@(.+)\.(.+)/i|max:191|unique:users',
            'password' => 'required|min:6',
            'roles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data_array = Arr::except($request->all(), ['_token', 'roles', 'password_confirmation']);
            $data_array['name'] = $request->first_name.' '.$request->last_name;
            $data_array['password'] = Hash::make($request->password);

            // Set email verification timestamp if applicable
            if ($request->has('confirmed') && $request->confirmed === 1) {
                $data_array = Arr::add($data_array, 'email_verified_at', now());
            }

            // Create a User
            $user = User::create($data_array);

            // Set Username
            $id = $user->id;
            $username = config('app.initial_username') + $id;
            $user->username = $username;
            $user->save();

            // Sync Roles if present
            if ($request->has('roles') && !empty($request->roles)) {
                $user->syncRoles($request->roles);
            }

            return response()->json([
                'status' => 'success',
                'data' => $user,
                'message' => "New $module_name_singular created successfully",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        logUserAccess($module_title.' '.$module_action);
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Show';

        try {
            $user = $module_model::select([
                'id',
                'name',
                'username',
                'email',
                'email_verified_at',
                'status',
                'created_at',
                'updated_at',
            ])->findOrFail($id);

            // Get user roles
            $user->roles = $user->roles()->select('id', 'name')->get();

            return response()->json([
                'status' => 'success',
                'data' => $user,
                'message' => "$module_name_singular retrieved successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }

        logUserAccess($module_title.' '.$module_action.' '.$id);
    }

    /**
     * Update the specified user in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Update';

        // Validate request
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|min:3|max:191',
            'last_name' => 'sometimes|required|min:3|max:191',
            'email' => 'sometimes|required|email:rfc,dns|regex:/(.+)@(.+)\.(.+)/i|max:191|unique:users,email,'.$id,
            'password' => 'sometimes|required|min:6',
            'roles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($id);

            // Super admin can't be modified
            if ($id === 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Super Admin user cannot be modified',
                ], 403);
            }

            $data_array = Arr::except($request->all(), ['_token', 'roles', 'password_confirmation']);
            
            // Set full name if both first and last name are provided
            if ($request->has('first_name') && $request->has('last_name')) {
                $data_array['name'] = $request->first_name.' '.$request->last_name;
            }

            // Hash password if it's being updated
            if ($request->has('password')) {
                $data_array['password'] = Hash::make($request->password);
            }

            $user->update($data_array);

            // Sync Roles if present
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            return response()->json([
                'status' => 'success',
                'data' => $user,
                'message' => "$module_name_singular updated successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }

        logUserAccess($module_title.' '.$module_action.' '.$id);
    }

    /**
     * Remove the specified user from the database.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Destroy';

        try {
            // Can't delete self or super admin
            if (auth()->user()->id == $id || $id == 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot delete this user',
                ], 403);
            }

            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => "$module_name_singular deleted successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }

        logUserAccess($module_title.' '.$module_action.' '.$id);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    public function index($id) {
        try {
            $module = Module::find($id);
            return response()->json($module, 200);
        }
        catch (\Exception $exception) {
            return response()->json([
                "message" => $exception->getMessage()
            ], 500);
        }
    }
    public function allModules() {
        $modules = Module::all();
        return response()->json($modules, 200);
    }
    public function selfModule() {
        try {
            $user = Auth::user();
            if ($user) {
                $modules = User::find($user->id)->modules;
                if ($modules) {
                    return response()->json($modules, 200);
                }
            }
        }
        catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }
    public function create(Request $request) {
        $this->validate(
            $request,
            [
                'name' => 'required | string',
                'public' => 'boolean',
                'max_score' => 'integer',
                'description' => 'string'
            ],
            [
                'name.required' => 'Module name can not be blank !',
            ]
        );
        $user = Auth::user();
        if ($user) {
            $current_time = getCurrentTime();
            $module_data = [
                'name' => htmlspecialchars($request->name),
                'max_score' => (int) ($request->max_score),
                'public' => (int) ($request->public),
                'user_id' => $user->id,
                'description' => htmlspecialchars($request->description),
                'created_at' => $current_time,
                'updated_at' => $current_time,
            ];
            try {
                $module = Module::create($module_data);
                return response()->json($module, 200);
            }
            catch (\Exception $exception) {
                return response()->json([
                    'message' => $exception->getMessage()
                ], 500);
            }
        }
    }
    public function update($id, Request $request) {
        $query = $request->query();
        $user = Auth::user();
//        $user = new User($user);
        $module = Module::find($id);
        $user = User::find($user->id);
//        $this->authorize('update', $module);
        if ($user->can('update', $module)) {
            $current_time = getCurrentTime();
//            $module = Module::where('id', $id)->where('user_id', $user->id)->first();
            $module = Module::where('id', $id)->first();
            if ($module) {
                $module_update_data = [
                    'name' => isset($query['name']) ? htmlspecialchars($query['name']) : $module->name,
                    'public' => isset($query['public']) ? (int) $query['public'] : $module->public,
                    'updated_at' => $current_time
                ];
                try {
                    Module::find($id)
                        ->update($module_update_data);
                    return response()->json(Module::find($id), 200);
                }
                catch (\Exception $exception) {
                    return response()->json([
                        "message" => $exception->getMessage()
                    ], 500);
                }
            }
            else {
                return response()->json([
                    'message' => 'Module not found'
                ], 404);
            }
        }
    }
    public function delete($id) {
        $user = Auth::user();
        $user_id = Module::find($id)->user->id;
        if ($user_id == $user->id) {
            try {
                Module::find($id)->delete();
                return $this->allModules();
//                return response()->json([
//                    'message' => 'Deleted success'
//                ], 200);
            }
            catch (\Exception $exception) {
                return response()->json([
                    "message" => $exception->getMessage()
                ], 500);
            }
        }
    }
    public function modulesInFolderService($folder_id) {
        $user = Auth::user();
        if ($folder_id) {
            $folder = Folder::find($folder_id);
            $folder_user_id = $folder->user->id;
            if ($folder_user_id == $user->id) {
                try {
                    $modules = DB::table('module')
                        ->join('folder_has_module', 'module_id', '=', 'module.id')
                        ->where('folder_has_module.folder_id', '=', $folder_id)
                        ->select('module.*')
                        ->get();
                    return response()->json($modules, 200);
                }
                catch (\Exception $exception) {
                    return response()->json([
                        'message' => $exception->getMessage()
                    ], 500);
                }
            }
            else {
                return response()->json([
                    'message' => 'Get modules by folder failed'
                ], 500);
            }
        }
    }
}

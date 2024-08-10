<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleCreateRequest;
use App\Http\Requests\Role\RoleUpdateRequest;
use App\Http\Resources\Role\RoleCollection;
use App\Http\Resources\Role\RoleResource;
use App\Http\Resources\Role\RoleResourceById;
use App\Models\Roles;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['isOwner', 'auth:api'])->only('store', 'update', 'destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 1);
        $roles = Roles::orderBy('created_at', 'desc')->paginate(6, ['*'], 'page', $perPage);
        $roleResponse = RoleResource::collection($roles);
        return (new RoleCollection($roleResponse))->response()->setStatusCode(201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleCreateRequest $request)
    {
        $validateData = $request->validated();
        $role = new Roles($validateData);
        $role->save();
        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Roles::find($id);
        if (!$role) {
            return response()->json([
                "message" => "Role with ID $id not found"
            ], 404);
        }
        return (new RoleResourceById($role))->response()->setStatusCode(201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleUpdateRequest $request, string $id)
    {
        $roleId = Roles::find($id);
        if (!$roleId) {
            return response()->json([
                "message" => "Role with ID $id not found"
            ], 404);
        }
        $validateData = $request->validated();
        $roleId->update($validateData);
        return (new RoleResource($roleId))->response()->setStatusCode(201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Roles::find($id);
        if (!$role) {
            return response()->json([
                "message" => "Role with ID $id not found"
            ], 404);
        }
        $role->delete();
        return response()->json([
            "message" => "Role deleted successfully"
        ], 201);
    }
}

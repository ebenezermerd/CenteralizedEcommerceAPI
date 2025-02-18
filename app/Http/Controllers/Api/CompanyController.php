<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\CompanyRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\MegaCompanyAddress;
use App\Http\Resources\MegaCompanyAddressResource;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with('owner');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', Company::PAGINATION_LIMIT);

        // If user is not admin, show only their company
        if (!Auth::user()->hasRole('admin')) {
            $query->where('owner_id', Auth::id());
        }

        // Apply filters from request
        foreach ($request->all() as $key => $value) {
            if (!in_array($key, ['page', 'limit']) && $value !== null && $value !== 'all') {
                if ($key === 'name') {
                    $query->where('name', 'like', "%{$value}%");
                } elseif ($key === 'status') {
                    $query->where('status', $value);
                }
            }
        }

        $companies = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'items' => $companies->items() ?? [],
            'total' => $companies->total() ?? 0
        ], 200);
    }

    public function store(CompanyRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = $data['owner_id'] ?? Auth::id();
        $data['status'] = $data['status'] ?? Company::STATUS_PENDING;

        $company = Company::create($data);
        $company->load('owner');

        return response()->json($company, 201);
    }

    public function show(Company $company)
    {
        // Check if user has permission to view
        if (!Auth::user()->hasAnyRole(['admin', 'supplier']) && $company->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company->load('owner');
        return response()->json($company);
    }

    public function vendorCompany(string $id)
    {
        $user = User::findOrFail($id);
        $company = $user->company()->with('owner')->first();

        if (!$company) {
            return response()->json(['message' => "No company found for user ID: {$id}"], 404);
        }

        // Check if user has permission to view
        if (!Auth::user()->hasAnyRole(['admin', 'supplier']) && $company->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($company);
    }


    public function update(CompanyRequest $request, Company $company)
    {
        // Check if user has permission to update
        if (!Auth::user()->hasRole('admin') && $company->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();
        $company->update($data);
        $company->load('owner');

        return response()->json($company);
    }

    public function destroy(Company $company)
    {
        // Only admin can delete companies
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company->delete();

        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, Company $company)
    {
        // Only admin can update company status
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:pending,active,inactive,blocked'
        ]);

        $company->update([
            'status' => $request->status
        ]);

        $company->load('owner');

        return response()->json([
            'success' => true,
            'message' => 'Company status updated successfully',
            'company' => $company
        ]);
    }

    public function getMegaCompanyAddresses()
    {
        $addresses = MegaCompanyAddress::all();
        return response()->json(MegaCompanyAddressResource::collection($addresses), 200);
    }

    public function getMegaCompanyAddress(string $id)
    {
        $address = MegaCompanyAddress::findOrFail($id);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }
        return response()->json(new MegaCompanyAddressResource($address), 200);
    }

    public function addMegaCompanyAddress(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'fullAddress' => 'required|string',
            'phoneNumber' => 'required|string',
            'email' => 'required|email',
            'isDefault' => 'required|boolean',
            'type' => 'required|string',
        ]);

        $data['full_address'] = $data['fullAddress'];
        $data['phone_number'] = $data['phoneNumber'];
        $data['is_default'] = $data['isDefault'];

        $address = MegaCompanyAddress::create($data);
        $address->refresh();
        return response()->json(new MegaCompanyAddressResource($address), 201);
    }

    public function updateMegaCompanyAddress(Request $request, string $id)
    {
        $address = MegaCompanyAddress::findOrFail($id);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'fullAddress' => 'required|string',
            'phoneNumber' => 'required|string',
            'email' => 'required|email',
            'isDefault' => 'required|boolean',
            'type' => 'required|string',
        ]);

        $data['full_address'] = $data['fullAddress'];
        $data['phone_number'] = $data['phoneNumber'];
        $data['is_default'] = $data['isDefault'];

        $address->update($data);
        $address->refresh();
        return response()->json(new MegaCompanyAddressResource($address), 200);
    }

    public function deleteMegaCompanyAddress(string $id)
    {
        $address = MegaCompanyAddress::findOrFail($id);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }
        $address->delete();
        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ], 204);
    }
}

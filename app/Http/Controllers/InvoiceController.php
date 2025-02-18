<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\InvoiceCollection;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('items', 'billFrom', 'billTo')->paginate(10);
        \Log::info('Invoices', ['invoices' => $invoices->toArray()]);

        if ($invoices->isEmpty()) {
            return response()->json([
                'message' => 'No invoices found',
                'invoices' => []
            ], 200);
        }

        return response()->json([
            'invoices' => InvoiceResource::collection($invoices) ?? [],
            'pagination' => [
                'total' => $invoices->total() ?? 0,
                'per_page' => $invoices->perPage() ?? 10,
                'current_page' => $invoices->currentPage() ?? 1,
                'last_page' => $invoices->lastPage() ?? 1
            ]
        ], 200);
    }

    public function show(Request $request, String $id)
    {
        \Log::info('Showing invoice', ['id' => $id]);

        // No need to validate the id from request since it comes from route parameter
        // Laravel will automatically convert string id to integer when querying
        $invoice = Invoice::with('items', 'billFrom', 'billTo')->findOrFail($id);
        \Log::info('Invoice', ['invoice' => $invoice->toArray()]);

        return response()->json(new InvoiceResource($invoice) ?? [], 200);
    }

/**
 * @group Invoices
 *
 * Retrieve a specific user's invoices.
 *
 * This endpoint retrieves all invoices associated with the given user ID.
 *
 * @param string $userId The ID of the user whose invoices are to be retrieved.
 *
 * @response 200 {
 *   "data": {
 *       "id": "string",
 *       "invoiceNumber": "string",
 *       "sent": "boolean",
 *       "taxes": "float",
 *       "status": "string",
 *       "subtotal": "float",
 *       "discount": "float",
 *       "shipping": "float",
 *       "totalAmount": "float",
 *       "createdAt": "string",
 *       "dueDate": "string",
 *       "items": [
 *           {
 *               "id": "string",
 *               "title": "string",
 *               "price": "float",
 *               "total": "float",
 *               "service": "string",
 *               "quantity": "int",
 *               "description": "string"
 *           }
 *       ],
 *       "invoiceFrom": {
 *           "name": "string",
 *           "fullAddress": "string",
 *           "phoneNumber": "string"
 *       },
 *       "invoiceTo": {
 *           "name": "string",
 *           "fullAddress": "string",
 *           "phoneNumber": "string"
 *       }
 *   }
 * }
 *
 * @response 404 {
 *   "message": "No invoices found for this user."
 * }
 */
public function showByUserId(Request $request, String $userId)
{
    $invoices = Invoice::where('user_id', $userId)->get();
    return response()->json(new InvoiceResource($invoices) ?? [], 200);
}

/**
 * @group Invoices
 *
 * Retrieve all invoices for a specific user with detailed information.
 *
 * This endpoint retrieves all invoices associated with the given user ID, including
 * related items, billing information, and pagination.
 *
 * @param string $userId The ID of the user whose invoices are to be retrieved.
 *
 * @response 200 {
 *   "invoices": [
 *       {
 *           "id": "string",
 *           "invoiceNumber": "string",
 *           "sent": "boolean",
 *           "taxes": "float",
 *           "status": "string",
 *           "subtotal": "float",
 *           "discount": "float",
 *           "shipping": "float",
 *           "totalAmount": "float",
 *           "createdAt": "string",
 *           "dueDate": "string",
 *           "items": [
 *               {
 *                   "id": "string",
 *                   "title": "string",
 *                   "price": "float",
 *                   "total": "float",
 *                   "service": "string",
 *                   "quantity": "int",
 *                   "description": "string"
 *               }
 *           ],
 *           "invoiceFrom": {
 *               "name": "string",
 *               "fullAddress": "string",
 *               "phoneNumber": "string"
 *           },
 *           "invoiceTo": {
 *               "name": "string",
 *               "fullAddress": "string",
 *               "phoneNumber": "string"
 *           }
 *       }
 *   ],
 *   "pagination": {
 *       "total": "int",
 *       "perPage": "int",
 *       "currentPage": "int",
 *       "lastPage": "int"
 *   }
 * }
 *
 * @response 404 {
 *   "message": "No invoices found for this user."
 * }
 */
public function userInvoices(Request $request, String $userId)
{
    $invoices = Invoice::whereHas('user', function($query) use ($userId) {
        $query->where('id', $userId);
    })->with('items', 'billFrom', 'billTo')->get();

    return response()->json(new InvoiceCollection($invoices) ?? [], 200);
}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $invoice = Invoice::create($validated);
        return response()->json($invoice, 201);
    }


    public function edit(string $id)
    {
        // This method can be used to show a form for editing an existing invoice
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'invoiceTo' => 'required|array',
            'invoiceTo.name' => 'required|string',
            'invoiceTo.fullAddress' => 'required|string',
            'invoiceTo.phoneNumber' => 'required|string',
            'items' => 'required|array',
            'items.*.title' => 'required|string',
            'items.*.service' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'taxes' => 'required|numeric|min:0',
            'status' => 'required|string',
            'discount' => 'required|numeric|min:0',
            'shipping' => 'required|numeric|min:0',
            'totalAmount' => 'required|numeric|min:0',
            'dueDate' => 'required|date',
            'createdAt' => 'required|date'
        ]);

        \DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($id);

            // Update invoice main details with correct field names
            $invoice->update([
                'status' => $validated['status'],
                'taxes' => $validated['taxes'],
                'discount' => $validated['discount'],
                'shipping' => $validated['shipping'],
                'total_amount' => $validated['totalAmount'],
                'due_date' => $validated['dueDate'],
                'create_date' => $validated['createdAt']
            ]);

            // Update invoice items
            $invoice->items()->delete();
            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'title' => $item['title'],
                    'service' => $item['service'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                    'description' => $item['description'] ?? null,
                ]);
            }

            // Update billing information with correct field names
            $invoice->billTo()->update([
                'name' => $validated['invoiceTo']['name'],
                'full_address' => $validated['invoiceTo']['fullAddress'],
                'phone_number' => $validated['invoiceTo']['phoneNumber'],
            ]);

            \DB::commit();

            // Load relationships and return updated invoice
            $invoice->load('items', 'billTo', 'billFrom');
            return response()->json(new InvoiceResource($invoice), 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Invoice update failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update invoice', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully']);
    }
}

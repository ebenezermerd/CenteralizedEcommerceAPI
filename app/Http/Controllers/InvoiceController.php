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

    public function showByUserId(Request $request, String $userId)
    {
        $invoices = Invoice::where('user_id', $userId)->get();
        return response()->json(new InvoiceResource($invoices) ?? [], 200);
    }

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
            'amount' => 'required|numeric',
            'status' => 'required|string',
        ]);

        $invoice = Invoice::findOrFail($id);
        $invoice->update($validated);
        return response()->json($invoice);
    }

    public function destroy(string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully']);
    }
}

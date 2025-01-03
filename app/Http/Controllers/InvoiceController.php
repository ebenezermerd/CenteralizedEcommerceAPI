<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $invoices = Invoice::with('order')->paginate(10);
        return response()->json($invoices);
    }

    public function create()
    {
        // This method can be used to show a form for creating a new invoice
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

    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with('order')->findOrFail($id);
        return response()->json($invoice);
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

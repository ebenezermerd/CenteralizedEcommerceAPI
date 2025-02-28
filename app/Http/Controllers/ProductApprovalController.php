<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Mail\ProductApprovalStatus;
use Illuminate\Support\Facades\Mail;


class ProductApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = Product::with(['reviews', 'category', 'brand', 'images', 'vendor']);

        // Get counts for each status
        $counts = [
            'pending' => Product::where('publish_status', 'pending')->count(),
            'approved' => Product::where('publish_status',

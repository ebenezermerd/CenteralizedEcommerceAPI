<?php

namespace App\Http\Controllers;

use App\Models\SupportInquiry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupportInquiryConfirmation;
use App\Mail\NewSupportInquiryNotification;
use App\Mail\SupportInquiryResponse;

class SupportInquiryController extends Controller
{
    /**
     * Display a listing of support inquiries.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $query = SupportInquiry::query()->with('assignedTo');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $inquiries = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $inquiries
        ]);
    }
    
    /**
     * Store a newly created support inquiry.
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'order_number' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB max
        ]);
        
        $attachmentPath = null;
        
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support-attachments', 'public');
        }
        
        $inquiry = SupportInquiry::create([
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'order_number' => $request->order_number,
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);
        
        // Send confirmation email to customer
        try {
            Mail::to($request->email)->send(new SupportInquiryConfirmation($inquiry));
            
            // Notify admin about new inquiry
            $admins = User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->get();
            
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new NewSupportInquiryNotification($inquiry));
            }
        } catch (\Exception $e) {
            // Log email sending errors but don't fail the request
            \Log::error('Failed to send support inquiry emails: ' . $e->getMessage());
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Your inquiry has been submitted successfully. We will get back to you soon.',
            'data' => $inquiry
        ], 201);
    }
    
    /**
     * Display the specified support inquiry.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $inquiry = SupportInquiry::with('assignedTo')->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $inquiry
        ]);
    }
    
    /**
     * Update the status of a support inquiry.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved',
            'assigned_to' => 'nullable|exists:users,id'
        ]);
        
        $inquiry = SupportInquiry::findOrFail($id);
        $inquiry->status = $request->status;
        
        if ($request->has('assigned_to')) {
            $inquiry->assigned_to = $request->assigned_to;
        }
        
        $inquiry->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Support inquiry status updated successfully',
            'data' => $inquiry
        ]);
    }
    
    /**
     * Respond to a support inquiry.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function respond(Request $request, $id)
    {
        $request->validate([
            'response' => 'required|string',
        ]);
        
        $inquiry = SupportInquiry::findOrFail($id);
        
        // Update inquiry status to 'resolved' if it's not already
        if ($inquiry->status !== 'resolved') {
            $inquiry->status = 'resolved';
            $inquiry->save();
        }
        
        // Send response email to customer
        try {
            Mail::to($inquiry->email)->send(new SupportInquiryResponse($inquiry, $request->response));
        } catch (\Exception $e) {
            // Log email sending errors but don't fail the request
            \Log::error('Failed to send support inquiry response email: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send response email, but status updated',
            ], 500);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Response sent successfully',
            'data' => $inquiry
        ]);
    }
} 
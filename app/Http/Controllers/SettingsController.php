<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all settings grouped by group
        $settings = Setting::all()->groupBy('group')->map(function ($group) {
            return [
                'group' => $group[0]->group,
                'settings' => $group
            ];
        })->values();

        return response()->json($settings);
    }

    /**
     * Get settings by group.
     */
    public function getByGroup(string $group)
    {
        $settings = Setting::where('group', $group)->get();
        return response()->json($settings);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $key)
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }
        
        return response()->json($setting);
    }

    /**
     * Update multiple settings.
     */
    public function updateMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
            'settings.*.group' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $settings = $request->input('settings');
        
        foreach ($settings as $settingData) {
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => $settingData['value'] ?? null,
                    'group' => $settingData['group'],
                    'description' => $settingData['description'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'nullable|string',
            'group' => 'required|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $request->input('value'),
                'group' => $request->input('group'),
                'description' => $request->input('description'),
            ]
        );

        return response()->json($setting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $key)
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }
        
        $setting->delete();
        
        return response()->json(['message' => 'Setting deleted successfully']);
    }
}

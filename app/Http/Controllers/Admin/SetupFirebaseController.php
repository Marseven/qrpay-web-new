<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BasicSettings;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SetupFirebaseController extends Controller
{
    public function configuration() {
        $page_title = "Firebase Api";
        $firebase_config = BasicSettings::first()->firebase_config;
        return view('admin.sections.setup-firebase.config',compact(
            'page_title',
            'firebase_config',
        ));
    }
    public function update(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'name'        => 'required|string|in:firebase|max:200',
            'api_key'          => 'required|string|max:100',
            'auth_domain'      => 'required|string|max:100',
            'project_id'      => 'required|string|max:100',
            'storage_bucket'      => 'required|string|max:100',
            'messaging_senderId'      => 'required|string|max:100',
            'app_id'      => 'required|string|max:100',
            'measurement_id'      => 'required|string|max:100',
        ]);

        $validated = $validator->validate();

        $basic_settings = BasicSettings::first();
        if(!$basic_settings) {
            return back()->with(['error' => ['Basic settings not found!']]);
        }

        // Make object of firebase data
        $data = [
            'name'              => $validated['name'] ?? false,
            'api_key'           => $validated['api_key'] ?? false,
            'auth_domain'       => $validated['auth_domain'] ?? false,
            'project_id'        => $validated['project_id'] ?? false,
            'storage_bucket'     => $validated['storage_bucket'] ?? false,
            'messaging_senderId'          => $validated['messaging_senderId'] ?? false,
            'app_id'              => $validated['app_id'] ?? false,
            'measurement_id'              => $validated['measurement_id'] ?? false
        ];

        try{
            $basic_settings->update([
                'firebase_config'       => $data,
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }
        return back()->with(['success' => ['Information updated successfully!']]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VirtualCardApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VirtualCardController extends Controller
{
    public function cardApi()
    {
        $page_title = "Setup Virtual Card Api";
        $api = VirtualCardApi::first();
        return view('admin.sections.virtual-card.api',compact(
            'page_title',
            'api',
        ));
    }
    public function cardApiUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'secret_key'        => 'required|string',
            'secret_hash'   => 'required|string',
            'url'         => 'required|string|url',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $api = VirtualCardApi::first();
        $api->secret_key = $request->secret_key;
        $api->secret_hash = $request->secret_hash;
        $api->url = $request->url;
        $api->save();

        file_put_contents(app()->environmentFilePath(), str_replace(
            "SECRET_KEY" . '=' . env("SECRET_KEY"),
            "SECRET_KEY" . '=' . $api->secret_key,
            file_get_contents(app()->environmentFilePath())
        ));

        file_put_contents(app()->environmentFilePath(), str_replace(
            "SECRET_HASH" . '=' . env("SECRET_HASH"),
            "SECRET_HASH" . '=' . $api->secret_hash,
            file_get_contents(app()->environmentFilePath())
        ));

        return back()->with(['success' => ['Card API has been updated.']]);
    }
}

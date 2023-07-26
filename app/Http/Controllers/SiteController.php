<?php

namespace App\Http\Controllers;

use App\Models\Admin\AppSettings;
use App\Models\Admin\BasicSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Models\Admin\Language;
use App\Models\Admin\SetupPage;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Contact;
use App\Models\Newsletter;
use Exception;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function home(Request $request){
        $basic_settings = BasicSettings::first();
        $page_title = $basic_settings->site_title??"Home";
        $app_urls = AppSettings::first();
        return view('frontend.index',compact('page_title','app_urls'));
    }

    public function about(){
        $page_title = "About";
        return view('frontend.about',compact('page_title'));
    }
    public function faq(){
        $page_title = "Faq";
        return view('frontend.faq',compact('page_title'));
    }
    public function service(){
        $page_title = " Service";
        return view('frontend.service',compact('page_title'));
    }
    public function blog(){
        $page_title = "Blog";
        $categories = BlogCategory::active()->latest()->get();
        $blogs = Blog::active()->orderBy('id',"DESC")->paginate(8);
        $recentPost = Blog::active()->latest()->limit(3)->get();
        return view('frontend.blog',compact('page_title','blogs','recentPost','categories'));
    }
    public function blogDetails($id,$slug){
        $page_title = "Blog Details";
        $categories = BlogCategory::active()->latest()->get();
        $blog = Blog::where('id',$id)->where('slug',$slug)->first();
        $recentPost = Blog::active()->where('id',"!=",$id)->latest()->limit(3)->get();
        return view('frontend.blogDetails',compact('page_title','blog','recentPost','categories'));
    }
    public function blogByCategory($id,$slug){
        $categories = BlogCategory::active()->latest()->get();
        $category = BlogCategory::findOrfail($id);
        $page_title = 'Category -'.' '. $category->name;
        $blogs = Blog::active()->where('category_id',$category->id)->latest()->paginate(8);
        $recentPost = Blog::active()->latest()->limit(3)->get();
        return view('frontend.blogByCategory',compact('page_title','blogs','category','categories','recentPost'));
    }
    public function merchant(){
        $page_title = "Merchant";
        return view('frontend.merchant',compact('page_title'));
    }
    public function contact(){
        $page_title = "Contact Us";
        return view('frontend.contact',compact('page_title'));
    }
    public function contactStore(Request $request){

        $validator = Validator::make($request->all(),[
            'name'    => 'required|string',
            'email'   => 'required|email',
            'mobile'  => 'required',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validate();
        try {
            Contact::create($validated);
        } catch (\Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }

        return back()->with(['success' => ['Your message submited!']]);

    }
    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        session()->put('local', $lang);
        return redirect()->back();
    }
    public function usefulPage($slug){
        $defualt = selectedLang();
        $page = SetupPage::where('slug', $slug)->where('status', 1)->first();
        if(empty($page)){
            abort(404);
        }
        $page_title = $page->title->language->$defualt->title;

        return view('frontend.policy_pages',compact('page_title','page','defualt'));
    }
    public function newsletterSubmit(Request $request){
        $validator = Validator::make($request->all(),[
            'fullname' => 'required|string|max:100',
            'email' => 'required|email|unique:newsletters',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $in['fullname'] = $request->fullname;
        $in['email'] = $request->email;
        try{
            Newsletter::create($in);
            return redirect()->back()->with(['success' => ['Your newsletter information submisstion successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }


}

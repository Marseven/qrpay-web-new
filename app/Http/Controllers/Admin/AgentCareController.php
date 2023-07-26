<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Constants\GlobalConst;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\Response;
use App\Models\AgentLoginLog;
use App\Models\AgentMailLog;
use Exception;
use Illuminate\Support\Arr;
use App\Notifications\User\SendMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class AgentCareController extends Controller
{
    public function index()
    {
        $page_title = "All Agents";
        $agents = Agent::orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.agent-care.index', compact(
            'page_title',
            'agents'
        ));
    }
    public function active()
    {
        $page_title = "Active Agent";
        $agents = Agent::active()->orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.agent-care.index', compact(
            'page_title',
            'agents'
        ));
    }
    public function banned()
    {
        $page_title = "Banned Agents";
        $agents = Agent::banned()->orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.agent-care.index', compact(
            'page_title',
            'agents',
        ));
    }
    public function emailUnverified()
    {
        $page_title = "Email Unverified Agents";
        $agents = Agent::active()->orderBy('id', 'desc')->emailUnverified()->paginate(12);
        return view('admin.sections.agent-care.index', compact(
            'page_title',
            'agents'
        ));
    }
    public function SmsUnverified()
    {
        $page_title = "SMS Unverified Agents";
        return view('admin.sections.agent-care.index', compact(
            'page_title',
        ));
    }
    public function KycUnverified()
    {
        $page_title = "KYC Unverified Agents";
        $agents = Agent::kycUnverified()->orderBy('id', 'desc')->paginate(8);
        return view('admin.sections.agent-care.index', compact(
            'page_title',
            'agents'
        ));
    }
    public function emailAllUsers()
    {
        $page_title = "Email To Agents";
        return view('admin.sections.agent-care.email-to-users', compact(
            'page_title',
        ));
    }
    public function sendMailUsers(Request $request) {
        $request->validate([
            'user_type'     => "required|string|max:30",
            'subject'       => "required|string|max:250",
            'message'       => "required|string|max:2000",
        ]);

        $users = [];
        switch($request->user_type) {
            case "active";
                $users = Agent::active()->get();
                break;
            case "all";
                $users = Agent::get();
                break;
            case "email_verified";
                $users = Agent::emailVerified()->get();
                break;
            case "kyc_verified";
                $users = Agent::kycVerified()->get();
                break;
            case "banned";
                $users = Agent::banned()->get();
                break;
        }

        try{
            Notification::send($users,new SendMail((object) $request->all()));
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }

        return back()->with(['success' => ['Email successfully sended']]);

    }
    public function userDetails($username)
    {
        $page_title = "Agent Details";
        $user = Agent::where('username', $username)->first();
        if(!$user) return back()->with(['error' => ['Opps! Agent not exists']]);
        return view('admin.sections.agent-care.details', compact(
            'page_title',
            'user',
        ));
    }
    public function userDetailsUpdate(Request $request, $username)
    {
        $request->merge(['username' => $username]);
        $validator = Validator::make($request->all(),[
            'username'              => "required|exists:agents,username",
            'firstname'             => "required|string|max:60",
            'lastname'              => "required|string|max:60",
            'mobile_code'           => "required|string|max:10",
            'mobile'                => "required|string|max:20",
            'address'               => "nullable|string|max:250",
            'country'               => "nullable|string|max:50",
            'state'                 => "nullable|string|max:50",
            'city'                  => "nullable|string|max:50",
            'zip_code'              => "nullable|numeric|max_digits:8",
            'email_verified'        => 'required|boolean',
            'two_factor_verified'   => 'required|boolean',
            'kyc_verified'          => 'required|boolean',
            'status'                => 'required|boolean',
        ]);
        $validated = $validator->validate();
        $validated['address']  = [
            'country'       => $validated['country'] ?? "",
            'state'         => $validated['state'] ?? "",
            'city'          => $validated['city'] ?? "",
            'zip'           => $validated['zip_code'] ?? "",
            'address'       => $validated['address'] ?? "",
        ];
        $validated['mobile_code']       = remove_speacial_char($validated['mobile_code']);
        $validated['mobile']            = remove_speacial_char($validated['mobile']);
        $validated['full_mobile']       = $validated['mobile_code'] . $validated['mobile'];

        $user = Agent::where('username', $username)->first();

        if(!$user) return back()->with(['error' => ['Opps! Agent not exists']]);

        try {
            $user->update($validated);
        } catch (Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }

        return back()->with(['success' => ['Profile Information Updated Successfully!']]);
    }
    public function kycDetails($username) {
        $user = Agent::where("username",$username)->first();
        if(!$user) return back()->with(['error' => ['Opps! agent doesn\'t exists']]);

        $page_title = "KYC Profile";
        return view('admin.sections.agent-care.kyc-details',compact("page_title","user"));
    }

    public function kycApprove(Request $request, $username) {
        $request->merge(['username' => $username]);
        $request->validate([
            'target'        => "required|exists:agents,username",
            'username'      => "required_without:target|exists:agents,username",
        ]);
        $user = Agent::where('username',$request->target)->orWhere('username',$request->username)->first();
        if($user->kyc_verified == GlobalConst::VERIFIED) return back()->with(['warning' => ['Agent already KYC verified']]);
        if($user->kyc == null) return back()->with(['error' => ['Agent KYC information not found']]);

        try{
            $user->update([
                'kyc_verified'  => GlobalConst::APPROVED,
            ]);
        }catch(Exception $e) {
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }
        return back()->with(['success' => ['Agent KYC successfully approved']]);
    }

    public function kycReject(Request $request, $username) {
        $request->validate([
            'target'        => "required|exists:agents,username",
            'reason'        => "required|string|max:500"
        ]);
        $user = Agent::where("username",$request->target)->first();
        if(!$user) return back()->with(['error' => ['Agent doesn\'t exists']]);
        if($user->kyc == null) return back()->with(['error' => ['Agent KYC information not found']]);

        try{
            $user->update([
                'kyc_verified'  => GlobalConst::REJECTED,
            ]);
            $user->kyc->update([
                'reject_reason' => $request->reason,
            ]);
        }catch(Exception $e) {
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            $user->kyc->update([
                'reject_reason' => null,
            ]);

            return back()->with(['error' => ['Something went worng! Please try again']]);
        }

        return back()->with(['success' => ['Agent KYC information is rejected']]);
    }

    public function search(Request $request) {
        $validator = Validator::make($request->all(),[
            'text'  => 'required|string',
        ]);

        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }

        $validated = $validator->validate();
        $agents = Agent::search($validated['text'])->limit(10)->get();
        return view('admin.components.search.agent-search',compact(
            'agents',
        ));
    }
    public function sendMail(Request $request, $username)
    {
        $request->merge(['username' => $username]);
        $validator = Validator::make($request->all(),[
            'subject'       => 'required|string|max:200',
            'message'       => 'required|string|max:2000',
            'username'      => 'required|string|exists:agents,username',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with("modal","email-send");
        }
        $validated = $validator->validate();
        $user = Agent::where("username",$username)->first();

        $validated['agent_id'] = $user->id;
        $validated = Arr::except($validated,['username']);
        $validated['method']   = "SMTP";
        try{
            AgentMailLog::create($validated);
            $user->notify(new SendMail((object) $validated));
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }
        return back()->with(['success' => ['Mail successfully sended']]);
    }
    public function mailLogs($username) {
        $page_title = "Agent Email Logs";
        $user = Agent::where("username",$username)->first();
        if(!$user) return back()->with(['error' => ['Opps! Aget doesn\'t exists']]);
        $logs = AgentMailLog::where("agent_id",$user->id)->paginate(12);
        return view('admin.sections.agent-care.mail-logs',compact(
            'page_title',
            'logs',
        ));
    }
    public function loginLogs($username)
    {
        $page_title = "Login Logs";
        $user = Agent::where("username",$username)->first();
        if(!$user) return back()->with(['error' => ['Opps! Agent doesn\'t exists']]);
        $logs = AgentLoginLog::where('agent_id',$user->id)->paginate(12);
        return view('admin.sections.agent-care.login-logs', compact(
            'logs',
            'page_title',
        ));
    }
    public function loginAsMember(Request $request,$username) {
        return back()->with(['error' => ["Agent Panel Does Not Added Yet"]]);
        $request->merge(['username' => $username]);
        $request->validate([
            'target'            => 'required|string|exists:agents,username',
            'username'          => 'required_without:target|string|exists:agents',
        ]);

        try{
            $user = Agent::where("username",$request->username)->first();
            Auth::guard("agent")->login($user);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->intended(route('user.dashboard'));
    }
}

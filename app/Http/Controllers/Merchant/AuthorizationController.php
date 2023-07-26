<?php

namespace App\Http\Controllers\Merchant;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\SetupKyc;
use App\Models\Merchants\MerchantAuthorization;
use App\Notifications\User\Auth\SendAuthorizationCode;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthorizationController extends Controller
{
    use ControlDynamicInputFields;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showMailFrom($token)
    {
        $page_title = "Mail Authorization";
        return view('merchant.auth.authorize.verify-mail',compact("page_title","token"));
    }
    public function showSmsFromRegister($token)
    {
        $data = MerchantAuthorization::where('token',$token)->first();
        $page_title = "Email Verification";
        return view('merchant.auth.authorize.verify-email',compact("page_title","token","data"));
    }
    public function showSmsFrom()
    {
        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->status) {
                Auth::logout();
                return redirect()->route('merchant.login')->with(['error' => ['Your account disabled,please contact with admin!!']]);
            }elseif (!$user->sms_verified) {
                $page_title = 'SMS Authorization';
                $firbase_token = $user->firebase_token??"";
                $phone = "+".$user->full_mobile??'';
                return view('merchant.auth.authorize.verify-sms-auth',compact("page_title","firbase_token",'phone'));
            }else{
                return redirect()->route('merchant.dashboard');
            }

        }
        return redirect()->route('merchant.login');
    }


    /**
     * Verify authorizaation code.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function mailVerify(Request $request,$token)
    {
        $request->merge(['token' => $token]);
        $request->validate([
            'token'     => "required|string|exists:merchant_authorizations,token",
            // 'code'      => "required|numeric|exists:user_authorizations,code",
            'code'      => "required|array",
            'code.*'    => "required|numeric",
        ]);
        $code = $request->code;
        $code = implode("",$code);
        $otp_exp_sec = BasicSettingsProvider::get()->otp_exp_seconds ?? GlobalConst::DEFAULT_TOKEN_EXP_SEC;
        $auth_column = MerchantAuthorization::where("token",$request->token)->where("code",$code)->first();
        if(!$auth_column){
            return back()->with(['error' => ['Verification code does not match']]);
        }
        if($auth_column->created_at->addSeconds($otp_exp_sec) < now()) {
            $this->authLogout($request);
            return redirect()->route('merchant.login')->with(['error' => ['Session expired. Please try again']]);
        }
        try{
            $auth_column->merchant->update([
                'email_verified'    => true,
            ]);
            $auth_column->delete();
        }catch(Exception $e) {
            $this->authLogout($request);
            return redirect()->route('merchant.login')->with(['error' => ['Something went worng! Please try again']]);
        }
        return redirect()->intended(route("merchant.dashboard"))->with(['success' => ['Account successfully verified']]);
    }
    public function resendCode()
    {
        $user = auth()->user();
        $resend = MerchantAuthorization::where("merchant_id",$user->id)->first();
        if( $resend){
            if(Carbon::now() <= $resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)) {
                throw ValidationException::withMessages([
                    'code'      => 'You can resend verification code after '.Carbon::now()->diffInSeconds($resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)). ' seconds',
                ]);
            }
        }
        $data = [
            'merchant_id'       =>  $user->id,
            'code'          => generate_random_code(),
            'token'         => generate_unique_string("merchant_authorizations","token",200),
            'created_at'    => now(),
        ];

        DB::beginTransaction();
        try{
            MerchantAuthorization::where("merchant_id",$user->id)->delete();
            DB::table("merchant_authorizations")->insert($data);
            $user->notify(new SendAuthorizationCode((object) $data));
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }

        return redirect()->route('merchant.authorize.mail',$data['token'])->with(['success' => ['Varification code resend success!']]);

    }

    public function authLogout(Request $request) {
        auth()->guard("web")->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function showKycFrom() {
        $user = auth()->user();
        if($user->kyc_verified == GlobalConst::VERIFIED) return back()->with(['success' => ['You are already KYC Verified User']]);
        $page_title = "KYC Verification";
        $user_kyc = SetupKyc::merchantKyc()->first();
        if(!$user_kyc) return back();
        $kyc_data = $user_kyc->fields;
        $kyc_fields = [];
        if($kyc_data) {
            $kyc_fields = array_reverse($kyc_data);
        }
        return view('merchant.auth.authorize.verify-kyc',compact("page_title","kyc_fields"));
    }

    public function kycSubmit(Request $request) {


        $user = auth()->user();
        if($user->kyc_verified == GlobalConst::VERIFIED) return back()->with(['success' => ['You are already KYC Verified User']]);

        $user_kyc_fields = SetupKyc::merchantKyc()->first()->fields ?? [];
        $validation_rules = $this->generateValidationRules($user_kyc_fields);

        $validated = Validator::make($request->all(),$validation_rules)->validate();
        $get_values = $this->placeValueWithFields($user_kyc_fields,$validated);

        $create = [
            'merchant_id'       => auth()->user()->id,
            'data'          => json_encode($get_values),
            'created_at'    => now(),
        ];

        DB::beginTransaction();
        try{
            DB::table('merchant_kyc_data')->updateOrInsert(["merchant_id" => $user->id],$create);
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $user->update([
                'kyc_verified'  => GlobalConst::DEFAULT,
            ]);
            $this->generatedFieldsFilesDelete($get_values);
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }

        return redirect()->route("merchant.profile.index")->with(['success' => ['KYC information successfully submited']]);
    }
    public function showGoogle2FAForm() {
        $page_title =  "Authorize Google Two Factor";
        return view('merchant.auth.authorize.verify-google-2fa',compact('page_title'));
    }

    public function google2FASubmit(Request $request) {

        $request->validate([
            'code'      => "required|array",
            'code.*'    => "required|numeric",
        ]);
        $code = $request->code;
        $code = implode("",$code);

        $user = auth()->user();

        if(!$user->two_factor_secret) {
            return back()->with(['warning' => ['Your secret key not stored properly. Please contact with system administrator']]);
        }

        if(google_2fa_verify($user->two_factor_secret,$code)) {

            $user->update([
                'two_factor_verified'   => true,

            ]);

            return redirect()->intended(route('merchant.dashboard'));
        }

        return back()->with(['warning' => ['Faild to login. Please try again']]);
    }
}

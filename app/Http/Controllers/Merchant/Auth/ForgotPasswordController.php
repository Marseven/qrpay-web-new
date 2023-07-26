<?php

namespace App\Http\Controllers\Merchant\Auth;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantPasswordReset;
use App\Models\User;
use App\Models\UserPasswordReset;
use App\Notifications\Merchant\Auth\PasswordResetEmail;
use App\Providers\Admin\BasicSettingsProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ForgotPasswordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showForgotForm()
    {
        $page_title = setPageTitle("Forgot Password");
        return view('merchant.auth.forgot-password.forgot',compact('page_title'));
    }


    /**
     * Send Verification code to user email/phone.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'credentials'   => "required|string|max:100",
        ]);
        $column = "username";
        if(check_email($request->credentials)) $column = "email";
        $user = Merchant::where($column,$request->credentials)->first();
        if(!$user) {
            throw ValidationException::withMessages([
                'credentials'       => "Merchant doesn't exists.",
            ]);
        }

        $token = generate_unique_string("merchant_password_resets","token",80);
        $code = generate_random_code();

        try{
            MerchantPasswordReset::where("merchant_id",$user->id)->delete();
            $password_reset = MerchantPasswordReset::create([
                'merchant_id'       => $user->id,
                'token'         => $token,
                'code'          => $code,
            ]);
            $user->notify(new PasswordResetEmail($user,$password_reset));
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }
        return redirect()->route('merchant.password.forgot.code.verify.form',$token)->with(['success' => ['Varification code sended to your email address.']]);
    }


    public function showVerifyForm($token) {
        $page_title = setPageTitle("Verify Merchant");
        $password_reset = MerchantPasswordReset::where("token",$token)->first();
        if(!$password_reset) return redirect()->route('merchant.password.forgot')->with(['error' => ['Password Reset Token Expired']]);
        $resend_time = 0;
        if(Carbon::now() <= $password_reset->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE)) {
            $resend_time = Carbon::now()->diffInSeconds($password_reset->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE));
        }
        $user_email = $password_reset->merchant->email ?? "";
        return view('merchant.auth.forgot-password.verify',compact('page_title','token','user_email','resend_time'));
    }

    /**
     * OTP Verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyCode(Request $request,$token)
    {
        $request->merge(['token' => $token]);
        $validated = Validator::make($request->all(),[
            'token'         => "required|string|exists:merchant_password_resets,token",
            'code'      => "required|array",
            'code.*'    => "required|numeric",
        ])->validate();
        $code = $request->code;
        $code = implode("",$code);

        $basic_settings = BasicSettingsProvider::get();
        $otp_exp_seconds = $basic_settings->otp_exp_seconds ?? 0;

        $password_reset = MerchantPasswordReset::where("token",$token)->first();
        if(!$password_reset){
            return back()->with(['error' => ['Verification code already used']]);
        }
        if( $password_reset){
            if(Carbon::now() >= $password_reset->created_at->addSeconds($otp_exp_seconds)) {
                foreach(UserPasswordReset::get() as $item) {
                    if(Carbon::now() >= $item->created_at->addSeconds($otp_exp_seconds)) {
                        $item->delete();
                    }
                }
                return redirect()->route('merchant.password.forgot')->with(['error' => ['Session expired. Please try again.']]);
            }
        }
        if($password_reset->code != $code) {
            throw ValidationException::withMessages([
                'code'      => "Verification Otp is Invalid",
            ]);
        }
        return redirect()->route('merchant.password.forgot.reset.form',$token);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function resendCode($token)
    {
        $password_reset = MerchantPasswordReset::where('token',$token)->first();
        if(!$password_reset) return back()->with(['error' => ['Request token is invalid']]);
        if(Carbon::now() <= $password_reset->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE)) {
            throw ValidationException::withMessages([
                'code'      => 'You can resend verification code after '.Carbon::now()->diffInSeconds($password_reset->created_at->addMinutes(GlobalConst::USER_PASS_RESEND_TIME_MINUTE)). ' seconds',
            ]);
        }

        DB::beginTransaction();
        try{
            $update_data = [
                'code'          => generate_random_code(),
                'created_at'    => now(),
                'token'         => $token,
            ];
            DB::table('merchant_password_resets')->where('token',$token)->update($update_data);
            $password_reset->merchant->notify(new PasswordResetEmail($password_reset->merchant,(object) $update_data));
            DB::commit();
        }catch(Exception $e) {
            DB::rollback();
            return back()->with(['error' => ['Something went worng. please try again']]);
        }
        return redirect()->route('merchant.password.forgot.code.verify.form',$token)->with(['success' => ['Varification code resend success!']]);

    }


    public function showResetForm($token) {
        $page_title = setPageTitle("Reset Password");
        return view('merchant.auth.forgot-password.reset',compact('page_title','token'));
    }


    public function resetPassword(Request $request,$token) {
        $basic_settings = BasicSettingsProvider::get();
        $passowrd_rule = "required|string|min:6|confirmed";
        if($basic_settings->secure_password) {
            $passowrd_rule = ["required",Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),"confirmed"];
        }

        $request->merge(['token' => $token]);
        $validated = Validator::make($request->all(),[
            'token'         => "required|string|exists:merchant_password_resets,token",
            'password'      => $passowrd_rule,
        ])->validate();

        $password_reset = MerchantPasswordReset::where("token",$token)->first();
        if(!$password_reset) {
            throw ValidationException::withMessages([
                'password'      => "Invalid Request. Please try again.",
            ]);
        }

        try{
            $password_reset->merchant->update([
                'password'      => Hash::make($validated['password']),
            ]);
            $password_reset->delete();
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }

        return redirect()->route('merchant.login')->with(['success' => ['Password reset success. Please login with new password.']]);
    }

}

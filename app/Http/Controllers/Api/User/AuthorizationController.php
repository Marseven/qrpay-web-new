<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\SetupKyc;
use App\Models\User;
use App\Models\UserAuthorization;
use App\Notifications\User\Auth\SendAuthorizationCode;
use App\Notifications\User\Auth\SendVerifyCode;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Traits\ControlDynamicInputFields;
use Illuminate\Support\Facades\Notification;

class AuthorizationController extends Controller
{
    use ControlDynamicInputFields;
    protected $basic_settings;

    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }
    public function sendMailCode()
    {
        $user = auth()->user();
        $resend = UserAuthorization::where("user_id",$user->id)->first();
        if( $resend){
            if(Carbon::now() <= $resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)) {

                $error = ['error'=>['You can resend verification code after '.Carbon::now()->diffInSeconds($resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)). ' seconds']];
                return Helpers::error($error);
            }
        }

        $data = [
            'user_id'       =>  $user->id,
            'code'          => generate_random_code(),
            'token'         => generate_unique_string("user_authorizations","token",200),
            'created_at'    => now(),
        ];
        DB::beginTransaction();
        try{
            if($resend) {
                UserAuthorization::where("user_id", $user->id)->delete();
            }
            DB::table("user_authorizations")->insert($data);
            $user->notify(new SendAuthorizationCode((object) $data));
            DB::commit();
            $message =  ['success'=>['Varification code send success']];
            return Helpers::onlysuccess($message);
        }catch(Exception $e) {
            DB::rollBack();
            $error = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($error);
        }
    }
    public function mailVerify(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required|numeric',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $user = auth()->user();
        $code = $request->code;
        $otp_exp_sec = BasicSettingsProvider::get()->otp_exp_seconds ?? GlobalConst::DEFAULT_TOKEN_EXP_SEC;
        $auth_column = UserAuthorization::where("user_id",$user->id)->where("code",$code)->first();

        if(!$auth_column){
             $error = ['error'=>['Verification code does not match']];
            return Helpers::error($error);
        }
        if($auth_column->created_at->addSeconds($otp_exp_sec) < now()) {
            $error = ['error'=>['Time expired. Please try again']];
            return Helpers::error($error);
        }
        try{
            $auth_column->user->update([
                'email_verified'    => true,
            ]);
            $auth_column->delete();
        }catch(Exception $e) {
            $error = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($error);
        }
        $message =  ['success'=>['Account successfully verified']];
        return Helpers::onlysuccess($message);
    }
    public function showKycFrom(){
        $user = auth()->user();
        $kyc_status = $user->kyc_verified;
        $user_kyc = SetupKyc::userKyc()->first();
        $status_info = "1==verified, 2==pending, 0==unverified; 3=rejected";
        $kyc_data = $user_kyc->fields;
        $kyc_fields = [];
        if($kyc_data) {
            $kyc_fields = array_reverse($kyc_data);
        }
        $data =[
            'status_info' => $status_info,
            'kyc_status' => $kyc_status,
            'userKyc' => $kyc_fields
        ];
        $message = ['success'=>['Your KYC info']];
        return Helpers::success($data,$message);

    }
    public function kycSubmit(Request $request){
        $user = auth()->user();
        if($user->kyc_verified == GlobalConst::VERIFIED){
            $message = ['error'=>['You are already KYC Verified User']];
            return Helpers::error($message);

        }
        $user_kyc_fields = SetupKyc::userKyc()->first()->fields ?? [];
        $validation_rules = $this->generateValidationRules($user_kyc_fields);
        $validated = Validator::make($request->all(), $validation_rules);

        if ($validated->fails()) {
            $message =  ['error' => $validated->errors()->all()];
            return Helpers::error($message);
        }
        $validated = $validated->validate();
        $get_values = $this->placeValueWithFields($user_kyc_fields, $validated);
        $create = [
            'user_id'       => auth()->user()->id,
            'data'          => json_encode($get_values),
            'created_at'    => now(),
        ];

        DB::beginTransaction();
        try{
            DB::table('user_kyc_data')->updateOrInsert(["user_id" => $user->id],$create);
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $user->update([
                'kyc_verified'  => GlobalConst::DEFAULT,
            ]);
            // $this->generatedFieldsFilesDelete($get_values);
            $message = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($message);
        }
        $message = ['success'=>['KYC information successfully submited']];
        return Helpers::onlysuccess($message);

    }

    //========================before registration======================================
    public function checkExist(Request $request){
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $column = "email";
        if(check_email($request->email)) $column = "email";
        $user = User::where($column,$request->email)->first();
        if($user){
            $error = ['error'=>['User already exist, please select another email address']];
            return Helpers::validation($error);
        }
        $message = ['success'=>['Now,You can register']];
        return Helpers::onlysuccess($message);

    }
    public function sendEmailOtp(Request $request){
        $basic_settings = $this->basic_settings;
        if($basic_settings->agree_policy){
            $agree = 'required';
        }else{
            $agree = '';
        }

        if( $request->agree != 1){
            return Helpers::error(['error' => ['Terms Of Use & Privacy Policy Field Is Required!']]);
        }

        $validator = Validator::make($request->all(), [
            'email'         => 'required|email',
            'agree'         =>  $agree,
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $validated = $validator->validate();

        $field_name = "username";
        if(check_email($validated['email'])) {
            $field_name = "email";
        }
        $exist = User::where($field_name,$validated['email'])->active()->first();
        if( $exist){
            $message = ['error'=>['User already  exists, please try with another email']];
            return Helpers::error($message);
        }

        $code = generate_random_code();
        $data = [
            'user_id'       =>  0,
            'email'         => $validated['email'],
            'code'          => $code,
            'token'         => generate_unique_string("user_authorizations","token",200),
            'created_at'    => now(),
        ];
        DB::beginTransaction();
        try{
            $oldToken = UserAuthorization::where("email",$validated['email'])->get();
            if($oldToken){
                foreach($oldToken as $token){
                    $token->delete();
                }
            }
            DB::table("user_authorizations")->insert($data);
            Notification::route("mail",$validated['email'])->notify(new SendVerifyCode($validated['email'], $code));
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $message = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($message);
        };
        $message = ['success'=>['Varification code send to your email successfully']];
        return Helpers::onlysuccess($message);
    }
    public function verifyEmailOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'email'     => "required|email",
            'code'    => "required|max:6",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $code = $request->code;
        $otp_exp_sec = BasicSettingsProvider::get()->otp_exp_seconds ?? GlobalConst::DEFAULT_TOKEN_EXP_SEC;
        $auth_column = UserAuthorization::where("email",$request->email)->where("code",$code)->first();
        if(!$auth_column){
            $message = ['error'=>['Verification code does not match']];
            return Helpers::error($message);
        }
        if($auth_column->created_at->addSeconds($otp_exp_sec) < now()) {
            $auth_column->delete();
            $message = ['error'=>['Verification code is expired']];
            return Helpers::error($message);
        }
        try{
            $auth_column->delete();
        }catch(Exception $e) {
            $message = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($message);
        }
        $message = ['success'=>['Otp successfully verified']];
        return Helpers::onlysuccess($message);
    }
    public function resendEmailOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'email'     => "required|email",
        ]);
        if($validator->fails()){
            $error =  ['error'=>$validator->errors()->all()];
            return Helpers::validation($error);
        }
        $resend = UserAuthorization::where("email",$request->email)->first();
        if($resend){
            if(Carbon::now() <= $resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)) {
                $message = ['error'=>['You can resend verification code after '.Carbon::now()->diffInSeconds($resend->created_at->addMinutes(GlobalConst::USER_VERIFY_RESEND_TIME_MINUTE)). ' seconds']];
                return Helpers::error($message);
            }
        }
        $code = generate_random_code();
        $data = [
            'user_id'       =>  0,
            'email'         => $request->email,
            'code'          => $code,
            'token'         => generate_unique_string("user_authorizations","token",200),
            'created_at'    => now(),
        ];
        DB::beginTransaction();
        try{
            $oldToken = UserAuthorization::where("email",$request->email)->get();
            if($oldToken){
                foreach($oldToken as $token){
                    $token->delete();
                }
            }
            DB::table("user_authorizations")->insert($data);
            Notification::route("mail",$request->email)->notify(new SendVerifyCode($request->email, $code));
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            $message = ['error'=>['Something went worng! Please try again']];
            return Helpers::error($message);
        }
        $message = ['success'=>['Varification code resend success']];
        return Helpers::onlysuccess($message);
    }
}

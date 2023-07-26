<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\Admin\BasicSettingsProvider;
use App\Traits\User\LoggedInUsers;
use App\Traits\User\RegisteredUsers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthentication extends Controller
{
    use LoggedInUsers,RegisteredUsers;

    public function google() {
        return Socialite::driver("google")->redirect();
    }

    public function googleResponse(Request $request) {
        try{
            $user = Socialite::driver("google")->user();
            if($user && $user->getEmail()) {
                $user_credentials = $user->getEmail();
                $inhouse_user = User::getSocial($user_credentials)->first();
                if($inhouse_user) {
                    return $this->handleInhouseUser($inhouse_user);
                }else {
                    return $this->handleNewUserFromGoogle($user);
                }
            }
        }catch(Exception $e) {
            return redirect()->route('user.login')->with(['error' => ["Something went worng! Please try again"]]);
        }
        return redirect()->route('user.login')->with(['error' => ["Something went worng! Please try again"]]);
    }

    public function handleInhouseUser($user) {
        Auth::guard("web")->login($user);
        $this->refreshUserWallets($user);
        $this->createLoginLog($user);
        return redirect()->intended(route('user.dashboard'));
    }

    public function handleNewUserFromGoogle($user) {
        try{
            $basic_settings = BasicSettingsProvider::get();
            $user_info = [];
            $user_info['firstname'] = $user->user['given_name'] ?? $user->getName();
            $user_info['lastname']  = $user->user['family_name'] ?? "";
            $user_info['username']  = make_username($user_info['firstname'],$user_info['lastname']);
            $user_info['email']     = $user->getEmail();
            $user_info['password']  = Hash::make($user->getId()."-".$user->getEmail());
            $user_info['image']     = $user->user['picture'];
            $user_info['email_verified']    = ($basic_settings->email_verification == true) ? false : true;
            $user_info['sms_verified']      = ($basic_settings->sms_verification == true) ? false : true;
            $validated['kyc_verified']      = ($basic_settings->kyc_verification == true) ? false : true;
            $user = User::create($user_info);
        }catch(Exception $e) {
            return redirect()->route('user.login')->with(['error' => ["Something went worng! Please try again"]]);
        }

        Auth::guard("web")->login($user);
        $this->createUserWallets($user);
        $this->createLoginLog($user);
        return redirect()->intended(route("user.dashboard"));
    }

    public function facebook() {
        return Socialite::driver("facebook")->redirect();
    }

    public function facebookResponse() {
        try{
            $user = Socialite::driver("facebook")->user();
            if($user && $user->getEmail()) {
                $user_credentials = $user->getEmail();
                $inhouse_user = User::getSocial($user_credentials)->first();
                if($inhouse_user) {
                    return $this->handleInhouseUser($inhouse_user);
                }else {
                    return $this->handleNewUserFromFacebook($user);
                }
            }
        }catch(Exception $e) {
            return redirect()->route('user.login')->with(['error' => ["Something went worng! Please try again"]]);
        }
        return redirect()->route('user.login')->with(['error' => ["Something went worng! Please try again"]]);
    }

    public function handleNewUserFromFacebook($user) {
        try{
            $basic_settings = BasicSettingsProvider::get();
            $user_info = [];
            $user_info['firstname'] = $user->getName() ?? "First Name";
            $lastname               = explode(" ",$user_info['firstname']);
            $lastname               = end($lastname);
            $user_info['lastname']  = $lastname ?? "Last Name";
            $user_info['username']  = make_username($user_info['firstname'],$user_info['lastname']);
            $user_info['email']     = $user->getEmail();
            $user_info['password']  = Hash::make($user->getId()."-".$user->getEmail());
            $user_info['image']     = $user->avatar ?? "";
            $user_info['email_verified']    = ($basic_settings->email_verification == true) ? false : true;
            $user_info['sms_verified']      = ($basic_settings->sms_verification == true) ? false : true;
            $validated['kyc_verified']      = ($basic_settings->kyc_verification == true) ? false : true;
            $user = User::create($user_info);
        }catch(Exception $e) {
            return redirect()->route('user.login')->with(['error' => ["Something went worng! Please try again"]]);
        }

        Auth::guard("web")->login($user);
        $this->createUserWallets($user);
        $this->createLoginLog($user);
        return redirect()->intended(route("user.dashboard"));
    }

}

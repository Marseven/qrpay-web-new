<?php
namespace App\Http\Controllers\User;

use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\Currency;
use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantQrCode;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use App\Models\UserQrCode;
use App\Models\VirtualCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index()
    {
        $page_title = "Dashboard";
        $baseCurrency = Currency::default();
        $transactions = Transaction::auth()->latest()->take(5)->get();
        $data['totalReceiveRemittance'] =Transaction::auth()->remitance()->where('attribute',"RECEIVED")->where('status',1)->sum('request_amount');
        $data['totalSendRemittance'] =Transaction::auth()->remitance()->where('attribute',"SEND")->where('status',1)->sum('request_amount');
        $data['cardAmount'] =VirtualCard::where('user_id',auth()->user()->id)->sum('amount');
        $data['billPay'] = Transaction::auth()->billPay()->where('status',1)->sum('request_amount');
        $data['topUps'] = Transaction::auth()->mobileTopup()->where('status',1)->sum('request_amount');
        $data['withdraw'] = Transaction::auth()->moneyOut()->where('status',1)->sum('request_amount');
        $data['toatlTransactions'] = Transaction::auth()->where('status',1)->sum('request_amount');
        $start = strtotime(date('Y-m-01'));
        $end = strtotime(date('Y-m-31'));
          // Add Money
        $pending_data  = [];
        $success_data  = [];
        $canceled_data = [];
        $hold_data     = [];
        $month_day  = [];
        // Money Out
        $Money_out_pending_data  = [];
        $Money_out_success_data  = [];
        $Money_out_canceled_data = [];
        $Money_out_hold_data     = [];
        while ($start <= $end) {
            $start_date = date('Y-m-d', $start);

            // Monthley add money
            $pending = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 2)
                                        ->count();
            $success = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 1)
                                        ->count();
            $canceled = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 4)
                                        ->count();
            $hold = Transaction::auth()->where('type', PaymentGatewayConst::TYPEADDMONEY)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 3)
                                        ->count();
            $pending_data[]  = $pending;
            $success_data[]  = $success;
            $canceled_data[] = $canceled;
            $hold_data[]     = $hold;

              // Monthley money Out
              $money_pending = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                                        ->whereDate('created_at',$start_date)
                                        ->where('status', 2)
                                        ->count();
            $money_success = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                                ->whereDate('created_at',$start_date)
                                ->where('status', 1)
                                ->count();
            $money_canceled = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                                ->whereDate('created_at',$start_date)
                                ->where('status', 4)
                                ->count();
            $money_hold = Transaction::auth()->where('type', PaymentGatewayConst::TYPEMONEYOUT)
                            ->whereDate('created_at',$start_date)
                            ->where('status', 3)
                            ->count();
            $Money_out_pending_data[]  = $money_pending;
            $Money_out_success_data[]  = $money_success;
            $Money_out_canceled_data[] = $money_canceled;
            $Money_out_hold_data[]     = $money_hold;

            $month_day[] = date('Y-m-d', $start);
            $start = strtotime('+1 day',$start);
        }
         // Chart one
         $chart_one_data = [
            'pending_data'  => $pending_data,
            'success_data'  => $success_data,
            'canceled_data' => $canceled_data,
            'hold_data'     => $hold_data,
        ];
         // Chart two
         $chart_two_data = [
            'pending_data'  => $Money_out_pending_data,
            'success_data'  => $Money_out_success_data,
            'canceled_data' => $Money_out_canceled_data,
            'hold_data'     => $Money_out_hold_data,
        ];
        $chartData =[
            'chart_one_data'   => $chart_one_data,
            'chart_two_data'   => $chart_two_data,
            'month_day'        => $month_day,
        ];


         //
        return view('user.dashboard',compact("page_title","baseCurrency",'transactions','data','chartData'));
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('user.login')->with(['success' => ['Logout Successfully!']]);
    }
    public function qrScan($qr_code)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, GET, POST");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        $qrCode = UserQrCode::where('qr_code',$qr_code)->first();
        if(!$qrCode){
            return response()->json(['error'=>'Invalid request']);
        }
        $user = User::find($qrCode->user_id);
        if(!$user){
            return response()->json(['error'=>'Not found']);
        }
        return $user->email;
    }
    public function merchantQrScan($qr_code)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, GET, POST");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        $qrCode = MerchantQrCode::where('qr_code',$qr_code)->first();
        if(!$qrCode){
            return response()->json(['error'=>'Invalid request']);
        }
        $user = Merchant::find($qrCode->merchant_id);
        if(!$user){
            return response()->json(['error'=>'Invalid merchant']);
        }
        return $user->email;
    }
    public function deleteAccount(Request $request) {
        $validator = Validator::make($request->all(),[
            'target'        => 'required',
        ]);
        $validated = $validator->validate();
        $user = auth()->user();
        $user->status = false;
        $user->email_verified = false;
        $user->sms_verified = false;
        $user->kyc_verified = false;
        $user->deleted_at = now();
        $user->save();
        try{
            Auth::logout();
            return redirect()->route('index')->with(['success' => ['Your profile deleted successfully!']]);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went wrong! Please try again.']]);
        }
    }
}

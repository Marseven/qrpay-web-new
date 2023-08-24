<?php

namespace App\Http\Controllers\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\Currency;
use App\Models\Admin\TransactionSetting;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\User\TicketPay\Approved;
use App\Notifications\User\TicketPay\TicketPayMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    //
    public function index()
    {
        $page_title = "Ticket Pay";
        $ticketPayCharge = TransactionSetting::where('slug', 'ticket_pay')->where('status', 1)->first();
        $ticketType = Ticket::active()->orderByDesc('id')->get();
        $transactions = Transaction::auth()->ticketPay()->latest()->take(10)->get();
        return view('user.sections.ticket-pay.index', compact("page_title", 'ticketPayCharge', 'transactions', 'ticketType'));
    }

    public function payConfirm(Request $request)
    {
        $request->validate([
            'ticket_type' => 'required|string',
            'ticket_number' => 'required',
            'amount' => 'required|numeric|gt:0',
        ]);
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        if ($basic_setting->kyc_verification) {
            if ($user->kyc_verified == 0) {
                return redirect()->route('user.profile.index')->with(['error' => ['Please submit kyc information']]);
            } elseif ($user->kyc_verified == 2) {
                return redirect()->route('user.profile.index')->with(['error' => ['Please wait before admin approved your kyc information']]);
            } elseif ($user->kyc_verified == 3) {
                return redirect()->route('user.profile.index')->with(['error' => ['Admin rejected your kyc information, Please re-submit again']]);
            }
        }
        $amount = $request->amount;
        $ticketType = $request->ticket_type;
        $ticket_type = Ticket::where('id', $ticketType)->first();
        $ticket_number = $request->ticket_number;
        $user = auth()->user();
        $ticketPayCharge = TransactionSetting::where('slug', 'ticket_pay')->where('status', 1)->first();
        $userWallet = UserWallet::where('user_id', $user->id)->first();
        if (!$userWallet) {
            return back()->with(['error' => ['Sender wallet not found']]);
        }
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if (!$baseCurrency) {
            return back()->with(['error' => ['Default currency not found']]);
        }

        $minLimit =  $ticketPayCharge->min_limit *  $rate;
        $maxLimit =  $ticketPayCharge->max_limit *  $rate;
        if ($amount < $minLimit || $amount > $maxLimit) {
            return back()->with(['error' => ['Please follow the transaction limit']]);
        }
        //charge calculations
        $fixedCharge = $ticketPayCharge->fixed_charge *  $rate;
        $percent_charge = ($request->amount / 100) * $ticketPayCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        if ($payable > $userWallet->balance) {
            return back()->with(['error' => ['Sorry, insuficiant balance']]);
        }
        try {
            $trx_id = 'TP' . getTrxNum();
            $sender = $this->insertSender($trx_id, $user, $userWallet, $amount, $ticket_type, $ticket_number, $payable);
            $this->insertSenderCharges($fixedCharge, $percent_charge, $total_charge, $amount, $user, $sender);
            $this->approved($sender);
            return redirect()->route("user.ticket.pay.index")->with(['success' => ['ticket pay request send successful']]);
        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }

    public function insertSender($trx_id, $user, $userWallet, $amount, $ticket_type, $ticket_number, $payable)
    {
        $trx_id = $trx_id;
        $authWallet = $userWallet;
        $afterCharge = ($authWallet->balance - $payable);
        $details = [
            'ticket_type_id' => $ticket_type->id ?? '',
            'ticket_type_name' => $ticket_type->label ?? '',
            'ticket_number' => $ticket_number,
            'ticket_amount' => $amount ?? "",
        ];
        DB::beginTransaction();
        try {
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::TICKETPAY,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $payable,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TICKETPAY, " ")) . " Request To Admin",
                'details'                       => json_encode($details),
                'attribute'                      => PaymentGatewayConst::SEND,
                'status'                        => 2,
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet, $afterCharge);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    public function updateSenderWalletBalance($authWalle, $afterCharge)
    {
        $authWalle->update([
            'balance'   => $afterCharge,
        ]);
    }

    public function insertSenderCharges($fixedCharge, $percent_charge, $total_charge, $amount, $user, $id)
    {
        DB::beginTransaction();
        try {
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      => $fixedCharge,
                'total_charge'      => $total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => "Ticket Pay ",
                'message'       => "Ticket Pay request send to admin " . $amount . ' ' . get_default_currency_code() . " successful.",
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::TICKET_PAY,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function approved($id)
    {
        $data = Transaction::where('id', $id)->where('status', 2)->where('type', PaymentGatewayConst::TICKETPAY)->first();

        $up['status'] = 1;
        try {
            $approved = $data->fill($up)->save();
            if ($approved) {

                //notification
                $notification_content = [
                    'title'         => "Ticket Pay",
                    'message'       => "Your Ticket Pay request approved " . getAmount($data->request_amount, 2) . ' ' . get_default_currency_code() . " & Ticket Number is: " . @$data->details->ticket_number . " successful.",
                    'image'         => files_asset_path('profile-default'),
                ];

                if ($data->user_id != null) {
                    $notifyData = [
                        'trx_id'  => $data->trx_id,
                        'ticket_type'  => @$data->details->ticket_type_name,
                        'ticket_number'  => @$data->details->ticket_number,
                        'request_amount'   => $data->request_amount,
                        'charges'   => $data->charge->total_charge,
                        'payable'  => $data->payable,
                        'current_balance'  => getAmount($data->available_balance, 4),
                        'status'  => "Success",
                    ];
                    $user = $data->user;
                    $user->notify(new Approved($user, (object)$notifyData));
                    UserNotification::create([
                        'type'      => NotificationConst::TICKET_PAY,
                        'user_id'  =>  $data->user_id,
                        'message'   => $notification_content,
                    ]);
                    DB::commit();
                }
            }
        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
}

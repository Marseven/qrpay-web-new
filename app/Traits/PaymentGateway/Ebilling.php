<?php

namespace App\Traits\PaymentGateway;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\User\AddMoneyController;
use App\Models\TemporaryData;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Notifications\User\AddMoney\ApprovedMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

trait Ebilling
{
    public function ebillingInit($output = null)
    {
        if (!$output) $output = $this->output;

        $credentials = $this->getEbillingCredentials($output);
        $trx_id = 'AM' . getTrxNum();

        $eb_name = Auth::user()->firstname . ' ' . Auth::user()->lastname;
        $eb_amount = $output['amount']->total_amount;
        $eb_shortdescription = 'Recharge de mon portefeuille CNOU.';
        $eb_reference = $trx_id;
        $eb_email = Auth::user()->email;
        $eb_msisdn = Auth::user()->phone ?? '074808000';
        $eb_callbackurl = url('user/add-money/ebilling/callback/' . $trx_id);
        $expiry_period = 60; // 60 minutes timeout


        // =============================================================
        // ============== E-Billing server invocation ==================
        // =============================================================

        $global_array =
            [
                'payer_email' => $eb_email,
                'payer_msisdn' => $eb_msisdn,
                'amount' => $eb_amount,
                'short_description' => $eb_shortdescription,
                'external_reference' => $eb_reference,
                'payer_name' => $eb_name,
                'expiry_period' => $expiry_period
            ];

        if ($credentials->mode == "sandbox") {
            $server_url =  env('SERVER_URL_LAB');
            $post_url = env('POST_URL_LAB');
        } else {
            $server_url =  env('SERVER_URL');
            $post_url = env('POST_URL');
        }

        $content = json_encode($global_array);

        $curl = curl_init($server_url);
        curl_setopt($curl, CURLOPT_USERPWD, $credentials->username . ":" . $credentials->sharedkey);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        $json_response = curl_exec($curl);

        // Get status code
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Check status <> 200
        if ($status < 200  || $status > 299) {
            //die("Error: call to URL failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
            throw new Exception(curl_error($curl));
        } else {
            curl_close($curl);

            // Get response in JSON format
            $response = json_decode($json_response, true);

            // Get unique transaction id
            $bill_id = $response['e_bill']['bill_id'];
            $this->ebillingJunkInsert($response, $trx_id);

            $this->ebillingCreateTransaction($output, $trx_id, $bill_id);

            // Redirect to E-Billing portal
            echo "<form action='" . $post_url . "' method='post' name='frm'>";
            echo "<input type='hidden' name='invoice_number' value='" . $bill_id . "'>";
            echo "<input type='hidden' name='eb_callbackurl' value='" . $eb_callbackurl . "'>";
            echo "</form>";
            echo "<script language='JavaScript'>";
            echo "document.frm.submit();";
            echo "</script>";

            die;

            // Redirect to E-Billing portal
            //return redirect()->away($url);
        }
    }

    public function ebillingInitApi($output = null)
    {
        if (!$output) $output = $this->output;
        $credentials = $this->getEbillingCredentials($output);
        $trx_id = 'AM' . getTrxNum();


        $eb_name = Auth::user()->firstname . ' ' . Auth::user()->lastname;
        $eb_amount = $output['amount']->total_amount;
        $eb_shortdescription = 'Recharge de mon portefeuille Cnou.';
        $eb_reference = $trx_id;
        $eb_email = Auth::user()->email;
        $eb_msisdn = Auth::user()->phone ?? '074808000';
        $eb_callbackurl = url('/ebilling/callback/' . $trx_id);
        $expiry_period = 60; // 60 minutes timeout


        // =============================================================
        // ============== E-Billing server invocation ==================
        // =============================================================

        $global_array =
            [
                'payer_email' => $eb_email,
                'payer_msisdn' => $eb_msisdn,
                'amount' => $eb_amount,
                'short_description' => $eb_shortdescription,
                'external_reference' => $eb_reference,
                'payer_name' => $eb_name,
                'expiry_period' => $expiry_period
            ];

        $content = json_encode($global_array);
        $curl = curl_init(env('SERVER_URL'));
        curl_setopt($curl, CURLOPT_USERPWD, $credentials->username . ":" . $credentials->sharedkey);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        $json_response = curl_exec($curl);

        // Get status code
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);


        // Check status <> 200
        if ($status < 200  || $status > 299) {
            //die("Error: call to URL failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
            throw new Exception(curl_error($curl));
        } else {
            curl_close($curl);

            // Get response in JSON format
            $response = json_decode($json_response, true);

            // Get unique transaction id
            $bill_id = $response['e_bill']['bill_id'];
            $this->ebillingJunkInsert($response, $trx_id);

            $this->ebillingCreateTransaction($output, $trx_id, $bill_id);
            $result['trx'] = $trx_id;
            $result['bill_id'] = $bill_id;

            // Redirect to E-Billing portal
            return $result;
        }
    }

    public function getEbillingCredentials($output)
    {
        $gateway = $output['gateway'] ?? null;

        if (!$gateway) throw new Exception("Payment gateway not available");
        $client_username_sample = ['username', 'user_name', 'Username', 'primary key'];
        $client_sharedkey_sample = ['shared_key', 'Shared Key', 'shared', 'shared key', 'shared id'];
        $username = '';
        $outer_break = false;
        foreach ($client_username_sample as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $item;
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                if ($label == $modify_item) {
                    $username = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $sharedkey = '';
        $outer_break = false;
        foreach ($client_sharedkey_sample as $item) {
            if ($outer_break == true) {
                break;
            }
            $modify_item = $item;
            foreach ($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                if ($label == $modify_item) {
                    $sharedkey = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }
        $mode = $gateway->env;

        $ebilling_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if (array_key_exists($mode, $ebilling_register_mode)) {
            $mode = $ebilling_register_mode[$mode];
        } else {
            $mode = "sandbox";
        }
        return (object) [
            'username'  => $username,
            'sharedkey' => $sharedkey,
            'mode'      => $mode,
        ];
    }

    public function ebillingJunkInsert($response, $reference)
    {

        $output = $this->output;

        $data = [
            'gateway'   => $output['gateway']->id,
            'currency'  => $output['currency']->id,
            'amount'    => json_decode(json_encode($output['amount']), true),
            'response'  => $response,
            'wallet_table'  => $output['wallet']->getTable(),
            'wallet_id'     => $output['wallet']->id,
            'creator_table' => auth()->guard(get_auth_guard())->user()->getTable(),
            'creator_id'    => auth()->guard(get_auth_guard())->user()->id,
            'creator_guard' => get_auth_guard(),
        ];

        return TemporaryData::create([
            'type'          => PaymentGatewayConst::EBILLING,
            'identifier'    => $reference,
            'data'          => $data,
        ]);
    }

    public function ebillingCreateTransaction($output, $trx_id, $bill_id)
    {
        $trx_id =  $trx_id;
        $inserted_id = $this->ebillingInsertRecord($output, $trx_id, $bill_id);
        $this->insertCharges($output, $inserted_id);
        $this->insertDevice($output, $inserted_id);
        //$this->removeTempData($output);
        if ($this->requestIsApiUser()) {
            // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if ($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }
    }

    public function ebillingInsertRecord($output, $trx_id, $bill_id)
    {
        $trx_id =  $trx_id;
        $token = $this->output['tempData']['identifier'] ?? "";
        DB::beginTransaction();
        try {
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => auth()->user()->id,
                'user_wallet_id'                => $output['wallet']->id,
                'payment_gateway_currency_id'   => $output['currency']->id,
                'type'                          => PaymentGatewayConst::TYPEADDMONEY,
                'trx_id'                        => $trx_id,
                'request_amount'                => $output['amount']->requested_amount,
                'payable'                       => $output['amount']->total_amount,
                'available_balance'             => $output['wallet']->balance + $output['amount']->requested_amount,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEADDMONEY, " ")) . " With " . $output['gateway']->name,
                'details'                       => "Recharge de compte par E-Billing",
                'status'                        => PaymentGatewayConst::STATUSPENDING,
                'attribute'                     => PaymentGatewayConst::SEND,
                'billing_id'                    => $bill_id,
                'created_at'                    => now(),
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return $id;
    }

    public function ebillingUpdateWalletBalance($output)
    {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    public function ebillingInsertCharges($output, $id)
    {
        DB::beginTransaction();
        try {
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $output['amount']->percent_charge,
                'fixed_charge'      => $output['amount']->fixed_charge,
                'total_charge'      => $output['amount']->total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => "Add Money",
                'message'       => "Your Wallet (" . $output['wallet']->currency->code . ") balance  has been added " . $output['amount']->requested_amount . ' ' . $output['wallet']->currency->code,
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function ebillingInsertDevice($output, $id)
    {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
        $mac = "";

        DB::beginTransaction();
        try {
            DB::table("transaction_devices")->insert([
                'transaction_id' => $id,
                'ip'            => $client_ip,
                'mac'           => $mac,
                'city'          => $location['city'] ?? "",
                'country'       => $location['country'] ?? "",
                'longitude'     => $location['lon'] ?? "",
                'latitude'      => $location['lat'] ?? "",
                'timezone'      => $location['timezone'] ?? "",
                'browser'       => $agent->browser() ?? "",
                'os'            => $agent->platform() ?? "",
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function ebillingRemoveTempData($token)
    {
        TemporaryData::where("identifier", $token)->delete();
    }

    public function str_reference($length)
    {
        $alphabet = "0123456789azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN";
        return substr(str_shuffle(str_repeat($alphabet, $length)), 0, $length);
    }
}

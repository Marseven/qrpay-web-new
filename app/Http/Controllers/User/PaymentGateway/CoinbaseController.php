<?php

namespace App\Http\Controllers\User\PaymentGateway;

use Illuminate\Http\Request;
use CoinbaseCommerce\ApiClient;
use App\Http\Controllers\Controller;

class CoinbaseController extends Controller
{
    public function coinbasePayment(Request $request)
    {
        dd($request);
        //Make sure you don't store your API Key in your source code!
        $apiClientObj = ApiClient::init();
        // $curl = curl_init();
        // $postFilds = array(
        //     'pricing_type' => 10,
        //     'metadata' => array('customer_id' => 10)
        // );
        // $postFilds = urldecode(http_build_query($postFilds));
        // curl_setopt_array(
        //     $curl,
        //     array(
        //         CURLOPT_URL => "https://api.commerce.coinbase.com/charges",
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => "",
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 30,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => "POST",
        //         CURLOPT_POSTFIELDS => $postFilds,
        //         CURLOPT_HTTPHEADER => array(
        //             "X-CC-Api-Key: Y8KPw7gKc02RNKpr",
        //             "X-CC-Version: 2018-03-22",
        //             "content-type: multipart/form-data"
        //         ),
        //     )
        // );
        // $response = curl_exec($curl);
        // $err = curl_error($curl);

        // curl_close($curl);
        // dd($response);
    }
}

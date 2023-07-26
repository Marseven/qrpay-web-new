<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\ReceiverCounty;
use App\Models\RemitanceBankDeposit;
use App\Models\RemitanceCashPickup;
use App\Models\Transaction;
use App\Models\UserWallet;
use App\Notifications\User\Remittance\Approved;
use App\Notifications\User\Remittance\BankTransferMail;
use App\Notifications\User\Remittance\Rejected;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class RemitanceController extends Controller
{
    ///========================Receiver countries start=============================================
        public function allCountries()
        {
            $page_title = "Receiver Countries";
            $allCountries = ReceiverCounty::latest()->paginate(20);
            return view('admin.sections.remitance.countries.index', compact(
                'page_title','allCountries'
            ));
        }
        public function storeCountry(Request $request) {

            $validator = Validator::make($request->all(),[
                'country'   => 'required|string',
                'name'      => 'required|string',
                'code'      => 'required|string|unique:receiver_counties',
                'symbol'    => 'required|string',
                'flag'      => 'nullable|image|mimes: jpg,png,jpeg,svg,webp',
                'rate'      => 'required',
                'mobile_code'      => 'required',
            ]);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with('modal','country_add');
            }
            $validated = $validator->validate();

            $validated['created_at']    = now();
            $validated['admin_id']      = Auth::user()->id;

            $validated = Arr::except($validated,['flag']);
            // insert_data
            try{
                $country = ReceiverCounty::create($validated);
            }catch(Exception $e) {
                return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
            }

            // Uplaod File
            if($request->hasFile('flag')) {
                try{
                    $image = get_files_from_fileholder($request,'flag');
                    $uploadFlag = upload_files_from_path_dynamic($image,'country-flag');

                    // Update Database
                    $country->update([
                        'flag'  => $uploadFlag,
                    ]);
                }catch(Exception $e) {
                    return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
                }
            }

            return back()->with(['success' => ['Country Saved Successfully!']]);
        }
        public function updateCountry(Request $request) {

            $target = $request->target ?? $request->currency_code;
            $country = ReceiverCounty::where('code',$target)->first();
            if(!$country) {
                return back()->with(['warning' => ['Country not found!']]);
            }
            $request->merge(['old_flag' =>$country->flag]);

            $validator = Validator::make($request->all(),[
                'currency_country'   => 'required|string',
                'currency_mobile_code'   => 'required',
                'currency_name'      => 'required|string',
                'currency_code'      => ['required','string'],
                'currency_symbol'    => 'required|string',
                'currency_rate'      => 'required|numeric',
                'currency_target'    => 'nullable|string',
            ]);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with('modal','country_edit');
            }
            $validated = $validator->validate();


            $validated = Arr::except($validated,['currency_flag']);

            if($request->hasFile('currency_flag')) {
                try{
                    $image = get_files_from_fileholder($request,'currency_flag');
                    $uploadFlag = upload_files_from_path_dynamic($image,'country-flag',$country->flag);
                    $validated['currency_flag'] = $uploadFlag;
                }catch(Exception $e) {
                    return back()->withErrors($validator)->withInput()->with(['error' => ['Image file upload faild!']]);
                }
            }
            $validated = replace_array_key($validated,"currency_");
            try{
            $country->update($validated);
            }catch(Exception $e) {
                return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
            }

            return back()->with(['success' => ['Successfully updated the information.']]);
        }
        public function deleteCountry(Request $request) {
            $validator = Validator::make($request->all(),[
                'target'        => 'required|string|exists:receiver_counties,code',
            ]);
            $validated = $validator->validate();
            $country = ReceiverCounty::where("code",$validated['target'])->first();

            try{
                $country->delete();
                delete_file(get_files_path('country-flag').'/'.$country->flag);
            }catch(Exception $e) {
                return back()->with(['error' => ['Something went worng! Please try again.']]);
            }

            return back()->with(['success' => ['Country deleted successfully!']]);
        }
        public function statusUpdateCountry(Request $request) {
            $validator = Validator::make($request->all(),[
                'status'                    => 'required|boolean',
                'data_target'               => 'required|string',
            ]);
            if ($validator->stopOnFirstFailure()->fails()) {
                $error = ['error' => $validator->errors()];
                return Response::error($error,null,400);
            }
            $validated = $validator->safe()->all();
            $currency_code = $validated['data_target'];

            $Country = ReceiverCounty::where('code',$currency_code)->first();
            if(!$Country) {
                $error = ['error' => ['Country record not found in our system.']];
                return Response::error($error,null,404);
            }

            try{
                $Country->update([
                    'status' => ($validated['status'] == true) ? false : true,
                ]);
            }catch(Exception $e) {
                $error = ['error' => ['Something went worng!. Please try again.']];
                return Response::error($error,null,500);
            }

            $success = ['success' => ['Country status updated successfully!']];
            return Response::success($success,null,200);
        }
        public function searchCountry(Request $request) {
            $validator = Validator::make($request->all(),[
                'text'  => 'required|string',
            ]);

            if($validator->fails()) {
                $error = ['error' => $validator->errors()];
                return Response::error($error,null,400);
            }

            $validated = $validator->validate();
            $allCountries = ReceiverCounty::search($validated['text'])->select()->limit(10)->get();
            return view('admin.components.search.country-search',compact(
                'allCountries',
            ));
        }

    ///========================Receiver countries end===============================================
    ///========================Bank Deposits end===============================================
        public function bankDeposits(){
            $page_title = "Bank Deposit Type";
            $banks = RemitanceBankDeposit::latest()->paginate(20);
            return view('admin.sections.remitance.banks.index', compact(
                'page_title','banks'
            ));
        }
        public function storeBankDeposit(Request $request){

            $validator = Validator::make($request->all(),[
                'name'      => 'required|string|max:200|unique:remitance_bank_deposits,name',
            ]);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with('modal','bank-deposit-add');
            }
            $validated = $validator->validate();
            $slugData = Str::slug($request->name);
            $makeUnique = RemitanceBankDeposit::where('alias',  $slugData)->first();
            if($makeUnique){
                return back()->with(['error' => [ $request->name.' '.'Bank Already Exists!']]);
            }
            $admin = Auth::user();

            $validated['admin_id']      = $admin->id;
            $validated['name']          = $request->name;
            $validated['alias']          = $slugData;
            try{
                RemitanceBankDeposit::create($validated);
                return back()->with(['success' => ['Bank Saved Successfully!']]);
            }catch(Exception $e) {
                return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
            }
        }
        public function bankDepositUpdate(Request $request){
            $target = $request->target;
            $bank = RemitanceBankDeposit::where('id',$target)->first();
            $validator = Validator::make($request->all(),[
                'name'      => 'required|string|max:200',
            ]);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with('modal','edit-bank');
            }
            $validated = $validator->validate();

            $slugData = Str::slug($request->name);
            $makeUnique = RemitanceBankDeposit::where('id',"!=",$bank->id)->where('alias',  $slugData)->first();
            if($makeUnique){
                return back()->with(['error' => [ $request->name.' '.'Bank Already Exists!']]);
            }
            $admin = Auth::user();
            $validated['admin_id']      = $admin->id;
            $validated['name']          = $request->name;
            $validated['alias']          = $slugData;

            try{
                $bank->fill($validated)->save();
                return back()->with(['success' => ['Bank Updated Successfully!']]);
            }catch(Exception $e) {
                return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
            }
        }

        public function bankDepositStatusUpdate(Request $request) {
            $validator = Validator::make($request->all(),[
                'status'                    => 'required|boolean',
                'data_target'               => 'required|string',
            ]);
            if ($validator->stopOnFirstFailure()->fails()) {
                $error = ['error' => $validator->errors()];
                return Response::error($error,null,400);
            }
            $validated = $validator->safe()->all();
            $bank_id = $validated['data_target'];

            $bank = RemitanceBankDeposit::where('id',$bank_id)->first();
            if(!$bank) {
                $error = ['error' => ['Bank record not found in our system.']];
                return Response::error($error,null,404);
            }

            try{
                $bank->update([
                    'status' => ($validated['status'] == true) ? false : true,
                ]);
            }catch(Exception $e) {
                $error = ['error' => ['Something went worng!. Please try again.']];
                return Response::error($error,null,500);
            }

            $success = ['success' => ['Bank status updated successfully!']];
            return Response::success($success,null,200);
        }
        public function bankDepositDelete(Request $request) {
            $validator = Validator::make($request->all(),[
                'target'        => 'required|string|exists:remitance_bank_deposits,id',
            ]);
            $validated = $validator->validate();
            $bank = RemitanceBankDeposit::where("id",$validated['target'])->first();

            try{
                $bank->delete();
            }catch(Exception $e) {
                return back()->with(['error' => ['Something went worng! Please try again.']]);
            }

            return back()->with(['success' => ['Bank deleted successfully!']]);
        }
        public function bankDepositSearch(Request $request) {
            $validator = Validator::make($request->all(),[
                'text'  => 'required|string',
            ]);

            if($validator->fails()) {
                $error = ['error' => $validator->errors()];
                return Response::error($error,null,400);
            }

            $validated = $validator->validate();

            $banks = RemitanceBankDeposit::search($validated['text'])->select()->limit(10)->get();
            return view('admin.components.search.bank-deposit-search',compact(
                'banks',
            ));
    }
    ///========================Bank Deposits end===============================================
    ///========================Cash Pickup end===============================================
        public function cashPickup(){
            $page_title = "Cash Pickup";
            $cashPickups = RemitanceCashPickup::latest()->paginate(20);
            return view('admin.sections.remitance.cash-pickup.index', compact(
                'page_title','cashPickups'
            ));
        }
        public function storeCashPickup(Request $request){

            $validator = Validator::make($request->all(),[
                'name'      => 'required|string|max:200|unique:remitance_cash_pickups,name',
            ]);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with('modal','cash-pickup-add');
            }
            $validated = $validator->validate();
            $slugData = Str::slug($request->name);
            $makeUnique = RemitanceCashPickup::where('alias',  $slugData)->first();
            if($makeUnique){
                return back()->with(['error' => [ $request->name.' '.'Cash Pickup Already Exists!']]);
            }
            $admin = Auth::user();

            $validated['admin_id']      = $admin->id;
            $validated['name']          = $request->name;
            $validated['alias']          = $slugData;
            try{
                RemitanceCashPickup::create($validated);
                return back()->with(['success' => ['Cash Pickup Saved Successfully!']]);
            }catch(Exception $e) {
                return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
            }
        }
        public function cashPickupUpdate(Request $request){
            $target = $request->target;
            $cashPickup = RemitanceCashPickup::where('id',$target)->first();
            $validator = Validator::make($request->all(),[
                'name'      => 'required|string|max:200',
            ]);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with('modal','edit-cash-pickup');
            }
            $validated = $validator->validate();

            $slugData = Str::slug($request->name);
            $makeUnique = RemitanceCashPickup::where('id',"!=",$cashPickup->id)->where('alias',  $slugData)->first();
            if($makeUnique){
                return back()->with(['error' => [ $request->name.' '.'Cash Pickup Already Exists!']]);
            }
            $admin = Auth::user();
            $validated['admin_id']      = $admin->id;
            $validated['name']          = $request->name;
            $validated['alias']          = $slugData;

            try{
                $cashPickup->fill($validated)->save();
                return back()->with(['success' => ['Cash Pickup Updated Successfully!']]);
            }catch(Exception $e) {
                return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
            }
        }

        public function cashPickuptatusUpdate(Request $request) {
            $validator = Validator::make($request->all(),[
                'status'                    => 'required|boolean',
                'data_target'               => 'required|string',
            ]);
            if ($validator->stopOnFirstFailure()->fails()) {
                $error = ['error' => $validator->errors()];
                return Response::error($error,null,400);
            }
            $validated = $validator->safe()->all();
            $pickup_id = $validated['data_target'];

            $pickup = RemitanceCashPickup::where('id',$pickup_id)->first();
            if(!$pickup) {
                $error = ['error' => ['Cash Pickup record not found in our system.']];
                return Response::error($error,null,404);
            }

            try{
                $pickup->update([
                    'status' => ($validated['status'] == true) ? false : true,
                ]);
            }catch(Exception $e) {
                $error = ['error' => ['Something went worng!. Please try again.']];
                return Response::error($error,null,500);
            }

            $success = ['success' => ['Status updated successfully!']];
            return Response::success($success,null,200);
        }
        public function cashPickuptDelete(Request $request) {
            $validator = Validator::make($request->all(),[
                'target'        => 'required|string|exists:remitance_cash_pickups,id',
            ]);
            $validated = $validator->validate();
            $cashPickup = RemitanceCashPickup::where("id",$validated['target'])->first();

            try{
                $cashPickup->delete();
            }catch(Exception $e) {
                return back()->with(['error' => ['Something went worng! Please try again.']]);
            }

            return back()->with(['success' => ['Cash Pickup deleted successfully!']]);
        }
        public function cashPickupSearch(Request $request) {
            $validator = Validator::make($request->all(),[
                'text'  => 'required|string',
            ]);

            if($validator->fails()) {
                $error = ['error' => $validator->errors()];
                return Response::error($error,null,400);
            }

            $validated = $validator->validate();

            $cashPickups = RemitanceCashPickup::search($validated['text'])->select()->limit(10)->get();
            return view('admin.components.search.cash-pick-search',compact(
                'cashPickups',
            ));
    }
    ///========================Cash Pickup end===============================================
    ///========================Remittance logs start=============================================
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = "All Logs";
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name',
        )->where('type', 'REMITTANCE')->where('attribute',"SEND")->latest()->paginate(20);

        return view('admin.sections.remitance.index', compact(
            'page_title',
            'transactions'
        ));
    }


    /**
     * Pending Add Money Logs View.
     * @return view $pending-remitance-logs
     */
    public function pending()
    {
        $page_title = "Pending Logs";
        $transactions = Transaction::with(
         'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name',
        )->where('type', 'REMITTANCE')->where('attribute',"SEND")->where('status', 2)->latest()->paginate(20);
        return view('admin.sections.remitance.index', compact(
            'page_title',
            'transactions'
        ));
    }


    /**
     * Complete Add Money Logs View.
     * @return view $complete-remitance-logs
     */
    public function complete()
    {
        $page_title = "Complete Logs";
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name',
        )->where('type', 'REMITTANCE')->where('attribute',"SEND")->where('status', 1)->latest()->paginate(20);
        return view('admin.sections.remitance.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Canceled Add Money Logs View.
     * @return view $canceled-remitance-logs
     */
    public function canceled()
    {
        $page_title = "Canceled Logs";
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name',
        )->where('type', 'REMITTANCE')->where('attribute',"SEND")->where('status',4)->latest()->paginate(20);
        return view('admin.sections.remitance.index', compact(
            'page_title',
            'transactions'
        ));
    }
    public function addMoneyDetails($id){

        $data = Transaction::where('id',$id)->with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name,alias,payment_gateway_id,currency_code,rate',
        )->where('type', 'REMITTANCE')->where('attribute',"SEND")->first();
        $page_title = "Remittance  details for".'  '.$data->trx_id;
        return view('admin.sections.remitance.details', compact(
            'page_title',
            'data'
        ));
    }

    public function approved(Request $request){

        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id',$request->id)->where('status',2)->where('type', 'REMITTANCE')->first();

        try{
            $receipient = $data->details->receiver;
            $notifyData = [
                'trx_id'  => $data->trx_id,
                'title'  => "Send Remittance to @" . $receipient->firstname.' '.@$receipient->lastname." (".@$receipient->mobile_code.@$receipient->mobile.")",
                'request_amount'  => getAmount($data->request_amount,4).' '.get_default_currency_code(),
                'exchange_rate'  => "1 " .get_default_currency_code().' = '.get_amount($data->details->to_country->rate,$data->details->to_country->code),
                'charges'   => getAmount( $data->charge->total_charge, 2).' ' .get_default_currency_code(),
                'payable'   =>  getAmount($data->payable,4).' ' .get_default_currency_code(),
                'sending_country'   => @$data->details->form_country,
                'receiving_country'   => @$data->details->to_country->country,
                'receiver_name'  =>  @$receipient->firstname.' '.@$receipient->lastname,
                'alias'  =>  ucwords(str_replace('-', ' ', @$receipient->alias)),
                'transaction_type'  =>  @$data->details->remitance_type,
                'receiver_get'   =>  getAmount($data->details->recipient_amount,4).' ' .$data->details->to_country->code,
                'status'  => "Success",
              ];
            //sender notifications
            $user = $data->user;
            $user->notify(new Approved($user,(object)$notifyData));
            $data->status = 1;
            $data->save();
            return redirect()->back()->with(['success' => ['Remittance request approved successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
    public function rejected(Request $request){

        $validator = Validator::make($request->all(),[
            'id' => 'required|integer',
            'reject_reason' => 'required|string|max:200',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id',$request->id)->where('status',2)->where('type', 'REMITTANCE')->first();

        try{
            $receipient = $data->details->receiver;
            $notifyData = [
                'trx_id'  => $data->trx_id,
                'title'  => "Send Remittance to @" . $receipient->firstname.' '.@$receipient->lastname." (".@$receipient->mobile_code.@$receipient->mobile.")",
                'request_amount'  => getAmount($data->request_amount,4).' '.get_default_currency_code(),
                'exchange_rate'  => "1 " .get_default_currency_code().' = '.get_amount($data->details->to_country->rate,$data->details->to_country->code),
                'charges'   => getAmount( $data->charge->total_charge, 2).' ' .get_default_currency_code(),
                'payable'   =>  getAmount($data->payable,4).' ' .get_default_currency_code(),
                'sending_country'   => @$data->details->form_country,
                'receiving_country'   => @$data->details->to_country->country,
                'receiver_name'  =>  @$receipient->firstname.' '.@$receipient->lastname,
                'alias'  =>  ucwords(str_replace('-', ' ', @$receipient->alias)),
                'transaction_type'  =>  @$data->details->remitance_type,
                'receiver_get'   =>  getAmount($data->details->recipient_amount,4).' ' .$data->details->to_country->code,
                'status'  => "Rejected",
                'reason'  => $request->reject_reason,
              ];
            //sender notifications
            $user = $data->user;
            $user->notify(new Rejected($user,(object)$notifyData));
            $userWallet = UserWallet::where('user_id',$data->user_id)->first();
            $userWallet->balance += $data->payable;
            $userWallet->save();
            $up['status'] = 4;
            $up['reject_reason'] = $request->reject_reason;
            $up['available_balance'] = $userWallet->balance;
            $data->fill($up)->save();

            return redirect()->back()->with(['success' => ['Remittance request rejected successfully']]);
        }catch(Exception $e){
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }

    ///========================Remittance logs end===============================================


}

<?php

namespace App\Http\Controllers\Admin;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Notifications\User\TicketPay\Approved;
use App\Notifications\User\TicketPay\Rejected;
use Illuminate\Http\Request;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    //
    //==============================================category start================================================
    public function ticketPayList()
    {
        $page_title = "Ticket Pay Type";
        $allTicket = Ticket::orderByDesc('id')->paginate(10);
        return view('admin.sections.ticket-pay.ticket', compact(
            'page_title',
            'allTicket',
        ));
    }
    public function storeTicket(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'label'      => 'required|string|max:200|unique:tickets,label',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'ticket-add');
        }
        $validated = $validator->validate();

        $admin = Auth::user();

        $validated['admin_id']      = $admin->id;
        $validated['label']          = $request->label;
        $validated['price']         = $request->price;
        try {
            Ticket::create($validated);
            return back()->with(['success' => ['Ticket Type Saved Successfully!']]);
        } catch (Exception $e) {
            return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
        }
    }
    public function ticketUpdate(Request $request)
    {
        $target = $request->target;
        $ticket = Ticket::where('id', $target)->first();
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:200',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'edit-ticket');
        }
        $validated = $validator->validate();

        $admin = Auth::user();
        $validated['label']  = $request->label;
        $validated['price']  = $request->price;

        try {
            $ticket->fill($validated)->save();
            return back()->with(['success' => ['Ticket Type Updated Successfully!']]);
        } catch (Exception $e) {
            return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
        }
    }

    public function ticketStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'                    => 'required|boolean',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Ticket::error($error, null, 400);
        }
        $validated = $validator->safe()->all();
        $ticket_id = $validated['data_target'];

        $ticket = Ticket::where('id', $ticket_id)->first();
        if (!$ticket) {
            $error = ['error' => ['Ticket Type record not found in our system.']];
            return Response::error($error, null, 404);
        }

        try {
            $ticket->update([
                'status' => ($validated['status'] == true) ? false : true,
            ]);
        } catch (Exception $e) {
            $error = ['error' => ['Something went worng!. Please try again.']];
            return Response::error($error, null, 500);
        }

        $success = ['success' => ['Ticket Type status updated successfully!']];
        return Response::success($success, null, 200);
    }

    public function ticketDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target'        => 'required|string|exists:tickets,id',
        ]);
        $validated = $validator->validate();
        $ticket = Ticket::where("id", $validated['target'])->first();

        try {
            $ticket->delete();
        } catch (Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }

        return back()->with(['success' => ['Ticket Type deleted successfully!']]);
    }

    public function ticketSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text'  => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error, null, 400);
        }

        $validated = $validator->validate();

        $allTicket = Ticket::search($validated['text'])->select()->limit(10)->get();
        return view('admin.components.search.ticket-type-search', compact(
            'allTicket',
        ));
    }
    //================================================category end=============================

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = "Toutes les transactions";
        $transactions = Transaction::with(
            'user:id,firstname,lastname,email,username,full_mobile',

        )->where('type', PaymentGatewayConst::TICKETPAY)->latest()->paginate(20);
        return view('admin.sections.ticket-pay.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Display All Pending Logs
     * @return view
     */
    public function pending()
    {
        $page_title = "Transactions en cours";
        $transactions = Transaction::with(
            'user:id,firstname,lastname,email,username,full_mobile',

        )->where('type', PaymentGatewayConst::TICKETPAY)->where('status', 2)->latest()->paginate(20);
        return view('admin.sections.ticket-pay.index', compact(
            'page_title',
            'transactions'
        ));
    }


    /**
     * Display All Complete Logs
     * @return view
     */
    public function complete()
    {
        $page_title = "Transactions Terminées";
        $transactions = Transaction::with(
            'user:id,firstname,lastname,email,username,full_mobile',
        )->where('type', PaymentGatewayConst::TICKETPAY)->where('status', 1)->latest()->paginate(20);
        return view('admin.sections.ticket-pay.index', compact(
            'page_title',
            'transactions'
        ));
    }


    /**
     * Display All Canceled Logs
     * @return view
     */
    public function canceled()
    {
        $page_title = "Transactions Annulées";
        $transactions = Transaction::with(
            'user:id,firstname,lastname,email,username,full_mobile',
        )->where('type', PaymentGatewayConst::TICKETPAY)->where('status', 4)->latest()->paginate(20);
        return view('admin.sections.ticket-pay.index', compact(
            'page_title',
            'transactions'
        ));
    }
    public function details($id)
    {

        $data = Transaction::where('id', $id)->with(
            'user:id,firstname,lastname,email,username,full_mobile',
        )->where('type', PaymentGatewayConst::TICKETPAY)->first();
        $page_title = "Ticket Pay details for" . '  ' . $data->trx_id . ' (' . $data->details->ticket_type_name . ")";
        return view('admin.sections.ticket-pay.details', compact(
            'page_title',
            'data'
        ));
    }
    public function approved(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id', $request->id)->where('status', 2)->where('type', PaymentGatewayConst::TICKETPAY)->first();

        $up['status'] = 1;
        try {
            $approved = $data->fill($up)->save();
            if ($approved) {

                //notification
                $notification_content = [
                    'title'         => "Ticket Pay",
                    'message'       => "Your Ticket Pay request approved by admin " . getAmount($data->request_amount, 2) . ' ' . get_default_currency_code() . " & Ticket Number is: " . @$data->details->ticket_number . " successful.",
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

            return redirect()->back()->with(['success' => ['Ticket Pay request approved successfully']]);
        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
    public function rejected(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'reject_reason' => 'required|string:max:200',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data = Transaction::where('id', $request->id)->where('status', 2)->where('type', PaymentGatewayConst::TICKETPAY)->first();

        try {
            //user wallet
            if ($data->user_id != null) {

                $userWallet = UserWallet::where('user_id', $data->user_id)->first();
                $userWallet->balance +=  $data->payable;
                $userWallet->save();
            }
            $up['status'] = 4;
            $up['reject_reason'] = $request->reject_reason;
            $up['available_balance'] = $userWallet->balance;
            $rejected =  $data->fill($up)->save();
            if ($rejected) {

                //user notifications
                $notification_content = [
                    'title'         => "Ticket Pay",
                    'message'       => "Your Ticket Pay request rejected by admin " . getAmount($data->request_amount, 2) . ' ' . get_default_currency_code() . " & Ticket Number is: " . @$data->details->ticket_number,
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
                        'status'  => "Rejected",
                        'reason'  => $request->reject_reason,
                    ];
                    $user = $data->user;
                    $user->notify(new Rejected($user, (object)$notifyData));
                    UserNotification::create([
                        'type'      => NotificationConst::TICKET_PAY,
                        'user_id'  =>  $data->user_id,
                        'message'   => $notification_content,
                    ]);
                    DB::commit();
                }
            }
            return redirect()->back()->with(['success' => ['Ticket Pay request rejected successfully']]);
        } catch (Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
    }
}

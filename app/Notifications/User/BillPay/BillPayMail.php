<?php

namespace App\Notifications\User\BillPay;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class BillPayMail extends Notification
{
    use Queueable;

    public $user;
    public $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user,$data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {


        $user = $this->user;
        $data = $this->data;
        $trx_id = $this->data->trx_id;
        $date = Carbon::now();
        $dateTime = $date->format('Y-m-d h:i:s A');

        return (new MailMessage)
                    ->greeting("Hello ".$user->fullname." !")
                    ->subject("Bill Pay For ". $data->bill_type.' ('.$data->bill_number.' )')
                    ->line("Your bill pay request send to admin successfully  for ".$data->bill_type." , details of bill pay:")
                    ->line("Transaction Id: " .$trx_id)
                    ->line("Request Amount: " . getAmount($data->request_amount,4).' '.get_default_currency_code())
                    ->line("Fees & Charges: " . getAmount($data->charges,4).' '.get_default_currency_code())
                    ->line("Total Payable Amount: " . get_amount($data->payable,get_default_currency_code(),'4'))
                    ->line("Status: ". $data->status)
                    ->line("Date And Time: " .$dateTime)
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

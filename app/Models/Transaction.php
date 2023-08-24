<?php

namespace App\Models;

use App\Constants\PaymentGatewayConst;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;
use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $appends = ['stringStatus'];


    protected $casts = [
        'admin_id' => 'integer',
        'user_id' => 'integer',
        'user_wallet_id' => 'integer',
        'merchant_id' => 'integer',
        'merchant_wallet_id' => 'integer',
        'payment_gateway_currency_id' => 'integer',
        'trx_id' => 'string',
        'request_amount' => 'double',
        'payable' => 'double',
        'available_balance' => 'double',
        'remark' => 'string',
        'status' => 'integer',
        'details' => 'object',
        'reject_reason' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user_wallet()
    {
        return $this->belongsTo(UserWallet::class, 'user_wallet_id');
    }
    public function agent_wallet()
    {
        return $this->belongsTo(AgentWallet::class, 'agent_wallet_id');
    }
    public function merchant_wallet()
    {
        return $this->belongsTo(MerchantWallet::class, 'merchant_wallet_id');
    }
    public function creator()
    {
        if ($this->user_id != null) {
            return $this->user();
        } else if ($this->agent_id != null) {
            return $this->agent();
        } else if ($this->merchant_id != null) {
            return $this->merchant();
        }
    }
    public function creator_wallet()
    {
        if ($this->user_id != null) {
            return $this->user_wallet();
        } else if ($this->agent_id != null) {
            return $this->agent_wallet();
        } else if ($this->merchant_id != null) {
            return $this->merchant_wallet();
        }
    }


    public function currency()
    {
        return $this->belongsTo(PaymentGatewayCurrency::class, 'payment_gateway_currency_id');
    }

    public function scopeAuth($query)
    {
        $query->where("user_id", auth()->user()->id);
    }
    public function scopeMerchantAuth($query)
    {
        $query->where("merchant_id", auth()->user()->id);
    }
    public function scopeAgentAuth($query)
    {
        $query->where("agent_id", auth()->user()->id);
    }

    public function getStringStatusAttribute()
    {
        $status = $this->status;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if ($status == PaymentGatewayConst::STATUSSUCCESS) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => "Success",
            ];
        } else if ($status == PaymentGatewayConst::STATUSPENDING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => "Pending",
            ];
        } else if ($status == PaymentGatewayConst::STATUSHOLD) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => "Hold",
            ];
        } else if ($status == PaymentGatewayConst::STATUSREJECTED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => "Rejected",
            ];
        }

        return (object) $data;
    }

    public function charge()
    {
        return $this->hasOne(TransactionCharge::class, "transaction_id", "id");
    }

    public function scopeAddMoney($query)
    {
        return $query->where("type", PaymentGatewayConst::TYPEADDMONEY);
    }

    public function scopeMoneyOut($query)
    {
        return $query->where("type", PaymentGatewayConst::TYPEMONEYOUT);
    }
    public function scopeSenMoney($query)
    {
        return $query->where("type", PaymentGatewayConst::TYPETRANSFERMONEY);
    }
    public function scopeBillPay($query)
    {
        return $query->where("type", PaymentGatewayConst::BILLPAY);
    }
    public function scopeTicketPay($query)
    {
        return $query->where("type", PaymentGatewayConst::TICKETPAY);
    }
    public function scopeMobileTopup($query)
    {
        return $query->where("type", PaymentGatewayConst::MOBILETOPUP);
    }
    public function scopeVirtualCard($query)
    {
        return $query->where("type", PaymentGatewayConst::VIRTUALCARD);
    }
    public function scopeRemitance($query)
    {
        return $query->where("type", PaymentGatewayConst::SENDREMITTANCE);
    }
    public function scopeMakePayment($query)
    {
        return $query->where("type", PaymentGatewayConst::TYPEMAKEPAYMENT);
    }

    public function scopeSearch($query, $data)
    {
        $data = Str::slug($data);
        return $query->where("trx_id", "like", "%" . $data . "%")
            ->orWhere('type', 'like', '%' . $data . '%')
            ->orderBy('id', "DESC");
    }

    public function scopeMoneyExchange($query)
    {
        return $query->where("type", PaymentGatewayConst::TYPEMONEYEXCHANGE);
    }

    public function isAuthUser()
    {
        if ($this->user_id === auth()->user()->id) return true;
        return false;
    }
    public function isAuthUserMerchant()
    {
        if ($this->merchant_id === auth()->user()->id) return true;
        return false;
    }
    public function isAuthUserAgent()
    {
        if ($this->agent_id === auth()->user()->id) return true;
        return false;
    }
}

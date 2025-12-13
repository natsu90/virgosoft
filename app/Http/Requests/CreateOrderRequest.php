<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TradeSymbol;
use App\Enums\OrderSide;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer',
            'symbol' => ['required', 'string', new Enum(TradeSymbol::class)],
            'side' => ['required', 'string', new Enum(OrderSide::class)],
            'price' => 'required|numeric|decimal:0,4',
            'amount' => 'required|numeric|decimal:0,8'
        ];
    }
}

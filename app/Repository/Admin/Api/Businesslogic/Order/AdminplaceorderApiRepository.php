<?php

namespace App\Repository\Admin\Api\Businesslogic\Order;

use App\Http\Resources\Admin\Order\OrderholdbyuuidResource;
use App\Http\Resources\Admin\Order\OrderholdCollection;
use App\Models\Admin\Customer\Customer;
use App\Models\Admin\Product\Product;
use App\Models\Admin\Salehold\Salehold;
use App\Models\Admin\Salehold\Saleholditem;
use App\Models\Admin\Sale\Sale;
use App\Models\Admin\Sale\Saleitem;
use App\Models\Admin\Settings\Generalsettings\Companysetting;
use App\Repository\Admin\Api\Interfacelayer\Order\IAdminplaceorderApiRepository;
use Illuminate\Support\Facades\Log;

class AdminplaceorderApiRepository implements IAdminplaceorderApiRepository
{
    public function adminplaceorder()
    {
        $salesdetails = collect(json_decode((request()->salesdetails)))->toArray();
        Log::info('Sales details');
        Log::info(json_encode($salesdetails));
        Log::info('----------------------------------------------------------------');
        Log::info('Customer uuid');
        Log::info($salesdetails['customer_uuid']);
        $customer = Customer::where('uuid', $salesdetails['customer_uuid'])->first();
        if (!$customer) {
            $customer = Customer::updateorCreate([
                'phone' => $salesdetails['customer_phone'],
            ], [
                'name' => $salesdetails['customer_name'],
            ]);
        }
        Log::info('Customer details');
        Log::info(json_encode($customer));
        if (!$salesdetails['is_hold']) {
            $companysetting = Companysetting::first();

            if ($salesdetails['sales_uuid']) {
                $sale = Sale::where('uuid', $salesdetails['sales_uuid'])->first();
                if ($sale->grandtotal >= $salesdetails['grandtotal']) {
                    $sale->amountcdable()
                        ->create([
                            'credit' => 0,
                            'debit' => ($sale->grandtotal - $salesdetails['grandtotal']),
                            'balance' => $companysetting->balance - ($sale->grandtotal - $salesdetails['grandtotal']),
                            'c_or_d' => 'D',
                        ]);
                    $companysetting->balance = $companysetting->balance - ($sale->grandtotal - $salesdetails['grandtotal']);
                } else {
                    $sale->amountcdable()
                        ->create([
                            'credit' => ($salesdetails['grandtotal'] - $sale->grandtotal),
                            'debit' => 0,
                            'balance' => $companysetting->balance + ($salesdetails['grandtotal'] - $sale->grandtotal),
                            'c_or_d' => 'C',
                        ]);
                    $companysetting->balance = $companysetting->balance + ($salesdetails['grandtotal'] - $sale->grandtotal);
                }
                $companysetting->save();
            }

            $sale = Sale::updateorCreate([
                'uuid' => $salesdetails['sales_uuid'],
            ], [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'sub_total' => isset($salesdetails['sub_total']) && $salesdetails['sub_total'] != '' ? $salesdetails['sub_total'] : null,
                'discount' => isset($salesdetails['discount']) && $salesdetails['discount'] != '' ? $salesdetails['discount'] : null,
                'extra_charges' => isset($salesdetails['extra_charges']) && $salesdetails['extra_charges'] != '' ? $salesdetails['extra_charges'] : null,
                'received_amount' => isset($salesdetails['received_amount']) && $salesdetails['received_amount'] != '' ? $salesdetails['received_amount'] : null,
                'remaining_amount' => isset($salesdetails['remaining_amount']) && $salesdetails['remaining_amount'] != '' ? $salesdetails['remaining_amount'] : null,
                'total' => isset($salesdetails['total']) && $salesdetails['total'] != '' ? $salesdetails['total'] : null,
                // 'taxamt' => isset($salesdetails['taxamt']) && $salesdetails['taxamt'] != '' ? $salesdetails['taxamt'] : null,
                // 'taxableamt' => isset($salesdetails['taxableamt']) && $salesdetails['taxableamt'] != '' ? $salesdetails['taxableamt'] : null,
                // 'cgst' => isset($salesdetails['cgst']) && $salesdetails['cgst'] != '' ? $salesdetails['cgst'] : null,
                // 'sgst' => isset($salesdetails['sgst']) && $salesdetails['sgst'] != '' ? $salesdetails['sgst'] : null,
                // 'vat' => isset($salesdetails['vat']) && $salesdetails['vat'] != '' ? $salesdetails['vat'] : null,
                'roundoff' => isset($salesdetails['roundoff']) && $salesdetails['roundoff'] != '' ? $salesdetails['roundoff'] : null,
                'grandtotal' => isset($salesdetails['grandtotal']) && $salesdetails['grandtotal'] != '' ? $salesdetails['grandtotal'] : null,
                'mode' => isset($salesdetails['mode']) && $salesdetails['mode'] != '' ? $salesdetails['mode'] : null,
                'source_type' => isset($salesdetails['source_type']) && $salesdetails['source_type'] != '' ? $salesdetails['source_type'] : null,
                'total_items' => sizeof($salesdetails['salesitem']),
            ]);
            Log::info('Sale');
            Log::info(json_encode($sale));
            if ($salesdetails['sales_uuid'] == null || $salesdetails['sales_uuid'] == '') {
                $sale->amountcdable()
                    ->create([
                        'credit' => $salesdetails['grandtotal'],
                        'debit' => 0,
                        'balance' => $companysetting->balance + $salesdetails['grandtotal'],
                        'c_or_d' => 'C',
                    ]);

                $companysetting->balance = $companysetting->balance + $salesdetails['grandtotal'];
                $companysetting->save();
            }

            Log::info('----------------------------------------------------------------');
            foreach ($salesdetails['salesitem'] as $key => $value) {
                Log::info(json_encode($value));
                Log::info('----------------------------------------------------------------');
                $product = Product::where('uuid', $value->product_uuid)->first();

                if ($value->saleitem_uuid) {

                    $saleitem = Saleitem::where('uuid', $value->saleitem_uuid)->first();

                    if ($saleitem->quantity != $value->quantity) {
                        if ($saleitem->quantity > $value->quantity) {

                            $productquantity = ($saleitem->quantity - $value->quantity);

                            $product->stock = $product->stock + $productquantity;
                            $sale->stockcdable()
                                ->create([
                                    'credit' => $productquantity,
                                    'debit' => 0,
                                    'balance' => $product->stock,
                                    'c_or_d' => 'C',
                                    'product_id' => $product->id,
                                ]);
                        } else {
                            $productquantity = ($value->quantity - $saleitem->quantity);
                            $product->stock = $product->stock - $productquantity;
                            $sale->stockcdable()
                                ->create([
                                    'credit' => 0,
                                    'debit' => $productquantity,
                                    'balance' => $product->stock,
                                    'c_or_d' => 'D',
                                    'product_id' => $product->id,
                                ]);
                        }
                    }
                } else {

                    $productquantity = $value->quantity;
                    $product->stock = $product->stock - $value->quantity;
                    $sale->stockcdable()
                        ->create([
                            'credit' => 0,
                            'debit' => $productquantity,
                            'balance' => $product->stock,
                            'c_or_d' => 'D',
                            'product_id' => $product->id,
                        ]);
                }

                $product->save();

                Saleitem::updateorCreate([
                    'uuid' => $value->saleitem_uuid,
                    'sale_id' => $sale->id,
                ], [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'purchaseprice' => $product->purchaseprice,
                    'sellingprice' => $product->sellingprice,
                    'price' => $value->price,
                    'quantity' => $value->quantity,
                    'returnable_quantity' => $value->quantity,
                    // 'taxamt' => $value->taxamt ? $value->taxamt : null,
                    // 'taxable' => $value->taxable ? $value->taxable : null,
                    // 'cgst' => $value->cgst ? $value->cgst : null,
                    // 'cgstamt' => $value->cgstamt ? $value->cgstamt : null,
                    // 'sgst' => $value->sgst ? $value->sgst : null,
                    // 'sgstamt' => $value->sgstamt ? $value->sgstamt : null,
                    // 'vat' => $value->vat ? $value->vat : null,
                    // 'vatamt' => $value->vatamt ? $value->vatamt : null,
                    'total' => $value->total,
                    'grandtotal' => $value->total,
                ]);
            }

            if (isset($salesdetails['saleshold_uuid']) && $salesdetails['saleshold_uuid'] != '') {
                $salehold = Salehold::where('uuid', $salesdetails['saleshold_uuid'])->first();
                Saleholditem::where('salehold_id', $salehold->id)->delete();
                $salehold->delete();
            }
            return [true, null, 'adminplaceorder'];
        } else {
            $salehold = Salehold::updateorCreate([
                'uuid' => $salesdetails['saleshold_uuid'],
                'reference_id' => $salesdetails['reference_id'],
            ], [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'source_type' => isset($salesdetails['source_type']) && $salesdetails['source_type'] != '' ? $salesdetails['source_type'] : null,
            ]);

            foreach ($salesdetails['salesitem'] as $key => $value) {
                Log::info(json_encode($value));
                Log::info('----------------------------------------------------------------');
                $product = Product::where('uuid', $value->product_uuid)->first();
                Saleholditem::updateorCreate([
                    'uuid' => $value->saleholditem_uuid,
                    'salehold_id' => $salehold->id,
                ], [
                    'product_id' => $product->id,
                    'quantity' => $value->quantity,
                ]);
            }
            return [true, null, 'adminplaceorderhold'];
        }
    }

    public function adminholdorderlist()
    {
        return [true,
            new OrderholdCollection(Salehold::where('user_id', auth()->user()->id)
                    ->where(fn($q) =>
                        $q->where('uniqid', 'like', '%' . request('search') . '%')
                            ->orWhere('reference_id', 'like', '%' . request('search') . '%')
                            ->orWhere('customer_name', 'like', '%' . request('search') . '%')
                            ->orWhere('customer_phone', 'like', '%' . request('search') . '%')
                    )
                    ->latest()
                    ->paginate(10)),
            'adminholdorderlist'];
    }

    public function admingetholdorder()
    {
        return [true,
            OrderholdbyuuidResource::collection(Salehold::where('uuid', request('uuid'))->get()),
            'admingetholdorder'];
    }

    public function admindeleteholdorder()
    {
        $salehold = Salehold::where('uuid', request('uuid'))->first();
        Saleholditem::where('salehold_id', $salehold->id)->delete();
        $salehold->delete();
        return [true, null, 'admindeleteholdorder'];
    }

    public function admindeleteholdorderitem()
    {
        Saleholditem::where('uuid', request('uuid'))->delete();

        return [true, null, 'admindeleteholdorderitem'];
    }

    public function admindeleteorderitem()
    {
        Saleitem::where('uuid', request('uuid'))->delete();

        return [true, null, 'admindeleteorderitem'];
    }
}

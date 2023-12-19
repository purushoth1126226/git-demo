<?php

namespace App\Http\Controllers\Admin\Api\Salereturn;

use App\Http\Controllers\Helper\BaseApiController;
use App\Models\Admin\Sale\Saleitem;
use App\Repository\Admin\Api\Interfacelayer\Salesreturn\IAdminsalesreturnApiRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
use Validator;

class AdminsalereturneApiController extends BaseApiController
{

    public $salereturn;

    public function __construct(IAdminsalesreturnApiRepository $salereturn)
    {
        $this->salereturn = $salereturn;
    }

    public function admincreatesalereturn(Request $request)
    {
        try {
            $requestdata = collect(json_decode(request()->salesreturndetails, true))->toArray();
            Log::info('Request Sales');
            Log::info(json_encode($requestdata));

            Log::info('----------------------------------------------------------------');
            foreach ($requestdata['salesreturnitem'] as $key => $value) {
                $saleitem = Saleitem::where('uuid', $value['saleitem_uuid'])->first();
                $requestdata['salesreturnitem'][$key]['quantity'] = $saleitem->quantity;
                $requestdata['salesreturnitem'][$key]['returnable_quantity'] = $saleitem->returnable_quantity;
            }
            $validator = Validator::make($requestdata, [

                'sales_uuid' => 'bail|required|max:50',
                'customer_uuid' => 'bail|required|max:50',
                "return_note" => "bail|required|min:3|max:255",
                'total' => 'bail|required',
                'roundoff' => 'bail|nullable',
                'grandtotal' => 'bail|required',
                'source_type' => 'bail|required|integer|min:1|max:5',

                'salesreturnitem.*.saleitem_uuid' => 'bail|required|max:50',
                'salesreturnitem.*.product_uuid' => 'bail|required|max:50',
                'salesreturnitem.*.total' => 'bail|required',
                'salesreturnitem.*.return_quantity' => 'bail|required|lte:salesreturnitem.*.returnable_quantity|lte:salesreturnitem.*.quantity|gt:0',
            ], [
                'salesreturnitem.*.return_quantity.required' => 'Enter Quantity',
                'salesreturnitem.*.return_quantity.lte' => 'Return Quantity Exceeds',
                'salesreturnitem.*.return_quantity.gt' => 'Invalid Quantity',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                return response()->error([false, $validator->errors()], 404);
            }
            return $this->callrepofuncion('salereturn', 'admincreatesalereturn', null, true);

        } catch (Exception $e) {
            return $this->exceptionone(null, 'admincreatesalereturn', $e->getMessage(), true, 404);
        } catch (QueryException $e) {
            return $this->exceptiontwo(null, 'admincreatesalereturn', $e->getMessage(), true, 404);
        } catch (PDOException $e) {
            return $this->exceptionthree(null, 'admincreatesalereturn', $e->getMessage(), true, 404);
        }
    }

}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Response;
use Image;
use DB;
use Hash;
use Carbon\Carbon;
use Crypt;
use Mail;
use PDF;
use App\Helpers\Helper;

use App\Models\admin\Mst_store;
use App\Models\admin\Mst_Tax;
use App\Models\admin\Trn_DeliveryBoyDeviceToken;

use App\Models\admin\Mst_store_product;
use App\Models\admin\Mst_business_types;

use App\Models\admin\Mst_attribute_group;
use App\Models\admin\Mst_attribute_value;

use App\Models\admin\Mst_categories;
use App\Models\admin\Mst_store_agencies;

use App\Models\admin\Mst_product_image;

use App\Models\admin\Trn_store_order;
use App\Models\admin\Trn_store_order_item;
use App\Models\admin\Trn_order_invoice;
use App\Models\admin\Trn_store_customer;
use App\Models\admin\Sys_store_order_status;
use App\Models\admin\Mst_store_link_delivery_boy;
use App\Models\admin\Mst_order_link_delivery_boy;
use App\Models\admin\Sys_DeliveryStatus;
use App\Models\admin\Mst_delivery_boy;
use App\Models\admin\Mst_store_product_varient;
use App\Models\admin\Mst_StockDetail;
use App\Models\admin\Trn_configure_points;
use App\Models\admin\Trn_customer_reward;
use App\Models\admin\Trn_CustomerDeviceToken;
use App\Models\admin\Trn_StoreDeliveryTimeSlot;


use App\Models\admin\Trn_customerAddress;
use App\Models\admin\Trn_DeliveryBoyLocation;



use App\Models\admin\Trn_OrderPaymentTransaction;
use App\Models\admin\Trn_OrderSplitPayments;
use App\Trn_wallet_log;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

    public function makeStoreCustomer(Request $request)
    {
        Trn_store_customer::where('customer_id', 3)->update(['customer_first_name' => 'Store Customer', 'customer_last_name' => null, 'customer_mobile_number' => '000000000']);
        echo "done";
    }
    public function listOrders(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $store_id = $request->store_id;
                if ($query = Trn_store_order::select(
                    'order_id',
                    'order_number',
                    'delivery_address',
                    'created_at',
                    'status_id',
                    'customer_id',
                    'product_total_amount',
                    'order_type',
                    'isRefunded',
                    'refundStatus',
                    'refundId'
                )->where('store_id', $request->store_id)) {
                    if (isset($request->order_number)) {
                        $query->where('order_number', 'LIKE', "%{$request->order_number}%");
                    }
                    if (isset($request->from) && isset($request->to)) {
                        $query->whereDate('created_at', '>=', $request->from)->whereDate('created_at', '<=', $request->to);
                    }
                    if (isset($request->from) && !isset($request->to)) {
                        $query->whereDate('created_at', '>=', $request->from);
                    }
                    if (!isset($request->from) && isset($request->to)) {
                        $query->whereDate('created_at', '<=', $request->to);
                    }

                    if (isset($request->page)) {
                        $data['orderDetails'] = $query->orderBy('order_id', 'DESC')->paginate(10, ['data'], 'page', $request->page);
                    } else {
                        $data['orderDetails'] = $query->orderBy('order_id', 'DESC')->paginate(10);
                    }


                    foreach ($data['orderDetails'] as $order) {


                        $customerData = Trn_store_customer::find($order->customer_id);
                        if ($order->order_type == 'POS') {
                            $order->customer_name = 'Store Customer';
                        } else {
                            $cusAdd = Trn_customerAddress::find($order->delivery_address);
                            $order->customer_name = @$cusAdd->name;
                            if (!isset($cusAdd->name))
                                $order->customer_name = @$customerData->customer_first_name . " " . @$customerData->customer_last_name;
                        }



                        if (isset($order->status_id)) {
                            $statusData = Sys_store_order_status::find(@$order->status_id);
                            $order->status_name = @$statusData->status;
                        } else {
                            $order->status_name = null;
                        }
                        $order->order_date = Carbon::parse($order->created_at)->format('d-m-Y');
                        $order->invoice_link =  url('get/invoice/' . Crypt::encryptString($order->order_id));
                        $order->item_list_link = url('item/list/' . Crypt::encryptString($order->order_id));
                    }
                    $data['status'] = 1;
                    $data['message'] = "success";
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function viewOrder(Request $request)
    {
        $data = array();

        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'order_id'          => 'required',
                    ],
                    [
                        'order_id.required'        => 'Order not found',
                    ]
                );

                if (!$validator->fails() && Trn_store_order::find($request->order_id)) {
                    $order_id = $request->order_id;
                    $store_id = $request->store_id;
                    // dd(Trn_store_order::select('order_id','time_slot','delivery_boy_id','order_note','payment_type_id','order_number','created_at','status_id','customer_id','product_total_amount')->where('order_id',$order_id)->where('store_id',$store_id)->first());

                    if ($data['orderDetails']  = Trn_store_order::select("*")->where('order_id', $order_id)->where('store_id', $store_id)->first()) {

                        if (!isset($data['orderDetails']->order_note))
                            $data['orderDetails']->order_note = '';

                        if (!isset($data['orderDetails']->reward_points_used))
                            $data['orderDetails']->reward_points_used = "0";

                        if (!isset($data['orderDetails']->amount_reduced_by_rp))
                            $data['orderDetails']->amount_reduced_by_rp = "0";



                        if (!isset($data['orderDetails']->delivery_accept))
                            $data['orderDetails']->delivery_accept = '0';

                        if (isset($data['orderDetails']->customer_id)) {
                            $customerData = Trn_store_customer::find($data['orderDetails']->customer_id);
                            //    dd($customerData);
                            // $data['orderDetails']->customer_name = $customerData->customer_first_name." ".$customerData->customer_last_name;

                            $data['orderDetails']->customer_mobile = @$customerData->customer_mobile_number;

                            if ($data['orderDetails']->order_type == 'POS') {
                                $customerAddressData = Trn_customerAddress::where('customer_id', $data['orderDetails']->customer_id)->where('default_status', 1)->first();
                            } else {
                                $customerAddressData = Trn_customerAddress::find($data['orderDetails']->delivery_address);
                            }

                            if ($data['orderDetails']->order_type == 'POS') {

                                $data['orderDetails']->customer_name = 'Store Customer';
                            } else {

                                if (isset($customerAddressData->name)) {
                                    $data['orderDetails']->customer_name = @$customerAddressData->name;
                                } else {
                                    $data['orderDetails']->customer_name = $customerData->customer_first_name . " " . $customerData->customer_last_name;
                                }
                            }

                            if (isset($customerAddressData->phone))
                                $data['orderDetails']->customer_mobile = @$customerAddressData->phone;

                            if (isset($customerAddressData->place))
                                $data['orderDetails']->place = @$customerAddressData->place;
                            else
                                $data['orderDetails']->place =    '';

                            if (isset($customerAddressData->districtFunction->district_name))
                                $data['orderDetails']->district_name =   @$customerAddressData->districtFunction->district_name;
                            else
                                $data['orderDetails']->district_name =    '';

                            if (isset($customerAddressData->stateFunction->state_name))
                                $data['orderDetails']->state_name =     @$customerAddressData->stateFunction->state_name;
                            else
                                $data['orderDetails']->state_name =    '';

                            if (isset($customerAddressData->stateFunction->country->country_name))
                                $data['orderDetails']->country_name =     @$customerAddressData->stateFunction->country->country_name;
                            else
                                $data['orderDetails']->country_name =    '';




                            if (isset($customerAddressData->address))
                                $data['orderDetails']->customer_address = @$customerAddressData->address;
                            else
                                $data['orderDetails']->customer_address = ' ';

                            if (isset($customerAddressData->longitude))
                                $data['orderDetails']->c_longitude = @$customerAddressData->longitude;
                            else
                                $data['orderDetails']->c_longitude = ' ';

                            if (isset($customerAddressData->latitude))
                                $data['orderDetails']->c_latitude = @$customerAddressData->latitude;
                            else
                                $data['orderDetails']->c_latitude = ' ';

                            if (isset($customerAddressData->place))
                                $data['orderDetails']->c_place = @$customerAddressData->place;
                            else
                                $data['orderDetails']->c_place = ' ';

                            if (isset($customerAddressData->pincode))
                                $data['orderDetails']->customer_pincode = @$customerAddressData->pincode;
                            else
                                $data['orderDetails']->customer_pincode = ' ';

                            if (isset($customerAddressData->place))
                                $data['orderDetails']->customer_place = @$customerAddressData->place;
                            else
                                $data['orderDetails']->customer_place = ' ';


                            $deliveryBoy = Mst_delivery_boy::find($data['orderDetails']->delivery_boy_id);
                            if (isset($deliveryBoy->delivery_boy_name))
                                $data['orderDetails']->delivery_boy = @$deliveryBoy->delivery_boy_name;
                            else
                                $data['orderDetails']->delivery_boy = '';

                            if (isset($deliveryBoy->delivery_boy_mobile))
                                $data['orderDetails']->delivery_boy_mobile = @$deliveryBoy->delivery_boy_mobile;
                            else
                                $data['orderDetails']->delivery_boy_mobile = '';

                            $deliveryBoyLoc = Trn_DeliveryBoyLocation::where('delivery_boy_id', $data['orderDetails']->delivery_boy_id)
                                ->orderBy('dbl_id', 'DESC')->first();

                            if (isset($deliveryBoyLoc->latitude))
                                $data['orderDetails']->db_latitude = @$deliveryBoyLoc->latitude;
                            else
                                $data['orderDetails']->db_latitude = '';

                            if (isset($deliveryBoyLoc->longitude))
                                $data['orderDetails']->db_longitude = @$deliveryBoyLoc->longitude;
                            else
                                $data['orderDetails']->db_longitude = '';

                            // $data['orderDetails']->db_latitude = @$deliveryBoyLoc->latitude;
                            // $data['orderDetails']->db_longitude = @$deliveryBoyLoc->longitude;

                            if ($data['orderDetails']->order_type == 'POS') {
                                $data['orderDetails']->customer_mobile = '';
                                $data['orderDetails']->customer_address = '';
                                $data['orderDetails']->customer_pincode = '';
                                $data['orderDetails']->customer_place = ' ';
                            }
                        } else {
                            $data['orderDetails']->customer_name = '';
                            $data['orderDetails']->delivery_boy = '';
                            $data['orderDetails']->customer_mobile = '';
                            $data['orderDetails']->customer_address = '';
                            $data['orderDetails']->customer_pincode = '';
                            $data['orderDetails']->db_latitude = '';
                            $data['orderDetails']->db_longitude = '';
                            $data['orderDetails']->customer_place = ' ';
                        }

                        $storeData = Mst_store::find($request->store_id);
                        $data['orderDetails']->store_name = $storeData->store_name;

                        if (isset($storeData->gst))
                            $data['orderDetails']->gst = $storeData->gst;
                        else
                            $data['orderDetails']->gst = "";

                        $data['orderDetails']->store_primary_address = $storeData->store_primary_address;
                        $data['orderDetails']->store_mobile = $storeData->store_mobile;

                        if (isset($storeData->place))
                            $data['orderDetails']->place = $storeData->place;
                        else
                            $data['orderDetails']->place = '';

                        if (isset($storeData->place))
                            $data['orderDetails']->place = $storeData->place;
                        else
                            $data['orderDetails']->place = '';

                        if (isset($storeData->country->country_name))
                            $data['orderDetails']->country_name = $storeData->country->country_name;
                        else
                            $data['orderDetails']->country_name = '';

                        if (isset($storeData->state->state_name))
                            $data['orderDetails']->state_name = $storeData->state->state_name;
                        else
                            $data['orderDetails']->state_name = '';

                        if (isset($storeData->district->district_name))
                            $data['orderDetails']->district_name = $storeData->district->district_name;
                        else
                            $data['orderDetails']->district_name = '';

                        if (isset($storeData->town->town_name))
                            $data['orderDetails']->town_name = $storeData->town->town_name;
                        else
                            $data['orderDetails']->town_name = '';



                        if (isset($data['orderDetails']->time_slot) && ($data['orderDetails']->time_slot != 0)) {
                            $deliveryTimeSlot = Trn_StoreDeliveryTimeSlot::find($data['orderDetails']->time_slot);
                            $data['orderDetails']->time_slot = @$deliveryTimeSlot->time_start . "-" . @$deliveryTimeSlot->time_end;
                            $data['orderDetails']->delivery_type = 2; //slot delivery

                        } else // timeslot null or zero
                        {
                            $data['orderDetails']->delivery_type = 1; // immediate delivery
                            $data['orderDetails']->time_slot = '';
                        }
                        if ($data['orderDetails']->order_type == 'POS') {
                        $data['orderDetails']->processed_by = $data['orderDetails']->storeadmin['admin_name'];
                        }else{
                            $data['orderDetails']->processed_by = null;
                        }
                        

                        $invoice_data = \DB::table('trn_order_invoices')->where('order_id', $order_id)->first();
                        $data['orderDetails']->invoice_id = @$invoice_data->invoice_id;
                        $data['orderDetails']->invoice_date = @$invoice_data->invoice_date;


                        if (isset($data['orderDetails']->status_id)) {
                            $statusData = Sys_store_order_status::find($data['orderDetails']->status_id);
                            $data['orderDetails']->status_name = @$statusData->status;
                        } else {
                            $data['orderDetails']->status_name = null;
                        }
                        $data['orderDetails']->order_date = Carbon::parse($data['orderDetails']->created_at)->format('d-m-Y');

                        if ($data['orderDetails']->payment_type_id == 1)
                            $data['orderDetails']->payment_type = 'Offline';
                        else
                            $data['orderDetails']->payment_type = 'Online';
                        $data['orderDetails']->invoice_link =  url('get/invoice/' . Crypt::encryptString($data['orderDetails']->order_id));
                        $data['orderDetails']->item_list_link = url('item/list/' . Crypt::encryptString($data['orderDetails']->order_id));



                        $data['orderDetails']->orderItems = Trn_store_order_item::where('order_id', $data['orderDetails']->order_id)
                            ->select('product_id', 'product_varient_id', 'order_item_id', 'quantity', 'discount_amount', 'discount_percentage', 'total_amount', 'tax_amount', 'unit_price', 'tick_status')
                            ->get();


                        $isServiceOrder = 0;
                        foreach ($data['orderDetails']->orderItems as $value) {


                            if ($datazz = \DB::table("mst_disputes")->where('store_id', $store_id)->where('order_id', $order_id)->first()) {

                                $colorsArray = explode(",", $datazz->item_ids);

                                $ordItemArr =  Trn_store_order_item::whereIn('order_item_id', $colorsArray)->get();
                                $colorsArray2 = array();
                                foreach ($ordItemArr as $i) {
                                    $colorsArray2[] = $i->product_varient_id;
                                }
                                if (in_array($value->product_varient_id, $colorsArray2)) {
                                    $value->dispute_status = 1;
                                } else {
                                    $value->dispute_status = 0;
                                }
                            } else {
                                $value->dispute_status = 0;
                            }



                            $value['productDetail'] = Mst_store_product_varient::find($value->product_varient_id);
                            $vaproductDetail = Mst_store_product_varient::find($value->product_varient_id);



                            @$value->productDetail->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . @$value->productDetail->product_varient_base_image;

                            $baseProductDetail = Mst_store_product::find($value->product_id);
                            if(@$baseProductDetail!=NULL)
                            {
                                if ((@$baseProductDetail->product_type == 2) && (@$baseProductDetail->service_type == 2)) {
                                    $isServiceOrder = 1;
                                }

                            }
                           



                            $value->product_base_image = '/assets/uploads/products/base_product/base_image/' . @$baseProductDetail->product_base_image;

                            if (@$baseProductDetail->product_name != @$value->productDetail->variant_name)
                                $value->product_name = @$baseProductDetail->product_name . " " . @$value->productDetail->variant_name;
                            else
                                $value->product_name = @$baseProductDetail->product_name;

                            $taxFullData = Mst_Tax::find(@$baseProductDetail->tax_id);

                            // $gstAmount = $value['productDetail']->product_varient_offer_price * $baseProductDetail->tax_value / (100 + $baseProductDetail->tax_value);
                            // $orgCost = $value['productDetail']->product_varient_offer_price * 100 / (100 + $baseProductDetail->tax_value);

                            $discount_amount = (@$vaproductDetail->product_varient_price - @$vaproductDetail->product_varient_offer_price) * $value->quantity;
                            $value->discount_amount =  number_format((float)$discount_amount, 2, '.', '');
                            $value->taxPercentage = @$taxFullData->tax_value;
                            $tTax = $value->quantity * (@$vaproductDetail->product_varient_offer_price * @$taxFullData->tax_value / (100 + @$taxFullData->tax_value));
                            $value->gstAmount = number_format((float)$tTax, 2, '.', '');
                            $orgCost =  $value->quantity * (@$vaproductDetail->product_varient_offer_price * 100 / (100 + @$taxFullData->tax_value));
                            $value->orgCost = number_format((float)$orgCost, 2, '.', '');

                            $stax = 0;
                            // dd($splitdata);

                            $splitdata = [];

                            if (isset($taxFullData)) {
                                $splitdata = \DB::table('trn__tax_split_ups')->where('tax_id', @$baseProductDetail->tax_id)->get();

                                foreach ($splitdata as $sd) {
                                    if (@$taxFullData->tax_value == 0 || !isset($taxFullData->tax_value))
                                        $taxFullData->tax_value = 1;

                                    $stax = ($sd->split_tax_value * $tTax) / @$taxFullData->tax_value;
                                    $sd->tax_split_value = number_format((float)$stax, 2, '.', '');
                                }
                            }

                            $value['taxSplitups']  = @$splitdata;
                        }

                        if ($isServiceOrder == 1) {
                            $data['orderDetails']->service_order = 1;
                        }

                        //  $tTax = $taxFullData->tax_value * $value->quantity;
                        // $value->total_amount = $value->total_amount  - $tTax;
                        // $value->tax_amount = $tTax;

                        // $value['productDetail']->product_varient_offer_price - $taxFullData->tax_value;





                        $data['orderDetails']->serviceData = new \stdClass();
                        if ($data['orderDetails']->service_booking_order == 1) {
                            $serviceData = Mst_store_product_varient::find(@$data['orderDetails']->product_varient_id);
                            @$serviceData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . @$serviceData->product_varient_base_image;
                            $baseProductDetail = Mst_store_product::find(@$serviceData->product_id);
                            $serviceData->product_base_image = '/assets/uploads/products/base_product/base_image/' . @$baseProductDetail->product_base_image;

                            if (@$baseProductDetail->product_name != @$serviceData->variant_name)
                                $serviceData->product_name = @$baseProductDetail->product_name . " " . @$serviceData->productDetail->variant_name;
                            else
                                $serviceData->product_name = @$baseProductDetail->product_name;
                            $data['orderDetails']->serviceData = $serviceData;
                        }


                        $data['orderPaymentTransaction'] = new \stdClass();
                        $opt = Trn_OrderPaymentTransaction::where('order_id', $request->order_id)->get();
                        $optConunt = Trn_OrderPaymentTransaction::where('order_id', $request->order_id)->count();
                        if ($optConunt > 0) {
                            foreach ($opt as $row) {
                                $ospCount = Trn_OrderSplitPayments::where('opt_id', $row->opt_id)->count();
                                if ($ospCount > 0) {
                                    $osp = Trn_OrderSplitPayments::where('opt_id', $row->opt_id)->get();
                                    $row->orderSplitPayments = $osp;
                                } else {
                                    $row->orderSplitPayments = [];
                                }
                            }
                        }
                        //Trn_OrderPaymentTransaction
                        $data['orderPaymentTransaction'] = $opt;
                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "failed";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    $data['message'] = "Order not found ";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }



    public function activeDelievryBoysList(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $store_id = $request->store_id;
                if ($data['deliveryBoysDetails'] = Mst_store_link_delivery_boy::join('mst_delivery_boys', 'mst_delivery_boys.delivery_boy_id', '=', 'mst_store_link_delivery_boys.delivery_boy_id')
                    ->select(
                        'mst_delivery_boys.delivery_boy_id',
                        'mst_delivery_boys.delivery_boy_name',
                        'mst_delivery_boys.delivery_boy_name',
                        'mst_delivery_boys.delivery_boy_name',
                        'mst_delivery_boys.delivery_boy_mobile'
                    )
                    ->where('mst_store_link_delivery_boys.store_id', $request->store_id)
                    ->where('mst_delivery_boys.availability_status', 1)
                    ->where('mst_delivery_boys.delivery_boy_status', 1)
                    ->get()
                ) {

                    $data['status'] = 1;
                    $data['message'] = "success";
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function listDeliveryBoys(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $store_id = $request->store_id;
                if ($data['deliveryBoysDetails'] = Mst_store_link_delivery_boy::join('mst_delivery_boys', 'mst_delivery_boys.delivery_boy_id', '=', 'mst_store_link_delivery_boys.delivery_boy_id')
                    ->select(
                        'mst_delivery_boys.delivery_boy_id',
                        'mst_delivery_boys.delivery_boy_name',
                        'mst_delivery_boys.delivery_boy_name',
                        'mst_delivery_boys.delivery_boy_name',
                        'mst_delivery_boys.delivery_boy_mobile'
                    )
                    ->where('mst_store_link_delivery_boys.store_id', $request->store_id)
                    //->where('mst_delivery_boys.delivery_boy_status', 1)
                    ->get()
                ) {

                    $data['status'] = 1;
                    $data['message'] = "success";
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function listOrderStatus(Request $request)
    {
        $data = array();
        try {

            if ($data['orderStatusDetails'] = Sys_store_order_status::select('status_id', 'status')->get()) {
                $data['status'] = 1;
                $data['message'] = "success";
                return response($data);
            } else {
                $data['status'] = 0;
                $data['message'] = "failed";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }



    public function updateOrder(Request $request)
    {
        $data = array();

        //$od = Trn_store_order::find($request->order_id);

        //  $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $od->customer_id)->get();

        // foreach ($customerDevice as $cd) {
        //     $title = 'working';
        //     //  $body = 'First order points credited successully..';
        //     $body = "working body";
        //     $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body);
        // }

        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                if (Trn_store_order::find($request->order_id)) {
                    $od = Trn_store_order::find($request->order_id);
                    $validator = Validator::make(
                        $request->all(),
                        [
                            'status_id'          => 'required',
                        ],
                        [
                            'status_id.required'        => 'Status not found',
                        ]
                    );

                    if (!$validator->fails()) {
                        $order_id = $request->order_id;
                        $store_id = $request->store_id;
                        if($od->status_id==1)
                        {
                          if(!in_array($request->status_id,[4,5]))
                          {
                            $data['status'] = 0;
                            $data['message'] = "Cannot update to this status before confirming the order";
                            return response($data);
                    
                          }
                        }

                        if (isset($request->status_id))
                            $orderdata2['order_note'] = $request->order_note;


                        $orderdata2['status_id'] = $request->status_id;

                        if ($request->status_id == 7) {
                            $orderdata2['delivery_status_id'] = 1;
                        } else if ($request->status_id == 8) {
                            $orderdata2['delivery_status_id'] = 2;
                        } else if ($request->status_id == 9) {
                            $orderdata2['delivery_status_id'] = 3;
                        } else {
                            $orderdata2['delivery_status_id'] = null;
                        }

                        if (($request->status_id == 9) && ($od->status_id != 9)) {
                            // $order->delivery_date = Carbon::now()->format('Y-m-d');
                            // $order->delivery_time = Carbon::now()->format('H:i');

                            $orderdata2['delivery_date'] = Carbon::now()->format('Y-m-d');
                            $orderdata2['delivery_time'] =  Carbon::now()->format('H:i');
                            $orderDataz = Trn_store_order::Find($order_id);

                            if ($orderDataz->order_type == 'APP') {
                                if (($orderDataz->delivery_boy_id == 0) || !isset($orderDataz->delivery_boy_id)) {
                                    $data['status'] = 0;
                                    $data['message'] = "Delivery boy not assigned";
                                    return response($data);
                                }
                            }

                            // reward points 

                            $configPoint = Trn_configure_points::find(1);
                            $orderAmount  = $configPoint->order_amount;
                            $orderPoint  = $configPoint->order_points;

                            $orderAmounttoPointPercentage =  $orderPoint / $orderAmount;
                            $orderPointAmount =  $orderDataz->product_total_amount * $orderAmounttoPointPercentage;
                            //echo $orderPointAmount;die;
                            ///////////////////////////////////////////////////////
                            $store_id=$request->store_id;
                            $storeConfigPoint = Trn_configure_points::where('store_id',$store_id)->first();
                            if($storeConfigPoint)
                            {
                            $storeOrderAmount  = $storeConfigPoint->order_amount;
                            $storeOrderPoint  = $storeConfigPoint->order_points;

                            $storeOrderAmounttoPointPercentage =  $storeOrderPoint / $storeOrderAmount;
                            $storeOrderPointAmount =  $orderDataz->product_total_amount * $storeOrderAmounttoPointPercentage;
                            }
                            ///////////////////////////////////////////////////////


                            if (Trn_store_order::where('customer_id', $orderDataz->customer_id)->count() == 1) {
                                $configPoint = Trn_configure_points::find(1);

                                // first - order - point
                                $refCusData = Trn_store_customer::find($orderDataz->customer_id);

                                $cr = new Trn_customer_reward;
                                $cr->transaction_type_id = 0;
                                $cr->reward_points_earned = $configPoint->first_order_points;
                                $cr->customer_id = $orderDataz->customer_id;
                                $cr->order_id = $orderDataz->order_id;
                                $cr->reward_approved_date = Carbon::now()->format('Y-m-d');
                                $cr->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                                $cr->reward_point_status = 1;
                                $cr->discription = "First order points";
                                if ($cr->save()) {
                                    $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $refCusData->referred_by)->get();

                                    foreach ($customerDevice as $cd) {
                                        $title = 'First order points credited';
                                        //  $body = 'First order points credited successully..';
                                        $body = $configPoint->first_order_points . ' points credited to your wallet..';
                                        $clickAction = "MyWalletFragment";
                                        $type = "wallet";
                                        $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                                    }
                                }


                                // referal - point
                                $refCusData = Trn_store_customer::find($orderDataz->customer_id);
                                if ($refCusData->referred_by) {
                                    $crRef = new Trn_customer_reward;
                                    $crRef->transaction_type_id = 0;
                                    $crRef->reward_points_earned = $configPoint->referal_points;
                                    $crRef->customer_id = $refCusData->referred_by;
                                    $crRef->order_id = null;
                                    $crRef->reward_approved_date = Carbon::now()->format('Y-m-d');
                                    $crRef->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                                    $crRef->reward_point_status = 1;
                                    $crRef->discription = "Referal points";
                                    $crRef->save();

                                    $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $refCusData->referred_by)->get();

                                    foreach ($customerDevice as $cd) {
                                        $title = 'Referal points credited';
                                        $body = $configPoint->referal_points . ' points credited to your wallet..';
                                        $clickAction = "MyWalletFragment";
                                        $type = "wallet";
                                        $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                                    }


                                    // joiner - point
                                    $crJoin = new Trn_customer_reward;
                                    $crJoin->transaction_type_id = 0;
                                    $crJoin->reward_points_earned = $configPoint->joiner_points;
                                    $crJoin->customer_id = $orderDataz->customer_id;
                                    $crJoin->order_id = $orderDataz->order_id;
                                    $crJoin->reward_approved_date = Carbon::now()->format('Y-m-d');
                                    $crJoin->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                                    $crJoin->reward_point_status = 1;
                                    $crJoin->discription = "Referal joiner points";
                                    if ($crJoin->save()) {
                                        $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $orderDataz->referred_by)->get();

                                        foreach ($customerDevice as $cd) {
                                            $title = 'Referal joiner points credited';
                                            //  $body = 'Referal joiner points credited successully..';
                                            $body = $configPoint->joiner_points . ' points credited to your wallet..';
                                            $clickAction = "MyWalletFragment";
                                            $type = "wallet";
                                            $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                                        }
                                    }
                                }
                            }

                            //if (Trn_customer_reward::where('order_id', $orderDataz->order_id)->count() < 1) {


                                //if ((Trn_customer_reward::where('order_id', $orderDataz->order_id)->count() < 1) || (Trn_store_order::where('customer_id', $orderDataz->customer_id)->count() >= 1)) {
                                    $cr = new Trn_customer_reward;
                                    $cr->transaction_type_id = 0;
                                    $cr->reward_points_earned = $orderPointAmount;
                                    $cr->customer_id = $orderDataz->customer_id;
                                    $cr->order_id = $orderDataz->order_id;
                                    $cr->reward_approved_date = Carbon::now()->format('Y-m-d');
                                    $cr->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                                    $cr->reward_point_status = 1;
                                    $cr->discription = null;
                                    $cr->save();
                                    if($storeConfigPoint)
                                    {
                                    $scr = new Trn_customer_reward;
                                    $scr->transaction_type_id = 0;
                                    $scr->store_id=$store_id;
                                    $scr->reward_points_earned = $storeOrderPointAmount;
                                    $scr->customer_id = $orderDataz->customer_id;
                                    $scr->order_id = $orderDataz->order_id;
                                    $scr->reward_approved_date = Carbon::now()->format('Y-m-d');
                                    $scr->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                                    $scr->reward_point_status = 1;
                                    $scr->discription = 'store points';
                                    $scr->save();

                                    $wallet_log=new Trn_wallet_log();
                                    $wallet_log->store_id=$orderDataz->store_id;
                                    $wallet_log->customer_id=$orderDataz->customer_id;
                                    $wallet_log->order_id=$orderDataz->order_id;
                                    $wallet_log->type='credit';
                                    $wallet_log->points_debited=null;
                                    $wallet_log->points_credited=$storeOrderPointAmount;
                                    $wallet_log->save();
                                    }
                            //$data['wallet_id']=$wallet_log->wallet_log_id;

                                    $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $orderDataz->customer_id)->get();

                                    foreach ($customerDevice as $cd) {
                                        if($od->payment_type_id==2)
                                    {
                                        $title = 'Order points credited';
                                        $body = $orderPointAmount . ' points credited to your wallet..';
                                        $clickAction = "MyWalletFragment";
                                        $type = "wallet";
                                        $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                                        if($storeConfigPoint)
                                        {
                                        $title = 'Store order points credited';
                                        $body = @$storeOrderPointAmount . ' points credited to your store wallet..';
                                        $clickAction = "MyWalletFragment";
                                        $type = "wallet";
                                        $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                                        }
                                    }
                                }
                               // }
                            //}

                            // echo $orderPointAmount;die;


                        }

                        if ($request->status_id == 4) { //confirm
                            $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $od->customer_id)->get();

                            foreach ($customerDevice as $cd) {
                                $title = 'Order confirmed';
                                $body = "Your order " . $od->order_number . ' is confirmed..';
                                $clickAction = "OrderListFragment";
                                $type = "order";
                                $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                            }
                        }

                        if ($request->status_id == 6) { //picking complede
                            $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $od->customer_id)->get();

                            foreach ($customerDevice as $cd) {
                                $title = 'Order picking completed';
                                $body = "Your order " . $od->order_number . ' picking completed..';
                                $clickAction = "OrderListFragment";
                                $type = "order";
                                $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                            }
                        }
                        if ($request->status_id == 7) { //ready for delivery
                            $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $od->customer_id)->get();

                            foreach ($customerDevice as $cd) {
                                $title = 'Order ready for delivery';
                                $body = "Your order " . $od->order_number . ' is ready for delivery..';
                                $clickAction = "OrderListFragment";
                                $type = "order";
                                $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                            }
                        }

                        if ($request->status_id == 8) { //out for delivery
                            $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $od->customer_id)->get();

                            foreach ($customerDevice as $cd) {
                                $title = 'Order out for delivery';
                                $body = "Your order " . $od->order_number . ' is out for delivery..';
                                $clickAction = "OrderListFragment";
                                $type = "order";
                                $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                            }
                        }

                        if (($request->status_id == 9) && ($od->status_id != 9)) { // delivered
                            $customerDevice = Trn_CustomerDeviceToken::where('customer_id', $od->customer_id)->get();

                            foreach ($customerDevice as $cd) {
                                $title = 'Order delivered';
                                $body = "Your order " . $od->order_number . ' is deliverd..';
                                $clickAction = "OrderListFragment";
                                $type = "order";
                                $data['response'] =  Helper::customerNotification($cd->customer_device_token, $title, $body,$clickAction,$type);
                            }
                        }




                        $orderdata2['delivery_boy_id'] = $request->delivery_boy_id;
                        if ($request->status_id == 7) {
                            if ($od->delivery_accept == null) {

                                $dBoyDevices = Trn_DeliveryBoyDeviceToken::where('delivery_boy_id', $request->delivery_boy_id)->get();

                                foreach ($dBoyDevices as $cd) {
                                    $title = 'Order Assigned';
                                    $body = 'New order(' . $od->order_number . ') arrived';
                                    $clickAction = "AssignedOrderFragment";
                                    $type = "order-assigned";
                                    $data['response'] =  Helper::deliveryBoyNotification($cd->dboy_device_token, $title, $body,$clickAction,$type);
                                }
                            }
                            $orderdata2['delivery_accept'] = null;
                        }

                        if ($request->status_id == 5) {


                            if (isset($od->referenceId) && ($od->isRefunded < 2)) {


                                $curl = curl_init();

                                curl_setopt_array($curl, array(
                                    CURLOPT_URL => 'https://api.cashfree.com/api/v1/order/refund',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'POST',
                                    CURLOPT_POSTFIELDS => array(
                                        'appId' => '165253d13ce80549d879dba25b352561',
                                        'secretKey' => 'bab0967cdc3e5559bded656346423baf0b1d38c4',
                                        'ContentType' => 'application/json',
                                        'referenceId' => $od->referenceId, 'refundAmount' => $od->product_total_amount, 'refundNote' => 'full refund'
                                    ),
                                    CURLOPT_HTTPHEADER => array(
                                        'Accept' => 'application/json',
                                        'x-api-version' => '2021-05-21',
                                        'x-client-id' => '165253d13ce80549d879dba25b352561',
                                        'x-client-secret' => 'bab0967cdc3e5559bded656346423baf0b1d38c4'
                                    ),
                                ));

                                $response = curl_exec($curl);
                                // dd($response);
                                curl_close($curl);
                                $dataString = json_decode($response);
                                if ($dataString->status == "OK") {
                                    $data['message'] = $dataString->message;
                                    $data['refundId'] = $dataString->refundId;
                                } else {
                                    $data['message'] = $dataString->message;
                                    //  $data['message'] = "Refund failed! Please contact store";
                                }

                                if ($dataString->status == "OK") {
                                    $orderdata2['refundId'] = $dataString->refundId;
                                    $orderdata2['refundStatus'] = "Inprogress";
                                    $orderdata2['isRefunded'] = 1;
                                }
                            }


                            $orderData = Trn_store_order_item::where('order_id', $order_id)->get();
                            //dd($orderData);
                            foreach ($orderData as $o) {

                                $productVarOlddata = Mst_store_product_varient::find($o->product_varient_id);

                                $sd = new Mst_StockDetail;
                                $sd->store_id = $request->store_id;
                                $sd->product_id = $o->product_id;
                                $sd->stock = $o->quantity;
                                $sd->product_varient_id = $o->product_varient_id;
                                $sd->prev_stock = $productVarOlddata->stock_count;
                                $sd->save();

                                DB::table('mst_store_product_varients')->where('product_varient_id', $o->product_varient_id)->increment('stock_count', $o->quantity);
                            }
                        }

                        Trn_store_order::where('order_id', $order_id)->update($orderdata2);

                        foreach ($request->tickStatus as $key => $val) {
                            $tickStatus['tick_status'] = $val['tick_status'];
                            Trn_store_order_item::where('order_item_id', $val['order_item_id'])->update($tickStatus);
                        }


                        if (isset($request->delivery_boy_id)) {
                            $orderData = [
                                'order_id'      => $order_id,
                                'delivery_boy_id' => $request->delivery_boy_id,
                                'created_at'         => Carbon::now(),
                                'updated_at'         => Carbon::now(),
                            ];

                            Mst_order_link_delivery_boy::insert($orderData);
                        }




                        $data['status'] = 1;
                        $data['message'] = "Order updated";
                        return response($data);
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "failed";
                        $data['errors'] = $validator->errors();
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    $data['message'] = "Order not found ";
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function listDeliveryBoysByStatus(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'work_status'          => 'required',
                    ],
                    [
                        'work_status.required'        => 'Work status required',
                    ]
                );

                if (!$validator->fails()) {
                    $work_status = $request->work_status;
                    $store_id = $request->store_id;
                    $dboy = array();


                    $delivery_boys1 = Mst_store_link_delivery_boy::join('mst_delivery_boys', 'mst_delivery_boys.delivery_boy_id', '=', 'mst_store_link_delivery_boys.delivery_boy_id')
                        ->where('mst_store_link_delivery_boys.store_id', $store_id)
                        ->pluck('mst_delivery_boys.delivery_boy_id')
                        ->toArray();


                    if ($work_status == 1) {
                        $assigned_delivery_boys = Trn_store_order::whereIn('delivery_boy_id', $delivery_boys1)
                            ->where('store_id', $store_id)
                            ->where('status_id', 7)
                            ->where('delivery_status_id', $work_status)
                            ->orderBy('order_id', 'DESC')
                            ->get();
                    } else if ($work_status == 2) {
                        $assigned_delivery_boys = Trn_store_order::whereIn('delivery_boy_id', $delivery_boys1)
                            ->where('store_id', $store_id)
                            ->where('status_id', 8)
                            ->where('delivery_status_id', $work_status)
                            ->orderBy('order_id', 'DESC')
                            ->get();
                    } else if ($work_status == 3) {
                        $assigned_delivery_boys = Trn_store_order::whereIn('delivery_boy_id', $delivery_boys1)
                            ->where('store_id', $store_id)
                            ->where('status_id', 9)
                            ->where('delivery_status_id', $work_status)
                            ->orderBy('order_id', 'DESC')
                            ->get();
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "work status not exist";
                        return response($data);
                    }



                    foreach ($assigned_delivery_boys as $ab) {
                        $custData = Trn_store_customer::find(@$ab->customer_id);
                        $ab->customer = @$custData->customer_first_name . " " . @$custData->customer_last_name;
                        $ab->orderDate = \Carbon\Carbon::parse($ab->created_at)->format('d-m-Y');


                        $deliveryBoy = \DB::table('mst_delivery_boys')
                            ->select('town_id', 'delivery_boy_id', 'delivery_boy_name', 'delivery_boy_mobile')
                            ->where('delivery_boy_id', @$ab->delivery_boy_id)
                            ->first();

                        $ab->town_id = @$deliveryBoy->town_id;
                        $ab->delivery_boy_id = @$deliveryBoy->delivery_boy_id;
                        $ab->delivery_boy_name = @$deliveryBoy->delivery_boy_name;
                        $ab->delivery_boy_mobile = @$deliveryBoy->delivery_boy_mobile;
                    }

                    $data['deliveryBoyDetails'] = $assigned_delivery_boys;


                    //   if($did = Mst_store_link_delivery_boy::join('mst_delivery_boys','mst_delivery_boys.delivery_boy_id','=','mst_store_link_delivery_boys.delivery_boy_id')
                    // ->select('mst_delivery_boys.delivery_boy_id',
                    // 'mst_delivery_boys.delivery_boy_name',
                    // 'mst_delivery_boys.delivery_boy_name',
                    // 'mst_delivery_boys.delivery_boy_name',
                    // 'mst_delivery_boys.delivery_boy_mobile')
                    // ->where('mst_store_link_delivery_boys.store_id',$request->store_id)
                    // ->get())
                    // {

                    //   foreach($did as $value)
                    //   {
                    //     if($orderData = Trn_store_order::
                    //     where('delivery_boy_id',$value->delivery_boy_id)
                    //     // ->where('payment_type_id',2)
                    //     ->where('store_id',$request->store_id)
                    //     ->where('delivery_status_id',$work_status)
                    //     ->orderBy('delivery_boy_id','DESC')->first())
                    //     {
                    //         $custData = Trn_store_customer::find($orderData->customer_id);
                    //         $value->order_id = $orderData->order_id;
                    //         $value->order_number = $orderData->order_number;
                    //         $value->order_date = Carbon::parse($orderData->created_at)->format('d-m-Y');
                    //          $value->customer = @$custData->customer_first_name." ".@$custData->customer_last_name;
                    //         $dboy[] = $value;
                    //     }


                    //     //  $value->orderData = Mst_order_link_delivery_boy::
                    //     //  join('trn_store_orders','trn_store_orders.order_id','=','mst_order_link_delivery_boys.order_id')
                    //     //  ->where('mst_order_link_delivery_boys.delivery_boy_id',$value->delivery_boy_id)
                    //     //  ->where('mst_order_link_delivery_boys.delivery_status_id',$work_status)
                    //     //  ->select('trn_store_orders.order_id',
                    //     //  'mst_order_link_delivery_boys.delivery_boy_id',
                    //     //  'mst_order_link_delivery_boys.delivery_status_id',
                    //     //  'trn_store_orders.order_number',
                    //     //  'trn_store_orders.customer_id'

                    //     //  )
                    //     //  ->first();

                    //     //   $customerData = Trn_store_customer::where('customer_id',@$value->orderData->customer_id)
                    //     //   ->select('customer_id','customer_first_name','customer_last_name','customer_mobile_number')
                    //     //   ->first();

                    //     //   $value->customerData = $customerData;

                    //   }

                    // $data['deliveryBoyDetails'] = $dboy;

                    //     $data['status'] = 1;
                    //     $data['message'] = "success";
                    //   // echo '<pre>';
                    //   // print_r($data);die;
                    //     return response($data);
                    // }
                    // else
                    // {
                    //     $data['status'] = 0;
                    //     $data['message'] = "failed";
                    //     return response($data);
                    // }
                    $data['status'] = 1;
                    $data['message'] = "success";
                    return response($data);
                } else {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    $data['errors'] = $validator->errors();
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function listDeliveryStatus(Request $request)
    {
        $data = array();
        try {

            $data['deiveryStatusList'] = Sys_DeliveryStatus::select('delivery_status_id', 'delivery_status')->get();
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];

            return response($response);
        }
    }

    public function orderInvoice(Request $request)
    {
        $data = array();
        try {
            if (isset($request->order_id) && Trn_store_order::find($request->order_id)) {
                $order_id = $request->order_id;
                $order = Trn_store_order::where('order_id', $order_id)
                    ->select(
                        'order_id',
                        'order_number',
                        'customer_id',
                        'store_id',
                        'product_total_amount',
                        'payment_type_id',
                        'status_id',
                        'order_note',
                        'created_at'
                    )
                    ->first()->toArray();
                $customer = Trn_store_customer::where('customer_id', $order['customer_id'])
                    ->select(
                        'customer_id',
                        'customer_first_name',
                        'customer_last_name',
                        'customer_email',
                        'customer_mobile_number',
                        'customer_address',
                        'customer_location',
                        'customer_pincode',
                        'country_id',
                        'state_id'
                    )
                    ->first()->toArray();
                $status = Sys_store_order_status::find($order['status_id']);
                $order_items = Trn_store_order_item::where('order_id', $order_id)->get()->toArray();
                $store_data = Mst_store::where('store_id', $order['store_id'])->first()->toArray();
                $order['customerDetails'] = $customer;
                $order['orderStatus'] = $status;
                $order['orderItems'] = $order_items;
                $order['store_data'] = $store_data;
                // array_push($order, $customer);

                $data['orderDetails'] = $order;
                $data['status'] = 1;
                $data['message'] = "success";
                return response($data);
            } else {
                $data['status'] = 0;
                $data['message'] = "Order not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function assignDeliveryBoy(Request $request)
    {
        $data = array();
        try {
            if (isset($request->order_id) && Trn_store_order::find($request->order_id)) {
                $order_id = $request->order_id;
                $validator = Validator::make(
                    $request->all(),
                    [
                        'delivery_boy_id'          => 'required',
                    ],
                    [
                        'delivery_boy_id.required'        => 'Delivery boy not found',
                    ]
                );

                if (!$validator->fails()) {
                    $delivery_boy_id = $request->delivery_boy_id;

                    if (Trn_store_order::where('order_id', $order_id)->update(['delivery_boy_id' => $delivery_boy_id, 'delivery_accept' => null])) {
                        $data['status'] = 1;
                        $data['message'] = "Assigned";
                        return response($data);
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "failed";
                        return response($data);
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    $data['errors'] = $validator->errors();
                    return response($data);
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Order not found ";
                return response($data);
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
}

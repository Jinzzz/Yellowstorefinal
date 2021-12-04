<?php

namespace App\Http\Controllers\Delivery_Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Helpers\Helper;

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
use Auth;

use App\Models\admin\Mst_delivery_boy;
use App\Models\admin\Trn_store_order;
use App\Models\admin\Sys_store_order_status;
use App\Models\admin\Trn_store_customer;
use App\Models\admin\Mst_store;
use App\Models\admin\Trn_store_order_item;
use App\Models\admin\Mst_store_product_varient;
use App\Models\admin\Mst_store_product;
use App\Models\admin\Mst_Tax;
use App\Models\admin\Trn_customerAddress;
use App\Models\admin\Trn_configure_points;
use App\Models\admin\Trn_customer_reward;
use App\Models\admin\Trn_CustomerDeviceToken;

use App\Models\admin\Trn_StoreAdmin;
use App\Models\admin\Trn_StoreDeviceToken;
use App\Models\admin\Trn_StoreWebToken;

class DeliveryBoyOrderController extends Controller
{
    public function assignedOrders(Request $request)
    {
        $data = array();
        try
        {
            if(isset($request->delivery_boy_id) && Mst_delivery_boy::find($request->delivery_boy_id))
            { 
                $ordersList = array();
                if($assignedOrders  = Trn_store_order::where('delivery_boy_id',$request->delivery_boy_id)
                ->orderBy('order_id','DESC')->get())
                {
                    foreach($assignedOrders as $order)
                    {
                        //dd($order);
                        if(($order->status_id != 9) && ($order->delivery_accept != 2))
                        {
                            if(!isset($order->delivery_accept))
                                $order->delivery_accept = "0";
                                        
                            $statusInfo = Sys_store_order_status::find($order->status_id);
                            $order->status = @$statusInfo->status;
                            $order->order_date = Carbon::parse($order->created_at)->format('d-m-Y');
                            $customerInfo = Trn_store_customer::find($order->customer_id);
    
                            if(isset($customerInfo->customer_last_name))
                            $order->customer_name = $customerInfo->customer_first_name." ".$customerInfo->customer_last_name;
                            else
                            $order->customer_name = $customerInfo->customer_first_name;
    
                            $storeInfo = Mst_store::find($order->store_id);
                            $order->store_name = $storeInfo->store_name;
                            
                            $ordersList[] = $order;

                        }
                    }
                    $data['assignedOrders'] = $ordersList;
                    $data['status'] = 1;
                    $data['message'] = "success";
                }
                else{
                    $data['status'] = 0;
                    $data['message'] = "failed";
                }
            }
            else
            {
                $data['status'] = 0;
                $data['message'] = "Delivery boy not found";
            }

        return response($data);

        }catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
         }catch (\Throwable $e) {
             $response = ['status' => '0','message' => $e->getMessage()];
             return response($response);
         }

    }

    public function completedOrders(Request $request)
    {
        $data = array();
        try
        {
            if(isset($request->delivery_boy_id) && Mst_delivery_boy::find($request->delivery_boy_id))
            { 
                if($data['completedOrders']  = Trn_store_order::where('delivery_boy_id',$request->delivery_boy_id)->where('status_id',9)->where('delivery_status_id',3)->orderBy('updated_at','DESC')->get())
                {
                    foreach($data['completedOrders'] as $order)
                    {
                        //dd($order);
                        $statusInfo = Sys_store_order_status::find($order->status_id);
                        $order->status = @$statusInfo->status;
                        $order->order_date = Carbon::parse($order->created_at)->format('d-m-Y');
                        $customerInfo = Trn_store_customer::find($order->customer_id);

                        if(isset($customerInfo->customer_last_name))
                        $order->customer_name = $customerInfo->customer_first_name." ".$customerInfo->customer_last_name;
                        else  
                        $order->customer_name = $customerInfo->customer_first_name;

                        $storeInfo = Mst_store::find($order->store_id);
                        $order->store_name = $storeInfo->store_name;

                    }
                    $data['status'] = 1;
                    $data['message'] = "success";
                }
                else{
                    $data['status'] = 0;
                    $data['message'] = "failed";
                }
            }
            else
            {
                $data['status'] = 0;
                $data['message'] = "Delivery boy not found";
            }

        return response($data);

        }catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
         }catch (\Throwable $e) {
             $response = ['status' => '0','message' => $e->getMessage()];
             return response($response);
         }

    }
    public function orderAcceptance(Request $request)
    {
        $data = array();
        try
        {
            if(isset($request->delivery_boy_id) && Mst_delivery_boy::find($request->delivery_boy_id))
            { 
                if(isset($request->order_id) && Trn_store_order::find($request->order_id))
                {
                    
                    $order_id = $request->order_id;
                    if($request->delivery_accept == 1)
                    {
                        if(Trn_store_order::where('order_id',$request->order_id)->where('delivery_boy_id',$request->delivery_boy_id)->update(['delivery_accept' => 1]))
                        {
                            
                            
                            $orderdatas = Trn_store_order::find($request->order_id);
                            $dBoy =  Mst_delivery_boy::find($request->delivery_boy_id);

                               $storeDatas = Trn_StoreAdmin::where('store_id',@$orderdatas->store_id)->where('role_id',0)->first();
                              $storeDevice = Trn_StoreDeviceToken::where('store_admin_id',$storeDatas->store_admin_id)
                              ->where('store_id',@$orderdatas->store_id)->get();
                              
                                foreach($storeDevice as $sd)
                               {
                                   $title = 'Delivery Boy Accepted Order';
                                   $body = 'New order with order id '.$orderdatas->order_number.' has been accepted by '.$dBoy->delivery_boy_name;
                                   $data['response'] =  $this->storeNotification($sd->store_device_token,$title,$body);
                               }
                               
                               
                                $storeWeb = Trn_StoreWebToken::where('store_admin_id',$storeDatas->store_admin_id)
                                ->where('store_id',@$orderdatas->store_id)->get();
                                foreach($storeWeb as $sw)
                               {
                                   $title = 'Delivery Boy Accepted Order';
                                   $body = 'New order with order id '.$orderdatas->order_number.' has been accepted by '.$dBoy->delivery_boy_name;
                                   $data['response'] =  Helper::storeNotifyWeb($sw->store_web_token,$title,$body);
                               }
                               
                               
                            $data['status'] = 1;
                            $data['message'] = "Order accepted";
                        }
                        else
                        {
                            $data['status'] = 0;
                            $data['message'] = "failed";
                        }
                    }
                    else if($request->delivery_accept == 2)
                    {
                        if(Trn_store_order::where('order_id',$request->order_id)->where('delivery_boy_id',$request->delivery_boy_id)->update(['delivery_accept' => 2]))
                        {
                            $orderdatas = Trn_store_order::find($request->order_id);
                            $dBoy =  Mst_delivery_boy::find($request->delivery_boy_id);

                               $storeDatas = Trn_StoreAdmin::where('store_id',@$orderdatas->store_id)->where('role_id',0)->first();
                              $storeDevice = Trn_StoreDeviceToken::where('store_admin_id',$storeDatas->store_admin_id)
                              ->where('store_id',$orderdatas->store_id)->get();
                              
                                foreach($storeDevice as $sd)
                               {
                                   $title = 'Delivery Boy Rejected Order';
                                   $body = 'New order with order id '.$orderdatas->order_number.' has been rejected by '.$dBoy->delivery_boy_name;
                                   $data['response'] =  $this->storeNotification($sd->store_device_token,$title,$body);
                               }
                               
                               
                                $storeWeb = Trn_StoreWebToken::where('store_admin_id',$storeDatas->store_admin_id)
                                ->where('store_id',$orderdatas->store_id)->get();
                                foreach($storeWeb as $sw)
                               {
                                   $title = 'Delivery Boy Rejected Order';
                                   $body = 'New order with order id '.$orderdatas->order_number.' has been rejected by '.$dBoy->delivery_boy_name;
                                   $data['response'] =  Helper::storeNotifyWeb($sw->store_web_token,$title,$body);
                               }
                            
                            $data['status'] = 1;
                            $data['message'] = "Order rejected";
                        }
                        else
                        {
                            $data['status'] = 0;
                            $data['message'] = "failed";
                        }
                    }
                    else
                    {
                        $data['status'] = 0;
                        $data['message'] = "failed";
                    }
                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "Order not found";
                }
            }
            else
            {
                $data['status'] = 0;
                $data['message'] = "Delivery boy not found";
            }

        return response($data);

        }catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
         }catch (\Throwable $e) {
             $response = ['status' => '0','message' => $e->getMessage()];
             return response($response);
         }
    }
    
    
    
     private function storeNotification($device_id,$title,$body){
        $url = 'https://fcm.googleapis.com/fcm/send';
        $api_key = 'AAAAnXagbe8:APA91bEqMgI9Wb_psiCzKPNCQcoFt3W7RwG08oucA_UHwMjTBIbLyalZgMnigItD-0e8SDrWPfxHrT4g5zlfXHovUITXLuB32RdWp3abYyqJh2xIy_tAsGuPJJdnV5sNGxrnrrnExYYm';
        $fields = array(
            'to' => $device_id,
            'notification' => array('title' => $title, 'body' => $body, 'sound' => 'default'),
            );
        $headers = array(
            'Content-Type:application/json',
            'Authorization:key='.$api_key
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch); 
        return $result;
  }
    
    public function viewOrder(Request $request)
    {
        $data = array(); 
        
        try {
                if(isset($request->delivery_boy_id) && Mst_delivery_boy::find($request->delivery_boy_id))
                {  
                    $validator = Validator::make($request->all(),
                    [
                        'order_id'          => 'required',
                    ],
                    [
                        'order_id.required'        => 'Order not found',
                    ]);
                    
                        if(!$validator->fails() && Trn_store_order::find($request->order_id))
                        {
                            $order_id = $request->order_id;
                            $delivery_boy_id = $request->delivery_boy_id;
                           // dd(Trn_store_order::select('order_id','delivery_boy_id','order_note','payment_type_id','order_number','created_at','status_id','customer_id','product_total_amount')->where('order_id',$order_id)->where('store_id',$store_id)->first());
                            
                            if($data['orderDetails']  = Trn_store_order::select('order_id','delivery_accept','delivery_address','delivery_date','delivery_time','store_id','delivery_boy_id','order_note','payment_type_id','order_number','created_at','status_id','customer_id','product_total_amount','delivery_charge')->where('order_id',$order_id)->where('delivery_boy_id',$delivery_boy_id)->first())
                            {
                                
                                if(!isset($data['orderDetails']->delivery_accept))
                                    $data['orderDetails']->delivery_accept = "0";

                               
                                if(isset($data['orderDetails']->customer_id))
                                {
                                    $customerData = Trn_store_customer::find($data['orderDetails']->customer_id);
                                    $data['orderDetails']->customer_name = $customerData->customer_first_name." ".$customerData->customer_last_name;
                                   
                                $data['orderDetails']->customer_mobile = @$customerData->customer_mobile_number;
                                $data['orderDetails']->customer_address = @$customerData->customer_address;
                                $data['orderDetails']->customer_pincode = @$customerData->customer_pincode;
                                
                                 $data['orderDetails']->customer_latitude = @$customerData->latitude;
                                $data['orderDetails']->customer_longitude = @$customerData->longitude;
                                
                                
                                $deliveryBoy = Mst_delivery_boy::find($data['orderDetails']->delivery_boy_id);
                                    $data['orderDetails']->delivery_boy = @$deliveryBoy->delivery_boy_name;
                                    $data['orderDetails']->delivery_boy_mobile = @$deliveryBoy->delivery_boy_mobile;
                                    
                                     $data['orderDetails']->customer_latitude = @$customerData->latitude;
                                $data['orderDetails']->customer_longitude = @$customerData->longitude;
                                }
                                else
                                {
                                    $data['orderDetails']->customer_name = null;
                                    $data['orderDetails']->delivery_boy = null;
                                    $data['orderDetails']->customer_mobile = null;
                                    $data['orderDetails']->customer_address = null;
                                    $data['orderDetails']->customer_pincode = null;

                                }

                                $storeData = Mst_store::find($data['orderDetails']->store_id);
                                $data['orderDetails']->store_name = @$storeData->store_name;
                                
                                
                                $data['orderDetails']->store_primary_address = @$storeData->store_primary_address;
                                $data['orderDetails']->store_mobile = @$storeData->store_mobile;
                                
                                
                                $data['orderDetails']->store_latitude = @$storeData->latitude;
                                $data['orderDetails']->store_longitude = @$storeData->longitude;
                                $data['orderDetails']->store_place = @$storeData->place;

                                $data['orderDetails']->delivery_type = null;
                                $data['orderDetails']->delivery_date = Carbon::parse($data['orderDetails']->delivery_date)->format('d-m-Y');
                                $data['orderDetails']->delivery_time =  Carbon::parse($data['orderDetails']->delivery_date)->format('h:i');
                                $data['orderDetails']->time_slot = null;
                                $data['orderDetails']->processed_by = null;

                                $invoice_data = \DB::table('trn_order_invoices')->where('order_id',$order_id)->first();
                                $data['orderDetails']->invoice_id = @$invoice_data->invoice_id;
                                $data['orderDetails']->invoice_date = @$invoice_data->invoice_date;
                    
                                $orderAddress = Trn_customerAddress::find($data['orderDetails']->delivery_address);

                                  $dist = Helper::haversineGreatCircleDistance($storeData->latitude,$storeData->longitude,$orderAddress->latitude,$orderAddress->longitude);

                                //  $settingsRow = Trn_store_setting::where('store_id',$data['orderDetails']->store_id)
                                //     ->where('service_start', '<=' , $dist)
                                //     ->where('service_end', '>=' , $dist)
                                //     ->first();

                                //     if(isset($settingsRow->delivery_charge))
                                //     $deliveryCharge = $settingsRow->delivery_charge;
                                //     else
                                //     $deliveryCharge = '0';
                                    
                                $data['orderDetails']->km_covered = $dist;
                                if(isset($data['orderDetails']->delivery_charge))
                                $data['orderDetails']->amount_earned = $data['orderDetails']->delivery_charge;
                                else
                                $data['orderDetails']->amount_earned = '0';

                                
                                   if(isset($orderAddress))
                                   {
                                       $orderAddress->stateData = @$orderAddress->stateFunction['state_name']; 
                                       $orderAddress->districtData = @$orderAddress->districtFunction['district_name']; 
                                        $data['orderDetails']->orderAddress =  $orderAddress;

                                   }
                                   else
                                   {
                                      $data['orderDetails']->orderAddress = $orderAddress; 
                                   }
                                    
                                    
   

                                if(isset($data['orderDetails']->status_id))
                                {
                                    $statusData = Sys_store_order_status::find($data['orderDetails']->status_id);
                                    $data['orderDetails']->status_name = @$statusData->status;
                                }
                                else
                                {
                                    $data['orderDetails']->status_name = null;
                                }
                                $data['orderDetails']->order_date = Carbon::parse($data['orderDetails']->created_at)->format('d-m-Y');
                                
                                if($data['orderDetails']->payment_type_id == 2)
                                $data['orderDetails']->payment_type = 'Offline';
                                else
                                $data['orderDetails']->payment_type = 'Online';
                                
                                $data['orderDetails']->invoice_link =  url('get/invoice/'.Crypt::encryptString($data['orderDetails']->order_id));
                                $data['orderDetails']->item_list_link = url('item/list/'.Crypt::encryptString($data['orderDetails']->order_id));
    
                                $data['orderDetails']->orderItems = Trn_store_order_item::where('order_id',$data['orderDetails']->order_id)
                                ->select('product_id','product_varient_id','order_item_id','quantity','discount_amount','discount_percentage','total_amount','tax_amount','unit_price','tick_status')
                                ->get();


                                foreach ($data['orderDetails']->orderItems as $value) 
                                {


                                    $value['productDetail'] = Mst_store_product_varient::find($value->product_varient_id);
                                    @$value->productDetail->product_varient_base_image = '/assets/uploads/products/base_product/base_image/'.@$value->productDetail->product_varient_base_image;
                                    
                                    $baseProductDetail = Mst_store_product::find($value->product_id);
                                    if(isset($baseProductDetail))
                                    {

                                        $value->product_base_image = '/assets/uploads/products/base_product/base_image/'.@$baseProductDetail->product_base_image;
                                        
                                        if($baseProductDetail->product_name != isset($value->productDetail->variant_name))
                                        $value->product_name = @$baseProductDetail->product_name." ". @$value->productDetail->variant_name;
                                        else
                                         $value->product_name = @$baseProductDetail->product_name;
                                    }
                                     
                                   
                                }

                                $data['status'] = 1;
                                $data['message'] = "success";
                                return response($data);
                            }
                            else
                            {
                                $data['status'] = 0;
                                $data['message'] = "failed";
                                return response($data);
                            }
                        }
                        else
                        {
                            $data['status'] = 0;
                            $data['message'] = "failed";
                            $data['message'] = "Order not found ";
                            return response($data);
                        }
                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "Delivery boy not found ";
                    return response($data);
                }
                
        }catch (\Exception $e) {
           $response = ['status' => '0', 'message' => $e->getMessage()];
           return response($response);
        }catch (\Throwable $e) {
            $response = ['status' => '0','message' => $e->getMessage()];
            return response($response);
        }
    }
    
    
    public function viewOrderItems(Request $request)
    {
        $data = array(); 
        
        try {
                if(isset($request->delivery_boy_id) && Mst_delivery_boy::find($request->delivery_boy_id))
                {  
                    $validator = Validator::make($request->all(),
                    [
                        'order_id'          => 'required',
                    ],
                    [
                        'order_id.required'        => 'Order not found',
                    ]);
                    
                        if(!$validator->fails() && Trn_store_order::find($request->order_id))
                        {
                            $order_id = $request->order_id;
                            $delivery_boy_id = $request->delivery_boy_id;
                           // dd(Trn_store_order::select('order_id','delivery_boy_id','order_note','payment_type_id','order_number','created_at','status_id','customer_id','product_total_amount')->where('order_id',$order_id)->where('store_id',$store_id)->first());
                            
                            if($orderDetails  = Trn_store_order::select('order_id','delivery_address','delivery_date','delivery_time','store_id','delivery_boy_id','order_note','payment_type_id','order_number','created_at','status_id','customer_id','product_total_amount')->where('order_id',$order_id)->where('delivery_boy_id',$delivery_boy_id)->first())
                            {
                               
                                $orderItems = Trn_store_order_item::where('order_id',$orderDetails->order_id)
                                ->select('product_id','product_varient_id','order_item_id','quantity','discount_amount','discount_percentage','total_amount','tax_amount','unit_price','tick_status','delivery_boy_tick_status')
                                ->get();


                                foreach ($orderItems as $value) 
                                {
                                    $value->variant_name = @$value->variant_name." ".$value->variant_name;
                                    $value['productDetail'] = Mst_store_product_varient::find($value->product_varient_id);
                                    @$value->productDetail->product_varient_base_image = '/assets/uploads/products/base_product/base_image/'.@$value->productDetail->product_varient_base_image;
                                    
                                    $baseProductDetail = Mst_store_product::find($value->product_id);

                                    $value->product_base_image = '/assets/uploads/products/base_product/base_image/'.@$baseProductDetail->product_base_image;
                                    
                                    if($baseProductDetail->product_name == $value['productDetail']->variant_name)
                                     $value->product_name = @$baseProductDetail->product_name;
                                    else
                                    $value->product_name = @$baseProductDetail->product_name." ". @$value['productDetail']->variant_name;

                                }
                                
                                $data['orderItems'] = $orderItems;

                                $data['status'] = 1;
                                $data['message'] = "success";
                                return response($data);
                            }
                            else
                            {
                                $data['status'] = 0;
                                $data['message'] = "failed";
                                return response($data);
                            }
                        }
                        else
                        {
                            $data['status'] = 0;
                            $data['message'] = "failed";
                            $data['message'] = "Order not found ";
                            return response($data);
                        }
                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "Delivery boy not found ";
                    return response($data);
                }
                
        }catch (\Exception $e) {
           $response = ['status' => '0', 'message' => $e->getMessage()];
           return response($response);
        }catch (\Throwable $e) {
            $response = ['status' => '0','message' => $e->getMessage()];
            return response($response);
        }
    }
    
    
    public function updateOrderDeliveryCheckStatue(Request $request)
    {
        $data = array(); 
        
        try {
                if(isset($request->delivery_boy_id) && Mst_delivery_boy::find($request->delivery_boy_id))
                {  
                    if(Trn_store_order::find($request->order_id))
                    {
                          
                                    $order_id = $request->order_id;
                                    $delivery_boy_id = $request->delivery_boy_id;

                                    
                                    foreach($request->tickStatus as $key => $val)
                                    {
                                        $tickStatus['delivery_boy_tick_status']= $val['delivery_boy_tick_status'];
                                        Trn_store_order_item::where('order_item_id',$val['order_item_id'])->update($tickStatus);
                                    }
                                 
                                    $data['status'] = 1;
                                    $data['message'] = "Order updated";
                                    return response($data);
                               
                        }
                        else
                        {
                            $data['status'] = 0;
                            $data['message'] = "failed";
                            $data['message'] = "Order not found ";
                            return response($data);
                        }
                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "Delivery boy not found ";
                    return response($data);
                }
                
        }catch (\Exception $e) {
           $response = ['status' => '0', 'message' => $e->getMessage()];
           return response($response);
        }catch (\Throwable $e) {
            $response = ['status' => '0','message' => $e->getMessage()];
            return response($response);
        }
    }
    
    
    public function updateOrderStatus(Request $request)
    {
        $data = array(); 
        
        try {
               
                    if(Trn_store_order::find($request->order_id))
                    {
                          
                                    $order_id = $request->order_id;
                                    $delivery_boy_id = $request->delivery_boy_id;


                                if($request->status_id == 8 || $request->status_id == '8')
                                {
                                        Trn_store_order::where('order_id',$order_id)->update([
                                                           'status_id' => $request->status_id,
                                                           'delivery_status_id' => 2
                                                           ]);                                
                                    
                                }
                                elseif($request->status_id == 9 || $request->status_id == '9')
                                {
                                       
                                       Trn_store_order::where('order_id',$order_id)->update([
                                           'status_id' => $request->status_id,
                                           'delivery_status_id' => 3
                                           ]);
                                        
                                        $order = Trn_store_order::Find($order_id);
    
                                        $order->delivery_date = Carbon::now()->format('Y-m-d');
                                        $order->delivery_time = Carbon::now()->format('H:i'); 
                                        
                                    $configPoint = Trn_configure_points::find(1);
                                    $orderAmount  = $configPoint->order_amount;
                                    $orderPoint  = $configPoint->order_points;
                                    $customer_id = $order->customer_id;
                                    $orderAmounttoPointPercentage =  $orderAmount / $orderPoint;
                                    $orderPointAmount = ( $order->product_total_amount * $orderAmounttoPointPercentage) / 100;
                                    
                    if(Trn_store_order::where('customer_id',$customer_id)->count() == 1)
                    {
                        $configPoint = Trn_configure_points::find(1);

                        $cr = new Trn_customer_reward;
                        $cr->transaction_type_id = 0;
                        $cr->reward_points_earned = $configPoint->first_order_points;
                        $cr->customer_id = $customer_id;
                        $cr->order_id = $order_id;
                        $cr->reward_approved_date = Carbon::now()->format('Y-m-d');
                        $cr->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                        $cr->reward_point_status = 1;
                        $cr->discription = "First order points";
                        $cr->save();
                        
                        
                         // referal - point
                       $refCusData = Trn_store_customer::find($order->customer_id);
                       if($refCusData->referred_by)
                       {
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
                            
                            $customerDevice = Trn_CustomerDeviceToken::where('customer_id',$refCusData->referred_by)->get();

                            foreach($customerDevice as $cd)
                            {
                                $title = 'Points creadited';
                                $body = 'Referal points credited successully..';
                                $data['response'] =  Helper::customerNotification($cd->customer_device_token,$title,$body);
                            }
                            
                            
                              // joiner - point
                            $crJoin = new Trn_customer_reward;
                            $crJoin->transaction_type_id = 0;
                            $crJoin->reward_points_earned = $configPoint->joiner_points;
                            $crJoin->customer_id = $order->customer_id;
                            $crJoin->order_id = $order->order_id;
                            $crJoin->reward_approved_date = Carbon::now()->format('Y-m-d');
                            $crJoin->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                            $crJoin->reward_point_status = 1;
                            $crJoin->discription = "Referal joiner points";
                            if($crJoin->save())
                            {
                                $customerDevice = Trn_CustomerDeviceToken::where('customer_id',$order->customer_id)->get();
                                foreach($customerDevice as $cd)
                                {
                                    $title = 'Points creadited';
                                    $body = 'Referal joiner points credited successully..';
                                    $data['response'] =  Helper::customerNotification($cd->customer_device_token,$title,$body);
                                }
                            }
                        


                        }
                    
                    
                    
                    }
                    
                    
                                           
                                }
                                else
                                {
                                    Trn_store_order::where('order_id',$order_id)->update([
                                           'status_id' => $request->status_id
                                           ]);
                                }
                                 
                                    $data['status'] = 1;
                                    $data['message'] = "Order updated";
                                    return response($data);
                               
                        }
                        else
                        {
                            $data['status'] = 0;
                            $data['message'] = "failed";
                            $data['message'] = "Order not found ";
                            return response($data);
                        }
               
                
        }catch (\Exception $e) {
           $response = ['status' => '0', 'message' => $e->getMessage()];
           return response($response);
        }catch (\Throwable $e) {
            $response = ['status' => '0','message' => $e->getMessage()];
            return response($response);
        }
    }
    
    
}

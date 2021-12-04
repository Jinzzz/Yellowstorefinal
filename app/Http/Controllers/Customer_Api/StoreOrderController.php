<?php

namespace App\Http\Controllers\Customer_Api;

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

use App\Models\admin\Mst_store_product;
use App\Models\admin\Mst_business_types;

use App\Models\admin\Mst_attribute_group;
use App\Models\admin\Mst_attribute_value;

use App\Models\admin\Mst_categories;
use App\Models\admin\Mst_store_agencies;
use App\Models\admin\Mst_SubCategory;
use App\Models\admin\Mst_GlobalProducts;
use App\Models\admin\Mst_store_product_varient;
use App\Models\admin\Trn_ProductVariantAttribute;

use App\Models\admin\Mst_product_image;
use App\Models\admin\Trn_GlobalProductImage;

use App\Models\admin\Mst_CustomerAppBanner;
use App\Models\admin\Trn_store_customer;
use App\Models\admin\Trn_Cart;
use App\Models\admin\Mst_Coupon;
use App\Models\admin\Mst_dispute;
use App\Models\admin\Mst_Issues;
use App\Models\admin\Sys_IssueType;
use App\Models\admin\Trn_StoreDeliveryTimeSlot;
use App\Models\admin\Sys_payment_type;
use App\Models\admin\Trn_store_order;
use App\Models\admin\Trn_order_invoice;
use App\Models\admin\Trn_store_order_item;
use App\Models\admin\Sys_store_order_status;
use App\Models\admin\Mst_delivery_boy;
use App\Models\admin\Trn_CustomerDeviceToken;
use App\Models\admin\Trn_StoreDeviceToken;
use App\Models\admin\Trn_StoreAdmin;
use App\Models\admin\Trn_StoreWebToken;
use App\Models\admin\Trn_customer_reward;

use App\Models\admin\Mst_StockDetail;
use App\Models\admin\Trn_DeliveryBoyLocation;

class StoreOrderController extends Controller
{
    
    
    public function saveOrderService(Request $request)
    {
        //dd($request->all());
         
        try {
            
            
            
                
            if(isset($request->store_id) && Mst_store::find($request->store_id))
            { 
                $validator = Validator::make($request->all(),
                [
                    'customer_id'   => 'required',
                    'order_total_amount'  =>'required',
                    'payment_type_id'   =>'required',
                    'status_id' =>'required',
                    'product_variants.*.product_id'    =>'required',
                    'product_variants.*.product_varient_id'    =>'required',
                    'product_variants.*.quantity'    =>'required',
                    'product_variants.*.unit_price'    =>'required',
                    'product_variants.*.total_amount'    =>'required',
                    'product_variants.*.tax_amount'    =>'required',
                    'product_variants.*.discount_amount'    =>'required',
                  //  'product_variants.*.discount_percentage'    =>'required',
                ],
                [
                    'customer_id.required'  => 'Customer required',
                    'total_amount.required' => 'Total order amount required',
                    'payment_type_id.required'  => 'Payment type required',
                    'status_id.required'    => 'Status required',
                    'product_variants.*.product_id.required'    =>'Product required',
                    'product_variants.*.product_varient_id.required'    =>'Product variant required',
                    'product_variants.*.quantity.required'    =>'Product quantity required',
                    'product_variants.*.unit_price.required'    =>'Product quantity required',
                    'product_variants.*.total_amount.required'    =>'Total amount required',
                    'product_variants.*.tax_amount.required'    =>'Tax amount required',
                    'product_variants.*.discount_amount.required'    =>'Discount amount required',
                  // 'product_variants.*.discount_percentage.required'    =>'Discount percentage required',
                ]);

                if(!$validator->fails())
                {
                    $noStockProducts = array();
                    foreach($request->product_variants as $value)
                    {
                          $varProdu = Mst_store_product_varient::find($value['product_varient_id']);
                          $proData = Mst_store_product::find($varProdu->product_id);
                           if(isset($varProdu))
                           {
                               if($value['quantity'] > $varProdu->stock_count)
                               {
                                   if(@$proData->product_name != $varProdu->variant_name)
                                   {
                                    $data['product_name'] = @$proData->product_name." ".$varProdu->variant_name;
                                   }
                                   else
                                   {
                                    $data['product_name'] = @$proData->product_name;
                                   }
                                   
                                   $noStockProducts[] = $varProdu->product_varient_id ;
                                   
                                   $data['product_varient_id'] = $varProdu->product_varient_id;
                                   $data['product_id'] = $varProdu->product_id;
                                   $data['message'] = 'Stock unavilable';
                                   $data['status'] = 2;
                                  //  return response($data);
                               }
                           }
                           else
                           {
                                $data['message'] = 'Product not found';
                                $data['status'] = 2;
                                return response($data);
                           }
                      
                    }
                    if(count($noStockProducts) > 0)
                    {
                        $data['noStockProducts'] = $noStockProducts;
                        return response($data);

                    }
                    $storeOrderCount = Trn_store_order::where('store_id',$request->store_id)->count();

                    $orderNumber = @$storeOrderCount + 1;

                    $store_data = Mst_store::find($request->store_id);
        
                    if(isset($store_data->order_number_prefix))
                    {
                        $orderNumberPrefix = $store_data->order_number_prefix;
                    }
                    else
                    {
                        $orderNumberPrefix = 'ORDRYSTR';
                    }
            

                    $store_order = new Trn_store_order;

                    $store_order->service_order =  1;
                    $store_order->service_booking_order =  $request->service_booking_order;
                    $store_order->product_varient_id =  $request->product_varient_id;

                    $store_order->order_number = $orderNumberPrefix.@$orderNumber;
                    // $store_order->order_number = 'ORDRYSTR'.@$orderNumber;
                    $store_order->customer_id = $request->customer_id;
                    $store_order->store_id =  $request->store_id;
                        
                    
                    $store_order->subadmin_id =  $store_data->subadmin_id;
                    $store_order->product_total_amount =  $request->order_total_amount - $request->amount_reduced_by_rp;
                    $store_order->payment_status = 1;
                    $store_order->status_id = 1;

                    // online
                    $store_order->payment_type_id = $request->payment_type_id;
                    $store_order->delivery_charge =  $request->delivery_charge;
                    $store_order->packing_charge =  $request->packing_charge;
                    
                    $store_order->time_slot =  $request->time_slot;

                    
                    
                    $store_order->delivery_address =  $request->delivery_address;
                    
                    $store_order->coupon_id =  $request->coupon_id;
                    $store_order->coupon_code =  $request->coupon_code;
                    $store_order->reward_points_used =  $request->reward_points_used;
                    $store_order->amount_before_applying_rp =  $request->amount_before_applying_rp;
                    $store_order->amount_reduced_by_rp =  $request->amount_reduced_by_rp;
                    $store_order->order_type = 'APP';
                    
                    if(isset($request->amount_reduced_by_coupon))
                    $store_order->amount_reduced_by_coupon =  $request->amount_reduced_by_coupon;

                    $store_order->save();
                    $order_id = DB::getPdo()->lastInsertId();
                    
                    // if(Trn_store_order::where('customer_id',$request->customer_id)->count() < 1)
                    // {
                    //      $configPoint = Trn_configure_points::find(1);

                    //     $cr = new Trn_customer_reward;
                    //     $cr->transaction_type_id = 0;
                    //     $cr->reward_points_earned = $configPoint->first_order_points;
                    //     $cr->customer_id = $request->customer_id;
                    //     $cr->order_id = $order_id;
                    //     $cr->reward_approved_date = Carbon::now()->format('Y-m-d');
                    //     $cr->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                    //     $cr->reward_point_status = 1;
                    //     $cr->discription = "First order points";
                    //     $cr->save();
                    // }
                    

                    $invoice_info['order_id'] = $order_id;
                    $invoice_info['invoice_date'] =  Carbon::now()->format('Y-m-d');
                    $invoice_info['invoice_id'] = "INV0".$order_id;
                    $invoice_info['created_at'] = Carbon::now();
                    $invoice_info['updated_at'] = Carbon::now();

                    Trn_order_invoice::insert($invoice_info);

                    foreach($request->product_variants as $value)
                    {
                        $productVarOlddata = Mst_store_product_varient::find($value['product_varient_id']);

                        if(!isset($value['discount_amount']))
                        {
                            $value['discount_amount'] = 0;
                        }
                        
                        
                        
                        $total_amount = $value['quantity'] * $value['unit_price'];

                        $data2 = [
                            'order_id'=> $order_id,
                            'product_id'=> $value['product_id'],
                            'product_varient_id'=> $value['product_varient_id'],
                            'customer_id'=> $request['customer_id'],
                            'store_id'=> $request['store_id'],
                            'quantity'=> $value['quantity'],
                            'unit_price'=>  $value['unit_price'],
                            'tax_amount'=> $value['tax_amount'],
                            'total_amount'=> $total_amount,
                            'discount_amount'=> $value['discount_amount'],
                            //'discount_percentage'=> $value['discount_percentage'],
                            'created_at'         => Carbon::now(),
                            'updated_at'         => Carbon::now(),
                        ];
                        Trn_store_order_item::insert($data2);
                    }
                    

                    
                  $storeDatas = Trn_StoreAdmin::where('store_id',$request->store_id)->where('role_id',0)->first();
                  $customerDevice = Trn_CustomerDeviceToken::where('customer_id',$request->customer_id)->get();
                  $storeDevice = Trn_StoreDeviceToken::where('store_admin_id',$storeDatas->store_admin_id)->where('store_id',$request->store_id)->get();
                  $orderdatas = Trn_store_order::find($order_id);
                  
                    foreach($storeDevice as $sd)
                   {
                       $title = 'New service order arrived';
                       $body = 'New order with order id '.$orderdatas->order_number.' has been saved successully..';
                     $data['response'] =  $this->storeNotification($sd->store_device_token,$title,$body);
                   }
                   
                   
                    $storeWeb = Trn_StoreWebToken::where('store_admin_id',$storeDatas->store_admin_id)->where('store_id',$request->store_id)->get();
                    foreach($storeWeb as $sw)
                   {
                       $title = 'New service order arrived';
                       $body = 'New order with order id '.$orderdatas->order_number.' has been saved successully..';
                       $data['response'] =  Helper::storeNotifyWeb($sw->store_web_token,$title,$body);
                   }
                   
                    foreach($customerDevice as $cd)
                   {
                       $title = 'Order Placed';
                       $body = 'Your order with order id '.$orderdatas->order_number.' has been saved successully..';
                          
                         //   $title = 'Title';
                         //  $body = 'Body';
                       
                     $data['response'] =  $this->customerNotification($cd->customer_device_token,$title,$body);
                   }
                    


                    $data['status'] = 1;
                    $data['order_id'] = $order_id;
                    if($request->service_booking_order == 1)
                    {
                        $data['message'] = "Booking successful.";
                    }
                    else
                    {                    
                        $data['message'] = "Order saved.";
                        
                    }

                    return response($data);


                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    $data['errors'] = $validator->errors();
                    return response($data);
            
                }
            
            }
            else
            {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
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
    
    public function storeTimeSlots(Request $request)
    {
        $data = array(); 
        try {
                if(isset($request->store_id) && Mst_store::find($request->store_id))
                {
                    $store_id = $request->store_id;
                    if($data['timeSlotDetails']  = Trn_StoreDeliveryTimeSlot::
                        select('store_delivery_time_slot_id','store_id','time_start','time_end')
                        ->where('store_id',$store_id)->get())
                    {
                       
                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    }
                    else{
                        $data['status'] = 0;
                        $data['message'] = "failed";
                        return response($data);
                    }
                }
                else
                {
                    $data['status'] = 4;
                    $data['message'] = "Customer not found";
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


    public function listPaymentType(Request $request)
    {
        $data = array(); 
        try {
               
                    if($data['paymentTypes']  = Sys_payment_type::all())
                    {
                       
                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    }
                    else{
                        $data['status'] = 0;
                        $data['message'] = "failed";
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


     public function saveOrder(Request $request)
    {
        //dd($request->all());
         
        try {
            
            
            
                
            if(isset($request->store_id) && Mst_store::find($request->store_id))
            { 
                $validator = Validator::make($request->all(),
                [
                    'customer_id'   => 'required',
                    'order_total_amount'  =>'required',
                    'payment_type_id'   =>'required',
                    'status_id' =>'required',
                    'product_variants.*.product_id'    =>'required',
                    'product_variants.*.product_varient_id'    =>'required',
                    'product_variants.*.quantity'    =>'required',
                    'product_variants.*.unit_price'    =>'required',
                    'product_variants.*.total_amount'    =>'required',
                    'product_variants.*.tax_amount'    =>'required',
                    'product_variants.*.discount_amount'    =>'required',
                  //  'product_variants.*.discount_percentage'    =>'required',
                ],
                [
                    'customer_id.required'  => 'Customer required',
                    'total_amount.required' => 'Total order amount required',
                    'payment_type_id.required'  => 'Payment type required',
                    'status_id.required'    => 'Status required',
                    'product_variants.*.product_id.required'    =>'Product required',
                    'product_variants.*.product_varient_id.required'    =>'Product variant required',
                    'product_variants.*.quantity.required'    =>'Product quantity required',
                    'product_variants.*.unit_price.required'    =>'Product quantity required',
                    'product_variants.*.total_amount.required'    =>'Total amount required',
                    'product_variants.*.tax_amount.required'    =>'Tax amount required',
                    'product_variants.*.discount_amount.required'    =>'Discount amount required',
                  // 'product_variants.*.discount_percentage.required'    =>'Discount percentage required',
                ]);

                if(!$validator->fails())
                {
                    $noStockProducts = array();
                    
                    
                    foreach($request->product_variants as $value)
                    {
                        if($request->service_order != 1)
                        {

                          $varProdu = Mst_store_product_varient::find($value['product_varient_id']);
                          $proData = Mst_store_product::find($varProdu->product_id);
                           if(isset($varProdu))
                           {
                               if($value['quantity'] > $varProdu->stock_count)
                               {
                                   if(@$proData->product_name != $varProdu->variant_name)
                                   {
                                    $data['product_name'] = @$proData->product_name." ".$varProdu->variant_name;
                                   }
                                   else
                                   {
                                    $data['product_name'] = @$proData->product_name;
                                   }
                                   
                                   $noStockProducts[] = $varProdu->product_varient_id ;
                                   
                                   $data['product_varient_id'] = $varProdu->product_varient_id;
                                   $data['product_id'] = $varProdu->product_id;
                                   $data['message'] = 'Stock unavilable';
                                   $data['status'] = 2;
                                  //  return response($data);
                               }
                           }
                           else
                           {
                                $data['message'] = 'Product not found';
                                $data['status'] = 2;
                                return response($data);
                           }
                        }
                      
                    }
                    if(count($noStockProducts) > 0)
                    {
                        $data['noStockProducts'] = $noStockProducts;
                        return response($data);

                    }
                    $storeOrderCount = Trn_store_order::where('store_id',$request->store_id)->count();

                    $orderNumber = @$storeOrderCount + 1;

                    $store_data = Mst_store::find($request->store_id);
        
                    if(isset($store_data->order_number_prefix))
                    {
                        $orderNumberPrefix = $store_data->order_number_prefix;
                    }
                    else
                    {
                        $orderNumberPrefix = 'ORDRYSTR';
                    }
            

                    $store_order = new Trn_store_order;
                    
                      if(isset($request->service_order))
                    $store_order->service_order =  $request->service_order; // service order - booking order
                    

                    $store_order->order_number = $orderNumberPrefix.@$orderNumber;
                    // $store_order->order_number = 'ORDRYSTR'.@$orderNumber;
                    $store_order->customer_id = $request->customer_id;
                    $store_order->store_id =  $request->store_id;
                        
                    
                    $store_order->subadmin_id =  $store_data->subadmin_id;
                    $store_order->product_total_amount =  $request->order_total_amount - $request->amount_reduced_by_rp;
                    $store_order->payment_status = 1;
                    $store_order->status_id = 1;

                    // online
                    $store_order->payment_type_id = $request->payment_type_id;
                    $store_order->delivery_charge =  $request->delivery_charge;
                    $store_order->packing_charge =  $request->packing_charge;
                    
                    $store_order->time_slot =  $request->time_slot;

                    
                    
                    $store_order->delivery_address =  $request->delivery_address;
                    
                    $store_order->coupon_id =  $request->coupon_id;
                    $store_order->coupon_code =  $request->coupon_code;
                    $store_order->reward_points_used =  $request->reward_points_used;
                    $store_order->amount_before_applying_rp =  $request->amount_before_applying_rp;
                    $store_order->amount_reduced_by_rp =  $request->amount_reduced_by_rp;
                    $store_order->order_type = 'APP';
                    
                    if(isset($request->amount_reduced_by_coupon))
                    $store_order->amount_reduced_by_coupon =  $request->amount_reduced_by_coupon;

                    $store_order->save();
                    $order_id = DB::getPdo()->lastInsertId();
                    
                    // if(Trn_store_order::where('customer_id',$request->customer_id)->count() < 1)
                    // {
                    //      $configPoint = Trn_configure_points::find(1);

                    //     $cr = new Trn_customer_reward;
                    //     $cr->transaction_type_id = 0;
                    //     $cr->reward_points_earned = $configPoint->first_order_points;
                    //     $cr->customer_id = $request->customer_id;
                    //     $cr->order_id = $order_id;
                    //     $cr->reward_approved_date = Carbon::now()->format('Y-m-d');
                    //     $cr->reward_point_expire_date = Carbon::now()->format('Y-m-d');
                    //     $cr->reward_point_status = 1;
                    //     $cr->discription = "First order points";
                    //     $cr->save();
                    // }
                    

                    $invoice_info['order_id'] = $order_id;
                    $invoice_info['invoice_date'] =  Carbon::now()->format('Y-m-d');
                    $invoice_info['invoice_id'] = "INV0".$order_id;
                    $invoice_info['created_at'] = Carbon::now();
                    $invoice_info['updated_at'] = Carbon::now();

                    Trn_order_invoice::insert($invoice_info);

                    foreach($request->product_variants as $value)
                    {
                        $productVarOlddata = Mst_store_product_varient::find($value['product_varient_id']);
                        Mst_store_product_varient::where('product_varient_id','=',$value['product_varient_id'])->decrement('stock_count',$value['quantity']);
                        
                        if(!isset($value['discount_amount']))
                        {
                            $value['discount_amount'] = 0;
                        }
                        
                        if($request->service_order != 1)
                        {
                         $negStock = -1 * abs($value['quantity']);

                           $sd = new Mst_StockDetail;
                           $sd->store_id = $request->store_id;
                           $sd->product_id = $value['product_id'];
                           $sd->stock = $negStock;
                           $sd->product_varient_id = $value['product_varient_id'];
                           $sd->prev_stock = $productVarOlddata->stock_count;
                           $sd->save();
                        }
           
                        
                        $total_amount = $value['quantity'] * $value['unit_price'];

                        $data2 = [
                            'order_id'=> $order_id,
                            'product_id'=> $value['product_id'],
                            'product_varient_id'=> $value['product_varient_id'],
                            'customer_id'=> $request['customer_id'],
                            'store_id'=> $request['store_id'],
                            'quantity'=> $value['quantity'],
                            'unit_price'=>  $value['unit_price'],
                            'tax_amount'=> $value['tax_amount'],
                            'total_amount'=> $total_amount,
                            'discount_amount'=> $value['discount_amount'],
                            //'discount_percentage'=> $value['discount_percentage'],
                            'created_at'         => Carbon::now(),
                            'updated_at'         => Carbon::now(),
                        ];
                        Trn_store_order_item::insert($data2);
                    }
                    

                    
                  $storeDatas = Trn_StoreAdmin::where('store_id',$request->store_id)->where('role_id',0)->first();
                  $customerDevice = Trn_CustomerDeviceToken::where('customer_id',$request->customer_id)->get();
                  $storeDevice = Trn_StoreDeviceToken::where('store_admin_id',$storeDatas->store_admin_id)->where('store_id',$request->store_id)->get();
                  $orderdatas = Trn_store_order::find($order_id);
                  
                    foreach($storeDevice as $sd)
                   {
                       $title = 'New order arrived';
                       $body = 'New order with order id '.$orderdatas->order_number.' has been saved successully..';
                     $data['response'] =  $this->storeNotification($sd->store_device_token,$title,$body);
                   }
                   
                   
                    $storeWeb = Trn_StoreWebToken::where('store_admin_id',$storeDatas->store_admin_id)->where('store_id',$request->store_id)->get();
                    foreach($storeWeb as $sw)
                   {
                       $title = 'New order arrived';
                       $body = 'New order with order id '.$orderdatas->order_number.' has been saved successully..';
                       $data['response'] =  Helper::storeNotifyWeb($sw->store_web_token,$title,$body);
                   }
                   
                    foreach($customerDevice as $cd)
                   {
                       $title = 'Order Placed';
                       $body = 'Your order with order id '.$orderdatas->order_number.' has been saved successully..';
                          
                         //   $title = 'Title';
                         //  $body = 'Body';
                       
                     $data['response'] =  $this->customerNotification($cd->customer_device_token,$title,$body);
                   }
                    


                    $data['status'] = 1;
                    $data['order_id'] = $order_id;
                    $data['message'] = "Order saved.";
                    return response($data);


                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    $data['errors'] = $validator->errors();
                    return response($data);
            
                }
            
            }
            else
            {
                $data['status'] = 0;
                $data['message'] = "Store not found ";
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
    
   private function customerNotification($device_id,$title,$body){
        $url = 'https://fcm.googleapis.com/fcm/send';
        $api_key = 'AAAA09gixf4:APA91bFiBdhtMnj2UBtqSQ9YlZ_uxvdOOOzE-otA9Ja2w0cFUpX230Xv0Yi87owPBlFDp1H02FWpv4m8azPsuMmeAmz0msoeF-1Cxx0iVpDSOjYBTCWxzUYT8tKTuUvLb08MDsRXHbgM';
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
  
  
    public function stockAvailability(Request $request)
    {
        $data = array(); 
        try {
               
                $noStockProducts = array();
                    foreach($request->product_variants as $value)
                    {
                          if($varProdu = Mst_store_product_varient::find($value['product_varient_id']))
                          {
                          $proData = Mst_store_product::find($varProdu->product_id);
                           if(isset($varProdu))
                           {
                               if($value['quantity'] > $varProdu->stock_count)
                               {
                                  
                                   $noStockProducts[] = $varProdu->product_varient_id ;
                                   
                                   $data['message'] = 'Stock unavilable';
                                   $data['status'] = 2;
                                  //  return response($data);
                               }
                           }
                           else
                           {
                                $data['message'] = 'Product not found';
                                $data['status'] = 0;
                                return response($data);
                           }
                          }
                          else
                           {
                                $data['message'] = 'Product not found';
                                $data['status'] = 0;
                                return response($data);
                           }
                          
                      
                    }
                    if(count($noStockProducts) > 0)
                    {
                        $data['noStockProducts'] = $noStockProducts;
                        return response($data);

                    }
                    else
                    {
                         $data['message'] = 'Product avilable';
                        $data['status'] = 1;
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

    public function issueTypes(Request $request)
    {
        $data = array(); 
        try {
               
                    if($data['issueTypes']  = Sys_IssueType::all())
                    {
                       
                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    }
                    else{
                        $data['status'] = 0;
                        $data['message'] = "failed";
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

     public function issues(Request $request)
    {
        $data = array(); 
        try {
               
                if(isset($request->issue_type_id) && Sys_IssueType::find($request->issue_type_id))
                {          
                    if($data['issues']  = Mst_Issues::where('issue_type_id',$request->issue_type_id)->get())
                    {
                    
                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    }
                    else{
                        $data['status'] = 0;
                        $data['message'] = "failed";
                        return response($data);
                    }
                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "Issue type not found";
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

     public function uploadIssue(Request $request)
    {
        try {
               // dd($request->all());
            // if(isset($request->customer_id) && Mst_store::find($request->customer_id))
            // { 
                $validator = Validator::make($request->all(),
                [
                    'order_id'   => 'required',
                    'issue_id'   => 'required',
                    'customer_id'   => 'required',
                    'discription'   => 'required',
                   
                ],
                [
                    'order_id.required'  => 'Order required',
                    'issue_id.required'  => 'Issue required',
                    'customer_id.required'  => 'Customer required',
                    'discription.required'  => 'Discription required',
                ]);

                if(!$validator->fails())
                {
                   
                    $dispute = new Mst_dispute;
                    $dispute->issue_id = $request->issue_id;
                    $dispute->order_id = $request->order_id;
                    
                    if(isset($request->order_item_id))
                    $dispute->order_item_id = $request->order_item_id;
                    else
                    $dispute->order_item_id = 0;

                    $orderData = Trn_store_order::find($request->order_id);
                    $dispute->order_number = $orderData->order_number;
                    $dispute->store_id = $orderData->store_id;
                    
                    $storeData = Mst_store::find($orderData->store_id);
                    
                    if(isset($storeData->subadmin_id))
                    $dispute->subadmin_id = $storeData->subadmin_id;
                    else
                    $dispute->subadmin_id = 0;
                    
                    if(isset($dispute->order_item_id))
                    $dispute->item_ids = $request->order_item_id;

                    // foreach($request->order_item_id as $item)
                    // {
                    //     $itemData = Trn_store_order_item::find($item);
                    //     $productData = Mst_store_product_varient::where('product_varient_id',$itemData->product_varient_id)->first();
                    //     $dispute->product_id = $itemData->product_varient_id;
                    // }
                    
                        $dispute->product_id = 0;

                    // if(isset($request->order_item_id) && $request->order_item_id != 0)
                    // {
                       
                    // }
                    // else
                    // {
                    //     $dispute->product_id = 0;

                    // }

                    $dispute->customer_id = $request->customer_id;
                    $dispute->dispute_date = Carbon::now()->toDateString();
                    $dispute->discription = $request->discription;
                    $dispute->dispute_status = 2;
                    $dispute->save();
                 

                    $data['status'] = 1;
                    $data['message'] = "Issue uploded.";
                    return response($data);


                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "failed";
                    $data['errors'] = $validator->errors();
                    return response($data);
            
                }
            
            // }
            // else
            // {
            //     $data['status'] = 0;
            //     $data['message'] = "Store not found ";
            //     return response($data);
            // }

        }catch (\Exception $e) {
           $response = ['status' => '0', 'message' => $e->getMessage()];
           return response($response);
        }catch (\Throwable $e) {
            $response = ['status' => '0','message' => $e->getMessage()];
            return response($response);
        }

    }


    public function orderHistory(Request $request)
    {
        $data = array(); 
        try {
                if(isset($request->customer_id) && Trn_store_customer::find($request->customer_id))
                {
                    $customer_id = $request->customer_id;
                    if($data['orderHistory'] = Trn_store_order::select('order_id','order_number','store_id','created_at','status_id','customer_id','product_total_amount')->where('customer_id',$request->customer_id)->orderBy('order_id','DESC')->get())
                    {
                        foreach($data['orderHistory'] as $order)
                        {
                            $storeData = Mst_store::find($order->store_id);
                            $order->store_name = $storeData->store_name;
                            if(isset($order->customer_id))
                            {
                                $customerData = Trn_store_customer::find($order->customer_id);
                                $order->customer_name = @$customerData->customer_first_name." ".@$customerData->customer_last_name;
                                $order->customer_mobile_number = @$customerData->customer_mobile_number;
                            }
                            else
                            {
                                $order->customer_name = null;
                            }

                            if(isset($order->status_id))
                            {
                                $statusData = Sys_store_order_status::find(@$order->status_id);
                                $order->status_name = @$statusData->status;
                            }
                            else
                            {
                                $order->status_name = null;
                            }
                            $order->order_date = Carbon::parse($order->created_at)->format('d-m-Y');
                            $order->invoice_link =  url('get/invoice/'.Crypt::encryptString($order->order_id));

                        }
                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    }
                    else{
                        $data['status'] = 0;
                        $data['message'] = "failed";
                        return response($data);
                    }
                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "Customer not found ";
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


    public function viewOrder(Request $request)
    {
        $data = array(); 
        
        try {
                if(isset($request->customer_id) && Trn_store_customer::find($request->customer_id))
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
                            $customer_id = $request->customer_id;
                           // dd(Trn_store_order::select('order_id','delivery_boy_id','order_note','payment_type_id','order_number','created_at','status_id','customer_id','product_total_amount')->where('order_id',$order_id)->where('store_id',$store_id)->first());
                            
                            if($data['orderDetails']  = Trn_store_order::select('order_id','delivery_accept','product_varient_id','service_order','service_booking_order','delivery_date','amount_reduced_by_rp','delivery_time','store_id','delivery_boy_id','order_note','payment_type_id','order_number','created_at','status_id','customer_id','product_total_amount')->where('order_id',$order_id)->where('customer_id',$customer_id)->first())
                            {
                                
                                if($data['orderDetails']->delivery_accept == 1)
                                {
                                    $data['orderDetails']->show_dboy_phone = 1;
                                }
                                else
                                {
                                    $data['orderDetails']->show_dboy_phone = 0;
                                }
                               
                                if(isset($data['orderDetails']->customer_id))
                                {
                                    if(!isset($data['orderDetails']->amount_reduced_by_rp))
                                    {
                                        $data['orderDetails']->amount_reduced_by_rp = "0";
                                    }
                                    $customerData = Trn_store_customer::find($data['orderDetails']->customer_id);
                                    $data['orderDetails']->customer_name = $customerData->customer_first_name." ".$customerData->customer_last_name;
                                   
                                   
                                   
                                $data['orderDetails']->customer_mobile = @$customerData->customer_mobile_number;
                                $data['orderDetails']->customer_address = @$customerData->customer_address;
                                $data['orderDetails']->customer_pincode = @$customerData->customer_pincode;
                                $deliveryBoy = Mst_delivery_boy::find($data['orderDetails']->delivery_boy_id);
                                    $data['orderDetails']->delivery_boy = @$deliveryBoy->delivery_boy_name;
                                    $data['orderDetails']->delivery_boy_mobile = @$deliveryBoy->delivery_boy_mobile;
                                    $dboyLoc = Trn_DeliveryBoyLocation::where('delivery_boy_id',@$deliveryBoy->delivery_boy_id)
                                        ->orderBy('dbl_id','DESC')->first();
                                    $data['orderDetails']->latitude = @$dboyLoc->latitude;
                                    $data['orderDetails']->longitude = @$dboyLoc->longitude;
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

                                $data['orderDetails']->delivery_type = null;
                                $data['orderDetails']->delivery_date = Carbon::parse($data['orderDetails']->delivery_date)->format('d-m-Y');
                                $data['orderDetails']->delivery_time =  Carbon::parse($data['orderDetails']->updated_at)->format('h:i');
                                $data['orderDetails']->time_slot = null;
                                $data['orderDetails']->processed_by = null;

                                $invoice_data = \DB::table('trn_order_invoices')->where('order_id',$order_id)->first();
                                $data['orderDetails']->invoice_id = @$invoice_data->invoice_id;
                                $data['orderDetails']->invoice_date = @$invoice_data->invoice_date;
                    

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
                                
                                if($data['orderDetails']->payment_type_id == 1)
                                $data['orderDetails']->payment_type = 'Offline';
                                else
                                $data['orderDetails']->payment_type = 'Online';
                                
                                $data['orderDetails']->invoice_link =  url('get/invoice/'.Crypt::encryptString($data['orderDetails']->order_id));
                                $data['orderDetails']->item_list_link = url('item/list/'.Crypt::encryptString($data['orderDetails']->order_id));
    
                                $data['orderDetails']->orderItems = Trn_store_order_item::where('order_id',$data['orderDetails']->order_id)
                                ->select('product_id','product_varient_id','order_item_id','quantity','discount_amount','discount_percentage','total_amount','tax_amount','unit_price','tick_status')
                                ->get();
                                
                                
                                $data['orderDetails']->serviceData = new \stdClass();
                                if($data['orderDetails']->service_booking_order == 1)
                                {
                                    $serviceData = Mst_store_product_varient::find(@$data['orderDetails']->product_varient_id);
                                    @$serviceData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/'.@$serviceData->product_varient_base_image;
                                    $baseProductDetail = Mst_store_product::find(@$serviceData->product_id);
                                    $serviceData->product_base_image = '/assets/uploads/products/base_product/base_image/'.@$baseProductDetail->product_base_image;
                                    
                                    if(@$baseProductDetail->product_name != @$serviceData->variant_name)
                                    $serviceData->product_name = @$baseProductDetail->product_name." ". @$serviceData->productDetail->variant_name;
                                    else
                                    $serviceData->product_name = @$baseProductDetail->product_name;
                                    $data['orderDetails']->serviceData = $serviceData; 

                                }


                                foreach ($data['orderDetails']->orderItems as $value) 
                                {


                                    $value['productDetail'] = Mst_store_product_varient::find($value->product_varient_id);
                                    @$value->productDetail->product_varient_base_image = '/assets/uploads/products/base_product/base_image/'.@$value->productDetail->product_varient_base_image;
                                    
                                    $baseProductDetail = Mst_store_product::find($value->product_id);

                                    $value->product_base_image = '/assets/uploads/products/base_product/base_image/'.@$baseProductDetail->product_base_image;
                                    
                                    if($baseProductDetail->product_name != isset($value->productDetail->variant_name))
                                    $value->product_name = @$baseProductDetail->product_name." ". @$value->productDetail->variant_name;
                                    else
                                     $value->product_name = @$baseProductDetail->product_name;
                                     
                                    $taxFullData = Mst_Tax::find(@$baseProductDetail->tax_id);
                                
                                    $splitdata = \DB::table('trn__tax_split_ups')->where('tax_id',@$baseProductDetail->tax_id)->get();
                                    $stax = 0;
                                    

                                    foreach($splitdata as $sd)
                                    {
                                        if(@$taxFullData->tax_value == 0 || !isset($taxFullData->tax_value))
                                        $taxFullData->tax_value = 1;
                                         
                                        $stax = ($sd->split_tax_value * $value->tax_amount) / @$taxFullData->tax_value;
                                        $sd->tax_split_value = number_format((float)$stax, 2, '.', '');
                                    }
                                    
                                    $value['taxSplitups']  = $splitdata;
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
                    $data['message'] = "Customer not found ";
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

 public function cancelOrder(Request $request)
    {
        $data = array(); 
        
        try {
                if(isset($request->customer_id) && Trn_store_customer::find($request->customer_id))
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
                            $customer_id = $request->customer_id;

                            $orderData = Trn_store_order::find($order_id);
                            
                            $orderData->status_id = 5;
                            if($orderData->update())
                            {
                                
                                $orderItemData = Trn_store_order_item::where('order_id',$order_id)->get();
                                    foreach($orderItemData as $o)
                                    {
                                        
                                        $productVarOlddata = Mst_store_product_varient::find($o->product_varient_id);
                        
                                        $sd = new Mst_StockDetail;
                                        $sd->store_id = $orderData->store_id;
                                        $sd->product_id = $o->product_id;
                                        $sd->stock = $o->quantity;
                                        $sd->product_varient_id = $o->product_varient_id;
                                        $sd->prev_stock = $productVarOlddata->stock_count;
                                        $sd->save();
                                        
                                        DB::table('mst_store_product_varients')->where('product_varient_id', $o->product_varient_id)->increment('stock_count',$o->quantity);
                                    }
            
            
                                $data['status'] = 1;
                                $data['message'] = "Order cancelled";
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
                            $data['message'] = "Order not found ";
                        }
                }
                else
                {
                    $data['status'] = 0;
                    $data['message'] = "Customer not found ";
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


}

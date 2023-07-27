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
use App\Models\admin\District;
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
use App\Models\admin\Trn_ProductVideo;

use App\Models\admin\Mst_CustomerAppBanner;
use App\Models\admin\Trn_store_customer;
use App\Models\admin\Trn_MostVisitedProductsByCustomer;
use App\Models\admin\Trn_store_order;
use App\Models\admin\Sys_store_order_status;
use App\Models\admin\Trn_RecentlyVisitedProducts;
use App\Models\admin\Trn_ReviewsAndRating;
use App\Models\admin\Mst_store_images;
use App\Models\admin\Trn_RecentlyVisitedStore;
use App\Models\admin\Trn_Cart;
use App\Models\admin\Trn_customerAddress;
use App\Models\admin\Trn_customer_reward;
use App\Models\admin\Trn_customer_reward_transaction_type;
use App\Models\admin\Mst_Coupon;
use App\Models\admin\Trn_store_order_item;
use App\Models\admin\Sys_IssueType;
use App\Models\admin\Mst_Issues;
use App\Models\admin\Sys_payment_type;
use App\Models\admin\Trn_StoreDeliveryTimeSlot;
use App\Models\admin\Trn_configure_points;
use App\Models\admin\Trn_CustomerFeedback;
use App\Models\admin\Trn_RecentlyVisitedProductCategory;
use App\Models\admin\Mst_FeedbackQuestion;
use App\Models\admin\Trn_points_redeemed;
use App\Models\admin\Trn_store_setting;
use App\Models\admin\Trn_StoreAdmin;
use App\Models\admin\Trn_StoreBankData;
use App\Trn_wallet_log;

class ProductController extends Controller
{


    public function viewBaseProductVariants(Request $request)
    {
        $data = array();
        try {

            if (isset($request->product_id) && $productData = Mst_store_product::find($request->product_id)) {

            $base_varient_stock=0;

                $productVartiantdata  = Mst_store_product_varient::where('product_id', $productData->product_id)
                    ->where('stock_count', '>', 0)
                    ->where('is_removed',0)
                    ->where('variant_status',1)
                    ->get();
                foreach ($productVartiantdata as $row) {
                    if($row->product_varient_base_image!=NULL)
                    {
                        $row->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $row->product_varient_base_image;

                    }
                    else
                    {
                        $row->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;

                    }
                    if($row->is_base_variant==1)
                    {
                        if($productData->product_status==0)
                        {
                            $row->variant_status="0";

                        }
                        $base_varient_stock=$row->stock_count;

                    }
                    $in_cart=Trn_Cart::where('customer_id',$request->customer_id)->where('product_varient_id',$row->product_varient_id)->where('remove_status',0)->first();
                    if($in_cart)
                    {
                        $cartCount=$in_cart->quantity;
                        $cartId=$in_cart->cart_id;


                    }

                    else
                    {
                        $cartCount=0;
                        $cartId=0;

                    }
                    $row->cartCount=(int)$cartCount;
                    $row->cartId=(int)$cartId;

                    
                    
                    $attributesData = Trn_ProductVariantAttribute::select('attr_group_id', 'attr_value_id')->where('product_varient_id', $row->product_varient_id)->get();
                    foreach ($attributesData as $j) {
                        $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                        if (isset($datas->group_name))
                            $j->attr_group = @$datas->group_name;
                        else
                            $j->attr_group = '';

                        $datasvalue = Mst_attribute_value::where('attr_value_id', $j->attr_value_id)->first();
                        if (isset($datasvalue->group_value))
                            $j->attr_value = @$datasvalue->group_value;
                        else
                            $j->attr_value = '';
                    }
                    $row->attributesData = $attributesData;
                    $row->store_name = Mst_store::find($productData->store_id)->store_name;
                    $row->product_name = $productData->product_name;
                    $row->product_type = $productData->product_type;
                    $row->service_type = $productData->service_type;
                }
                $data['productVartiantdata'] = $productVartiantdata;
                $variant_stock_count=Mst_store_product_varient::where('product_id',$productData->product_id)->where('is_removed',0)->where('stock_count','>',0)->where('variant_status',1)->sum('stock_count');
                if($variant_stock_count<=0)
                {
                    $data['message'] = 'Product unavailable.Can not add this item to cart' ;
                    $data['status'] = 0;
                    return response($data);

                }
                $productData->stock_count=$base_varient_stock;

                $data['productData'] = $productData;


                $data['message'] = 'Success';
                $data['status'] = 1;
            } else {
                $data['message'] = 'Product not found';
                $data['status'] = 0;
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }



    public function viewBaseProduct(Request $request)
    {
        $data = array();
        try {

            if (isset($request->product_id) && $productData = Mst_store_product::find($request->product_id)) {

                if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                    $rvs = new Trn_RecentlyVisitedProducts;
                    $rvs->customer_id = $request->customer_id;
                    $prData = Mst_store_product::find($request->product_id);
                    $rvs->store_id = $prData->store_id;
                    $rvs->product_id = $request->product_id;
                    $rvs->product_varient_id = 0;
                    $rvs->vendor_id = $prData->vendor_id;
                    $rvs->category_id = $prData->product_cat_id;
                    $rvs->sub_category_id = $prData->sub_category_id;
                    $rvs->visit_count = 1;
                    $rvs->save();
                }


                $productData->product_description =   strip_tags(@$productData->product_description);
                $productData->product_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;
                $productData->rating = Helper::productRating($productData->product_id);
                $productData->ratingCount = Helper::productRatingCount($productData->product_id);
                $productData->store_name = Mst_store::find($productData->store_id)->store_name;


                $productData->productStock = Helper::productStock($productData->product_id);
                $productData->variantCount = Helper::variantCount($productData->product_id);
                $productData->isBaseVariant = Helper::isBaseVariant($productData->product_id);
                $productData->attrCount = Helper::attrCount($productData->product_id);

                $data['productdata'] = $productData;

                $productVartiantdata  = Mst_store_product_varient::where('product_id', $productData->product_id)
                    ->where('stock_count', '>', 0)
                    ->where('variant_status',1)
                    ->get();
                $productVarientIds = array();
                foreach ($productVartiantdata as $row) {
                    $row->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $row->product_varient_base_image;
                    $row->store_name = Mst_store::find($productData->store_id)->store_name;
                    $row->product_name = $productData->product_name;
                    $row->product_type = $productData->product_type;
                    $row->service_type = $productData->service_type;
                    $productVarientIds[] = $row->product_varient_id;
                }
                $data['productVartiantdata'] = $productVartiantdata;


                $varIds = Mst_store_product_varient::where('product_id', $productData->product_id)->pluck('product_varient_id')->toArray();
                $attributesData = Trn_ProductVariantAttribute::select('attr_group_id')->whereIn('product_varient_id', $varIds)->groupBy('attr_group_id')->get();
                foreach ($attributesData as $j) {
                    $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                    $j->attr_group = @$datas->group_name;
                    $aarVat = Trn_ProductVariantAttribute::select('product_varient_id', 'variant_attribute_id', 'attr_group_id', 'attr_value_id')
                        ->whereIn('product_varient_id', $varIds)
                        ->where('attr_group_id', $j->attr_group_id)
                        ->groupBy('attr_value_id')->get();

                    foreach ($aarVat as $l) {
                        $datasvalue = Mst_attribute_value::where('attr_value_id', $l->attr_value_id)->first();
                        $l->attr_value = @$datasvalue->group_value;
                    }
                    $j->attr_value = $aarVat;
                }
                $data['attributesData'] = $attributesData;

                $feedbacks = Mst_FeedbackQuestion::where('category_id', $productData->product_cat_id)->get();
                $data['feedbackData'] = $feedbacks;

                $reviewData = Trn_ReviewsAndRating::where('product_id', $productData->product_id)->where('isVisible', 1)->get();
                foreach ($reviewData as $r) {
                    $r->customer_image =  Helper::default_user_image();
                    $customerData =  Trn_store_customer::find($r->customer_id);
                    $r->customer_name = @$customerData->customer_first_name . " " . @$customerData->customer_last_name;
                }
                $data['reviewData'] = $reviewData;



                $productImages = Mst_product_image::where('product_id', $productData->product_id)->where('product_varient_id', 0)->orderBy('image_flag', 'DESC')->get();
                foreach ($productImages as $pi) {
                    $pi->product_image = '/assets/uploads/products/base_product/base_image/' . $pi->product_image;
                }
                $productVideos = Trn_ProductVideo::where('product_id', $productData->product_id)->get();
                foreach ($productVideos as $v) {
                    if ($v->platform == 'Youtube') {
                        $revLink = strrev($v->link);
                        $revLinkCode = substr($revLink, 0, strpos($revLink, '='));
                        $linkCode = strrev($revLinkCode);
                        if ($linkCode == "") {
                            $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                            $linkCode = strrev($revLinkCode);
                        }
                    }
                    if ($v->platform == 'Vimeo') {
                        $revLink = strrev($v->link);
                        $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                        $linkCode = strrev($revLinkCode);
                    }
                    $v->link_code = @$linkCode;
                }
                $data['productImages'] = $productImages;
                $data['productVideos'] = $productVideos;


                $orderData = Trn_store_order::join('trn_order_items', 'trn_order_items.order_id', '=', 'trn_store_orders.order_id')
                    ->where('trn_order_items.product_id', $productData->product_id)
                    ->where('trn_store_orders.customer_id', $request->customer_id)
                    ->whereIn('trn_store_orders.status_id', [9])
                    ->first();

        $is_purchased=Trn_store_order_item::where('product_id',$productData->product_id)->where('customer_id',$request->customer_id)->count();
        if($is_purchased>0)
            {
                $oArray=[];
                $orders=Trn_store_order_item::where('product_id',$productData->product_id)->where('customer_id',$request->customer_id)->get();
                foreach($orders as $order)
                {
                    array_push($oArray,$order->order_id);

                }
                $store_order_purchase_count=Trn_store_order::whereIn('order_id',$oArray)->where('status_id','=',9)->count();
                
                if($store_order_purchase_count>0)
                {
                    $data['itemPurchasedStatus'] = 1;

                }
                else
                {
                    $data['itemPurchasedStatus'] = 0;
                }
                
            }
            else
            {
                $data['itemPurchasedStatus'] = 0;

            }


                // if (!$orderData)
                //     $data['itemPurchasedStatus'] = 0;
                // else
                //     $data['itemPurchasedStatus'] = 1;

                $fbStatus = Trn_CustomerFeedback::whereIn('product_varient_id', $productVarientIds)->where('customer_id', $request->customer_id)->first();
                if (!$fbStatus)
                    $data['feedbackAddedStatus'] = 0;
                else
                    $data['feedbackAddedStatus'] = 1;

                $rwStatus = Trn_ReviewsAndRating::where('product_id', $productData->product_id)->where('customer_id', $request->customer_id)->first();
                if (!$rwStatus)
                    $data['reviewAddedStatus'] = 0;
                else
                    $data['reviewAddedStatus'] = 1;



                $productVartiantdata  = Mst_store_product_varient::where('product_id', $productData->product_id)
                    ->where('stock_count', '>', 0)
                    ->where('variant_status',1)
                    ->get();
                foreach ($productVartiantdata as $row) {

                    $row->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $row->product_varient_base_image;
                    $attributesData = Trn_ProductVariantAttribute::select('attr_group_id', 'attr_value_id')->where('product_varient_id', $row->product_varient_id)->get();
                    $row->store_name = Mst_store::find($row->store_id)->store_name;

                    foreach ($attributesData as $j) {
                        $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                        if (isset($datas->group_name))
                            $j->attr_group = @$datas->group_name;
                        else
                            $j->attr_group = '';

                        $datasvalue = Mst_attribute_value::where('attr_value_id', $j->attr_value_id)->first();
                        if (isset($datasvalue->group_value))
                            $j->attr_value = @$datasvalue->group_value;
                        else
                            $j->attr_value = '';
                    }
                    $row->attributesData = $attributesData;
                }
                $data['productVartiantdata'] = $productVartiantdata;

                $otherVariants = Mst_store_product_varient::select('product_varient_id', 'product_varient_base_image')
                    ->where('product_id', $productData->product_id)->get();
                foreach ($otherVariants as $r) {
                    $r->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $r->product_varient_base_image;
                }
                $data['otherVariants'] = $otherVariants;


                $data['message'] = 'Success';
                $data['status'] = 1;
            } else {
                $data['message'] = 'Product not found';
                $data['status'] = 0;
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
    public function checkPurchasedOrders(Request $request)
    {
        $is_purchased=Trn_store_order_item::where('product_id',$request->product_id)->where('customer_id',$request->customer_id)->count();
        $puArray=[];
        if($is_purchased>0)
            {
                $oArray=[];
                
                $orders=Trn_store_order_item::where('product_id',$request->product_id)->where('customer_id',$request->customer_id)->get();
                foreach($orders as $order)
                {
                    array_push($oArray,$order->order_id);

                }
                $purchased_orders=Trn_store_order::whereIn('order_id',$oArray)->where('status_id','=',9)->get();
                
                foreach($purchased_orders as $porder)
                {
                    array_push($puArray,$porder->order_id);

                }
                
            }
            return response($puArray);

    }


    public function viewProductPopup(Request $request)
    {
        $data = array();
        try {

            if (isset($request->product_varient_id) && $vardata = Mst_store_product_varient::find($request->product_varient_id)) {
                $productVarientId = $request->product_varient_id;
                if (isset($request->variant_attribute_id) && ($request->variant_attribute_id != 0)) {
                    $proVaattrrData = Trn_ProductVariantAttribute::find($request->variant_attribute_id);
                    //  $proVarData = Mst_store_product_varient::find($proVaattrrData->product_varient_id);
                    $productVarientId = $proVaattrrData->product_varient_id;
                }

                // dd($varAv);
                if ($request->customer_id == 0) {
                    $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.business_type_id',
                            'mst_store_products.product_cat_id',
                            'mst_store_products.business_type_id',
                            'mst_store_products.product_description',
                            'mst_store_products.product_base_image',
                            'mst_store_products.store_id',
                            'mst_store_products.product_status',
                            'mst_store_products.display_flag',
                            'mst_store_products.is_timeslot_based_product',
                            'mst_store_products.timeslot_start_time',
                            'mst_store_products.timeslot_end_time',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_type',
                            'mst_store_products.product_brand',
                            'mst_store_products.service_type',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_stores.store_name'
                        )
                        ->where('mst_store_product_varients.product_varient_id', $productVarientId)
                        ->first();
                    $productData->product_description =   strip_tags(@$productData->product_description);
                    $productData->product_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;
                    $productData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_varient_base_image;

                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->sum('rating');
                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->count();

                    if ($countRating == 0) {
                        $ratingData = $sumRating / 1;
                    } else {
                        $ratingData = $sumRating / $countRating;
                    }

                    $productData->ratingData = number_format((float)$ratingData, 2, '.', '');
                    $productData->ratingCount = $countRating;


                    $varIds = Mst_store_product_varient::where('product_id', $productData->product_id)->pluck('product_varient_id')->toArray();
                    // dd($varIds);

                    $attributesData = Trn_ProductVariantAttribute::select('attr_group_id')->whereIn('product_varient_id', $varIds)->groupBy('attr_group_id')->get();

                    foreach ($attributesData as $j) {
                        $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                        $j->attr_group = @$datas->group_name;


                        $aarVat = Trn_ProductVariantAttribute::select('product_varient_id', 'variant_attribute_id', 'attr_group_id', 'attr_value_id')
                            ->whereIn('product_varient_id', $varIds)
                            ->where('attr_group_id', $j->attr_group_id)
                            ->groupBy('attr_value_id')->get();

                        // dd($aarVat);

                        foreach ($aarVat as $l) {
                            $datasvalue = Mst_attribute_value::where('attr_value_id', $l->attr_value_id)->first();
                            $l->attr_value = @$datasvalue->group_value;

                            $varAttrInfo = Trn_ProductVariantAttribute::where('product_varient_id', $productVarientId)
                                ->where('attr_group_id', $l->attr_group_id)
                                ->where('attr_value_id', $l->attr_value_id)
                                ->count();

                            if ($varAttrInfo > 0) {
                                $l->attr_status = 1;
                            } else {
                                $l->attr_status = 0;
                            }
                        }

                        $j->attr_value = $aarVat;
                    }



                    $data['productData'] = $productData;

                    $data['attributesData'] = $attributesData;





                    $data['message'] = 'success';
                    $data['status'] = 1;
                } else {
                    if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                        // recently visited products
                        //  $recVisStrRowCount = Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->count();
                        // if($recVisStrRowCount < 1)
                        // {
                        // Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->where('product_varient_id',$productVarientId)->delete();

                        $rvs = new Trn_RecentlyVisitedProducts;
                        $rvs->customer_id = $request->customer_id;
                        $gData = Mst_store_product_varient::find($productVarientId);
                        $rvs->store_id = $gData->store_id;
                        $rvs->product_id = $gData->product_id;
                        $rvs->product_varient_id = $productVarientId;
                        $prData = Mst_store_product::find($gData->product_id);
                        $rvs->vendor_id = $prData->vendor_id;
                        $rvs->category_id = $prData->product_cat_id;
                        $rvs->sub_category_id = $prData->sub_category_id;

                        $rvs->visit_count = 1;
                        $rvs->save();


                        // }
                        // else
                        // {
                        //     $rvs = Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->where('product_varient_id',$productVarientId)->first();
                        //     $rvs->visit_count = $rvs->visit_count + 1;
                        //     $rvs->update();

                        // }



                        $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                            ->select(
                                'mst_store_products.product_id',
                                'mst_store_products.product_name',
                                'mst_store_products.product_code',
                                'mst_store_products.business_type_id',
                                'mst_store_products.product_cat_id',
                                'mst_store_products.business_type_id',
                                'mst_store_products.product_description',
                                'mst_store_products.product_base_image',
                                'mst_store_products.store_id',
                                'mst_store_products.product_status',
                                'mst_store_products.display_flag',
                                'mst_store_products.is_timeslot_based_product',
                                'mst_store_products.timeslot_start_time',
                                'mst_store_products.timeslot_end_time',
                                'mst_store_products.show_in_home_screen',
                                'mst_store_products.product_type',
                                'mst_store_products.product_brand',
                                'mst_store_products.service_type',
                                'mst_store_product_varients.product_varient_id',
                                'mst_store_product_varients.variant_name',
                                'mst_store_product_varients.product_varient_price',
                                'mst_store_product_varients.product_varient_offer_price',
                                'mst_store_product_varients.product_varient_base_image',
                                'mst_store_product_varients.stock_count',
                                'mst_stores.store_name'
                            )
                            ->where('mst_store_product_varients.product_varient_id', $productVarientId)
                            ->first();
                        // $productData->product_description =   strip_tags(@$productData->product_description);
                        // $productData->product_base_image = '/assets/uploads/products/base_product/base_image/'.$productData->product_base_image;
                        // $productData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/'.$productData->product_varient_base_image;

                        //     $sumRating = Trn_ReviewsAndRating::where('product_varient_id',$productVarientId)->sum('rating');
                        //     $countRating = Trn_ReviewsAndRating::where('product_varient_id',$productVarientId)->count();

                        //     if($countRating == 0)
                        //     {
                        //         $countRating = 1;
                        //     }

                        //     $ratingData = $sumRating / $countRating ;

                        // $productData->ratingData = number_format((float)$ratingData, 2, '.', '');
                        // $productData->ratingCount = $countRating;

                        $varIds = Mst_store_product_varient::where('product_id', $productData->product_id)->pluck('product_varient_id')->toArray();
                        // dd($varIds);

                        $attributesData = Trn_ProductVariantAttribute::select('attr_group_id')->whereIn('product_varient_id', $varIds)->groupBy('attr_group_id')->get();

                        foreach ($attributesData as $j) {
                            $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                            $j->attr_group = @$datas->group_name;

                            $aarVat = Trn_ProductVariantAttribute::select('product_varient_id', 'variant_attribute_id', 'attr_group_id', 'attr_value_id')
                                ->whereIn('product_varient_id', $varIds)
                                ->where('attr_group_id', $j->attr_group_id)
                                ->groupBy('attr_value_id')->get();

                            // dd($aarVat);

                            foreach ($aarVat as $l) {
                                $datasvalue = Mst_attribute_value::where('attr_value_id', $l->attr_value_id)->first();
                                $l->attr_value = @$datasvalue->group_value;

                                $varAttrInfo = Trn_ProductVariantAttribute::where('product_varient_id', $productVarientId)
                                    ->where('attr_group_id', $l->attr_group_id)
                                    ->where('attr_value_id', $l->attr_value_id)
                                    ->count();

                                if ($varAttrInfo > 0) {
                                    $l->attr_status = 1;
                                } else {
                                    $l->attr_status = 0;
                                }
                            }

                            $j->attr_value = $aarVat;
                        }


                        $data['productData'] = $productData;

                        $data['attributesData'] = $attributesData;


                        $data['message'] = 'success';
                        $data['status'] = 1;
                    } else {
                        $data['message'] = 'Customer not found';
                        $data['status'] = 0;
                    }
                }
            } else {
                $data['message'] = 'Product not found';
                $data['status'] = 0;
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function viewProductAttr(Request $request)
    {
        $data = array();
        try {

            if (isset($request->product_varient_id) && $vardata = Mst_store_product_varient::find($request->product_varient_id)) {
                $productVarientId = $request->product_varient_id;
                if (isset($request->variant_attribute_id) && ($request->variant_attribute_id != 0)) {
                    $proVaattrrData = Trn_ProductVariantAttribute::find($request->variant_attribute_id);
                    //  $proVarData = Mst_store_product_varient::find($proVaattrrData->product_varient_id);
                    $productVarientId = $proVaattrrData->product_varient_id;
                }

                // dd($varAv);
                if ($request->customer_id == 0) {
                    $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.business_type_id',
                            'mst_store_products.product_cat_id',
                            'mst_store_products.business_type_id',
                            'mst_store_products.product_description',
                            'mst_store_products.product_base_image',
                            'mst_store_products.store_id',
                            'mst_store_products.product_status',
                            'mst_store_products.display_flag',
                            'mst_store_products.is_timeslot_based_product',
                            'mst_store_products.timeslot_start_time',
                            'mst_store_products.timeslot_end_time',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_type',
                            'mst_store_products.product_brand',
                            'mst_store_products.service_type',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_store_product_varients.variant_status',
                            'mst_store_product_varients.is_base_variant',
                            'mst_stores.store_name'
                        )
                        ->where('mst_store_product_varients.product_varient_id', $productVarientId)
                        ->first();
                    if($productData->is_base_variant==1)
                    {
                        if($productData->product_status==0)
                        {
                            $productData->variant_status="0";

                        }

                    }
                    $productData->product_description =   strip_tags(@$productData->product_description);
                    $productData->product_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;
                    if($productData->product_varient_base_image!=NULL)
                    {
                        $productData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_varient_base_image;

                    }
                    else
                    {
                        $productData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;

                    }
                   


                    $productData->productStock = Helper::productStock($productData->product_id);
                    $productData->variantCount = Helper::variantCount($productData->product_id);
                    $productData->isBaseVariant = Helper::isBaseVariant($productData->product_id);
                    $productData->attrCount = Helper::attrCount($productData->product_id);
                    $productData->display_flag=$productData->display_flag;


                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->sum('rating');
                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->count();

                    if ($countRating == 0) {
                        $ratingData = $sumRating / 1;
                    } else {
                        $ratingData = $sumRating / $countRating;
                    }

                    $productData->ratingData = number_format((float)$ratingData, 2, '.', '');
                    $productData->ratingCount = $countRating;
                    



                    $varIds = Mst_store_product_varient::where('product_id', $productData->product_id)
                        ->where('is_removed', 0)->pluck('product_varient_id')->toArray();
                    // dd($varIds);

                    // $attributesData = Trn_ProductVariantAttribute::select('attr_group_id')->whereIn('product_varient_id', $varIds)->groupBy('attr_group_id')->get();
                    $attributesData = Trn_ProductVariantAttribute::select('attr_group_id')->whereIn('product_varient_id', [$request->product_varient_id])->groupBy('attr_group_id')->get();

                    foreach ($attributesData as $j) {
                        $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                        $j->attr_group = @$datas->group_name;

                        $aarVat = Trn_ProductVariantAttribute::select('product_varient_id', 'variant_attribute_id', 'attr_group_id', 'attr_value_id')
                            ->whereIn('product_varient_id', [$request->product_varient_id])
                            // ->whereIn('product_varient_id', $varIds)

                            ->where('attr_group_id', $j->attr_group_id)
                            ->groupBy('attr_value_id')->get();

                        // dd($aarVat);

                        foreach ($aarVat as $l) {
                            $datasvalue = Mst_attribute_value::where('attr_value_id', $l->attr_value_id)->first();
                            $l->attr_value = @$datasvalue->group_value;

                            $varAttrInfo = Trn_ProductVariantAttribute::where('product_varient_id', $productVarientId)
                                ->where('attr_group_id', $l->attr_group_id)
                                ->where('attr_value_id', $l->attr_value_id)
                                ->count();

                            if ($varAttrInfo > 0) {
                                $l->attr_status = 1;
                            } else {
                                $l->attr_status = 0;
                            }
                        }

                        $j->attr_value = $aarVat;
                    }

                    // dd($attributesData);
                    //   foreach($attributesData  as $k)
                    //     {
                    //         $k->attr_group = Mst_attribute_group::where('attr_group_id',$k->attr_group_id)->first()->group_name;
                    //         $k->attr_value = Mst_attribute_value::where('attr_value_id',$k->attr_value_id)->first()->group_value;

                    //         $varAttrInfo = Trn_ProductVariantAttribute::where('product_varient_id',$productVarientId)
                    //                 ->where('attr_group_id',$k->attr_group_id)
                    //                 ->where('attr_value_id',$k->attr_value_id)
                    //                 ->count();
                    //         if($varAttrInfo > 0)
                    //         {
                    //              $k->attr_status = 1;
                    //         }
                    //         else
                    //         {
                    //           $k->attr_status = 0;
                    //         }

                    //       // $k->attrValues = Mst_attribute_value::where('attribute_group_id',$k->attr_group_id)->get();
                    //     }


                    $data['productData'] = $productData;



                    $data['attributesData'] = $attributesData;


                    $otherVariants = Mst_store_product_varient::select('product_varient_id', 'product_varient_base_image')
                        ->where('is_removed', 0)
                        ->where('variant_status',1)
                        ->where('product_id', $productData->product_id)
                        ->get();
                    foreach ($otherVariants as $r) {
                        if($r->product_varient_base_image!=NULL)
                        {
                            $r->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $r->product_varient_base_image;
    
                        }
                        else
                        {
                            $r->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;
    
                        }
                       
                    }
                    $data['otherVariants'] = $otherVariants;

                    $data['itemPurchasedStatus'] = 0;
                    $data['feedbackAddedStatus'] = 0;
                    $data['reviewAddedStatus'] = 0;




                    $feedbacks = Mst_FeedbackQuestion::where('category_id', $productData->product_cat_id)->get();
                    $data['feedbackData'] = $feedbacks;

                    $reviewData = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->get();
                    foreach ($reviewData as $r) {
                        $r->customer_image =  Helper::default_user_image();
                        $customerData =  Trn_store_customer::find($r->customer_id);
                        $r->customer_name = @$customerData->customer_first_name . " " . @$customerData->customer_last_name;
                    }

                    $data['reviewData'] = $reviewData;



                    $productImages = Mst_product_image::where('product_varient_id', $productVarientId)->orderBy('image_flag', 'DESC')->get();
                    foreach ($productImages as $pi) {
                        $pi->product_image = '/assets/uploads/products/base_product/base_image/' . $pi->product_image;
                    }
                    $productVideos = Trn_ProductVideo::where('product_id', $productData->product_id)->get();
                    foreach ($productVideos as $v) {
                        if ($v->platform == 'Youtube') {
                            $revLink = strrev($v->link);

                            $revLinkCode = substr($revLink, 0, strpos($revLink, '='));
                            $linkCode = strrev($revLinkCode);

                            if ($linkCode == "") {
                                $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                                $linkCode = strrev($revLinkCode);
                            }
                        }
                        if ($v->platform == 'Vimeo') {
                            $revLink = strrev($v->link);
                            $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                            $linkCode = strrev($revLinkCode);
                        }
                        $v->link_code = @$linkCode;
                    }
                    $data['productImages'] = $productImages;
                    $data['productVideos'] = $productVideos;

                    $data['message'] = 'success';
                    $data['status'] = 1;
                } else {
                    if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                        // recently visited products
                        //  $recVisStrRowCount = Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->count();
                        // if($recVisStrRowCount < 1)
                        // {
                        // Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->where('product_varient_id',$productVarientId)->delete();

                        $rvs = new Trn_RecentlyVisitedProducts;
                        $rvs->customer_id = $request->customer_id;
                        $gData = Mst_store_product_varient::find($productVarientId);
                        $rvs->store_id = $gData->store_id;
                        $rvs->product_id = $gData->product_id;
                        $rvs->product_varient_id = $productVarientId;
                        $prData = Mst_store_product::find($gData->product_id);
                        $rvs->vendor_id = $prData->vendor_id;
                        $rvs->category_id = $prData->product_cat_id;
                        $rvs->sub_category_id = $prData->sub_category_id;

                        $rvs->visit_count = 1;
                        $rvs->save();


                        // }
                        // else
                        // {
                        //     $rvs = Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->where('product_varient_id',$productVarientId)->first();
                        //     $rvs->visit_count = $rvs->visit_count + 1;
                        //     $rvs->update();

                        // }


                      


                        $orderData = Trn_store_order::join('trn_order_items', 'trn_order_items.order_id', '=', 'trn_store_orders.order_id')
                            ->where('trn_order_items.product_varient_id', $productVarientId)
                            ->where('trn_store_orders.customer_id', $request->customer_id)
                            ->whereIn('trn_store_orders.status_id', [9])
                            ->first();
                        if (!$orderData)
                            $data['itemPurchasedStatus'] = 0;
                        else
                            $data['itemPurchasedStatus'] = 1;

                        $fbStatus = Trn_CustomerFeedback::where('product_varient_id', $productVarientId)->where('customer_id', $request->customer_id)->first();
                        if (!$fbStatus)
                            $data['feedbackAddedStatus'] = 0;
                        else
                            $data['feedbackAddedStatus'] = 1;

                        $rwStatus = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('customer_id', $request->customer_id)->count();
                        if ($rwStatus > 0)
                            $data['reviewAddedStatus'] = 1;
                        else
                            $data['reviewAddedStatus'] = 0;

                        $data['productVarientId'] = $productVarientId;
                        $data['customer_ids'] = $request->customer_id;



                        $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                            ->select(
                                'mst_store_products.product_id',
                                'mst_store_products.product_name',
                                'mst_store_products.product_code',
                                'mst_store_products.business_type_id',
                                'mst_store_products.product_cat_id',
                                'mst_store_products.business_type_id',
                                'mst_store_products.product_description',
                                'mst_store_products.product_base_image',
                                'mst_store_products.store_id',
                                'mst_store_products.product_status',
                                'mst_store_products.display_flag',
                                'mst_store_products.is_timeslot_based_product',
                                'mst_store_products.timeslot_start_time',
                                'mst_store_products.timeslot_end_time',
                                'mst_store_products.show_in_home_screen',
                                'mst_store_products.product_type',
                                'mst_store_products.product_brand',
                                'mst_store_products.service_type',
                                'mst_store_product_varients.product_varient_id',
                                'mst_store_product_varients.variant_name',
                                'mst_store_product_varients.product_varient_price',
                                'mst_store_product_varients.product_varient_offer_price',
                                'mst_store_product_varients.product_varient_base_image',
                                'mst_store_product_varients.stock_count',
                                'mst_store_product_varients.variant_status',
                                'mst_store_product_varients.is_base_variant',
                                'mst_stores.store_name'
                            )
                            ->where('mst_store_product_varients.product_varient_id', $productVarientId)
                            ->first();
                            // Mst_store_product::where('product_id')
                            if($productData->is_base_variant==1)
                            {
                                if($productData->product_status==0)
                                {
                                    $productData->variant_status="0";
        
                                }
        
                            }
                            
                        $productData->product_description =   strip_tags(@$productData->product_description);
                        $productData->product_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;
                        if($productData->product_varient_base_image!=NULL)
                        {
                            $productData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/'.$productData->product_varient_base_image;
    
                        }
                        else
                        {
                            $productData->product_varient_base_image = $productData->product_base_image;
    
                        }


                        $productData->productStock = Helper::productStock($productData->product_id);
                        $productData->variantCount = Helper::variantCount($productData->product_id);
                        $productData->isBaseVariant = Helper::isBaseVariant($productData->product_id);
                        $productData->attrCount = Helper::attrCount($productData->product_id);
                        $otherVariants = Mst_store_product_varient::select('product_varient_id', 'product_varient_base_image')
                        ->where('product_id', $gData->product_id)
                        ->where('is_removed', 0)
                        ->where('variant_status',1)
                        ->get();
                    foreach ($otherVariants as $r) {
                        if($r->product_varient_base_image!=NULL)
                        {
                            $r->product_varient_base_image = '/assets/uploads/products/base_product/base_image/'.$r->product_varient_base_image;
    
                        }
                        else
                        {
                            $r->product_varient_base_image = $productData->product_base_image;
    
                        }
                    }
                    $data['otherVariants'] = $otherVariants;

                        $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->sum('rating');
                        $countRating = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->count();

                        if ($countRating == 0) {
                            $ratingData = $sumRating / 1;
                        } else {
                            $ratingData = $sumRating / $countRating;
                        }

                        $productData->ratingData = number_format((float)$ratingData, 2, '.', '');
                        $productData->ratingCount = $countRating;
                        $in_cart=Trn_Cart::where('customer_id',$request->customer_id)->where('product_varient_id',$productVarientId)->where('remove_status',0)->first();
                        if($in_cart)
                        {
                            $cartCount=$in_cart->quantity;
                            $cartId=$in_cart->cart_id;


                        }
                        else
                        {
                            $cartCount=0;
                            $cartId=0;

                        }
                        $productData->cartCount = (int)$cartCount;
                        $productData->cartId = (int)$cartId;


                        $varIds = Mst_store_product_varient::where('product_id', $productData->product_id)
                            ->where('is_removed', 0)->pluck('product_varient_id')->toArray();
                        // dd($varIds);

                        // $attributesData = Trn_ProductVariantAttribute::select('attr_group_id')->whereIn('product_varient_id', $varIds)->groupBy('attr_group_id')->get();
                        $attributesData = Trn_ProductVariantAttribute::select('attr_group_id')->whereIn('product_varient_id', [$request->product_varient_id])->groupBy('attr_group_id')->get();

                        foreach ($attributesData as $j) {
                            $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                            $j->attr_group = @$datas->group_name;

                            $aarVat = Trn_ProductVariantAttribute::select('product_varient_id', 'variant_attribute_id', 'attr_group_id', 'attr_value_id')
                                ->whereIn('product_varient_id', [$request->product_varient_id])
                                // ->whereIn('product_varient_id', $varIds)

                                ->where('attr_group_id', $j->attr_group_id)
                                ->groupBy('attr_value_id')->get();

                            // dd($aarVat);

                            foreach ($aarVat as $l) {
                                $datasvalue = Mst_attribute_value::where('attr_value_id', $l->attr_value_id)->first();
                                $l->attr_value = @$datasvalue->group_value;

                                $varAttrInfo = Trn_ProductVariantAttribute::where('product_varient_id', $productVarientId)
                                    ->where('attr_group_id', $l->attr_group_id)
                                    ->where('attr_value_id', $l->attr_value_id)
                                    ->count();

                                if ($varAttrInfo > 0) {
                                    $l->attr_status = 1;
                                } else {
                                    $l->attr_status = 0;
                                }
                            }

                            $j->attr_value = $aarVat;
                        }


                        $data['productData'] = $productData;

                        $data['attributesData'] = $attributesData;

                        // dd($productData->product_cat_id);
                        $feedbacks = Mst_FeedbackQuestion::where('category_id', $productData->product_cat_id)->get();
                        $data['feedbackData'] = $feedbacks;


                        // $data['feedbackData'] = [
                        //     [
                        //         'feedback_id' => 1,
                        //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus"
                        //     ],
                        //     [
                        //         'feedback_id' => 2,
                        //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus ss"
                        //     ],
                        //     [
                        //         'feedback_id' => 3,
                        //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus"
                        //     ]
                        // ];  

                        $reviewData = Trn_ReviewsAndRating::where('product_varient_id', $productVarientId)->where('isVisible', 1)->get();

                        foreach ($reviewData as $r) {
                            $r->customer_image =  Helper::default_user_image();
                            $customerData =  Trn_store_customer::find($r->customer_id);
                            $r->customer_name = @$customerData->customer_first_name . " " . @$customerData->customer_last_name;
                        }

                        $data['reviewData'] = $reviewData;


                        $productImages = Mst_product_image::where('product_varient_id', $productVarientId)->orderBy('image_flag', 'DESC')->get();
                        foreach ($productImages as $pi) {
                            $pi->product_image = '/assets/uploads/products/base_product/base_image/' . $pi->product_image;
                        }
                        $productVideos = Trn_ProductVideo::where('product_id', $productData->product_id)->get();
                        foreach ($productVideos as $v) {
                            if ($v->platform == 'Youtube') {
                                $revLink = strrev($v->link);

                                $revLinkCode = substr($revLink, 0, strpos($revLink, '='));
                                $linkCode = strrev($revLinkCode);
                                // echo $linkCode;

                                if ($linkCode == "") {
                                    $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                                    $linkCode = strrev($revLinkCode);
                                }
                            }
                            if ($v->platform == 'Vimeo') {
                                $revLink = strrev($v->link);
                                $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                                $linkCode = strrev($revLinkCode);
                            }
                            $v->link_code = @$linkCode;
                        }
                        $data['productImages'] = $productImages;
                        $data['productVideos'] = $productVideos;




                        $data['message'] = 'success';
                        $data['status'] = 1;
                    } else {
                        $data['message'] = 'Customer not found';
                        $data['status'] = 0;
                    }
                }

                $productVartiantdata  = Mst_store_product_varient::where('product_id', $vardata->product_id)
                    ->where('stock_count', '>', 0)
                    ->where('is_removed', 0)
                    ->where('variant_status',1)
                    ->get();
                $pdt=Mst_store_product::where('product_id',$vardata->product_id)->first();
                foreach ($productVartiantdata as $row) {
                    
                    if($row->is_base_variant==1)
                    {
                        if($pdt->product_status==0)
                        {
                            $row->variant_status="0";

                        }
                        
                        $row->product_varient_base_image = $productData->product_base_image;

                    }
                    else
                    {
                        $row->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $row->product_varient_base_image;

                    }
                    $in_cart2=Trn_Cart::where('customer_id',$request->customer_id)->where('product_varient_id',$row->product_varient_id)->where('remove_status',0)->first();
                    if($in_cart2)
                    {
                        $cartCount2=$in_cart2->quantity;
                        $cartId2=$in_cart2->cart_id;


                    }
                    else
                    {
                        $cartCount2=0;
                        $cartId2=0;

                    }
                    $row->cartCount = (int)$cartCount2;
                    $row->cartId = (int)$cartId2;
                    
                    
                    $attributesData = Trn_ProductVariantAttribute::select('attr_group_id', 'attr_value_id')->where('product_varient_id', $row->product_varient_id)->get();
                    foreach ($attributesData as $j) {
                        $datas = Mst_attribute_group::where('attr_group_id', $j->attr_group_id)->first();
                        if (isset($datas->group_name))
                            $j->attr_group = @$datas->group_name;
                        else
                            $j->attr_group = '';

                        $datasvalue = Mst_attribute_value::where('attr_value_id', $j->attr_value_id)->first();
                        if (isset($datasvalue->group_value))
                            $j->attr_value = @$datasvalue->group_value;
                        else
                            $j->attr_value = '';
                    }
                    $row->attributesData = $attributesData;
                }
                $data['productVartiantdata'] = $productVartiantdata;
            } else {
                $data['message'] = 'Product not found';
                $data['status'] = 0;
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }




    public function testApi(Request $request)
    {
        $data = array();
        try {
            $usedCoupinIds = Trn_store_order::where('customer_id', 18)->get('coupon_id')->toArray();
            $result = array_column($usedCoupinIds, 'coupon_id');
            dd($result);
            $data['km'] = Helper::haversineGreatCircleDistance('11.601558', '75.5919758', '', '75.7800259'); // lat long

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function paymentResponse(Request $request)
    {
        $data = array();
        try {
            if (isset($request->order_id) && Trn_store_order::find($request->order_id)) {
                if ($request->payment_status == 'success') {
                    Trn_store_order::where('order_id', $request->order_id)->update(['status_id' => 4, 'trn_id' => $request->trn_id]);
                    $data['status'] = 1;
                    $data['message'] = "Order confirmed";
                } else {
                    Trn_store_order::where('order_id', $request->order_id)->update(['status_id' => 5]);
                    $data['status'] = 1;
                    $data['message'] = "Order cancelled";
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Order not found";
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function shareFeedback(Request $request)
    {
        $data = array();
        try {

            if (isset($request->product_variant_id) && Mst_store_product_varient::find($request->product_variant_id)) {

                if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {


                    foreach ($request['feedbackData'] as $fd) {
                        $fb  = new Trn_CustomerFeedback;
                        $fb->feedback_question_id = $fd['feedback_question_id'];
                        $fb->feedback = $fd['feedback'];
                        $fb->product_varient_id = $request->product_variant_id;
                        $fb->customer_id = $request->customer_id;
                        $fb->save();
                    }

                    $data['status'] = 1;
                    $data['message'] = "Feedback added";
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Customer not found";
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Product not found";
            }






            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function reduceRewardPoint(Request $request)
    {
        $data = array();
        try {
            if (isset($request->order_amount)) {
                if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                    $customer_id = $request->customer_id;

                    $totalCustomerRewardsCount = Trn_customer_reward::where('customer_id', $request->customer_id)->where('reward_point_status', 1)->sum('reward_points_earned');
                    $totalusedPoints = Trn_store_order::where('customer_id', $request->customer_id)->whereNotIn('status_id', [5])->sum('reward_points_used');
                    $customerRewardPoint = $totalCustomerRewardsCount - $totalusedPoints;

                    //echo $customerRewardPoint;die;

                    if ($customerRewardPoint > 0) {

                        //  $customerRewardPoint = Trn_customer_reward::where('customer_id',$request->customer_id)->where('reward_point_status',1)->sum('reward_points_earned');

                        $ConfigPoints = Trn_configure_points::find(1);
                        $pointToRupeeRatio =   $ConfigPoints->rupee / $ConfigPoints->rupee_points; // points to rupee ratio

                        $orderAmount = $request->order_amount;
                        $totalReducableAmount = ($orderAmount * $ConfigPoints->redeem_percentage) / 100; // 10% of order amount

                        $amountCanBeReduced = $pointToRupeeRatio * $customerRewardPoint;
                        //  echo $pointToRupeeRatio."-".$customerRewardPoint."--".$reducableAmount;

                        // echo $usedPoint;die;


                        if ($totalReducableAmount >= $amountCanBeReduced) {
                            //echo "here";die;
                            $reducedOrderAmount = $orderAmount - $amountCanBeReduced;
                            $data['orderAmount'] = number_format((float)$orderAmount, 2, '.', '');
                            $data['totalReducableAmount'] = number_format((float)$totalReducableAmount, 2, '.', '');
                            $data['reducedOrderAmount'] = number_format((float)$reducedOrderAmount, 2, '.', '');
                            $data['reducedAmountByWalletPoints'] = number_format((float)$amountCanBeReduced, 2, '.', '');
                            $data['usedPoint'] = number_format((float)$customerRewardPoint, 2, '.', '');
                            $data['balancePoint'] = 0;
                            // success - blance 0, reduced ** 
                        } else {
                            //echo "here2";die;

                            $usedPoint = $totalReducableAmount / $pointToRupeeRatio;
                            $reducedOrderAmount = $orderAmount - $totalReducableAmount;
                            $balancePoint = $customerRewardPoint - $usedPoint;
                            $data['orderAmount'] = number_format((float)$orderAmount, 2, '.', '');
                            $data['totalReducableAmount'] = number_format((float)$totalReducableAmount, 2, '.', '');
                            $data['reducedOrderAmount'] = number_format((float)$reducedOrderAmount, 2, '.', '');
                            $data['reducedAmountByWalletPoints'] = number_format((float)$totalReducableAmount, 2, '.', '');
                            $data['usedPoint'] = number_format((float)$usedPoint, 2, '.', '');
                            $data['balancePoint'] = number_format((float)$balancePoint, 2, '.', '');

                            // blance 25 , reduced = 100 ;

                        }
                        $data['status'] = 1;
                        $data['message'] = "success";
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "No reward points available";
                    }
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Customer not found";
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Order amount required";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function checkOutPage(Request $request)
    {
        $data = array();
        try {

            if ($request->customer_id == 0) {
                $data['status'] = 0;
                $data['message'] = "No Customer ";
                return response($data);
            } else {

                if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                    if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                        $customer_id = $request->customer_id;

                        // $data['deliveryAddress']  =  Trn_customerAddress::where('customer_id',$request->customer_id)->where('default_status',1)->first();
                        $data['sloatDelivery']  = [
                            [
                                'slot_id' => 1,
                                'slot' => "Immediate"
                            ],
                            [
                                'slot_id' => 2,
                                'slot' => "Slot"
                            ]

                        ];

                        $storeData =  Mst_store::find($request->store_id);
                        $cusData =  Trn_store_customer::find($request->customer_id);
                        $data['customer_name']  = @$cusData->customer_first_name . "" . @$cusData->customer_last_name;
                        $data['customer_mobile_number']  = @$cusData->customer_mobile_number;

                        if (isset($cusData->customer_email))
                            $data['customer_email']  = $cusData->customer_email;
                        else
                            $data['customer_email']  = '';


                        $data['upi_id']  = $storeData->upi_id;
                        if (isset($storeData->store_commision_percentage))
                            $data['commision_percentage']  = $storeData->store_commision_percentage;
                        else
                            $data['commision_percentage']  = '0';

                        $data['bankDetails']  = new \stdClass();

                        $bankDetails = Trn_StoreBankData::where('store_id', $request->store_id)->first();
                        if (isset($bankDetails->vendor_id))
                            $data['vendorId']  = $bankDetails->vendor_id;
                        else
                            $data['vendorId']  = '0';

                        $data['timeSlotDetails']  = Trn_StoreDeliveryTimeSlot::select('store_delivery_time_slot_id', 'store_id', 'time_start', 'time_end')->where('store_id', $request->store_id)->get();
                          
                        //   $data['paymentTypes']  = Sys_payment_type::all();
                        $data['rewardReducible']  = Trn_configure_points::find(1)->redeem_percentage;
                        $data['redeemAmt']  = Trn_configure_points::find(1)->max_redeem_amount;
                        $data['customerRewardsCount'] = Trn_customer_reward::where('customer_id', $request->customer_id)->where('reward_point_status', 1)->whereNull('store_id')->where('discription','!=','store points')->sum('reward_points_earned');
                    if(Trn_configure_points::where('store_id',$request->store_id)->first()!=NULL)
                    {
                        $data['storeRewardReducible']  = Trn_configure_points::where('store_id',$request->store_id)->first()->redeem_percentage;
                        $data['storeRedeemAmt']  = Trn_configure_points::where('store_id',$request->store_id)->first()->max_redeem_amount;
                        //$data['storeCustomerRewardsCount'] = Trn_customer_reward::where('customer_id', $request->customer_id)->where('reward_point_status', 1)->where('discription','store_points')->sum('reward_points_earned');
                        $data['storeCustomerRewardsCount'] = Trn_wallet_log::where('customer_id', $request->customer_id)->where('store_id',$request->store_id)->where('type','=','credit')->sum('points_credited');

                    }
                    else
                    {
                        $data['storeRewardReducible']  = 0;
                        $data['storeRedeemAmt']  = 0;
                        $data['storeCustomerRewardsCount'] = 0;

                    }
                        



                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    }
                } else {
                    $data['status'] = 2;
                    $data['message'] = "Customer not found ";
                    return response($data);
                }
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function raiseIssuesPage(Request $request)
    {
        $data = array();
        try {

            if (isset($request->order_id) && Trn_store_order::find($request->order_id)) {
                $data['issueTypes']  = Sys_IssueType::all();
                $data['orderSpecificIssues']  = Mst_Issues::where('issue_type_id', 1)->get();

                $data['orderDetails'] = Trn_store_order_item::where('order_id', $request->order_id)
                    ->select('product_id', 'product_varient_id', 'order_item_id', 'quantity', 'discount_amount','mrp', 'discount_percentage', 'total_amount', 'tax_amount', 'unit_price', 'tick_status')
                    ->get();


                foreach ($data['orderDetails'] as $value) {
                    $value['productDetail'] = Mst_store_product_varient::find($value->product_varient_id);
                    @$value->productDetail->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . @$value->productDetail->product_varient_base_image;
                    $baseProductDetail = Mst_store_product::find($value->product_id);
                    $value->product_base_image = '/assets/uploads/products/base_product/base_image/' . @$baseProductDetail->product_base_image;

                    if (@$baseProductDetail->product_name != @$value->productDetail->variant_name)
                        $value->product_name = @$baseProductDetail->product_name . " " . @$value->productDetail->variant_name;
                    else
                        $value->product_name = @$baseProductDetail->product_name;

                    $taxFullData = Mst_Tax::find(@$baseProductDetail->tax_id);

                    $splitdata = \DB::table('trn__tax_split_ups')->where('tax_id', @$baseProductDetail->tax_id)->get();
                    $stax = 0;

                    foreach ($splitdata as $sd) {
                        if (@$taxFullData->tax_value == 0 || !isset($taxFullData->tax_value))
                            $taxFullData->tax_value = 1;

                        $stax = ($sd->split_tax_value * $value->tax_amount) / @$taxFullData->tax_value;
                        $sd->tax_split_value = number_format((float)$stax, 2, '.', '');
                    }

                    $value['taxSplitups']  = $splitdata;
                }

                $data['status'] = 1;
                $data['message'] = "success ";
            } else {
                $data['status'] = 0;
                $data['message'] = "Order not found ";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function searchStore(Request $request)
    {
        $data = array();
        $expiredStores=array();
        $today = Carbon::now()->toDateString();
        try {
            $store = $request->store;
           
       


            $storeData = Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id');

            $storeData = $storeData->where('trn__store_admins.role_id', 0)
                ->where('mst_stores.online_status', 1);

            if (isset($store)) {
                $storeData = $storeData->where('mst_stores.store_name', 'LIKE', "%{$store}%");
            }

            $storeData  = $storeData->where('trn__store_admins.store_account_status', 1);

            if (($request->customer_id == 0) && (isset($request->latitude)) && (isset($request->longitude))) {
                $storeData = $storeData->select("*", DB::raw("6371 * acos(cos(radians(" . $request->latitude . "))
                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $request->longitude . "))
                                    + sin(radians(" . $request->latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                $storeData = $storeData->orderBy('distance', 'ASC');
            }

            if (isset($request->customer_id) && ($request->customer_id != 0)) {
                $cusData = Trn_store_customer::select('latitude', 'longitude')->where('customer_id', '=', $request->customer_id)->first();
                $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();
                if (isset($cusAddData)) {
                    $cusAddDataLat =  $cusAddData->latitude;
                    $cusAddDataLog =  $cusAddData->longitude;
                } else {
                    $cusAddDataLat =  $cusData->latitude;
                    $cusAddDataLog =  $cusData->longitude;
                }
                $storeData = $storeData->select("*", DB::raw("6371 * acos(cos(radians(" . $cusAddDataLat . "))
                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $cusAddDataLog . "))
                                    + sin(radians(" . $cusAddDataLat . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                $storeData = $storeData->orderBy('distance', 'ASC');
            }


            $storeData =    $storeData->orderBy('mst_stores.store_id', 'ASC');
           $listedStores=$storeData->get();
             foreach($listedStores as $store)
            {
                $getParentExpiry = Trn_StoreAdmin::where('store_id','=',$store->store_id)->where('role_id','=',0)->first();
                if($getParentExpiry)
               {
                    $parentExpiryDate = $getParentExpiry->expiry_date;
                    if($today>=$parentExpiryDate)
                   {
                       array_push($expiredStores,$store->store_id);
                    }
                
                }
             }

            // dd($storeData);
            $storeData=$storeData->whereNotIn('mst_stores.store_id',$expiredStores)->get();
          
            $storesList  =  array();

            foreach ($storeData as $s) {

                $timeslotdata = Helper::findHoliday($s->store_id);

                if ($timeslotdata == true) {

                    if ($s->distance != null) {
                        if (isset($s->profile_image)) {
                            $s->store_image =  '/assets/uploads/store_images/images/' . $s->profile_image;
                        } else {
                            $s->store_image =  Helper::default_store_image();
                        }

                        if (isset($s->store_district_id))
                            $s->district_name = District::find($s->store_district_id)->district_name;
                        else
                            $s->district_name = '';



                        $storeProductData = Mst_store_product::select('product_cat_id')->where('store_id', '=', $s->store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                        $catData = Mst_categories::whereIn('category_id', $storeProductData)->where('category_status', 1)->get()->pluck('category_name')->toArray();
                        $catString = implode(', ', @$catData);
                        if (isset($catString))
                            $string = @$catString;
                        else
                            $string = null;

                        $s->categories =  @$string;


                        // $s->rating = number_format((float)4.20, 1, '.', '');
                        // $s->ratingCount = 120;

                        $s->rating = Helper::storeRating($s->store_id);
                        $s->ratingCount = Helper::storeRatingCount($s->store_id);

                        $storesList[] = $s;
                    }
                }
            }

           
            $storeDatassss = collect($storesList);
            $perPage = 10;
            $page=$request->page??1;
            $offset = ($page - 1) * $perPage;
           
            $storeDatas =   $storeDatassss->slice($offset, $perPage)->values()->all();
            $roWc=count($storesList);
            $data['storesList']  = $storeDatas;
            if ($roWc >9) {
                $data['pageCount'] = ceil(@$roWc /10);
             } else {
                 $data['pageCount'] = 1;
             }

            $data['status'] = 1;
            $data['message'] = "success ";
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }



    public function walletPage(Request $request)
    {
        $data = array();
        try {
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {

                $totalCustomerRewardsCount = Trn_customer_reward::where('customer_id', $request->customer_id)->where('reward_point_status', 1)->whereNull('store_id')->where('discription','!=','store points')->sum('reward_points_earned');
                $totalusedPoints = Trn_store_order::where('customer_id', $request->customer_id)->whereNotIn('status_id', [5])->sum('reward_points_used');
                $redeemedPoints = Trn_points_redeemed::where('customer_id', $request->customer_id)->sum('points');

                $balanceCount =  ($totalCustomerRewardsCount - $totalusedPoints)-$redeemedPoints;
                $data['customerRewardsCount'] = number_format(floor($totalCustomerRewardsCount), 2);
                $totalAdminRedeemedPoints = Trn_points_redeemed::where('customer_id', $request->customer_id)->sum('points');
                if ($totalusedPoints >= 0)
                    $data['totalusedPoints']  = number_format((float)floor($totalusedPoints), 2, '.', '');
                else
                    $data['totalusedPoints']  = '0';

               

                if ($totalAdminRedeemedPoints >= 0)
                    $data['totalAdminRedeemedPoints']  =  number_format((float)floor($totalAdminRedeemedPoints), 2, '.', '');//$totalAdminRedeemedPoints;
                else
                    $data['totalAdminRedeemedPoints']  = '0';

                if($balanceCount>=0)
                {
                    $data['balancePoints']=number_format((float)floor($balanceCount), 2, '.', '');//$totalAdminRedeemedPoints;

                }
                else
                {
                    $data['balancePoints']='0';


                }

                
                $data['customerRewards'] = Trn_customer_reward::where('customer_id',$request->customer_id)
                    ->where('reward_point_status', 1)->where('reward_points_earned','!=',0.00)->whereNull('store_id')->where('discription','!=','store points')->orderBy('reward_id', 'DESC')->get();
                foreach ($data['customerRewards'] as $cr) {
                    if (Trn_customer_reward_transaction_type::find(@$cr->transaction_type_id)) {
                        $cr->rewardTransactionType = Trn_customer_reward_transaction_type::find(@$cr->transaction_type_id);
                    } 
                    else {
                        $cr->rewardTransactionType = new \stdClass();

                        if (($cr->discription == null) && ($cr->discription == '')) {
                            $orderInfo = Trn_store_order::find($cr->order_id);
                            $cr->rewardTransactionType->transaction_type = 'from order ' . $orderInfo->order_number;
                        } else {
                            $cr->rewardTransactionType->transaction_type = $cr->discription;
                        }
                    }
                }
                $data['customerUsedLogs']=Trn_store_order::where('customer_id', $request->customer_id)->whereNotIn('status_id', [5])->orderBy('updated_at','DESC')->get();
                $data['customerAdminRedeemedLogs']=Trn_points_redeemed::where('customer_id', $request->customer_id)->orderBy('updated_at','DESC')->get();
                $data['status'] = 1;
                $data['message'] = "Success";
            } else {
                $data['status'] = 0;
                $data['message'] = "Customer not found ";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
    public function storeWalletPage(Request $request)
    {
        $data = array();
        $wallet_logs=array();
        
        try {
            $type1="debit";
            $type2="credit";
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                $unused_redeems=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNull('order_id')->where('type','debit');
                if($unused_redeems->count()>0)
                {
                    $unused_redeems->delete();
                }

                $wallet_logs=Trn_wallet_log::Where('trn_wallet_logs.customer_id',$request->customer_id)
                ->leftjoin('trn_store_orders','trn_wallet_logs.order_id', '=','trn_store_orders.order_id')
                ->leftjoin('mst_stores','trn_wallet_logs.store_id', '=','mst_stores.store_id')
                ->leftjoin('trn_configure_points','trn_configure_points.store_id', '=','mst_stores.store_id')
                ->select('trn_wallet_logs.order_id',
                'trn_wallet_logs.customer_id',
                'trn_wallet_logs.type',
                'trn_wallet_logs.points_credited',
                'trn_wallet_logs.points_debited',
                'trn_wallet_logs.created_at',
                'trn_store_orders.order_number',
                'mst_stores.store_id',
                'trn_wallet_logs.store_id as stid',
                'mst_stores.store_name',
                DB::raw("(SELECT SUM(trn_wallet_logs.points_credited) FROM trn_wallet_logs

                WHERE trn_wallet_logs.store_id = mst_stores.store_id AND trn_wallet_logs.customer_id =".$request->customer_id."

                GROUP BY stid) as store_points_credited")
            ,
            DB::raw("(SELECT SUM(trn_wallet_logs.points_debited) FROM trn_wallet_logs

            WHERE trn_wallet_logs.store_id = mst_stores.store_id AND  trn_wallet_logs.customer_id =".$request->customer_id."

             GROUP BY stid ) as store_points_debited"),

            )
                //->where('trn_wallet_logs.points_debited','!=',0.00)
               /* ->when($type1, function ($query) use ($type1) {
                    return $query->where('trn_wallet_logs.type', $type1)->whereNotNull('trn_wallet_logs.order_id');
                })
                ->orWhen($type2, function ($query) use ($type2) {
                    return $query->where('trn_wallet_logs.type', $type2);
                })*/
                ->orderBy('trn_wallet_logs.wallet_log_id','DESC')
                ->get();
                $wallet_log_credited=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->sum('points_credited');
                $wallet_log_redeemed=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->whereNotNull('order_id')->sum('points_debited');
                $available_points=$wallet_log_credited-$wallet_log_redeemed;              
                $data['logs']=$wallet_logs;  
                foreach($data['logs'] as $log)
                {
                    $log->store_points_balance=number_format($log->store_points_credited-$log->store_points_debited,2);
                }            
                if ($wallet_log_credited >= 0)
                    $data['totalCreditedPoints']  =number_format($wallet_log_credited,2);
                else
                    $data['totalcreditedPoints']  = '0';

                if ($wallet_log_redeemed >= 0)
                    $data['totalRedeemedPoints']  = number_format($wallet_log_redeemed,2);
                else
                    $data['totalRedeemedPoints']  = '0';
               
               if($available_points>=0)
               {
                $data['totalBalancePoints']= number_format($available_points,2);

               }
               else
               {
                $data['totalBalancePoints']= "0.00";

               }
                $data['status'] = 1;
                $data['message'] = "Success";
            } else {
                $data['status'] = 0;
                $data['message'] = "Customer not found ";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
    public function customerWalletByStore(Request $request)
    {
        $data = array();
        $wallet_logs=array();
        
        try {
            $type1="debit";
            $type2="credit";
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                $unused_redeems=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNull('order_id')->where('type','debit');
                if($unused_redeems->count()>0)
                {
                    $unused_redeems->delete();
                }

                $wallet_logs=Trn_wallet_log::Where('trn_wallet_logs.customer_id',$request->customer_id)
                ->leftjoin('trn_store_orders','trn_wallet_logs.order_id', '=','trn_store_orders.order_id')
                ->leftjoin('mst_stores','trn_wallet_logs.store_id', '=','mst_stores.store_id')
                ->leftjoin('trn_configure_points','trn_configure_points.store_id', '=','mst_stores.store_id')
                ->select(
                
                'mst_stores.store_id',
                'trn_wallet_logs.store_id as stid',
                'mst_stores.store_name',
                DB::raw("(SELECT SUM(trn_wallet_logs.points_credited) FROM trn_wallet_logs

                WHERE trn_wallet_logs.store_id = mst_stores.store_id AND trn_wallet_logs.customer_id =".$request->customer_id."

                GROUP BY stid) as store_points_credited")
            ,
            DB::raw("(SELECT SUM(trn_wallet_logs.points_debited) FROM trn_wallet_logs

            WHERE trn_wallet_logs.store_id = mst_stores.store_id AND  trn_wallet_logs.customer_id =".$request->customer_id."

             GROUP BY stid ) as store_points_debited"),

            )
                //->where('trn_wallet_logs.points_debited','!=',0.00)
               /* ->when($type1, function ($query) use ($type1) {
                    return $query->where('trn_wallet_logs.type', $type1)->whereNotNull('trn_wallet_logs.order_id');
                })
                ->orWhen($type2, function ($query) use ($type2) {
                    return $query->where('trn_wallet_logs.type', $type2);
                })*/
               // ->whereNotNull('trn_wallet_logs.order_id')
                ->groupBy('stid')
               
                ->orderBy('trn_wallet_logs.wallet_log_id','DESC')
                ->get();
                $wallet_log_credited=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->sum('points_credited');
                $wallet_log_redeemed=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->whereNotNull('order_id')->sum('points_debited');
                $available_points=$wallet_log_credited-$wallet_log_redeemed;              
                $data['logs']=$wallet_logs;  
                foreach($data['logs'] as $log)
                {
                    if($log->type=='debit')
                    {
                        if($log->order_id==NULL)
                        {
                            continue;
                        }
                       

                    }
                    $log->store_points_debited=Trn_wallet_log::where('customer_id',$request->customer_id)->where('store_id',$log->store_id)->whereNotNull('store_id')->whereNotNull('order_id')->sum('points_debited');

                    $log->store_points_balance=number_format($log->store_points_credited-$log->store_points_debited,2);
                }            
                if ($wallet_log_credited >= 0)
                    $data['totalCreditedPoints']  =number_format($wallet_log_credited,2);
                else
                    $data['totalcreditedPoints']  = '0';

               if ($wallet_log_redeemed >= 0)
                    $data['totalRedeemedPoints']  = number_format($wallet_log_redeemed,2);
                else
                    $data['totalRedeemedPoints']  = '0';
               
               if($available_points>=0)
               {
                $data['totalBalancePoints']= number_format($available_points,2);

               }
               else
               {
                $data['totalBalancePoints']= "0.00";

               }
                $data['status'] = 1;
                $data['message'] = "Success";
            } else {
                $data['status'] = 0;
                $data['message'] = "Customer not found ";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
    public function customerWalletByStoreNew(Request $request)
    {
        $data = array();
        $wallet_logs=array();
        
        try {
            $type1="debit";
            $type2="credit";
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                $wallet_logs=Trn_wallet_log::Where('trn_wallet_logs.customer_id',$request->customer_id)
                ->leftjoin('trn_store_orders','trn_wallet_logs.order_id', '=','trn_store_orders.order_id')
                ->leftjoin('mst_stores','trn_wallet_logs.store_id', '=','mst_stores.store_id')
                ->leftjoin('trn_configure_points','trn_configure_points.store_id', '=','mst_stores.store_id')
                ->select(
                
                'mst_stores.store_id',
                'trn_wallet_logs.store_id as stid',
                'mst_stores.store_name',
                DB::raw("(SELECT SUM(trn_wallet_logs.points_credited) FROM trn_wallet_logs

                WHERE trn_wallet_logs.store_id = mst_stores.store_id AND trn_wallet_logs.customer_id =".$request->customer_id."

                GROUP BY stid) as store_points_credited")
            ,
            DB::raw("(SELECT SUM(trn_wallet_logs.points_debited) FROM trn_wallet_logs

            WHERE trn_wallet_logs.store_id = mst_stores.store_id AND  trn_wallet_logs.customer_id =".$request->customer_id."

             GROUP BY stid ) as store_points_debited"),

            )
                //->where('trn_wallet_logs.points_debited','!=',0.00)
               /* ->when($type1, function ($query) use ($type1) {
                    return $query->where('trn_wallet_logs.type', $type1)->whereNotNull('trn_wallet_logs.order_id');
                })
                ->orWhen($type2, function ($query) use ($type2) {
                    return $query->where('trn_wallet_logs.type', $type2);
                })*/
               // ->whereNotNull('trn_wallet_logs.order_id')
                ->groupBy('stid')
               
                ->orderBy('trn_wallet_logs.wallet_log_id','DESC')
                ->get();
                $wallet_log_credited=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->sum('points_credited');
                $wallet_log_redeemed=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->whereNotNull('order_id')->sum('points_debited');
                $available_points=$wallet_log_credited-$wallet_log_redeemed;              
                $data['logs']=$wallet_logs;  
                foreach($data['logs'] as $log)
                {
                    if($log->type=='debit')
                    {
                        if($log->order_id==NULL)
                        {
                            continue;
                        }

                    }

                    $log->store_points_balance=number_format($log->store_points_credited-$log->store_points_debited,2);
                }            
                if ($wallet_log_credited >= 0)
                    $data['totalCreditedPoints']  =number_format($wallet_log_credited,2);
                else
                    $data['totalcreditedPoints']  = '0';

               if ($wallet_log_redeemed >= 0)
                    $data['totalRedeemedPoints']  = number_format($wallet_log_redeemed,2);
                else
                    $data['totalRedeemedPoints']  = '0';
               
               if($available_points>=0)
               {
                $data['totalBalancePoints']= number_format($available_points,2);

               }
               else
               {
                $data['totalBalancePoints']= "0.00";

               }
                $data['status'] = 1;
                $data['message'] = "Success";
            } else {
                $data['status'] = 0;
                $data['message'] = "Customer not found ";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
    public function customerStoreWalletTransactions(Request $request)
    {
        $data = array();
        $wallet_logs=array();
                
        try {
            $type1="debit";
            $type2="credit";
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                $wallet_logs=Trn_wallet_log::Where('trn_wallet_logs.customer_id',$request->customer_id)
                ->leftjoin('trn_store_orders','trn_wallet_logs.order_id', '=','trn_store_orders.order_id')
                ->leftjoin('mst_stores','trn_wallet_logs.store_id', '=','mst_stores.store_id')
                ->leftjoin('trn_configure_points','trn_configure_points.store_id', '=','mst_stores.store_id')
                ->select(
                
                    'trn_wallet_logs.order_id',
                    'trn_wallet_logs.customer_id',
                    'trn_wallet_logs.type',
                    'trn_wallet_logs.points_credited',
                    'trn_wallet_logs.points_debited',
                    'trn_wallet_logs.created_at',
                    'trn_wallet_logs.description',
                    'trn_store_orders.order_number',
                    'mst_stores.store_id',
                    'trn_wallet_logs.store_id as stid',
                    'mst_stores.store_name',
                DB::raw("(SELECT SUM(trn_wallet_logs.points_credited) FROM trn_wallet_logs

                WHERE trn_wallet_logs.store_id = ".$request->store_id." AND trn_wallet_logs.customer_id =".$request->customer_id."

                GROUP BY stid) as store_points_credited")
            ,
            DB::raw("(SELECT SUM(trn_wallet_logs.points_debited) FROM trn_wallet_logs

            WHERE trn_wallet_logs.store_id = ".$request->store_id." AND  trn_wallet_logs.customer_id =".$request->customer_id." 

             GROUP BY stid ) as store_points_debited"),

            )
                //->where('trn_wallet_logs.points_debited','!=',0.00)
               /* ->when($type1, function ($query) use ($type1) {
                    return $query->where('trn_wallet_logs.type', $type1)->whereNotNull('trn_wallet_logs.order_id');
                })
                ->orWhen($type2, function ($query) use ($type2) {
                    return $query->where('trn_wallet_logs.type', $type2);
                })*/
                ->where('trn_wallet_logs.store_id',$request->store_id)
                ->orderBy('trn_wallet_logs.wallet_log_id','DESC')
                ->get();
                $wallet_log_credited=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->where('store_id',$request->store_id)->sum('points_credited');
                $wallet_log_redeemed=Trn_wallet_log::where('customer_id',$request->customer_id)->whereNotNull('store_id')->whereNotNull('order_id')->where('store_id',$request->store_id)->sum('points_debited');
                $available_points=$wallet_log_credited-$wallet_log_redeemed;              
                $data['logs']=$wallet_logs; 
                $debited=0; 
                foreach($data['logs'] as $log)
                {
                    if($log->type=='debit')
                    {
                        $o_check=Trn_store_order::where('order_id',$log->order_id)->first();
                        if($o_check!=NULL)
                        {
                        if($o_check->reward_points_used_store>$log->points_debited)
                        {
                            $log->points_debited=$o_check->reward_points_used_store;

                        }
                        $debited=$debited+$log->points_debited;
                       
                        if($log->order_id==NULL)
                        {
                            //$debited=$debited-$log->points_debited;
                            continue;
                        }
                        
                       
                       
                    }

                    }

                    $log->store_points_balance=number_format($log->store_points_credited-$log->store_points_debited,2);
                    if($log->description==NULL)
                    {
                        $log->description='Order Points';
                    }
                    if($log->order_number==NULL)
                    {
                        $log->order_number='Gift Credit(Non order)';
                    }
                }            
                if ($wallet_log_credited >= 0)
                    $data['totalCreditedPoints']  =number_format($wallet_log_credited,2);
                else
                    $data['totalcreditedPoints']  = '0';

                if ($wallet_log_redeemed >= 0)
                    $data['totalRedeemedPoints']  = number_format($wallet_log_redeemed,2);
                else
                    $data['totalRedeemedPoints']  = '0';
               
               if($available_points>=0)
               {
                $data['totalBalancePoints']= number_format($available_points,2);

               }
               else
               {
                $data['totalBalancePoints']= "0.00";

               }
                $data['status'] = 1;
                $data['message'] = "Success";
            } else {
                $data['status'] = 0;
                $data['message'] = "Customer not found ";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function searchProduct(Request $request)
    {
        $data = array();
        try {
            $product = $request->product;

            if ($request->store_id == 0) {
                $productData =  Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                    ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                    ->select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_type',
                        'mst_store_products.service_type',
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.is_base_variant',
                        'mst_store_product_varients.variant_status',
                        'mst_store_product_varients.stock_count',
                        'mst_store_product_varients.store_id'
                    );

                if (($request->customer_id == 0) && (isset($request->latitude)) && (isset($request->longitude))) {
                    $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $request->latitude . "))
                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $request->longitude . "))
                                    + sin(radians(" . $request->latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                    $productData = $productData->orderBy('distance');
                }


                if ((isset($request->customer_id)) && ($request->customer_id != 0)) {
                    // near by store
                    $cusData = Trn_store_customer::select('latitude', 'longitude')->where('customer_id', '=', $request->customer_id)->first();
                    $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();

                    if (isset($cusAddData)) {
                        $cusAddDataLat =  $cusAddData->latitude;
                        $cusAddDataLog =  $cusAddData->longitude;
                    } else {
                        $cusAddDataLat =  $cusData->latitude;
                        $cusAddDataLog =  $cusData->longitude;
                    }

                    $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $cusAddDataLat . "))
                            * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $cusAddDataLog . "))
                            + sin(radians(" . $cusAddDataLat . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                    $productData = $productData->orderBy('distance');
                }

                $productData = $productData->where('mst_store_products.display_flag', 1)
                    ->where('mst_store_products.product_name', 'LIKE', "%{$product}%")
                    ->whereOr('mst_store_product_varients.variant_name', 'LIKE', "%{$product}%")
                    ->where('mst_store_product_varients.stock_count', '>', 0)
                    ->where('mst_store_product_varients.is_removed', 0)
                    ->where('mst_store_product_varients.variant_status', 1)
                    ->where('mst_store_products.is_removed', 0)
                    // ->orWhere('mst_store_products.product_type',2)

                    ->get();

                $data['productsData']  = $productData;
                $searchProducts = collect($productData);
                $perPage = 10;
                $page=$request->page??1;
                $offset = ($page - 1) * $perPage;
                $roWc=count($searchProducts);
                $serachProductList =   $searchProducts->slice($offset, $perPage)->values()->all();
                $data['productsData']=$serachProductList;
                foreach ($data['productsData'] as $offerProduct) {
                    $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                    if($offerProduct->is_base_variant==1)
                    {
                        $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;

                    }
                    else
                    {
                        $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;

                    }
                   
                    $storeData = Mst_store::find($offerProduct->store_id);
                    $offerProduct->store_name = $storeData->store_name;
                    //$offerProduct->rating = number_format((float)4.20, 1, '.', '');
                    //$offerProduct->ratingCount = 120;

                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                    if ($countRating == 0) {
                        $ratingData = $sumRating / 1;
                    } else {
                        $ratingData = $sumRating / $countRating;
                    }

                    $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                    $offerProduct->ratingCount = $countRating;

                    $offerProduct->productStock = Helper::productStock($offerProduct->product_id);
                    $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                    $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                    $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);
                }
                if ($roWc >9) {
                    $data['pageCount'] = ceil(@$roWc /10);
                 } else {
                     $data['pageCount'] = 1;
                 }
                $data['status'] = 1;
                $data['message'] = "success ";
            } else {
                if (isset($request->store_id) && Mst_store::find($request->store_id)) {


                    $productData =  Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_type',
                            'mst_store_products.service_type',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.product_base_image',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_status',
                            'mst_store_products.display_flag',
                            'mst_store_products.is_timeslot_based_product',
                            'mst_store_products.timeslot_start_time',
                            'mst_store_products.timeslot_end_time',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_store_product_varients.store_id',
                            'mst_store_product_varients.is_base_variant',
                            'mst_store_product_varients.variant_status',
                        );

                    $productData = $productData->where('mst_store_products.display_flag', 1)
                        ->where('mst_store_product_varients.stock_count', '>', 0)

                        ->where('mst_store_products.store_id', $request->store_id)
                        //  ->orWhere('mst_store_products.product_type',2)

                        ->where('mst_store_product_varients.is_removed', 0)
                        ->where('mst_store_product_varients.variant_status', 1)
                        ->where('mst_store_products.is_removed', 0)
                        ->where('mst_store_products.product_name', 'LIKE', "%{$product}%")
                        ->whereOr('mst_store_product_varients.variant_name', 'LIKE', "%{$product}%");

                    if (isset($request->customer_id)) {
                        // near by store
                        $cusData = Trn_store_customer::select('latitude', 'longitude')->where('customer_id', '=', $request->customer_id)->first();
                        $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();

                        if (isset($cusAddData)) {
                            $cusAddDataLat =  $cusAddData->latitude;
                            $cusAddDataLog =  $cusAddData->longitude;
                        } else {
                            $cusAddDataLat =  $cusData->latitude;
                            $cusAddDataLog =  $cusData->longitude;
                        }

                        $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $cusAddDataLat . "))
                                            * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $cusAddDataLog . "))
                                            + sin(radians(" . $cusAddDataLat . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                        $productData = $productData->orderBy('distance');
                    }


                    $productData = $productData->get();
                    $searchProducts = collect($productData);
                    $perPage = 10;
                    $page=$request->page??1;
                    $offset = ($page - 1) * $perPage;
                    $roWc=count($searchProducts);
                    $serachProductList =   $searchProducts->slice($offset, $perPage)->values()->all();

                    $data['productsData']  = $serachProductList;

                    foreach ($data['productsData'] as $offerProduct) {
                        $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                        if($offerProduct->is_base_variant==1)
                        {
                            $offerProduct->product_varient_base_image = $offerProduct->product_base_image;
    
                        }
                        else
                        {
                            $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
    
                        }
                        $storeData = Mst_store::find($offerProduct->store_id);
                        $offerProduct->store_name = $storeData->store_name;
                        // $offerProduct->rating = number_format((float)4.20, 1, '.', '');
                        // $offerProduct->ratingCount = 120;

                        $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                        $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                        if ($countRating == 0) {
                            $ratingData = $sumRating / 1;
                        } else {
                            $ratingData = $sumRating / $countRating;
                        }

                        $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                        $offerProduct->ratingCount = $countRating;
                    }
                    if ($roWc >9) {
                        $data['pageCount'] = ceil(@$roWc /10);
                     } else {
                         $data['pageCount'] = 1;
                     }
                    $data['status'] = 1;
                    $data['message'] = "success ";
                } else {
                    $data['status'] = 2;
                    $data['message'] = "store not found ";
                    return response($data);
                }
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }



    // public function searchProduct(Request $request)
    // {
    //     $data = array();
    //     try {
    //         $product = $request->product;

    //         if ($request->store_id == 0) {
    //             $productData =  Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
    //                 ->select(
    //                     'mst_store_products.product_id',
    //                     'mst_store_products.product_type',
    //                     'mst_store_products.service_type',
    //                     'mst_store_products.product_name',
    //                     'mst_store_products.product_code',
    //                     'mst_store_products.product_base_image',
    //                     'mst_store_products.show_in_home_screen',
    //                     'mst_store_products.store_id',

    //                 );

    //             if (($request->customer_id == 0) && (isset($request->latitude)) && (isset($request->longitude))) {
    //                 $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $request->latitude . "))
    //                                 * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $request->longitude . "))
    //                                 + sin(radians(" . $request->latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
    //                 $productData = $productData->orderBy('distance');
    //             }


    //             if ((isset($request->customer_id)) && ($request->customer_id != 0)) {
    //                 // near by store
    //                 $cusData = Trn_store_customer::select('latitude', 'longitude')->where('customer_id', '=', $request->customer_id)->first();
    //                 $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();

    //                 if (isset($cusAddData)) {
    //                     $cusAddDataLat =  $cusAddData->latitude;
    //                     $cusAddDataLog =  $cusAddData->longitude;
    //                 } else {
    //                     $cusAddDataLat =  $cusData->latitude;
    //                     $cusAddDataLog =  $cusData->longitude;
    //                 }

    //                 $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $cusAddDataLat . "))
    //                         * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $cusAddDataLog . "))
    //                         + sin(radians(" . $cusAddDataLat . ")) * sin(radians(mst_stores.latitude))) AS distance"));
    //                 $productData = $productData->orderBy('distance');
    //             }

    //             $productData = $productData->where('mst_store_products.product_status', 1)
    //                 ->where('mst_store_products.product_name', 'LIKE', "%{$product}%")

    //                 ->get();

    //             $data['productsData']  = $productData;
    //             $proFinalArr = array();


    //             foreach ($data['productsData'] as $offerProduct) {
    //                 if (Helper::productStock($offerProduct->product_id) > 0) {

    //                     $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
    //                     $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
    //                     $storeData = Mst_store::find($offerProduct->store_id);
    //                     $offerProduct->store_name = $storeData->store_name;

    //                     $offerProduct->rating = Helper::productRating($offerProduct->product_id);
    //                     $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
    //                     $proFinalArr[] = $offerProduct;
    //                 }
    //             }
    //             $data['status'] = 1;
    //             $data['message'] = "success ";
    //         } else {
    //             if (isset($request->store_id) && Mst_store::find($request->store_id)) {


    //                 $productData =  Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
    //                     ->select(
    //                         'mst_store_products.product_id',
    //                         'mst_store_products.product_type',
    //                         'mst_store_products.service_type',
    //                         'mst_store_products.product_name',
    //                         'mst_store_products.product_code',
    //                         'mst_store_products.product_base_image',
    //                         'mst_store_products.show_in_home_screen',
    //                         'mst_store_products.product_status',
    //                         'mst_store_products.store_id',

    //                     );

    //                 $productData = $productData->where('mst_store_products.product_status', 1)

    //                     ->where('mst_store_products.store_id', $request->store_id)

    //                     ->where('mst_store_products.product_name', 'LIKE', "%{$product}%");

    //                 if (isset($request->customer_id)) {
    //                     // near by store
    //                     $cusData = Trn_store_customer::select('latitude', 'longitude')->where('customer_id', '=', $request->customer_id)->first();
    //                     $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();

    //                     if (isset($cusAddData)) {
    //                         $cusAddDataLat =  $cusAddData->latitude;
    //                         $cusAddDataLog =  $cusAddData->longitude;
    //                     } else {
    //                         $cusAddDataLat =  $cusData->latitude;
    //                         $cusAddDataLog =  $cusData->longitude;
    //                     }

    //                     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $cusAddDataLat . "))
    //                                         * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $cusAddDataLog . "))
    //                                         + sin(radians(" . $cusAddDataLat . ")) * sin(radians(mst_stores.latitude))) AS distance"));
    //                     $productData = $productData->orderBy('distance');
    //                 }


    //                 $productData = $productData->get();

    //                 $data['productsData']  = $productData;
    //                 $proFinalArr = array();

    //                 foreach ($data['productsData'] as $offerProduct) {
    //                     if (Helper::productStock($offerProduct->product_id) > 0) {

    //                         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
    //                         $storeData = Mst_store::find($offerProduct->store_id);
    //                         $offerProduct->store_name = $storeData->store_name;
    //                         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
    //                         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
    //                         $proFinalArr[] = $offerProduct;
    //                     }
    //                 }
    //                 $data['status'] = 1;
    //                 $data['message'] = "success ";
    //             } else {
    //                 $data['status'] = 2;
    //                 $data['message'] = "store not found ";
    //                 return response($data);
    //             }
    //         }
    //         return response($data);
    //     } catch (\Exception $e) {
    //         $response = ['status' => '0', 'message' => $e->getMessage()];
    //         return response($response);
    //     } catch (\Throwable $e) {
    //         $response = ['status' => '0', 'message' => $e->getMessage()];
    //         return response($response);
    //     }
    // }




    public function listCouponAndAddress(Request $request)
    {
        $data = array();
        try {
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                if (isset($request->store_id) && $Storedata = Mst_store::find($request->store_id)) {
                    $today = Carbon::now()->toDateString();
                   
                    $usedCoupinIds = Trn_store_order::join('mst__coupons', 'mst__coupons.coupon_id', '=', 'trn_store_orders.coupon_id')
                        ->where('mst__coupons.coupon_type', 1)
                        ->where('trn_store_orders.customer_id', $request->customer_id)
                        ->groupBy('trn_store_orders.coupon_id')->pluck('trn_store_orders.coupon_id')->toArray();

                    $couponDetail = Mst_Coupon::where('store_id', $request->store_id);
                    $couponDetail = $couponDetail->where('coupon_status', 0);
                    $couponDetail = $couponDetail->whereNotIn('coupon_id', $usedCoupinIds);

                    $couponDetail = $couponDetail->whereDate('valid_from', '<=', $today)->whereDate('valid_to', '>=', $today);

                   

                    $data['couponDetails'] = $couponDetail->orderBy('coupon_id', 'DESC')->get();

                    $addressList  =  Trn_customerAddress::where('customer_id', $request->customer_id)->get();
                    $data['addressList']  = $addressList;

                    foreach ($data['addressList'] as $a) {
                        if (isset($a->longitude) && isset($a->latitude)) {

                            if (!isset($a->default_status))
                                $a->default_status = 0;

                            $longitude = $a->longitude;
                            $latitude = $a->latitude;
                            if (isset($Storedata->service_area))
                                $serVdata = $Storedata->service_area;
                            else
                                $serVdata = 0;

                            if (isset($latitude) && ($longitude)) {
                                $storesData          =       DB::table("mst_stores")->join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id');
                                $storesData         = $storesData->where('trn__store_admins.role_id', 0);
                                $storesData         = $storesData->where('mst_stores.online_status', 1);
                                $storesData         = $storesData->where('mst_stores.store_id', $request->store_id);
                                $storesData         = $storesData->where('trn__store_admins.store_account_status', 1);
                                $storesData          =       $storesData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                                                    + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                                $storesData          =       $storesData->having('distance', '<', $serVdata);
                                $storesData          =       $storesData->orderBy('distance', 'asc');
                                $storesData =       $storesData->get()->toArray();

                                $sCount = count($storesData);
                            }

                            if ($sCount > 0) {
                                $a->storeAvailabilityStatus = 1;
                            } else {
                                $a->storeAvailabilityStatus = 0;
                            }
                        } else {
                            $a->storeAvailabilityStatus = 0;
                        }

                        $a->stateData = @$a->stateFunction['state_name'];
                        $a->districtData = @$a->districtFunction['district_name'];

                        $dist = Helper::haversineGreatCircleDistance($Storedata->latitude, $Storedata->longitude, $a->latitude, $a->longitude);
                        $a->distance = @$dist;

                        $settingsRow = Trn_store_setting::where('store_id', $request->store_id)
                            ->where('service_start', '<=', $dist)
                            ->where('service_end', '>=', $dist)
                            ->first();
                        //dd($settingsRow);
                        if (isset($settingsRow->delivery_charge))
                            $a->deliveryCharge = $settingsRow->delivery_charge;
                        else
                            $a->deliveryCharge = '0';

                        if (isset($settingsRow->packing_charge))
                            $a->packingCharge = $settingsRow->packing_charge;
                        else
                            $a->packingCharge = '0';
                    }
                    $data['status'] = 1;
                    $data['message'] = "success";
                } else {
                    $data['status'] = 0;
                    $data['message'] = "Store not found ";
                }
            } else {
                $data['status'] = 0;
                $data['message'] = "Customer not found ";
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function viewCart(Request $request)
    {
        $data = array();
        try {
            if ($request->customer_id == 0) {
                $data['status'] = 0;
                $data['message'] = "No Customer ";
                return response($data);
            } else {
                if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                    $customer_id = $request->customer_id;

                    if ($cartDatas = Trn_Cart::join('mst_store_products', 'mst_store_products.product_id', '=', 'trn__carts.product_id')
                        ->join('mst_store_product_varients', 'mst_store_product_varients.product_varient_id', '=', 'trn__carts.product_varient_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'trn__carts.store_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.product_base_image',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_status',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_store_product_varients.is_base_variant',
                            'mst_store_product_varients.variant_status',
                            'mst_stores.store_name',
                            'mst_stores.store_id',
                            'trn__carts.quantity',
                            'trn__carts.remove_status'
                        )
                        ->where('trn__carts.customer_id', $customer_id)
                        ->where('trn__carts.remove_status', 0)
                        ->where('mst_store_product_varients.variant_status','=',1)
                        ->get()
                    ) {
                        $storeId = 0;
                        foreach ($cartDatas as $cartData) {
                            $cartData->product_base_image = '/assets/uploads/products/base_product/base_image/' . @$cartData->product_base_image;
                            $cartData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . @$cartData->product_varient_base_image;

                            $storeId = $cartData->store_id;
                        }

                        $data['cartItems'] = $cartDatas;
                        $data['deliveryAddress']  =  Trn_customerAddress::where('customer_id', $request->customer_id)->where('default_status', 1)->first();
                        $data['deliveryAddress']->stateData = @$data['deliveryAddress']->stateFunction['state_name'];
                        $data['deliveryAddress']->districtData = @$data['deliveryAddress']->districtFunction['district_name'];

                        $data['addressList']  =  Trn_customerAddress::where('customer_id', $request->customer_id)->get();
                        foreach ($data['addressList'] as $a) {
                            $a->stateData = @$a->stateFunction['state_name'];
                            $a->districtData = @$a->districtFunction['district_name'];
                        }

                        $today = Carbon::now()->toDateTimeString();

                        $usedCoupinIds = Trn_store_order::where('customer_id', $request->customer_id)->pluck('coupon_id')->groupBy('coupon_id')->toArray();


                        $couponDetail = Mst_Coupon::where('store_id', $storeId);
                        $couponDetail = $couponDetail->where('coupon_status', 0);
                        $couponDetail = $couponDetail->whereNotIn('coupon_id', $usedCoupinIds);
                        $couponDetail = $couponDetail->whereDate('valid_from', '<=', $today)->whereDate('valid_to', '>=', $today);
                        $data['couponDetails'] = $couponDetail->orderBy('coupon_id', 'DESC')->get();

                        $data['status'] = 1;
                        $data['message'] = "success";
                        return response($data);
                    } else {
                        $data['status'] = 0;
                        $data['message'] = "failed";
                        return response($data);
                    }
                } else {
                    $data['status'] = 2;
                    $data['message'] = "Customer not found ";
                    return response($data);
                }
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
    public function CartOutOfStockDelete(Request $request)
    {
        $data = array();
        try {
            if ($request->customer_id == 0) {
                $data['status'] = 0;
                $data['message'] = "No Customer ";
                return response($data);
            } else {
                if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                    $customer_id = $request->customer_id;
                    $OutStockProduct=[];

                    if ($cartDatas = Trn_Cart::join('mst_store_products', 'mst_store_products.product_id', '=', 'trn__carts.product_id')
                        ->join('mst_store_product_varients', 'mst_store_product_varients.product_varient_id', '=', 'trn__carts.product_varient_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'trn__carts.store_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.product_base_image',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_status',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_store_product_varients.is_base_variant',
                            'mst_store_product_varients.variant_status',
                            'mst_stores.store_name',
                            'mst_stores.store_id',
                            'trn__carts.quantity',
                            'trn__carts.remove_status'
                        )
                        ->where('trn__carts.customer_id', $customer_id)
                        ->where('trn__carts.remove_status', 0)
                        ->where('mst_store_product_varients.variant_status','=',1)
                        ->get()
                    ) {
                        foreach ($cartDatas as $cartData) {
                            if($cartData->stock_count<=0)
                            {
                                array_push($OutStockProduct,$cartData->product_varient_id);

                            }
                            
                        }
                        
                       //return $OutStockProduct;
                    if(!empty($OutStockProduct))
                    {
                        Trn_Cart::where('customer_id',$customer_id)->whereIn('product_varient_id',$OutStockProduct)->where('remove_status',0)->update(['remove_status'=>1]);
                        
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
                       
                        return response($data);
                    }
                } else {
                    $data['status'] = 2;
                    $data['message'] = "Customer not found ";
                    return response($data);
                }
            }
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }
    public function viewProduct(Request $request)
    {
        $data = array();
        try {
            if (isset($request->product_varient_id) && Mst_store_product_varient::where('product_varient_id',$request->product_varient_id)->where('variant_status','=',1)->first()) {
                if ($request->customer_id == 0) {
                    $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.business_type_id',
                            'mst_store_products.product_cat_id',
                            'mst_store_products.business_type_id',
                            'mst_store_products.product_description',
                            'mst_store_products.product_base_image',
                            'mst_store_products.store_id',
                            'mst_store_products.product_status',
                            'mst_store_products.display_flag',
                            'mst_store_products.is_timeslot_based_product',
                            'mst_store_products.timeslot_start_time',
                            'mst_store_products.timeslot_end_time',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_type',
                            'mst_store_products.product_brand',
                            'mst_store_products.service_type',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_stores.store_name'
                        )
                        ->where('mst_store_product_varients.product_varient_id', $request->product_varient_id)
                        ->first();
                    $productData->product_description =   strip_tags(@$productData->product_description);
                    $productData->product_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;
                    $productData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_varient_base_image;



                    $productData->productStock = Helper::productStock($productData->product_id);
                    $productData->variantCount = Helper::variantCount($productData->product_id);
                    $productData->isBaseVariant = Helper::isBaseVariant($productData->product_id);
                    $productData->attrCount = Helper::attrCount($productData->product_id);


                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->sum('rating');
                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->count();

                    if ($countRating == 0) {
                        $ratingData = $sumRating / 1;
                    } else {
                        $ratingData = $sumRating / $countRating;
                    }

                    $productData->ratingData = number_format((float)$ratingData, 2, '.', '');
                    $productData->ratingCount = $countRating;

                    $productData->attributesData = Trn_ProductVariantAttribute::where('product_varient_id', $productData->product_varient_id)->get();
                    foreach ($productData->attributesData as $k) {
                        $k->attr_group = Mst_attribute_group::where('attr_group_id', $k->attr_group_id)->first()->group_name;
                        $k->attr_value = Mst_attribute_value::where('attr_value_id', $k->attr_value_id)->first()->group_value;
                        $k->attrValues = Mst_attribute_value::where('attribute_group_id', $k->attr_group_id)->get();
                    }
                    $data['productData'] = $productData;

                    $data['itemPurchasedStatus'] = 0;
                    $data['feedbackAddedStatus'] = 0;

                    $otherVariants = Mst_store_product_varient::select('product_varient_id', 'product_varient_base_image')
                        ->where('product_id', $productData->product_id)->get();
                    foreach ($otherVariants as $r) {
                        $r->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $r->product_varient_base_image;
                    }
                    $data['otherVariants'] = $otherVariants;


                    // $data['feedbackData'] = [
                    //     [
                    //         'feedback_id' => 1,
                    //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus"
                    //     ],
                    //     [
                    //         'feedback_id' => 2,
                    //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus ss"
                    //     ],
                    //     [
                    //         'feedback_id' => 3,
                    //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus"
                    //     ]
                    // ]; 

                    $feedbacks = Mst_FeedbackQuestion::where('category_id', $productData->product_cat_id)->get();
                    $data['feedbackData'] = $feedbacks;

                    $reviewData = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->get();
                    foreach ($reviewData as $r) {
                        $r->customer_image =  Helper::default_user_image();
                        $customerData =  Trn_store_customer::find($r->customer_id);
                        $r->customer_name = @$customerData->customer_first_name . " " . @$customerData->customer_last_name;
                    }

                    $data['reviewData'] = $reviewData;


                    $productImages = Mst_product_image::where('product_varient_id', $request->product_varient_id)->orderBy('image_flag', 'DESC')->get();
                    foreach ($productImages as $pi) {
                        $pi->product_image = '/assets/uploads/products/base_product/base_image/' . $pi->product_image;
                    }

                    $productVideos = Trn_ProductVideo::where('product_id', $productData->product_id)->get();
                    foreach ($productVideos as $v) {

                        if ($v->platform == 'Youtube') {
                            $revLink = strrev($v->link);

                            $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                            $linkCode = strrev($revLinkCode);
                        }

                        if ($v->platform == 'Vimeo') {
                            $revLink = strrev($v->link);
                            $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                            $linkCode = strrev($revLinkCode);
                        }
                        $v->link_code = @$linkCode;
                    }

                    $data['productImages'] = $productImages;
                    $data['productVideos'] = $productVideos;

                    $data['message'] = 'success';
                    $data['status'] = 1;
                } else {
                    if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                        // recently visited products
                        //  $recVisStrRowCount = Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->count();
                        // if($recVisStrRowCount < 1)
                        // {
                        // Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->where('product_varient_id',$request->product_varient_id)->delete();
                      
                            $rvs = new Trn_RecentlyVisitedProducts;
                            $rvs->customer_id = $request->customer_id;
                            $gData = Mst_store_product_varient::find($request->product_varient_id);
                            $rvs->store_id = $gData->store_id;
                            $rvs->product_id = $gData->product_id;
                            $rvs->product_varient_id = $request->product_varient_id;
                            $prData = Mst_store_product::find($gData->product_id);
                            $rvs->vendor_id = $prData->vendor_id;
                            $rvs->category_id = $prData->product_cat_id;
                            $rvs->sub_category_id = $prData->sub_category_id;
    
                            $rvs->visit_count = 1;
                            $rvs->save();

                        
                       


                        // }
                        // else
                        // {
                        //     $rvs = Trn_RecentlyVisitedProducts::where('customer_id',$request->customer_id)->where('product_varient_id',$request->product_varient_id)->first();
                        //     $rvs->visit_count = $rvs->visit_count + 1;
                        //     $rvs->update();

                        // }

                        $orderData = Trn_store_order::join('trn_order_items', 'trn_order_items.order_id', '=', 'trn_store_orders.order_id')
                            ->where('trn_order_items.product_varient_id', $request->product_varient_id)
                            ->where('trn_store_orders.customer_id', $request->customer_id)
                            ->whereIn('trn_store_orders.status_id', [9])
                            ->first();
                        if (!$orderData)
                            $data['itemPurchasedStatus'] = 0;
                        else
                            $data['itemPurchasedStatus'] = 1;

                        $fbStatus = Trn_CustomerFeedback::where('product_varient_id', $request->product_varient_id)->where('customer_id', $request->customer_id)->first();
                        if (!$fbStatus)
                            $data['feedbackAddedStatus'] = 0;
                        else
                            $data['feedbackAddedStatus'] = 1;

                        $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                            ->select(
                                'mst_store_products.product_id',
                                'mst_store_products.product_name',
                                'mst_store_products.product_code',
                                'mst_store_products.business_type_id',
                                'mst_store_products.business_type_id',
                                'mst_store_products.product_description',
                                'mst_store_products.product_base_image',
                                'mst_store_products.store_id',
                                'mst_store_products.product_status',
                                'mst_store_products.display_flag',
                                'mst_store_products.is_timeslot_based_product',
                                'mst_store_products.timeslot_start_time',
                                'mst_store_products.timeslot_end_time',
                                'mst_store_products.show_in_home_screen',
                                'mst_store_products.product_type',
                                'mst_store_products.product_brand',
                                'mst_store_products.service_type',
                                'mst_store_product_varients.product_varient_id',
                                'mst_store_product_varients.variant_name',
                                'mst_store_product_varients.product_varient_price',
                                'mst_store_product_varients.product_varient_offer_price',
                                'mst_store_product_varients.product_varient_base_image',
                                'mst_store_product_varients.stock_count',
                                'mst_stores.store_name'
                            )
                            ->where('mst_store_product_varients.product_varient_id', $request->product_varient_id)
                            ->first();
                        $productData->product_description =   strip_tags(@$productData->product_description);
                        $productData->product_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_base_image;
                        $productData->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $productData->product_varient_base_image;

                        $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->sum('rating');
                        $countRating = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->count();

                        if ($countRating == 0) {
                            $ratingData = $sumRating / 1;
                        } else {
                            $ratingData = $sumRating / $countRating;
                        }

                        $productData->ratingData = number_format((float)$ratingData, 2, '.', '');
                        $productData->ratingCount = $countRating;

                        $productData->attributesData = Trn_ProductVariantAttribute::where('product_varient_id', $productData->product_varient_id)->get();
                        foreach ($productData->attributesData as $k) {
                            $k->attr_group = Mst_attribute_group::where('attr_group_id', $k->attr_group_id)->first()->group_name;
                            $k->attr_value = Mst_attribute_value::where('attr_value_id', $k->attr_value_id)->first()->group_value;
                            $k->attrValues = Mst_attribute_value::where('attribute_group_id', $k->attr_group_id)->get();
                        }
                        $data['productData'] = $productData;



                        $feedbacks = Mst_FeedbackQuestion::where('category_id', $productData->product_cat_id)->get();
                        $data['feedbackData'] = $feedbacks;


                        // $data['feedbackData'] = [
                        //     [
                        //         'feedback_id' => 1,
                        //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus"
                        //     ],
                        //     [
                        //         'feedback_id' => 2,
                        //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus ss"
                        //     ],
                        //     [
                        //         'feedback_id' => 3,
                        //         'feedback'=> "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the indus"
                        //     ]
                        // ];  

                        $reviewData = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->get();

                        foreach ($reviewData as $r) {
                            $r->customer_image =  Helper::default_user_image();
                            $customerData =  Trn_store_customer::find($r->customer_id);
                            $r->customer_name = @$customerData->customer_first_name . " " . @$customerData->customer_last_name;
                        }

                        $data['reviewData'] = $reviewData;

                        $productImages = Mst_product_image::where('product_varient_id', $request->product_varient_id)->orderBy('image_flag', 'DESC')->get();
                        foreach ($productImages as $pi) {
                            $pi->product_image = '/assets/uploads/products/base_product/base_image/' . $pi->product_image;
                        }

                        $productVideos = Trn_ProductVideo::where('product_id', $productData->product_id)->get();
                        foreach ($productVideos as $v) {

                            if ($v->platform == 'Youtube') {
                                $revLink = strrev($v->link);

                                $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                                $linkCode = strrev($revLinkCode);
                                //  echo $revLink." *** ".$linkCode." *** ".$linkCode." *** ".$v->link;die;
                            }

                            if ($v->platform == 'Vimeo') {
                                $revLink = strrev($v->link);
                                $revLinkCode = substr($revLink, 0, strpos($revLink, '/'));
                                $linkCode = strrev($revLinkCode);
                            }
                            $v->link_code = @$linkCode;
                        }

                        $data['productImages'] = $productImages;
                        $data['productVideos'] = $productVideos;

                        $data['message'] = 'success';
                        $data['status'] = 1;
                    } else {
                        $data['message'] = 'Customer not found';
                        $data['status'] = 0;
                    }
                }
            } else {
                $data['message'] = 'Product not found';
                $data['status'] = 0;
            }
            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function homePageSubCategory(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                if (isset($request->sub_category_id) && $subCatData = Mst_SubCategory::find($request->sub_category_id)) {
                    if ($request->customer_id == 0) {
                        $category_id = $subCatData->category_id;
                        $subCat_id = $request->sub_category_id;
                        $store_id = $request->store_id;

                        $data['subCategoryInfo'] = $subCatData;
                        $data['storeInfo'] = Mst_store::find($store_id);


                        $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $category_id)->where('sub_category_status', 1)->get();
                        foreach ($data['subCategoriesList'] as $cat) {
                            if (isset($cat->sub_category_icon)) {
                                $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                            } else {
                                $cat->sub_category_icon =  Helper::default_subcat_image();
                            }
                        }


                        $offerProductsData  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                            ->select(
                                'mst_stores.business_type_id',
                                'mst_store_products.product_type',
                                'mst_store_products.service_type',
                                'mst_store_products.product_cat_id',
                                'mst_store_products.sub_category_id',
                                'mst_store_products.product_id',
                                'mst_store_products.product_name',
                                'mst_store_products.product_code',
                                'mst_store_products.product_base_image',
                                'mst_store_products.show_in_home_screen',
                                'mst_store_products.product_status',
                                'mst_store_products.display_flag',
                                'mst_store_products.is_timeslot_based_product',
                                'mst_store_products.timeslot_start_time',
                                'mst_store_products.timeslot_end_time',
                                'mst_store_product_varients.product_varient_id',
                                'mst_store_product_varients.variant_name',
                                'mst_store_product_varients.product_varient_price',
                                'mst_store_product_varients.product_varient_offer_price',
                                'mst_store_product_varients.product_varient_base_image',
                                'mst_store_product_varients.stock_count',
                                'mst_store_product_varients.store_id'
                            )
                            ->where('mst_store_products.display_flag', 1)

                            ->where('mst_store_product_varients.stock_count', '>', 0)
                            // ->orWhere('mst_store_products.product_type',2)

                            ->where('mst_store_products.store_id', $store_id)
                            ->where('mst_store_products.sub_category_id', $subCat_id)

                            ->where('mst_store_products.show_in_home_screen', 1)->get();
                        $offerProductsFinal = array();
                        foreach ($offerProductsData as $offerProduct) {

                            $timeslotdata = Helper::findHoliday($offerProduct->store_id);

                            if ($timeslotdata == true) {

                                $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                                $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                                $storeData = Mst_store::find($offerProduct->store_id);
                                $offerProduct->store_name = $storeData->store_name;
                                // $offerProduct->rating = number_format((float)4.20, 1, '.', '');
                                // $offerProduct->ratingCount = 120;

                                $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                                $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                                if ($countRating == 0) {
                                    $ratingData = $sumRating / 1;
                                } else {
                                    $ratingData = $sumRating / $countRating;
                                }

                                $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                                $offerProduct->ratingCount = $countRating;
                                $offerProductsFinal[] = $offerProduct;
                            }
                        }

                        $data['offerProducts'] = $offerProductsFinal;

                        $listProductsData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                            ->select(
                                'mst_stores.business_type_id',
                                'mst_store_products.product_type',
                                'mst_store_products.service_type',
                                'mst_store_products.product_cat_id',
                                'mst_store_products.sub_category_id',
                                'mst_store_products.product_id',
                                'mst_store_products.product_name',
                                'mst_store_products.product_code',
                                'mst_store_products.product_base_image',
                                'mst_store_products.show_in_home_screen',
                                'mst_store_products.product_status',
                                'mst_store_products.display_flag',
                                'mst_store_products.is_timeslot_based_product',
                                'mst_store_products.timeslot_start_time',
                                'mst_store_products.timeslot_end_time',
                                'mst_store_product_varients.product_varient_id',
                                'mst_store_product_varients.variant_name',
                                'mst_store_product_varients.product_varient_price',
                                'mst_store_product_varients.product_varient_offer_price',
                                'mst_store_product_varients.product_varient_base_image',
                                'mst_store_product_varients.stock_count',
                                'mst_store_product_varients.store_id'
                            )
                            ->where('mst_store_products.display_flag', 1)
                            ->where('mst_store_product_varients.stock_count', '>', 0)
                            // ->orWhere('mst_store_products.product_type',2)

                            ->where('mst_store_products.store_id', $store_id)
                            ->where('mst_store_products.sub_category_id', $subCat_id)
                            // ->where('mst_store_products.show_in_home_screen',1)
                            ->get();

                        foreach ($listProductsData as $product) {
                            $timeslotdata = Helper::findHoliday($product->store_id);

                            if ($timeslotdata == true) {

                                $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                                $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
                                $storeData2 = Mst_store::find($product->store_id);
                                $product->store_name = $storeData2->store_name;
                                //$product->rating = number_format((float)4.20, 1, '.', '');
                                //$product->ratingCount = 120;

                                $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $product->product_varient_id)->where('isVisible', 1)->sum('rating');
                                $countRating = Trn_ReviewsAndRating::where('product_varient_id', $product->product_varient_id)->where('isVisible', 1)->count();

                                if ($countRating == 0) {
                                    $ratingData = $sumRating / 1;
                                } else {
                                    $ratingData = $sumRating / $countRating;
                                }

                                $product->variantCount = Helper::variantCount($product->product_id);
                                $product->attrCount = Helper::varAttrCount($product->product_varient_id);

                                $product->rating = number_format((float)$ratingData, 2, '.', '');
                                $product->ratingCount = $countRating;
                                $listProductsFinal[] = $product;
                            }
                        }

                        $data['listProducts'] = $listProductsFinal;

                        $data['message'] = 'success';
                        $data['status'] = 1;
                    } else {
                        if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {


                            $category_id = $subCatData->category_id;
                            $subCat_id = $request->sub_category_id;
                            $store_id = $request->store_id;

                            $data['subCategoryInfo'] = $subCatData;
                            $data['storeInfo'] = Mst_store::find($store_id);


                            $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $category_id)->where('sub_category_status', 1)->get();
                            foreach ($data['subCategoriesList'] as $cat) {
                                if (isset($cat->sub_category_icon)) {
                                    $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                                } else {
                                    $cat->sub_category_icon =  Helper::default_subcat_image();
                                }
                            }





                            $offerProductsObj  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                                ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                                ->select(
                                    'mst_stores.business_type_id',
                                    'mst_store_products.product_type',
                                    'mst_store_products.service_type',
                                    'mst_store_products.product_cat_id',
                                    'mst_store_products.sub_category_id',
                                    'mst_store_products.product_id',
                                    'mst_store_products.product_name',
                                    'mst_store_products.product_code',
                                    'mst_store_products.product_base_image',
                                    'mst_store_products.show_in_home_screen',
                                    'mst_store_products.product_status',
                                    'mst_store_products.display_flag',
                                    'mst_store_products.is_timeslot_based_product',
                                    'mst_store_products.timeslot_start_time',
                                    'mst_store_products.timeslot_end_time',
                                    'mst_store_product_varients.product_varient_id',
                                    'mst_store_product_varients.variant_name',
                                    'mst_store_product_varients.product_varient_price',
                                    'mst_store_product_varients.product_varient_offer_price',
                                    'mst_store_product_varients.product_varient_base_image',
                                    'mst_store_product_varients.stock_count',
                                    'mst_store_product_varients.store_id'
                                )
                                ->where('mst_store_products.display_flag', 1)

                                ->where('mst_store_product_varients.stock_count', '>', 0)
                                // ->orWhere('mst_store_products.product_type',2)

                                ->where('mst_store_products.store_id', $store_id)
                                ->where('mst_store_products.sub_category_id', $subCat_id)

                                ->where('mst_store_products.show_in_home_screen', 1)->get();
                            $offerProductsFinal = array();
                            foreach ($offerProductsObj as $offerProduct) 
                            {

                                $timeslotdata = Helper::findHoliday($offerProduct->store_id);

                                if ($timeslotdata == true) {
                                    $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                                    $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                                    $storeData = Mst_store::find($offerProduct->store_id);
                                    $offerProduct->store_name = $storeData->store_name;
                                    // $offerProduct->rating = number_format((float)4.20, 1, '.', '');
                                    // $offerProduct->ratingCount = 120;

                                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                                    if ($countRating == 0) {
                                        $ratingData = $sumRating / 1;
                                    } else {
                                        $ratingData = $sumRating / $countRating;
                                    }

                                    $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                                    $offerProduct->ratingCount = $countRating;
                                    $offerProductsFinal[] = $offerProduct;
                                }
                            }

                            $data['offerProducts'] = $offerProductsFinal;

                            $listProductsObj  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                                ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                                ->select(
                                    'mst_stores.business_type_id',
                                    'mst_store_products.product_type',
                                    'mst_store_products.service_type',
                                    'mst_store_products.product_cat_id',
                                    'mst_store_products.sub_category_id',
                                    'mst_store_products.product_id',
                                    'mst_store_products.product_name',
                                    'mst_store_products.product_code',
                                    'mst_store_products.product_base_image',
                                    'mst_store_products.show_in_home_screen',
                                    'mst_store_products.product_status',
                                    'mst_store_products.display_flag',
                                    'mst_store_products.is_timeslot_based_product',
                                    'mst_store_products.timeslot_start_time',
                                    'mst_store_products.timeslot_end_time',
                                    'mst_store_product_varients.product_varient_id',
                                    'mst_store_product_varients.variant_name',
                                    'mst_store_product_varients.product_varient_price',
                                    'mst_store_product_varients.product_varient_offer_price',
                                    'mst_store_product_varients.product_varient_base_image',
                                    'mst_store_product_varients.stock_count',
                                    'mst_store_product_varients.store_id'
                                )
                                ->where('mst_store_products.display_flag', 1)
                                ->where('mst_store_product_varients.stock_count', '>', 0)
                                //  ->orWhere('mst_store_products.product_type',2)

                                ->where('mst_store_products.store_id', $store_id)
                                ->where('mst_store_products.sub_category_id', $subCat_id)
                                // ->where('mst_store_products.show_in_home_screen',1)
                                ->get();
                            $listProductsFinal = array();
                            foreach ($listProductsObj as $product) {
                                $timeslotdata = Helper::findHoliday($product->store_id);

                                if ($timeslotdata == true) {

                                    $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                                    $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
                                    $storeData2 = Mst_store::find($product->store_id);
                                    $product->store_name = $storeData2->store_name;
                                    //$product->rating = number_format((float)4.20, 1, '.', '');
                                    //$product->ratingCount = 120;

                                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $product->product_varient_id)->where('isVisible', 1)->sum('rating');
                                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $product->product_varient_id)->where('isVisible', 1)->count();

                                    if ($countRating == 0) {
                                        $ratingData = $sumRating / 1;
                                    } else {
                                        $ratingData = $sumRating / $countRating;
                                    }


                                    $product->rating = number_format((float)$ratingData, 2, '.', '');
                                    $product->ratingCount = $countRating;

                                    $product->variantCount = Helper::variantCount($product->product_id);
                                    $product->attrCount = Helper::varAttrCount($product->product_varient_id);

                                    $listProductsFinal[] = $product;
                                }
                            }

                            $data['listProducts'] = $listProductsFinal;

                            $data['message'] = 'success';
                            $data['status'] = 1;
                        } else {
                            $data['message'] = 'Customer not found';
                            $data['status'] = 0;
                        }
                    }
                } else {
                    $data['message'] = 'Category not found';
                    $data['status'] = 0;
                }
            } else {
                $data['message'] = 'Store not found';
                $data['status'] = 0;
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }



    public function homePageCategory(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                if (isset($request->category_id) && Mst_categories::find($request->category_id)) {
                    $category_id = $request->category_id;
                    $store_id = $request->store_id;

                    if ($request->customer_id == 0) {

                        $data['categoryInfo'] = Mst_categories::find($category_id);
                        $data['storeInfo'] = Mst_store::find($store_id);



                        $storeProductData = Mst_store_product::select('product_cat_id')->where('store_id', '=', $store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                        $data['categoriesList'] = Mst_categories::whereIn('category_id', $storeProductData)->where('category_status', 1)->get();
                        foreach ($data['categoriesList'] as $cat) {
                            $cat->category_icon = '/assets/uploads/category/icons/' . $cat->category_icon;
                        }


                        if ($request->sub_category_id == 0) {
                            $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->get();
                            foreach ($data['subCategoriesList'] as $cat) {
                                if (isset($cat->sub_category_icon)) {
                                    $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                                } else {
                                    $cat->sub_category_icon =  Helper::default_subcat_image();
                                }
                            }
                            $additionalSubCategory = (object) [
                                "sub_category_id" => 0,
                                "category_id" => $request->category_id,
                                "sub_category_name" => "",
                                "sub_category_name_slug"=> "otherss",
                                "sub_category_icon" =>'/assets/uploads/others.png',
                                "sub_category_description" => "Others",
                                "sub_category_status"=>"1",
                                "deleted_at"=>null,
                                "created_at"=> "2023-06-19T14:51:36.000000Z",
                                "updated_at"=> "2023-06-19T14:51:36.000000Z"
                            ];

                            $data['subCategoriesList']->push($additionalSubCategory);
                           
                        } else {
                            $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->get();
                            foreach ($data['subCategoriesList'] as $cat) {
                                if (isset($cat->sub_category_icon)) {
                                    $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                                } else {
                                    $cat->sub_category_icon =  Helper::default_subcat_image();
                                }
                            }
                        }



                        $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');
                        // ->select(
                        //     'mst_store_products.product_id',
                        //     'mst_store_products.product_type',
                        //     'mst_store_products.service_type',
                        //     'mst_store_products.product_name',
                        //     'mst_store_products.product_code',
                        //     'mst_store_products.product_base_image',
                        //     'mst_store_products.show_in_home_screen',
                        //     'mst_store_products.product_status',
                        //     'mst_store_product_varients.product_varient_id',
                        //     'mst_store_product_varients.variant_name',
                        //     'mst_store_product_varients.product_varient_price',
                        //     'mst_store_product_varients.product_varient_offer_price',
                        //     'mst_store_product_varients.product_varient_base_image',
                        //     'mst_store_product_varients.stock_count',
                        //     'mst_store_product_varients.store_id'
                        // );
                        if (isset($latitude) && ($longitude)) {
                            $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                    + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                            $productData = $productData->orderBy('distance');
                        }
                        //return $request->sub_category_id;
                        if (isset($request->sub_category_id)) {
                           
                            if($request->sub_category_id>=0)
                            {
                                $productData = $productData->where('mst_store_products.sub_category_id', $request->sub_category_id);

                            }
                            else
                            {
                                
                               
                                $subcat_first= Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->first();
                                if($subcat_first)
                                {
                                    $productData = $productData->where('mst_store_products.sub_category_id', $subcat_first->sub_category_id);


                                }
                    

                              

                               }
                               


                        
                            
                        }

                        $productData = $productData->where('mst_store_products.display_flag', 1)
                            ->where('mst_store_product_varients.stock_count', '>', 0)
                            ->where('mst_store_products.product_cat_id', $category_id)
                            ->where('mst_store_products.is_removed', 0)
                            ->where('mst_store_product_varients.is_removed', 0)
                            ->where('mst_store_products.store_id', $store_id)
                            ->where('mst_store_product_varients.is_base_variant', 1)
                            ->where('mst_store_products.show_in_home_screen', 1)->get();
                        $productDataFinal = array();
                        foreach ($productData as $offerProduct) {
                            $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                            $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                            $storeData = Mst_store::find($offerProduct->store_id);
                            $offerProduct->store_name = $storeData->store_name;
                            $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->sum('rating');
                            $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->count();
                            if ($countRating == 0) {
                                $countRating = 1;
                            }
                            $ratingData = $sumRating / $countRating;
                            $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                            $offerProduct->ratingCount = $countRating;
                            $productDataFinal[] =   $offerProduct;
                        }
                        $data['offerProducts']  =    $productDataFinal;



                        // $productData = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');


                        // if (isset($latitude) && ($longitude)) {
                        //     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                        //                                 * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                        //                                 + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                        //     $productData = $productData->orderBy('distance');
                        // }

                        // if (isset($request->sub_category_id) && ($request->sub_category_id != 0)) {
                        //     $productData = $productData->where('mst_store_products.sub_category_id', $request->sub_category_id);
                        // }

                        // $productData = $productData->where('mst_store_products.product_status', 1)
                        //     ->where('mst_store_products.store_id', $store_id)
                        //     ->where('mst_store_products.product_cat_id', $category_id)
                        //     ->where('mst_store_products.show_in_home_screen', 1)->get();
                        // $productDataFinal = array();
                        // $stockCount = 0;
                        // foreach ($productData as $offerProduct) {

                        //     if (Helper::productStock($offerProduct->product_id) > 0) {
                        //         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                        //         $storeData = Mst_store::find($offerProduct->store_id);
                        //         $offerProduct->store_name = $storeData->store_name;
                        //         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
                        //         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
                        //         $offerProduct->productStock = Helper::productStock($offerProduct->product_id);
                        //         $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                        //         $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                        //         $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);

                        //         $productDataFinal[] =   $offerProduct;
                        //     }
                        // }
                        // $data['offerProducts']  =    $productDataFinal;


                        // $allProducts = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                        // if (isset($request->sub_category_id) && ($request->sub_category_id != 0)) {
                        //     $allProducts = $allProducts->where('mst_store_products.sub_category_id', $request->sub_category_id);
                        // }

                        // $allProducts = $allProducts->where('mst_store_products.product_status', 1)
                        //     ->where('mst_store_products.product_cat_id', $category_id)
                        //     ->where('mst_store_products.store_id', $store_id)
                        //     ->get();

                        // $allProductDataFinal = array();

                        // foreach ($allProducts as $allProduct) {
                        //     if (Helper::productStock($allProduct->product_id) > 0) {
                        //         $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                        //         $storeData = Mst_store::find($allProduct->store_id);
                        //         $allProduct->store_name = $storeData->store_name;
                        //         $allProduct->rating = Helper::productRating($allProduct->product_id);
                        //         $allProduct->ratingCount = Helper::productRatingCount($allProduct->product_id);
                        //         $allProduct->varAttrStatus =  Helper::varAttrStatus($allProduct->product_id);

                        //         $allProduct->product_varient_id =  Helper::findServiceVariant($allProduct->product_id);
                        //         $allProduct->productStock = Helper::productStock($allProduct->product_id);

                        //         $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                        //         $allProduct->isBaseVariant = Helper::isBaseVariant($allProduct->product_id);
                        //         $allProduct->attrCount = Helper::attrCount($allProduct->product_id);

                        //         $allProductDataFinal[] =   $allProduct;
                        //     }
                        // }

                        // $data['listProducts']  = $allProductDataFinal;




                        $allProducts  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                            if (isset($request->sub_category_id)) {
                           
                                if($request->sub_category_id>=0)
                                {
                                    $allProducts = $allProducts->where('mst_store_products.sub_category_id', $request->sub_category_id);
    
                                }
                                else
                                {
                                    
                                   
                                    $subcat_first= Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->first();
                                    if($subcat_first)
                                    {
                                        $allProducts = $allProducts->where('mst_store_products.sub_category_id', $subcat_first->sub_category_id);
    
    
                                    }
                        
    
                                  
    
                                   }
                                   
    
    
                            
                                
                            }
                           

                        $allProducts = $allProducts->where('mst_store_products.display_flag', 1)
                            ->where('mst_store_products.store_id', $store_id)
                            //->where('mst_store_product_varients.stock_count', '>', 0)
                            ->where('mst_store_product_varients.is_removed', 0)
                            ->where('mst_store_products.is_removed', 0)
                            ->where('mst_store_product_varients.is_base_variant', 1)
                            ->where('mst_store_products.product_cat_id', $category_id)
                            ->get();

                        foreach ($allProducts as $allProduct) {
                            $allProduct->variant_stock_count=Mst_store_product_varient::where('product_id',$allProduct->product_id)->where('is_removed',0)->where('stock_count','>',0)->sum('stock_count');
                            $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                            $allProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_varient_base_image;
                            $storeData = Mst_store::find($allProduct->store_id);
                            $allProduct->store_name = $storeData->store_name;

                            $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                            $countRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->count();

                            if ($countRating == 0) {
                                $ratingData = $sumRating / 1;
                            } else {
                                $ratingData = $sumRating / $countRating;
                            }

                            $allProduct->rating = number_format((float)$ratingData, 2, '.', '');
                            $allProduct->ratingCount = $countRating;

                            $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                            $allProduct->attrCount = Helper::varAttrCount($allProduct->product_varient_id);
                            $allProduct->cartCount=0;
                            $allProduct->cartId=0;
                            $allProduct->cartStoreId=0;
                        }

                        $data['listProducts']  = $allProducts->where('variant_stock_count','>',0);
                        // $products = collect($data['listProducts'] )->values();
                        //
                        $perPage = 10;
                        $page=$request->page??1;
                        $offset = ($page - 1) * $perPage;
                        $roWc=count(collect($data['listProducts'] )->values());
                        $data['allProductCount']=$roWc;
                        $products = collect($data['listProducts'] )->slice($offset, $perPage)->values();
                        //
                        $data['listProducts']=$products;
                        if ($roWc >9) {
                            $data['pageCount'] = ceil(@$roWc /10);
                         } else {
                             $data['pageCount'] = 1;
                         }




                        $data['message'] = 'success';
                        $data['status'] = 1;
                    } else {
                        if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {

                            $category_id = $request->category_id;
                            $store_id = $request->store_id;

                            $rvpc = new Trn_RecentlyVisitedProductCategory;
                            $rvpc->customer_id = $request->customer_id;
                            $rvpc->store_id = $request->store_id;
                            $rvpc->category_id = $category_id;
                            $rvpc->visit_count = 1;
                            $rvpc->save();

                            $data['categoryInfo'] = Mst_categories::find($category_id);
                            $data['storeInfo'] = Mst_store::find($store_id);

                            $storeProductData = Mst_store_product::select('product_cat_id')->where('store_id', '=', $store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                            $data['categoriesList'] = Mst_categories::whereIn('category_id', $storeProductData)->where('category_status', 1)->get();
                            foreach ($data['categoriesList'] as $cat) {
                                $cat->category_icon = '/assets/uploads/category/icons/' . $cat->category_icon;
                            }



                            if ($request->sub_category_id == 0) {
                                $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->get();
                                foreach ($data['subCategoriesList'] as $cat) {
                                    if (isset($cat->sub_category_icon)) {
                                        $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                                    } else {
                                        $cat->sub_category_icon =  Helper::default_subcat_image();
                                    }
                                }
                                $additionalSubCategory = (object) [
                                    "sub_category_id" => 0,
                                    "category_id" => $request->category_id,
                                    "sub_category_name" => "",
                                    "sub_category_name_slug"=> "otherss",
                                    "sub_category_icon" => '/assets/uploads/others.png',
                                    "sub_category_description" => "Others",
                                    "sub_category_status"=>"1",
                                    "deleted_at"=>null,
                                    "created_at"=> "2023-06-19T14:51:36.000000Z",
                                    "updated_at"=> "2023-06-19T14:51:36.000000Z"
                                ];

                                $data['subCategoriesList']->push($additionalSubCategory);
                            } else {
                                $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->get();
                                foreach ($data['subCategoriesList'] as $cat) {
                                    if (isset($cat->sub_category_icon)) {
                                        $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                                    } else {
                                        $cat->sub_category_icon =  Helper::default_subcat_image();
                                    }
                                }
                                $additionalSubCategory = (object) [
                                    "sub_category_id" => 0,
                                    "category_id" => $request->category_id,
                                    "sub_category_name" => "",
                                    "sub_category_name_slug"=> "otherss",
                                    "sub_category_icon" => '/assets/uploads/others.png',
                                    "sub_category_description" => "Others",
                                    "sub_category_status"=>"1",
                                    "deleted_at"=>null,
                                    "created_at"=> "2023-06-19T14:51:36.000000Z",
                                    "updated_at"=> "2023-06-19T14:51:36.000000Z"
                                ];

                                $data['subCategoriesList']->push($additionalSubCategory);
                              
                               


                            }



                            // $productData = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                            // if (isset($latitude) && ($longitude)) {
                            //     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                            //                             * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                            //                             + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                            //     $productData = $productData->orderBy('distance');
                            // }

                            // if (isset($request->sub_category_id) && ($request->sub_category_id != 0)) {
                            //     $productData = $productData->where('mst_store_products.sub_category_id', $request->sub_category_id);
                            // }


                            // $productData = $productData->where('mst_store_products.product_status', 1)
                            //     ->where('mst_store_products.store_id', $store_id)
                            //     ->where('mst_store_products.product_cat_id', $category_id)
                            //     ->where('mst_store_products.show_in_home_screen', 1)->get();
                            // $productDataFinal = array();
                            // $stockCount = 0;
                            // foreach ($productData as $offerProduct) {

                            //     if (Helper::productStock($offerProduct->product_id) > 0) {
                            //         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                            //         $storeData = Mst_store::find($offerProduct->store_id);
                            //         $offerProduct->store_name = $storeData->store_name;
                            //         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
                            //         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
                            //         $offerProduct->productStock = Helper::productStock($offerProduct->product_id);

                            //         $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                            //         $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                            //         $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);

                            //         $productDataFinal[] =   $offerProduct;
                            //     }
                            // }
                            // $data['offerProducts']  =    $productDataFinal;



                            $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                                ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                            if (isset($latitude) && isset($longitude)) {
                                $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                                * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                                + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                                $productData = $productData->orderBy('distance');
                            }

                            if (isset($request->sub_category_id)) {
                           
                                if($request->sub_category_id>=0)
                                {
                                    $productData = $productData->where('mst_store_products.sub_category_id', $request->sub_category_id);
    
                                }
                                else
                                {
                                    
                                   
                                    $subcat_first= Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->first();
                                    //dd($subcat_first);
                                    if($subcat_first)
                                    {
                                        $productData = $productData->where('mst_store_products.sub_category_id', $subcat_first->sub_category_id);
    
    
                                    }
                        
    
                                  
    
                                   }
                                   
    
    
                            
                                
                            }

                            $productData = $productData->where('mst_store_products.display_flag', 1)
                                ->where('mst_store_product_varients.stock_count', '>', 0)
                                ->where('mst_store_products.store_id', $store_id)
                                ->where('mst_store_products.product_cat_id', $category_id)
                                ->where('mst_store_product_varients.is_removed', 0)
                                ->where('mst_store_products.is_removed', 0)
                                ->where('mst_store_product_varients.is_base_variant', 1)
                                ->where('mst_store_products.show_in_home_screen', 1)->get();
                            $productDataFinal = array();
                            foreach ($productData as $offerProduct) {
                                $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                                $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                                $storeData = Mst_store::find($offerProduct->store_id);
                                $offerProduct->store_name = $storeData->store_name;

                                $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                                $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                                if ($countRating == 0) {
                                    $ratingData = $sumRating / 1;
                                } else {
                                    $ratingData = $sumRating / $countRating;
                                }

                                $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                                $offerProduct->ratingCount = $countRating;


                                $productDataFinal[] =   $offerProduct;
                            }
                            $data['offerProducts']  =    $productDataFinal;




                            // $allProducts = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                            // if (isset($request->sub_category_id) && ($request->sub_category_id != 0)) {
                            //     $allProducts = $allProducts->where('mst_store_products.sub_category_id', $request->sub_category_id);
                            // }

                            // $allProducts = $allProducts->where('mst_store_products.product_status', 1)
                            //     ->where('mst_store_products.product_cat_id', $category_id)
                            //     ->where('mst_store_products.store_id', $store_id)
                            //     ->get();

                            // $allProductDataFinal = array();

                            // foreach ($allProducts as $allProduct) {
                            //     if (Helper::productStock($allProduct->product_id) > 0) {
                            //         $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                            //         $storeData = Mst_store::find($allProduct->store_id);
                            //         $allProduct->store_name = $storeData->store_name;
                            //         $allProduct->rating = Helper::productRating($allProduct->product_id);
                            //         $allProduct->ratingCount = Helper::productRatingCount($allProduct->product_id);
                            //         $allProduct->varAttrStatus =  Helper::varAttrStatus($allProduct->product_id);
                            //         $allProduct->product_varient_id =  Helper::findServiceVariant($allProduct->product_id);
                            //         $allProduct->productStock = Helper::productStock($allProduct->product_id);

                            //         $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                            //         $allProduct->isBaseVariant = Helper::isBaseVariant($allProduct->product_id);
                            //         $allProduct->attrCount = Helper::attrCount($allProduct->product_id);

                            //         $allProductDataFinal[] =   $allProduct;
                            //     }
                            // }

                            // $data['listProducts']  = $allProductDataFinal;



                            $allProducts  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                                ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                            // if (isset($request->sub_category_id) && ($request->sub_category_id != 0)) {
                            //     $allProducts = $allProducts->where('mst_store_products.sub_category_id', $request->sub_category_id);
                            // }
                            if (isset($request->sub_category_id)) {
                           
                                if($request->sub_category_id>=0)
                                {
                                    $allProducts = $allProducts->where('mst_store_products.sub_category_id', $request->sub_category_id);
    
                                }
                                else
                                {
                                    
                                   
                                    $subcat_first= Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->first();
                                    if($subcat_first)
                                    {
                                        $allProducts = $allProducts->where('mst_store_products.sub_category_id', $subcat_first->sub_category_id);
    
    
                                    }
                        
    
                                  
    
                                   }
                                   
    
    
                            
                                
                            }
                           



                            $allProducts = $allProducts->where('mst_store_products.display_flag', 1)
                                ->where('mst_store_products.store_id', $store_id)
                                //->where('mst_store_product_varients.stock_count', '>', 0)
                                ->where('mst_store_product_varients.is_removed', 0)
                                ->where('mst_store_products.is_removed', 0)
                                ->where('mst_store_product_varients.is_base_variant', 1)
                                ->where('mst_store_products.product_cat_id', $category_id)
                                ->get();

                            foreach ($allProducts as $allProduct) {
                                $allProduct->variant_stock_count=Mst_store_product_varient::where('product_id',$allProduct->product_id)->where('is_removed',0)->where('stock_count','>',0)->sum('stock_count');
                                $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                                $allProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_varient_base_image;
                                $storeData = Mst_store::find($allProduct->store_id);
                                $allProduct->store_name = $storeData->store_name;

                                $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                                $countRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->count();

                                if ($countRating == 0) {
                                    $ratingData = $sumRating / 1;
                                } else {
                                    $ratingData = $sumRating / $countRating;
                                }

                                $allProduct->rating = number_format((float)$ratingData, 2, '.', '');
                                $allProduct->ratingCount = $countRating;

                                $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                                $allProduct->attrCount = Helper::varAttrCount($allProduct->product_varient_id);
                                $in_cart=Trn_Cart::where('customer_id',$request->customer_id)->where('product_varient_id',$allProduct->product_varient_id)->where('remove_status',0)->first();
                                $getlatestCart =  Trn_Cart::where('customer_id', $request->customer_id)->where('remove_status',0)->first();
                                if($getlatestCart)
                                {
                                    $cartStoreId=$getlatestCart->store_id;

                                }
                                else
                                {
                                    $cartStoreId=0;

                                }
                                if($in_cart)
                                {
                                    $cartCount=$in_cart->quantity;
                                    $cartId=$in_cart->cart_id;
                                    
    
    
                                }
                                else
                                {
                                    $cartCount=0;
                                    $cartId=0;
    
                                }
                                $allProduct->cartCount=(int)$cartCount;
                                $allProduct->cartId=(int)$cartId;
                                $allProduct->cartStoreId=(int)$cartStoreId;
                            }

                            $data['listProducts'] =$allProducts->where('variant_stock_count','>',0);

                            // $products = collect($data['listProducts'] )->values();
                            // $data['listProducts']=$products;
                            $perPage = 10;
                            $page=$request->page??1;
                            $offset = ($page - 1) * $perPage;
                            $roWc=count(collect($data['listProducts'] )->values());
                            $data['allProductCount']=$roWc;
                            $products = collect($data['listProducts'] )->slice($offset, $perPage)->values();
                            //
                            $data['listProducts']=$products;
                            if ($roWc >9) {
                                $data['pageCount'] = ceil(@$roWc /10);
                            } else {
                                $data['pageCount'] = 1;
                            }

                            $data['message'] = 'success';
                            $data['status'] = 1;
                        } else {
                            $data['message'] = 'Customer not found';
                            $data['status'] = 0;
                        }
                    }
                } else {
                    if($request->category_id!=0)
                    {
                    $data['message'] = 'Category not found';
                    $data['status'] = 0;
                    }
                    else
                    {
                        $category_id=$request->category_id;
                        $store_id=$request->store_id;
                        
                        $brand_name=$request->brand_name??'tset';
                        $data['categoryInfo'] = (object)[];
                        $data['storeInfo'] = Mst_store::find($store_id);



                        $storeProductData = Mst_store_product::select('product_cat_id')->where('store_id', '=', $store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                        $data['categoriesList'] = Mst_categories::whereIn('category_id', $storeProductData)->where('category_status', 1)->get();
                        foreach ($data['categoriesList'] as $cat) {
                            $cat->category_icon = '/assets/uploads/category/icons/' . $cat->category_icon;
                        }


                        if ($request->sub_category_id == 0) {
                            $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->get();
                            foreach ($data['subCategoriesList'] as $cat) {
                                if (isset($cat->sub_category_icon)) {
                                    $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                                } else {
                                    $cat->sub_category_icon =  Helper::default_subcat_image();
                                }
                            }
                            $additionalSubCategory = (object) [
                                "sub_category_id" => 0,
                                "category_id" => $request->category_id,
                                "sub_category_name" => "",
                                "sub_category_name_slug"=> "otherss",
                                "sub_category_icon" =>'/assets/uploads/others.png',
                                "sub_category_description" => "Others",
                                "sub_category_status"=>"1",
                                "deleted_at"=>null,
                                "created_at"=> "2023-06-19T14:51:36.000000Z",
                                "updated_at"=> "2023-06-19T14:51:36.000000Z"
                            ];

                            $data['subCategoriesList']->push($additionalSubCategory);
                           
                        } else {
                            $data['subCategoriesList'] = Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->get();
                            foreach ($data['subCategoriesList'] as $cat) {
                                if (isset($cat->sub_category_icon)) {
                                    $cat->sub_category_icon = '/assets/uploads/category/icons/' . $cat->sub_category_icon;
                                } else {
                                    $cat->sub_category_icon =  Helper::default_subcat_image();
                                }
                            }
                        }



                        $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');
                        // ->select(
                        //     'mst_store_products.product_id',
                        //     'mst_store_products.product_type',
                        //     'mst_store_products.service_type',
                        //     'mst_store_products.product_name',
                        //     'mst_store_products.product_code',
                        //     'mst_store_products.product_base_image',
                        //     'mst_store_products.show_in_home_screen',
                        //     'mst_store_products.product_status',
                        //     'mst_store_product_varients.product_varient_id',
                        //     'mst_store_product_varients.variant_name',
                        //     'mst_store_product_varients.product_varient_price',
                        //     'mst_store_product_varients.product_varient_offer_price',
                        //     'mst_store_product_varients.product_varient_base_image',
                        //     'mst_store_product_varients.stock_count',
                        //     'mst_store_product_varients.store_id'
                        // );
                        if (isset($latitude) && ($longitude)) {
                            $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                    + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                            $productData = $productData->orderBy('distance');
                        }
                        //return $request->sub_category_id;
                        if (isset($request->sub_category_id)) {
                           
                            if($request->sub_category_id>=0)
                            {
                                $productData = $productData->where('mst_store_products.sub_category_id', $request->sub_category_id);

                            }
                            else
                            {
                                
                               
                                $subcat_first= Mst_SubCategory::where('category_id', $request->category_id)->where('sub_category_status', 1)->first();
                                if($subcat_first)
                                {
                                    $productData = $productData->where('mst_store_products.sub_category_id', $subcat_first->sub_category_id);


                                }
                    

                              

                               }
                               


                        
                            
                        }

                        $productData = $productData->where('mst_store_products.display_flag', 1)
                            ->where('mst_store_product_varients.stock_count', '>', 0)
                            ->where('mst_store_products.product_brand', $brand_name)
                            ->where('mst_store_products.is_removed', 0)
                            ->where('mst_store_product_varients.is_removed', 0)
                            ->where('mst_store_products.store_id', $store_id)
                            ->where('mst_store_product_varients.is_base_variant', 1)
                            ->where('mst_store_products.show_in_home_screen', 1)->get();
                        $productDataFinal = array();
                        foreach ($productData as $offerProduct) {
                            $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                            $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                            $storeData = Mst_store::find($offerProduct->store_id);
                            $offerProduct->store_name = $storeData->store_name;
                            $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->sum('rating');
                            $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->count();
                            if ($countRating == 0) {
                                $countRating = 1;
                            }
                            $ratingData = $sumRating / $countRating;
                            $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                            $offerProduct->ratingCount = $countRating;
                            $productDataFinal[] =   $offerProduct;
                        }
                        $data['offerProducts']  =    $productDataFinal;



                        // $productData = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');


                        // if (isset($latitude) && ($longitude)) {
                        //     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                        //                                 * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                        //                                 + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                        //     $productData = $productData->orderBy('distance');
                        // }

                        // if (isset($request->sub_category_id) && ($request->sub_category_id != 0)) {
                        //     $productData = $productData->where('mst_store_products.sub_category_id', $request->sub_category_id);
                        // }

                        // $productData = $productData->where('mst_store_products.product_status', 1)
                        //     ->where('mst_store_products.store_id', $store_id)
                        //     ->where('mst_store_products.product_cat_id', $category_id)
                        //     ->where('mst_store_products.show_in_home_screen', 1)->get();
                        // $productDataFinal = array();
                        // $stockCount = 0;
                        // foreach ($productData as $offerProduct) {

                        //     if (Helper::productStock($offerProduct->product_id) > 0) {
                        //         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                        //         $storeData = Mst_store::find($offerProduct->store_id);
                        //         $offerProduct->store_name = $storeData->store_name;
                        //         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
                        //         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
                        //         $offerProduct->productStock = Helper::productStock($offerProduct->product_id);
                        //         $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                        //         $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                        //         $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);

                        //         $productDataFinal[] =   $offerProduct;
                        //     }
                        // }
                        // $data['offerProducts']  =    $productDataFinal;


                        // $allProducts = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                        // if (isset($request->sub_category_id) && ($request->sub_category_id != 0)) {
                        //     $allProducts = $allProducts->where('mst_store_products.sub_category_id', $request->sub_category_id);
                        // }

                        // $allProducts = $allProducts->where('mst_store_products.product_status', 1)
                        //     ->where('mst_store_products.product_cat_id', $category_id)
                        //     ->where('mst_store_products.store_id', $store_id)
                        //     ->get();

                        // $allProductDataFinal = array();

                        // foreach ($allProducts as $allProduct) {
                        //     if (Helper::productStock($allProduct->product_id) > 0) {
                        //         $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                        //         $storeData = Mst_store::find($allProduct->store_id);
                        //         $allProduct->store_name = $storeData->store_name;
                        //         $allProduct->rating = Helper::productRating($allProduct->product_id);
                        //         $allProduct->ratingCount = Helper::productRatingCount($allProduct->product_id);
                        //         $allProduct->varAttrStatus =  Helper::varAttrStatus($allProduct->product_id);

                        //         $allProduct->product_varient_id =  Helper::findServiceVariant($allProduct->product_id);
                        //         $allProduct->productStock = Helper::productStock($allProduct->product_id);

                        //         $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                        //         $allProduct->isBaseVariant = Helper::isBaseVariant($allProduct->product_id);
                        //         $allProduct->attrCount = Helper::attrCount($allProduct->product_id);

                        //         $allProductDataFinal[] =   $allProduct;
                        //     }
                        // }

                        // $data['listProducts']  = $allProductDataFinal;

                        
                        $allProducts  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                        
                       
                   
                    $allProducts = $allProducts->where('mst_store_products.display_flag', 1)
                        ->where('mst_store_products.store_id', $store_id)
                        ->where('mst_store_products.product_brand',$brand_name)
                        //->where('mst_store_product_varients.stock_count', '>', 0)
                        ->where('mst_store_product_varients.is_removed', 0)
                        ->where('mst_store_products.is_removed', 0)
                        ->where('mst_store_product_varients.is_base_variant', 1)
                       // ->where('mst_store_products.product_cat_id', $category_id)
                        ->get();

                    foreach ($allProducts as $allProduct) {
                        $allProduct->variant_stock_count=Mst_store_product_varient::where('product_id',$allProduct->product_id)->where('is_removed',0)->where('stock_count','>',0)->sum('stock_count');
                        $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                        $allProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_varient_base_image;
                        $storeData = Mst_store::find($allProduct->store_id);
                        $allProduct->store_name = $storeData->store_name;

                        $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                        $countRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->count();

                        if ($countRating == 0) {
                            $ratingData = $sumRating / 1;
                        } else {
                            $ratingData = $sumRating / $countRating;
                        }

                        $allProduct->rating = number_format((float)$ratingData, 2, '.', '');
                        $allProduct->ratingCount = $countRating;

                        $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                        $allProduct->attrCount = Helper::varAttrCount($allProduct->product_varient_id);
                        $allProduct->cartCount=0;
                        $allProduct->cartId=0;
                        $allProduct->cartStoreId=0;
                    }

                    $data['listProducts']  = $allProducts->where('variant_stock_count','>',0);
                    // $products = collect($data['listProducts'] )->values();
                    //
                    $perPage = 10;
                    $page=$request->page??1;
                    $offset = ($page - 1) * $perPage;
                    $roWc=count(collect($data['listProducts'] )->values());
                    $data['allProductCount']=$roWc;
                    $products = collect($data['listProducts'] )->slice($offset, $perPage)->values();
                    //
                    $data['listProducts']=$products;
                    if ($roWc >9) {
                        $data['pageCount'] = ceil(@$roWc /10);
                     } else {
                         $data['pageCount'] = 1;
                     }




                    $data['message'] = 'success';
                    $data['status'] = 1;
                    }
                }
            } else {
                $data['message'] = 'Store not found';
                $data['status'] = 0;
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function homePageStore(Request $request)
    {

        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                if ($request->customer_id == 0) {
                    $store_id = $request->store_id;

                    $latitude = $request->latitude;
                    $longitude = $request->longitude;




                    $data['storeInfo'] = Mst_store::find($store_id);
                    $sCount = 0;

                    if (isset($data['storeInfo']->store_district_id))
                        $data['storeInfo']->district_name = District::find($data['storeInfo']->store_district_id)->district_name;
                    else
                        $data['storeInfo']->district_name = '';

                    if (isset($latitude) && isset($longitude)) {
                        $sa  = $data['storeInfo']->service_area;
                        if (!isset($sa))
                            $sa = 0;

                        $storesData          =       DB::table("mst_stores")->join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id');
                        $storesData         = $storesData->where('trn__store_admins.role_id', 0);
                        $storesData         = $storesData->where('mst_stores.online_status', 1);
                        $storesData         = $storesData->where('mst_stores.store_id', $store_id);
                        $storesData         = $storesData->where('trn__store_admins.store_account_status', 1);
                        $storesData          =       $storesData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                                    + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                        $storesData          =       $storesData->having('distance', '<', $sa);
                        $storesData          =       $storesData->orderBy('distance', 'asc');
                        $storesData =       $storesData->get()->toArray();

                        $sCount = count($storesData);
                    }

                    if ($sCount > 0) {
                        $data['storeAvailabilityStatus'] = 1;
                    } else {
                        $data['storeAvailabilityStatus'] = 0;
                    }


                    $data['sliderImages'] =  Mst_store_images::where('store_id', $store_id)->get();
                    foreach ($data['sliderImages'] as $img) {
                        if (isset($img->store_image)) {
                            $img->store_image =  '/assets/uploads/store_images/images/' . $img->store_image;
                        } else {
                            $img->store_image =  null;
                        }
                    }

                    $storeProductData = Mst_store_product::select('product_cat_id')->where('store_id', '=', $store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                    $data['categoriesList'] = Mst_categories::whereIn('category_id', $storeProductData)->where('category_status', 1)->get();
                    foreach ($data['categoriesList'] as $cat) {
                        $cat->category_icon = '/assets/uploads/category/icons/' . $cat->category_icon;
                    }
                    $data['storeBrandList']= Helper::getProductBrandsByStore($store_id);




                    $data['offerProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        // ->select('mst_stores.business_type_id', 'mst_store_products.product_id', 'mst_store_products.product_type', 'mst_store_products.service_type', 'mst_store_products.product_name', 'mst_store_products.product_code', 'mst_store_products.product_base_image', 'mst_store_products.show_in_home_screen', 'mst_store_products.product_status', 'mst_store_product_varients.product_varient_id', 'mst_store_product_varients.variant_name', 'mst_store_product_varients.product_varient_price', 'mst_store_product_varients.product_varient_offer_price', 'mst_store_product_varients.product_varient_base_image', 'mst_store_product_varients.stock_count', 'mst_store_product_varients.store_id')
                        ->where('mst_store_products.display_flag', 1)
                        ->where('mst_store_products.store_id', $store_id)
                       
                        //->orWhere('mst_store_products.product_type',2)
                        ->where('mst_store_product_varients.is_removed', 0)
                        ->where('mst_store_products.is_removed', 0)
                        ->where('mst_store_products.is_removed', 0)

                        ->where('mst_store_product_varients.is_base_variant', 1)
                        ->where('mst_store_products.show_in_home_screen', 1)->get();

                    foreach ($data['offerProducts'] as $offerProduct) {
                        $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                        $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                        $storeData = Mst_store::find($offerProduct->store_id);
                        $offerProduct->store_name = $storeData->store_name;
                        //    $offerProduct->rating = number_format((float)4.20, 1, '.', '');
                        //    $offerProduct->ratingCount = 120;

                        $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                        $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                        if ($countRating == 0) {
                            $ratingData = $sumRating / 1;
                        } else {
                            $ratingData = $sumRating / $countRating;
                        }

                        $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                        $offerProduct->ratingCount = $countRating;
                    }


                    // $productData = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                    //     ->select(
                    //         'mst_store_products.product_id',
                    //         'mst_store_products.product_type',
                    //         'mst_store_products.service_type',
                    //         'mst_store_products.product_name',
                    //         'mst_store_products.product_code',
                    //         'mst_store_products.product_base_image',
                    //         'mst_store_products.show_in_home_screen',
                    //         'mst_store_products.product_status',
                    //         'mst_store_products.store_id'
                    //     );

                    // if (isset($latitude) && ($longitude)) {
                    //     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                    //                     * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                    //                     + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                    //     $productData = $productData->orderBy('distance');
                    // }
                    // $productData = $productData->where('mst_store_products.product_status', 1)
                    //     ->where('mst_store_products.store_id', $store_id)
                    //     ->where('mst_store_products.show_in_home_screen', 1)->get();
                    // $productDataFinal = array();
                    // $stockCount = 0;
                    // foreach ($productData as $offerProduct) {

                    //     if (Helper::productStock($offerProduct->product_id) > 0) {
                    //         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                    //         $storeData = Mst_store::find($offerProduct->store_id);
                    //         $offerProduct->store_name = $storeData->store_name;
                    //         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
                    //         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
                    //         $offerProduct->productStock = Helper::productStock($offerProduct->product_id);

                    //         $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                    //         $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                    //         $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);

                    //         $productDataFinal[] =   $offerProduct;
                    //     }
                    // }
                    // $data['offerProducts']  =    $productDataFinal;


                    $data['recentlyVisitedProducts'] = [];
                    $data['purchasedProducts']  = [];




                    $allProducts  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        // ->select('mst_stores.business_type_id', 'mst_store_products.product_id', 'mst_store_products.product_type', 'mst_store_products.service_type', 'mst_store_products.product_name', 'mst_store_products.product_code', 'mst_store_products.product_base_image', 'mst_store_products.show_in_home_screen', 'mst_store_products.product_status', 'mst_store_product_varients.product_varient_id', 'mst_store_product_varients.variant_name', 'mst_store_product_varients.product_varient_price', 'mst_store_product_varients.product_varient_offer_price', 'mst_store_product_varients.product_varient_base_image', 'mst_store_product_varients.stock_count', 'mst_store_product_varients.store_id')
                        ->where('mst_store_products.display_flag', 1)
                        ->where('mst_store_products.store_id', $store_id)
                        //->where('mst_store_product_varients.stock_count', '>', 0)
                        ->where('mst_store_product_varients.is_removed', 0)
                        ->where('mst_store_products.is_removed', 0)
                       ->where('mst_store_product_varients.is_base_variant', 1)
                        ->get();

                    foreach ($allProducts as $allProduct) {
                        $allProduct->variant_stock_count=Mst_store_product_varient::where('product_id',$allProduct->product_id)->where('is_removed',0)->where('stock_count','>',0)->sum('stock_count');
                        //$vaCount=Helper::variantCount($allProduct->product_id);
                        $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                        $allProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_varient_base_image;
                        $storeData = Mst_store::find($allProduct->store_id);
                        $allProduct->store_name = $storeData->store_name;

                        $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                        $countRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->count();

                        if ($countRating == 0) {
                            $ratingData = $sumRating / 1;
                        } else {
                            $ratingData = $sumRating / $countRating;
                        }

                        $allProduct->rating = number_format((float)$ratingData, 2, '.', '');
                        $allProduct->ratingCount = $countRating;

                        $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                        $allProduct->attrCount = Helper::varAttrCount($allProduct->product_varient_id);
                    }

                    $data['allProducts']  = $allProducts->where('variant_stock_count','>',0)->all();
                    $perPage = 10;
                    $page=$request->page??1;
                    $offset = ($page - 1) * $perPage;
                    $roWc=count(collect($data['allProducts'] )->values());
                    $data['allProductCount']=$roWc;
                    $products = collect($data['allProducts'] )->slice($offset, $perPage)->values();
                    $data['allProducts']=$products;


                    //   $allProducts = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');
                    //     $allProducts = $allProducts->where('mst_store_products.product_status', 1)
                    //         ->where('mst_store_products.store_id', $store_id)
                    //         ->get();

                    //     $allProductDataFinal = array();

                    //     foreach ($allProducts as $allProduct) {
                    //         if (Helper::productStock($allProduct->product_id) > 0) {
                    //             $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                    //             $storeData = Mst_store::find($allProduct->store_id);
                    //             $allProduct->store_name = $storeData->store_name;
                    //             $allProduct->rating = Helper::productRating($allProduct->product_id);
                    //             $allProduct->ratingCount = Helper::productRatingCount($allProduct->product_id);
                    //             $allProduct->varAttrStatus =  Helper::varAttrStatus($allProduct->product_id);
                    //             $allProduct->product_varient_id =  Helper::findServiceVariant($allProduct->product_id);
                    //             $allProduct->productStock = Helper::productStock($allProduct->product_id);

                    //             $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                    //             $allProduct->isBaseVariant = Helper::isBaseVariant($allProduct->product_id);
                    //             $allPr oduct->attrCount = Helper::attrCount($allProduct->product_id);

                    //             $allProductDataFinal[] =   $allProduct;
                    //         }
                    //     }

                    //     $data['allProducts']  = $allProductDataFinal;

                    if ($roWc >9) {
                        $data['pageCount'] = ceil(@$roWc /10);
                     } else {
                         $data['pageCount'] = 1;
                     }

                    $data['message'] = 'success';
                    $data['status'] = 1;
                } else {
                    if (isset($request->customer_id) && $Cdata = Trn_store_customer::find($request->customer_id)) {
                        $store_id = $request->store_id;
                        // insert/update row to RECENTLY_VISITED_STORE table :)
                        // $recVisStrRowCount = Trn_RecentlyVisitedStore::where('customer_id',$request->customer_id)->where('store_id',$store_id)->count();
                        // if($recVisStrRowCount < 1)
                        // {
                        //Trn_RecentlyVisitedStore::where('customer_id',$request->customer_id)->where('store_id',$request->store_id)->delete();

                        $rvs = new Trn_RecentlyVisitedStore;
                        $rvs->customer_id = $request->customer_id;
                        $rvs->store_id = $request->store_id;
                        $rvs->visit_count = 1;
                        $rvs->save();
                        // }
                        // else
                        // {
                        //     $rvs = Trn_RecentlyVisitedStore::where('customer_id',$request->customer_id)->where('store_id',$store_id)->first();
                        //     $rvs->visit_count = $rvs->visit_count + 1;
                        //     $rvs->update();

                        // }


                        $data['storeInfo'] = Mst_store::find($store_id);

                        if (isset($data['storeInfo']->store_district_id))
                            $data['storeInfo']->district_name = District::find($data['storeInfo']->store_district_id)->district_name;
                        else
                            $data['storeInfo']->district_name = '';

                        if (!isset($data['storeInfo']->store_referral_id ))
                        {
                            $data['storeInfo']->store_referral_id=$data['storeInfo']->store_id;

                        }
                        $config=Trn_configure_points::where('store_id',$request->store_id)->first(); 
                        if($config)
                        {
                            $data['storeInfo']->referal_points=$config->referal_points;
                            $data['storeInfo']->joiner_points=$config->joiner_points;
                            $data['storeInfo']->first_order_points=$config->first_order_points;
                            $data['storeInfo']->rupee=$config->rupee;
                            $data['storeInfo']->rupee_points=$config->rupee_points;
                            $data['storeInfo']->order_amount=$config->order_amount;
                            $data['storeInfo']->order_points=$config->order_points;
                            $data['storeInfo']->redeem_percentage=$config->redeem_percentage;
                            $data['storeInfo']->max_redeem_amount=$config->max_redeem_amount;
                        
                        }
                        else
                        {
                            $data['storeInfo']->referal_points=0;
                            $data['storeInfo']->joiner_points=0;
                            $data['storeInfo']->first_order_points=0;
                            $data['storeInfo']->rupee=0;
                            $data['storeInfo']->rupee_points=0;
                            $data['storeInfo']->order_amount=0;
                            $data['storeInfo']->order_points=0;
                            $data['storeInfo']->redeem_percentage=0;
                            $data['storeInfo']->max_redeem_amount=0;

                        }
                       


                        $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();


                        if (isset($cusAddData->latitude) && ($cusAddData->longitude)) {

                            if (isset($cusAddData)) {
                                $cusAddDataLat =  $cusAddData->latitude;
                                $cusAddDataLog =  $cusAddData->longitude;
                            } else {
                                $cusAddDataLat =  $Cdata->latitude;
                                $cusAddDataLog =  $Cdata->longitude;
                            }

                            $latitude = $cusAddDataLat;
                            $longitude = $cusAddDataLog;
                        } 
                        else {
                            $latitude = $request->latitude;
                            $longitude = $request->longitude;
                        }

                        if (isset($latitude) && ($longitude)) {

                            $sa  = $data['storeInfo']->service_area;
                            if (!isset($sa))
                                $sa = 0;

                            $storesData          =       DB::table("mst_stores")->join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id');
                            $storesData         = $storesData->where('trn__store_admins.role_id', 0);
                            $storesData         = $storesData->where('mst_stores.online_status', 1);
                            $storesData         = $storesData->where('mst_stores.store_id', $store_id);
                            $storesData         = $storesData->where('trn__store_admins.store_account_status', 1);
                            $storesData          =       $storesData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                                    + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                            $storesData          =       $storesData->having('distance', '<', $sa);
                            $storesData          =       $storesData->orderBy('distance', 'asc');
                            $storesData =       $storesData->get()->toArray();

                            $sCount = count($storesData);
                        }

                        if ($sCount > 0) {
                            $data['storeAvailabilityStatus'] = 1;
                        } else {
                            $data['storeAvailabilityStatus'] = 0;
                        }




                        $data['sliderImages'] =  Mst_store_images::where('store_id', $store_id)->get();
                        foreach ($data['sliderImages'] as $img) {
                            if (isset($img->store_image)) {
                                $img->store_image =  '/assets/uploads/store_images/images/' . $img->store_image;
                            } else {
                                $img->store_image =  null;
                            }
                        }

                        $storeProductData = Mst_store_product::select('product_cat_id')->where('store_id', '=', $store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                        $data['categoriesList'] = Mst_categories::whereIn('category_id', $storeProductData)->where('category_status', 1)->get();
                        foreach ($data['categoriesList'] as $cat) {
                            $cat->category_icon = '/assets/uploads/category/icons/' . $cat->category_icon;
                        }
                       $data['storeBrandList']= Helper::getProductBrandsByStore($store_id);



                        $data['offerProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                            // ->select('mst_stores.business_type_id', 'mst_store_products.product_id', 'mst_store_products.product_type', 'mst_store_products.service_type', 'mst_store_products.product_name', 'mst_store_products.product_code', 'mst_store_products.product_base_image', 'mst_store_products.show_in_home_screen', 'mst_store_products.product_status', 'mst_store_product_varients.product_varient_id', 'mst_store_product_varients.variant_name', 'mst_store_product_varients.product_varient_price', 'mst_store_product_varients.product_varient_offer_price', 'mst_store_product_varients.product_varient_base_image', 'mst_store_product_varients.stock_count', 'mst_store_product_varients.store_id')
                            ->where('mst_store_products.display_flag', 1)
                            ->where('mst_store_products.store_id', $store_id)
                            //->where('mst_store_product_varients.stock_count', '>', 0)
                            //->orWhere('mst_store_products.product_type',2)
                            ->where('mst_store_product_varients.is_removed', 0)
                            ->where('mst_store_products.is_removed', 0)
                            ->where('mst_store_product_varients.is_base_variant', 1)
                            ->where('mst_store_products.show_in_home_screen', 1)->get();

                        foreach ($data['offerProducts'] as $offerProduct) {
                            $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                            $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                            $storeData = Mst_store::find($offerProduct->store_id);
                            $offerProduct->store_name = $storeData->store_name;
                            //    $offerProduct->rating = number_format((float)4.20, 1, '.', '');
                            //    $offerProduct->ratingCount = 120;

                            $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                            $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                            if ($countRating == 0) {
                                $ratingData = $sumRating / 1;
                            } else {
                                $ratingData = $sumRating / $countRating;
                            }

                            $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                            $offerProduct->ratingCount = $countRating;
                        }



                        // $productData = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        //     ->select(
                        //         'mst_store_products.product_id',
                        //         'mst_store_products.product_type',
                        //         'mst_store_products.service_type',
                        //         'mst_store_products.product_name',
                        //         'mst_store_products.product_code',
                        //         'mst_store_products.product_base_image',
                        //         'mst_store_products.show_in_home_screen',
                        //         'mst_store_products.product_status',
                        //         'mst_store_products.store_id'
                        //     );

                        // if (isset($latitude) && ($longitude)) {
                        //     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                        //                     * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                        //                     + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                        //     $productData = $productData->orderBy('distance');
                        // }
                        // $productData = $productData->where('mst_store_products.product_status', 1)
                        //     ->where('mst_store_products.store_id', $store_id)
                        //     ->where('mst_store_products.show_in_home_screen', 1)->get();
                        // $productDataFinal = array();
                        // $stockCount = 0;
                        // foreach ($productData as $offerProduct) {

                        //     if (Helper::productStock($offerProduct->product_id) > 0) {
                        //         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                        //         $storeData = Mst_store::find($offerProduct->store_id);
                        //         $offerProduct->store_name = $storeData->store_name;
                        //         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
                        //         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
                        //         $offerProduct->productStock = Helper::productStock($offerProduct->product_id);

                        //         $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                        //         $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                        //         $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);

                        //         $productDataFinal[] =   $offerProduct;
                        //     }
                        // }
                        // $data['offerProducts']  =    $productDataFinal;

                        $recentlyVisitedProducts  = Trn_RecentlyVisitedProducts::join('mst_store_products', 'mst_store_products.product_id', '=', 'trn__recently_visited_products.product_id')
                            ->join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'trn__recently_visited_products.store_id')

                            ->where('trn__recently_visited_products.customer_id', $request->customer_id)
                            ->where('mst_store_products.store_id', $store_id)

                            ->where('mst_store_products.display_flag', 1)
                            ->where('mst_store_product_varients.is_removed', 0)
                            ->where('mst_store_products.is_removed', 0)
                            ->where('mst_store_product_varients.is_base_variant', 1)

                            // ->groupBy('trn__recently_visited_products.product_id')
                            ->orderBy('trn__recently_visited_products.rvp_id', 'DESC')
                            ->orderBy('trn__recently_visited_products.created_at', 'DESC')
                            ->limit(1)
                            ->get();
                        $recentlyVisited = collect($recentlyVisitedProducts);
                        $recentlyVisitedS = $recentlyVisited->unique('product_id');
                        $dataReViStorePros =   $recentlyVisitedS->values()->all();

                        $recentlyVisitedProductsArr = array();

                        foreach ($dataReViStorePros as $rvProduct) {
                            if (Helper::productStock($rvProduct->product_id) > 0) {
                                $rvProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $rvProduct->product_base_image;
                                $rvpstoreData = Mst_store::find($rvProduct->store_id);
                                $rvProduct->store_name = $rvpstoreData->store_name;
                                $rvProduct->rating = Helper::productRating($rvProduct->product_id);
                                $rvProduct->ratingCount = Helper::productRatingCount($rvProduct->product_id);
                                $rvProduct->productStock = Helper::productStock($rvProduct->product_id);

                                $rvProduct->variantCount = Helper::variantCount($rvProduct->product_id);
                                $rvProduct->isBaseVariant = Helper::isBaseVariant($rvProduct->product_id);
                                $rvProduct->attrCount = Helper::attrCount($rvProduct->product_id);

                                $recentlyVisitedProductsArr[] = $rvProduct;
                            }
                        }

                        $data['recentlyVisitedProducts'] = $recentlyVisitedProductsArr;
                       //Mst_store_product_varient::where()

                        $allProducts  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                            // ->select('mst_stores.business_type_id', 'mst_store_products.product_id', 'mst_store_products.product_type', 'mst_store_products.service_type', 'mst_store_products.product_name', 'mst_store_products.product_code', 'mst_store_products.product_base_image', 'mst_store_products.show_in_home_screen', 'mst_store_products.product_status', 'mst_store_product_varients.product_varient_id', 'mst_store_product_varients.variant_name', 'mst_store_product_varients.product_varient_price', 'mst_store_product_varients.product_varient_offer_price', 'mst_store_product_varients.product_varient_base_image', 'mst_store_product_varients.stock_count', 'mst_store_product_varients.store_id')
                            ->where('mst_store_products.display_flag', 1)
                            ->where('mst_store_products.store_id', $store_id)
                            //->where('mst_store_product_varients.stock_count', '>', 0)
                            //->selectRaw('count(mst_store_product_varients.*) as varCount')
                            ->where('mst_store_product_varients.is_removed', 0)
                            ->where('mst_store_products.is_removed', 0)
                            ->where('mst_store_product_varients.is_base_variant', 1)
                            ->get();

                        foreach ($allProducts as $allProduct) {
                            $allProduct->variant_stock_count=Mst_store_product_varient::where('product_id',$allProduct->product_id)->where('is_removed',0)->where('stock_count','>',0)->sum('stock_count');
                            $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                            $allProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_varient_base_image;
                            $storeData = Mst_store::find($allProduct->store_id);
                            $allProduct->store_name = $storeData->store_name;

                            $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                            $countRating = Trn_ReviewsAndRating::where('product_varient_id', $allProduct->product_varient_id)->where('isVisible', 1)->count();
                            $in_cart=Trn_Cart::where('customer_id',$request->customer_id)->where('product_varient_id',$allProduct->product_varient_id)->where('remove_status',0)->first();
                            if($in_cart)
                            {
                                $cartCount=$in_cart->quantity;
                                $cartId=$in_cart->cart_id;


                            }
                            else
                            {
                                $cartCount=0;
                                $cartId=0;

                            }
                            if ($countRating == 0) {
                                $ratingData = $sumRating / 1;
                            } else {
                                $ratingData = $sumRating / $countRating;
                            }

                            $allProduct->rating = number_format((float)$ratingData, 2, '.', '');
                            $allProduct->ratingCount = $countRating;

                            $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                            $allProduct->attrCount = Helper::varAttrCount($allProduct->product_varient_id);
                            $allProduct->cartCount=(int)$cartCount;
                            $allProduct->cartId=(int)$cartId;
                        }
                        $brand_name=$request->product_brand;
                        if(isset($brand_name))
                        {
                            $allProducts=$allProducts->where('product_brand',$brand_name);

                        }

                        $data['allProducts']  = $allProducts->where('variant_stock_count','>',0);
                        //$decodedData = json_decode($data['allProducts'], true);
                        //$data['allProducts']  = $allProducts->where('variant_stock_count','>',0);

                        $perPage = 10;
                        $page=$request->page??1;
                        $offset = ($page - 1) * $perPage;
                        $roWc=count(collect($data['allProducts'] )->values());
                        $data['allProductCount']=$roWc;
                        $products = collect($data['allProducts'] )->slice($offset, $perPage)->values();
                        $data['allProducts']=$products;
                    if ($roWc >9) {
                        $data['pageCount'] = ceil(@$roWc /10);
                     } else {
                         $data['pageCount'] = 1;
                     }
                        //$data['allProducts']=json_decode()
                        // $data['allProducts']=collect($data['allProducts'])->map(function ($item) {
                        //     return (array) $item;
                        // })->toJson();
                        //$allProducts = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                        // $allProducts = $allProducts->where('mst_store_products.product_status', 1)
                        //     ->where('mst_store_products.store_id', $store_id)
                        //     ->get();

                        // $allProductDataFinal = array();

                        // foreach ($allProducts as $allProduct) {
                        //     if (Helper::productStock($allProduct->product_id) > 0) {
                        //         $allProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $allProduct->product_base_image;
                        //         $storeData = Mst_store::find($allProduct->store_id);
                        //         $allProduct->store_name = $storeData->store_name;
                        //         $allProduct->rating = Helper::productRating($allProduct->product_id);
                        //         $allProduct->ratingCount = Helper::productRatingCount($allProduct->product_id);
                        //         $allProduct->varAttrStatus =  Helper::varAttrStatus($allProduct->product_id);
                        //         $allProduct->product_varient_id =  Helper::findServiceVariant($allProduct->product_id);
                        //         $allProduct->productStock = Helper::productStock($allProduct->product_id);

                        //         $allProduct->variantCount = Helper::variantCount($allProduct->product_id);
                        //         $allProduct->isBaseVariant = Helper::isBaseVariant($allProduct->product_id);
                        //         $allProduct->attrCount = Helper::attrCount($allProduct->product_id);

                        //         $allProductDataFinal[] =   $allProduct;
                        //     }
                        // }

                        // $data['allProducts']  = $allProductDataFinal;


                        $PurproductData = Trn_store_order_item::select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_type',
                            'mst_store_products.service_type',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.product_base_image',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_status',
                            'mst_store_products.is_timeslot_based_product',
                            'mst_store_products.timeslot_start_time',
                            'mst_store_products.timeslot_end_time',
                            'trn_order_items.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_stores.store_name',
                        )
                            ->join('mst_store_products', 'mst_store_products.product_id', '=', 'trn_order_items.product_id')
                            ->join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'trn_order_items.product_id')
                            ->join('mst_stores', 'mst_stores.store_id', '=', 'trn_order_items.store_id')
                            ->join('trn_store_orders', 'trn_store_orders.order_id', '=', 'trn_order_items.order_id');

                        $PurproductData = $PurproductData->where('trn_store_orders.customer_id', $request->customer_id)
                            ->where('mst_store_products.store_id', $request->store_id)
                            ->where('trn_store_orders.store_id', $request->store_id)
                            ->orderBy('trn_order_items.order_item_id', 'DESC')
                            ->limit(1);


                        $PurproductData = $PurproductData->get();
                        foreach($PurproductData as $p)
                        {
                           $pvar=Mst_store_product_varient::where('product_varient_id',$p->product_varient_id)->first();
                           $pvar_base=Mst_store_product_varient::where('product_id',$p->product_id)->where('is_base_variant',1)->first();
                           $p->product_varient_id=strval($pvar_base->product_varient_id);
                           $p->variant_name=$p->product_name;
                           $p->product_varient_base_image=$p->product_base_image;
                        }

                        $PurproductData = collect($PurproductData);
                        $PurproductDatas = $PurproductData->unique('product_varient_id');
                        $PurproductDataz =  $PurproductDatas->values()->all();

                        $data['purchasedProducts'] = $PurproductDataz;
                        // $dataPurchase = array();
                        foreach ($data['purchasedProducts'] as $offerProduct) {

                            // if($offerProduct->product_varient_id )
                            $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                            $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;

                            $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                            $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();
                            if ($countRating == 0) {
                                $ratingData = $sumRating / 1;
                            } else {
                                $ratingData = $sumRating / $countRating;
                            }
                            $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                            $offerProduct->ratingCount = $countRating;
                            $offerProduct->productStock = Helper::productStock($offerProduct->product_id);

                            $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                            $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                            $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);

                            //  $dataPurchase[] = $offerProduct;
                        }
                        //   $data['purchasedProducts'] = $dataPurchase;

                        $data['message'] = 'success';
                        $data['status'] = 1;
                    } else {
                        $data['message'] = 'Customer not found';
                        $data['status'] = 0;
                    }
                }
            } else {
                $data['message'] = 'Store not found';
                $data['status'] = 0;
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function homePage(Request $request)
    {
        $data = array();
        $expiredStores=array();
        $expiredStoresOthers=array();
        $today = Carbon::now()->toDateString();
        try {
            if ($request->customer_id == 0) {   // if the customer is not logged in 
                $data['sliderImages'] =  Mst_CustomerAppBanner::select('banner_id', 'image', 'town_id', 'status', 'default_status')->where('default_status', 1)->where('store_id', 0)->where('status', 1)->get();

                foreach ($data['sliderImages'] as $img) {
                    $img->image = '/assets/uploads/customer_banner/' . $img->image;
                }
                $data['BusinessTypes'] = Mst_business_types::select('business_type_id', 'business_type_name', 'business_type_icon', 'business_type_status')->where('business_type_status', 1)->orderBy('business_type_name', 'ASC')->get();
                foreach ($data['BusinessTypes'] as $BT) {
                    $BT->business_type_icon = '/assets/uploads/business_type/icons/' . $BT->business_type_icon;
                }

                $latitude = $request->latitude;
                $longitude = $request->longitude;

                $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                    ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id');

                if (isset($latitude) && ($longitude)) {
                    $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                    + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                    $productData = $productData->orderBy('distance');
                }
                $productData = $productData->where('mst_store_products.display_flag', 1)
                    ->where('mst_store_product_varients.stock_count', '>', 0)
                    ->where('mst_store_product_varients.is_removed', 0)
                    ->where('mst_store_products.is_removed', 0)
                    ->where('mst_store_product_varients.is_base_variant', 1)
                    ->where('mst_store_product_varients.variant_status', 1)
                    ->where('mst_store_products.show_in_home_screen', 1)->get();
                $productDataFinal = array();
                foreach ($productData as $offerProduct) {
                    $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                    $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                    $storeData = Mst_store::find($offerProduct->store_id);
                    $offerProduct->store_name = $storeData->store_name;
                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->sum('rating');
                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->count();
                    if ($countRating == 0) {
                        $countRating = 1;
                    }
                    $ratingData = $sumRating / $countRating;
                    $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                    $offerProduct->ratingCount = $countRating;
                    $productDataFinal[] =   $offerProduct;
                }
                $data['offerProducts']  =    $productDataFinal;

                // $productData = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                //     ->select(
                //         'mst_store_products.product_id',
                //         'mst_store_products.product_type',
                //         'mst_store_products.service_type',
                //         'mst_store_products.product_name',
                //         'mst_store_products.product_code',
                //         'mst_store_products.product_base_image',
                //         'mst_store_products.show_in_home_screen',
                //         'mst_store_products.product_status',
                //         'mst_store_products.store_id'
                //     );
                // if (isset($latitude) && ($longitude)) {
                //     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                //                     * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                //                     + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                //     $productData = $productData->orderBy('distance');
                // }
                // $productData = $productData->where('mst_store_products.product_status', 1)
                //     ->where('mst_store_products.show_in_home_screen', 1)->get();
                // $productDataFinal = array();
                // $stockCount = 0;
                // foreach ($productData as $offerProduct) {

                //     if (Helper::productStock($offerProduct->product_id) > 0) {
                //         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                //         $storeData = Mst_store::find($offerProduct->store_id);
                //         $offerProduct->store_name = $storeData->store_name;
                //         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
                //         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
                //         $offerProduct->productStock = Helper::productStock($offerProduct->product_id);

                //         $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                //         $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                //         $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);

                //         $productDataFinal[] =   $offerProduct;
                //     }
                // }
                // $data['offerProducts']  =    $productDataFinal;


                $data['recentlyVisitedStores'] = [];
                $nearStoreArray[] = 0;



                if (isset($latitude) && isset($longitude)) {
                    $stores          =       DB::table("mst_stores")->join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id');
                    $stores         = $stores->where('trn__store_admins.role_id', 0);
                    $stores         = $stores->where('mst_stores.online_status', 1);
                    $stores         = $stores->where('trn__store_admins.store_account_status', 1);
                    $stores          =       $stores->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                                * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                                + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                    $stores          =       $stores->having('distance', '<', 10);
                    $stores          =       $stores->orderBy('distance', 'asc');
                    //$listedStores    = $stores->get();
                    $listedStores =Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                    ->where('trn__store_admins.role_id', 0)
                    ->where('mst_stores.online_status', 1)
                    ->where('trn__store_admins.store_account_status', 1)
                    ->orderBy('mst_stores.store_id', 'DESC')->get();
                    foreach($listedStores as $store)
                    {
                        $getParentExpiry = Trn_StoreAdmin::where('store_id','=',$store->store_id)->where('role_id','=',0)->first();
                        if($getParentExpiry)
                        {
                            $parentExpiryDate = $getParentExpiry->expiry_date;
                            if($today>=$parentExpiryDate)
                            {
                                array_push($expiredStores,$store->store_id);
                            }
                        
                        }
                    }
                    $nearByStoreData         =       $stores->whereNotIn('mst_stores.store_id',$expiredStores)->get();


                    $nearStoreArray[] = 0;
                    $nearByStoreFinal = array();

                    foreach ($nearByStoreData as $nearByStore) {

                        $timeslotdata = Helper::findHoliday($nearByStore->store_id);

                        if ($timeslotdata == true) {


                            $nearStoreArray[] = $nearByStore->store_id;
                            if (isset($nearByStore->profile_image)) {
                                $nearByStore->store_image =  '/assets/uploads/store_images/images/' . $nearByStore->profile_image;
                            } else {
                                $nearByStore->store_image =  Helper::default_store_image();
                            }

                            if (isset($nearByStore->store_district_id))
                                $nearByStore->district_name = District::find($nearByStore->store_district_id)->district_name;
                            else
                                $nearByStore->district_name = '';

                            $storeProductData2 = Mst_store_product::select('product_cat_id')->where('store_id', '=', $nearByStore->store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                            $catData2 = Mst_categories::whereIn('category_id', $storeProductData2)->where('category_status', 1)->get()->pluck('category_name')->toArray();
                            $catString2 = implode(', ', @$catData2);
                            if (isset($catString2))
                                $string2 = @$catString2;
                            else
                                $string2 = null;


                            // $string2 = substr(@$catString2, 0, 27);

                            $nearByStore->categories =  @$string2;


                            //   $nearByStore->rating = number_format((float)4.20, 1, '.', '');
                            // $nearByStore->ratingCount = 120;

                            $nearByStore->rating = Helper::storeRating($nearByStore->store_id);
                            $nearByStore->ratingCount = Helper::storeRatingCount($nearByStore->store_id);

                            $nearByStoreFinal[] = $nearByStore;
                        }
                    }


                    $data['nearByStores'] = $nearByStoreFinal;

                } else {
                    $data['nearByStores'] = [];
                }



                // other stores

                $otherStoresData = Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')

                    ->where('trn__store_admins.role_id', 0)->where('mst_stores.online_status', 1)
                    ->where('trn__store_admins.store_account_status', 1)
                    ->whereNotIn('mst_stores.store_id', $nearStoreArray)
                    ->orderBy('mst_stores.store_id', 'ASC');
                //$listedStoresOthers=$otherStoresData->get();
                $listedStoresOthers=Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                    ->where('trn__store_admins.role_id', 0)
                    ->where('mst_stores.online_status', 1)
                    ->where('trn__store_admins.store_account_status', 1)
                    ->orderBy('mst_stores.store_id', 'DESC')->get();
                    foreach($listedStoresOthers as $store)
                    {
                        $getParentExpiry = Trn_StoreAdmin::where('store_id','=',$store->store_id)->where('role_id','=',0)->first();
                        if($getParentExpiry)
                        {
                            $parentExpiryDate = $getParentExpiry->expiry_date;
                            if($today>=$parentExpiryDate)
                            {
                                array_push($expiredStoresOthers,$store->store_id);
                            }
                        
                        }
                    }
                    //return $otherStoresData->get();
                
                $otherStoresData=$otherStoresData->whereNotIn('mst_stores.store_id',$expiredStoresOthers)->get();
                //return $otherStoresData;
                $otherStoresFinal = array();
                foreach ($otherStoresData as $otherStores) {

                    $timeslotdata = Helper::findHoliday($otherStores->store_id);

                    if ($timeslotdata == true) {


                        if (isset($otherStores->profile_image)) {
                            $otherStores->store_image =  '/assets/uploads/store_images/images/' . $otherStores->profile_image;
                        } else {
                            $otherStores->store_image =  Helper::default_store_image();
                        }

                        if (isset($otherStores->store_district_id))
                            $otherStores->district_name = District::find($otherStores->store_district_id)->district_name;
                        else
                            $otherStores->district_name = '';

                        $storeProductData2 = Mst_store_product::select('product_cat_id')->where('store_id', '=', $otherStores->store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                        $catData2 = Mst_categories::whereIn('category_id', $storeProductData2)->where('category_status', 1)->get()->pluck('category_name')->toArray();
                        $catString2 = implode(', ', @$catData2);
                        if (isset($catString2))
                            $string2 = @$catString2;
                        else
                            $string2 = null;

                        $otherStores->categories =  @$string2;


                        // $otherStores->rating = number_format((float)4.20, 1, '.', '');
                        // $otherStores->ratingCount = 120;

                        $otherStores->rating = Helper::storeRating($otherStores->store_id);
                        $otherStores->ratingCount = Helper::storeRatingCount($otherStores->store_id);

                        $otherStoresFinal[] = $otherStores;
                       
                    }
                }
                $data['CurrentCartCount'] = 0;
                $data['otherStores']  = $otherStoresFinal;
                //return $otherStoresFinal;
                $data['purchasedProducts']  = [];

                $data['message'] = 'success';
                $data['status'] = 1;
            } else {
                if (isset($request->customer_id) &&  $cusDataz = Trn_store_customer::find($request->customer_id)) {

                    $sliderImages =  Mst_CustomerAppBanner::select('banner_id', 'image', 'town_id', 'status', 'default_status');


                    $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)
                        ->where('default_status', 1)->first();
                    $pinCode = 0;
                    if (isset($cusAddData->town_id)) {
                        $pinCode = $cusAddData->town_id;
                    } elseif (isset($cusDataz->town_id)) {
                        $pinCode = $cusDataz->town_id;
                    } else {
                        $pinCode = 0;
                    }
                    // dd($pinCode);
                    $sliderImages = $sliderImages->where('town_id', $pinCode)->orWhere('town_id', null)->where('status', 1)->get();

                    //dd($sliderImages);

                    foreach ($sliderImages as $img) {
                        $img->image = '/assets/uploads/customer_banner/' . @$img->image;
                    }
                    $data['sliderImages'] =  $sliderImages;

                    $data['BusinessTypes'] = Mst_business_types::select('business_type_id', 'business_type_name', 'business_type_icon', 'business_type_status')->where('business_type_status', 1)->orderBy('business_type_name', 'ASC')->get();
                    foreach ($data['BusinessTypes'] as $BT) {
                        $BT->business_type_icon = '/assets/uploads/business_type/icons/' . $BT->business_type_icon;
                    }

                    $productData = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_type',
                            'mst_store_products.service_type',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.product_base_image',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_status',
                            'mst_store_products.display_flag',
                            'mst_store_products.is_timeslot_based_product',
                            'mst_store_products.timeslot_start_time',
                            'mst_store_products.timeslot_end_time',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_store_product_varients.store_id'
                        );

                    $productData = $productData->where('mst_store_products.display_flag', 1)
                        ->where('mst_store_product_varients.stock_count', '>', 0)
                        //  ->orWhere('mst_store_products.product_type',2)

                        ->where('mst_store_products.show_in_home_screen', 1)

                        ->where('mst_store_product_varients.is_removed', 0)
                        ->where('mst_store_product_varients.variant_status', 1)
                        ->where('mst_store_products.is_removed', 0)
                        ->where('mst_store_product_varients.is_base_variant', 1);

                    if ((isset($request->customer_id)) && ($request->customer_id != 0)) {
                        // near by store
                        // dd($cusData);

                        if (isset($request->latitude) && ($request->longitude)) {
                            $latitude = $request->latitude;
                            $longitude = $request->longitude;
                        } else {
                            $cusData = Trn_store_customer::select('latitude', 'longitude')->where('customer_id', '=', $request->customer_id)->first();
                            $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();
                            if (isset($cusAddData)) {
                                $cusAddDataLat =  $cusAddData->latitude;
                                $cusAddDataLog =  $cusAddData->longitude;
                            } else {
                                $cusAddDataLat =  $cusData->latitude;
                                $cusAddDataLog =  $cusData->longitude;
                            }

                            $latitude = $cusAddDataLat;
                            $longitude = $cusAddDataLog;
                        }

                        $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                    * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                    + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                        $productData = $productData->orderBy('distance');
                    }

                    $productData = $productData->get();

                    $productDataFinal = array();

                    foreach ($productData as $offerProduct) {

                        $timeslotdata = Helper::findHoliday($offerProduct->store_id);

                        if ($timeslotdata == true) {

                            $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                            $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                            $storeData = Mst_store::find($offerProduct->store_id);
                            $offerProduct->store_name = $storeData->store_name;
                            //$offerProduct->rating = number_format((float)4.20, 1, '.', '');
                            //$offerProduct->ratingCount = 120;

                            $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->sum('rating');
                            $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->count();

                            if ($countRating == 0) {
                                $countRating = 1;
                            }

                            $ratingData = $sumRating / $countRating;

                            $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                            $offerProduct->ratingCount = $countRating;
                            $productDataFinal[] = $offerProduct;
                        }
                    }
                    $data['offerProducts']  = $productDataFinal;



                    // $productData = Mst_store_product::join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                    //     ->select(
                    //         'mst_store_products.product_id',
                    //         'mst_store_products.product_type',
                    //         'mst_store_products.service_type',
                    //         'mst_store_products.product_name',
                    //         'mst_store_products.product_code',
                    //         'mst_store_products.product_base_image',
                    //         'mst_store_products.show_in_home_screen',
                    //         'mst_store_products.product_status',
                    //         'mst_store_products.store_id'
                    //     );


                    // if ((isset($request->customer_id)) && ($request->customer_id != 0)) {
                    //     // near by store
                    //     if (isset($request->latitude) && ($request->longitude)) {
                    //         $latitude = $request->latitude;
                    //         $longitude = $request->longitude;
                    //     } else {
                    //         $cusData = Trn_store_customer::select('latitude', 'longitude')->where('customer_id', '=', $request->customer_id)->first();
                    //         $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();
                    //         if (isset($cusAddData)) {
                    //             $cusAddDataLat =  $cusAddData->latitude;
                    //             $cusAddDataLog =  $cusAddData->longitude;
                    //         } else {
                    //             $cusAddDataLat =  $cusData->latitude;
                    //             $cusAddDataLog =  $cusData->longitude;
                    //         }
                    //         $latitude = $cusAddDataLat;
                    //         $longitude = $cusAddDataLog;
                    //     }
                    //     $productData = $productData->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                    //                 * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                    //                 + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                    //     $productData = $productData->orderBy('distance');
                    // }

                    // $productData = $productData->where('mst_store_products.product_status', 1)
                    //     ->where('mst_store_products.show_in_home_screen', 1)->get();
                    // $productDataFinal = array();
                    // $stockCount = 0;
                    // foreach ($productData as $offerProduct) {
                    //     if (Helper::productStock($offerProduct->product_id) > 0) {
                    //         $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                    //         $storeData = Mst_store::find($offerProduct->store_id);
                    //         $offerProduct->store_name = $storeData->store_name;
                    //         $offerProduct->rating = Helper::productRating($offerProduct->product_id);
                    //         $offerProduct->ratingCount = Helper::productRatingCount($offerProduct->product_id);
                    //         $offerProduct->productStock = Helper::productStock($offerProduct->product_id);

                    //         $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                    //         $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                    //         $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);
                    //         $productDataFinal[] =   $offerProduct;
                    //     }
                    // }
                    // $data['offerProducts']  =    $productDataFinal;



                    //  Trn_RecentlyVisitedStore::where('customer_id',$request->customer_id)->where('store_id',$request->store_id)->delete();
                    $listedStores= Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                    ->where('trn__store_admins.role_id', 0)
                    ->where('mst_stores.online_status', 1)
                    ->where('trn__store_admins.store_account_status', 1)
                    ->orderBy('mst_stores.store_id', 'DESC')->get();
                    foreach($listedStores as $store)
                    {
                        $getParentExpiry = Trn_StoreAdmin::where('store_id','=',$store->store_id)->where('role_id','=',0)->first();
                        if($getParentExpiry)
                        {
                            $parentExpiryDate = $getParentExpiry->expiry_date;
                            if($today>=$parentExpiryDate)
                            {
                                array_push($expiredStores,$store->store_id);
                            }
                        
                        }
                    }
                    $recentlyVisited = Trn_RecentlyVisitedStore::select('*')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'trn__recently_visited_stores.store_id')
                        ->join('trn__store_admins', 'trn__store_admins.store_id', '=', 'trn__recently_visited_stores.store_id')
                        ->where('trn__store_admins.role_id', 0)->where('mst_stores.online_status', 1)
                        ->where('trn__store_admins.store_account_status', 1)
                        ->where('trn__recently_visited_stores.customer_id', $request->customer_id)
                        ->whereNotIn('mst_stores.store_id',$expiredStores)
                        ->orderBy('trn__recently_visited_stores.rvs_id', 'DESC')
                        //->groupBy('trn__recently_visited_stores.store_id')
                        ->get();

                    //dd($data['recentlyVisitedStores']);

                    $recentlyVisited = collect($recentlyVisited);
                    $recentlyVisitedS = $recentlyVisited->unique('store_id');
                    $dataReViStore =   $recentlyVisitedS->values()->all();

                    // dd($recentlyVisitedS);

                    $recentlyVisitedStoreF = array();


                    foreach ($dataReViStore as $recentlyVisitedStore) {

                        $timeslotdata = Helper::findHoliday($recentlyVisitedStore->store_id);

                        if ($timeslotdata == true) {

                            if (isset($recentlyVisitedStore->profile_image)) {
                                $recentlyVisitedStore->store_image =  '/assets/uploads/store_images/images/' . $recentlyVisitedStore->profile_image;
                            } else {
                                $recentlyVisitedStore->store_image =  Helper::default_store_image();
                            }

                            if (isset($recentlyVisitedStore->store_district_id))
                                $recentlyVisitedStore->district_name = District::find($recentlyVisitedStore->store_district_id)->district_name;
                            else
                                $recentlyVisitedStore->district_name = '';


                            $storeProductData = Mst_store_product::select('product_cat_id')->where('store_id', '=', $recentlyVisitedStore->store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                            $catData = Mst_categories::whereIn('category_id', $storeProductData)->where('category_status', 1)->get()->pluck('category_name')->toArray();
                            $catString = implode(', ', @$catData);
                            if (isset($catString))
                                $string = @$catString;
                            else
                                $string = null;

                            $recentlyVisitedStore->categories =  @$string;


                            //$recentlyVisitedStore->rating = number_format((float)4.20, 1, '.', '');
                            // $recentlyVisitedStore->ratingCount = 120;

                            $recentlyVisitedStore->rating = Helper::storeRating($recentlyVisitedStore->store_id);
                            $recentlyVisitedStore->ratingCount = Helper::storeRatingCount($recentlyVisitedStore->store_id);

                            $recentlyVisitedStoreF[] = $recentlyVisitedStore;
                        }
                    }


                    $data['recentlyVisitedStores'] = $recentlyVisitedStoreF;




                    if (isset($request->latitude) && ($request->longitude)) {
                        $latitude = $request->latitude;
                        $longitude = $request->longitude;
                    } else {
                        $cusData = Trn_store_customer::find($request->customer_id);
                        $cusAddData = Trn_customerAddress::where('customer_id', '=', $request->customer_id)->where('default_status', 1)->first();

                        if (isset($cusAddData)) {
                            $cusAddDataLat =  $cusAddData->latitude;
                            $cusAddDataLog =  $cusAddData->longitude;
                        } else {
                            $cusAddDataLat =  $cusData->latitude;
                            $cusAddDataLog =  $cusData->longitude;
                        }

                        $latitude = $cusAddDataLat;
                        $longitude = $cusAddDataLog;
                    }




                    if (isset($latitude) && isset($longitude)) {
                        $stores          =       DB::table("mst_stores")->join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id');
                        $stores         = $stores->where('trn__store_admins.role_id', 0);
                        $stores         = $stores->where('mst_stores.online_status', 1);
                        $stores         = $stores->where('trn__store_admins.store_account_status', 1);
                        $stores          =       $stores->select("*", DB::raw("6371 * acos(cos(radians(" . $latitude . "))
                                                * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $longitude . "))
                                                + sin(radians(" . $latitude . ")) * sin(radians(mst_stores.latitude))) AS distance"));
                        //  $stores          =       $stores->having('distance', '<', 20);
                        $stores          =       $stores->orderBy('distance', 'asc');
                        $listedStores =Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                    ->where('trn__store_admins.role_id', 0)
                    ->where('mst_stores.online_status', 1)
                    ->where('trn__store_admins.store_account_status', 1)
                    ->orderBy('mst_stores.store_id', 'DESC')->get();
                    foreach($listedStores as $store)
                    {
                        $getParentExpiry = Trn_StoreAdmin::where('store_id','=',$store->store_id)->where('role_id','=',0)->first();
                        if($getParentExpiry)
                        {
                            $parentExpiryDate = $getParentExpiry->expiry_date;
                            if($today>=$parentExpiryDate)
                            {
                                array_push($expiredStores,$store->store_id);
                            }
                        
                        }
                    }
                        $nearByStoresdata        =       $stores->whereNotIn('mst_stores.store_id',$expiredStores)->get();
                    } else {
                        $listedStores =Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                    ->where('trn__store_admins.role_id', 0)
                    ->where('mst_stores.online_status', 1)
                    ->where('trn__store_admins.store_account_status', 1)
                    ->orderBy('mst_stores.store_id', 'DESC')->get();
                    foreach($listedStores as $store)
                    {
                        $getParentExpiry = Trn_StoreAdmin::where('store_id','=',$store->store_id)->where('role_id','=',0)->first();
                        if($getParentExpiry)
                        {
                            $parentExpiryDate = $getParentExpiry->expiry_date;
                            if($today>=$parentExpiryDate)
                            {
                                array_push($expiredStores,$store->store_id);
                            }
                        
                        }
                    }
                        $nearByStoresdata  = Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                            ->where('trn__store_admins.role_id', 0)->where('mst_stores.online_status', 1)
                            ->where('trn__store_admins.store_account_status', 1)->orderBy('mst_stores.store_id', 'ASC')->whereNotIn('mst_stores.store_id',$expiredStores)->limit(10)->get();
                    }
                    $nearByStoresdataf = array();

                    foreach ($nearByStoresdata as $nearByStore) {

                        $timeslotdata = Helper::findHoliday($nearByStore->store_id);

                        if (isset($nearByStore->store_district_id))
                            $nearByStore->district_name = District::find($nearByStore->store_district_id)->district_name;
                        else
                            $nearByStore->district_name = '';

                        if ($timeslotdata == true) {

                            if (isset($nearByStore->profile_image)) {
                            $nearByStore->store_image =  '/assets/uploads/store_images/images/' . $nearByStore->profile_image;
                            } else {
                                $nearByStore->store_image =  Helper::default_store_image();
                            }

                            $storeProductData1 = Mst_store_product::select('product_cat_id')->where('store_id', '=', $nearByStore->store_id)->orderBy('product_id', 'DESC')->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();
                            $catData1 = Mst_categories::whereIn('category_id', $storeProductData1)->where('category_status', 1)->get()->pluck('category_name')->toArray();
                            $catString1 = implode(', ', @$catData1);
                            if (isset($catString1))
                                $string1 = @$catString1;
                            else
                                $string1 = null;

                            $nearByStore->categories =  @$string1;
                            // $nearByStore->rating = number_format((float)4.20, 1, '.', '');
                            // $nearByStore->ratingCount = 120;

                            $nearByStore->rating = Helper::storeRating($nearByStore->store_id);
                            $nearByStore->ratingCount = Helper::storeRatingCount($nearByStore->store_id);
                            $nearByStoresdataf[] = $nearByStore;
                        }
                    }

                    $data['nearByStores'] = $nearByStoresdataf;





                    // $PurproductData = Mst_store_product::



                    $PurproductData = Trn_store_order_item::select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_name',
                        'mst_store_products.product_type',
                        'mst_store_products.service_type',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.stock_count',
                        'mst_stores.store_name',
                    )
                        ->join('mst_store_products', 'mst_store_products.product_id', '=', 'trn_order_items.product_id')
                        ->join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'trn_order_items.product_id')
                        ->join('mst_stores', 'mst_stores.store_id', '=', 'trn_order_items.store_id')
                        //->join('trn_order_items','trn_order_items.product_id','=','mst_store_products.product_id')
                        ->join('trn_store_orders', 'trn_store_orders.order_id', '=', 'trn_order_items.order_id');



                    $PurproductData = $PurproductData->where('mst_store_products.display_flag', 1)
                        ->where('mst_store_products.is_removed',0)
                        ->where('trn_store_orders.customer_id', $request->customer_id)
                        ->orderBy('trn_order_items.order_item_id', 'DESC');


                    // if((isset($request->customer_id)) && ($request->customer_id != 0))  
                    // {
                    //     // near by store
                    //     $cusData = Trn_store_customer::select('latitude','longitude')->where('customer_id','=',$request->customer_id)->first();
                    //   // dd($cusData);
                    //     $PurproductData = $PurproductData->select("*", DB::raw("6371 * acos(cos(radians(" . $cusData->latitude . "))
                    //                 * cos(radians(mst_stores.latitude)) * cos(radians(mst_stores.longitude) - radians(" . $cusData->longitude . "))
                    //                 + sin(radians(" .$cusData->latitude. ")) * sin(radians(mst_stores.latitude))) AS distance"));
                    //     $PurproductData = $PurproductData->orderBy('distance');
                    // }

                    $PurproductData = $PurproductData->get();

                    $PurproductData = collect($PurproductData);
                    $PurproductDatas = $PurproductData->unique('product_varient_id');
                    $PurproductDataz =   $PurproductDatas->values()->all();


                    $data['purchasedProducts']  = $PurproductDataz;

                    foreach ($data['purchasedProducts'] as $offerProduct) {
                        $offerProduct->product_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_base_image;
                        $offerProduct->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $offerProduct->product_varient_base_image;
                        //  $storeData = Mst_store::find($offerProduct->store_id);
                        //  $offerProduct->store_name = $storeData->store_name;
                        //$offerProduct->rating = number_format((float)4.20, 1, '.', '');
                        //$offerProduct->ratingCount = 120;

                        $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->sum('rating');
                        $countRating = Trn_ReviewsAndRating::where('product_varient_id', $offerProduct->product_varient_id)->where('isVisible', 1)->count();

                        if ($countRating == 0) {
                            $ratingData = $sumRating / 1;
                        } else {
                            $ratingData = $sumRating / $countRating;
                        }

                        $offerProduct->rating = number_format((float)$ratingData, 2, '.', '');
                        $offerProduct->ratingCount = $countRating;
                        $offerProduct->productStock = Helper::productStock($offerProduct->product_id);

                        $offerProduct->variantCount = Helper::variantCount($offerProduct->product_id);
                        $offerProduct->isBaseVariant = Helper::isBaseVariant($offerProduct->product_id);
                        $offerProduct->attrCount = Helper::attrCount($offerProduct->product_id);
                    }


                    if(Trn_Cart::where('customer_id', $request->customer_id)->where('remove_status','=',0)->count() > 0)
                        {
                            $data['CurrentCartCount'] = Trn_Cart::where('customer_id', $request->customer_id)->where('remove_status','=',0)->count();
                        }else{
                            $data['CurrentCartCount'] = 0; 
                        }


                    $data['message'] = 'success';
                    $data['status'] = 1;
                } else {
                    $data['message'] = 'customer not found';
                    $data['status'] = 0;
                }
            }

            return response($data);
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }

    public function listStoreProductCategory(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $store_id = $request->store_id;
                $storeProductData = Mst_store_product::select('product_cat_id')
                    ->where('store_id', '=', $store_id)
                    ->orderBy('product_id', 'DESC')
                    ->get()->unique('product_cat_id')->pluck('product_cat_id')->toArray();

                if ($data['CategoryDetails']  = Mst_categories::select('category_id', 'business_type_id', 'category_description', 'category_icon', 'category_name')->whereIn('category_id', $storeProductData)
                    ->where('category_status', 1)->orderBy('category_id', 'DESC')->get()
                ) {
                    foreach ($data['CategoryDetails'] as $productCategory) {
                        $productCategory->category_icon = '/assets/uploads/category/icons/' . @$productCategory->category_icon;
                        $productCategory->category_description = strip_tags(@$productCategory->category_description);
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


    public function storeData(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $store_id = $request->store_id;

                if ($data['storeDetails']  = Mst_store::select('store_name')->find($request->store_id)) {
                    $data['storeDetails']->appBanners = Mst_CustomerAppBanner::get();

                    foreach ($data['storeDetails']->appBanners as $appBanner) {
                        $appBanner->image = '/assets/uploads/customer_banner/' . @$appBanner->image;
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
            $responzzse = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function offerProducts(Request $request)
    {
        $data = array();
        try {

            if (
                $data['offerProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                ->select(
                    'mst_store_products.product_id',
                    'mst_store_products.product_name',
                    'mst_store_products.product_code',
                    'mst_store_products.product_base_image',
                    'mst_store_products.show_in_home_screen',
                    'mst_store_products.product_status',
                    'mst_store_products.display_flag',
                    'mst_store_products.is_timeslot_based_product',
                    'mst_store_products.timeslot_start_time',
                    'mst_store_products.timeslot_end_time',
                    'mst_store_product_varients.product_varient_id',
                    'mst_store_product_varients.variant_name',
                    'mst_store_product_varients.product_varient_price',
                    'mst_store_product_varients.product_varient_offer_price',
                    'mst_store_product_varients.product_varient_base_image',
                    'mst_store_product_varients.stock_count',
                    'mst_store_product_varients.store_id'
                )
                ->where('mst_store_products.display_flag', 1)
                ->where('mst_store_products.show_in_home_screen', 1)->get()
            ) {

                foreach ($data['storeOfferProducts'] as $product) {
                    $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                    $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
                }
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



    public function storeOfferProducts(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $store_id = $request->store_id;

                if (
                    $data['storeOfferProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                    ->select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.stock_count',
                        'mst_store_product_varients.store_id'
                    )
                    ->where('mst_store_products.store_id', $store_id)
                    ->where('mst_store_products.display_flag', 1)
                    ->where('mst_store_products.show_in_home_screen', 1)->get()
                ) {

                    foreach ($data['storeOfferProducts'] as $product) {
                        $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                        $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
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


    public function storeProducts(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                $store_id = $request->store_id;

                if (
                    $data['storeProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                    ->select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.stock_count',
                        'mst_store_product_varients.store_id'
                    )
                    ->where('mst_store_products.store_id', $store_id)
                    ->where('mst_store_products.display_flag', 1)->get()
                ) {

                    foreach ($data['storeProducts'] as $product) {
                        $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                        $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
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


    public function storeProductsByCat(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store_id) && Mst_store::find($request->store_id)) {
                if (isset($request->category_id) && Mst_categories::find($request->category_id)) {
                    $store_id = $request->store_id;
                    $category_id = $request->category_id;

                    if (
                        $data['storeProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                        ->select(
                            'mst_store_products.product_id',
                            'mst_store_products.product_name',
                            'mst_store_products.product_code',
                            'mst_store_products.product_base_image',
                            'mst_store_products.show_in_home_screen',
                            'mst_store_products.product_status',
                            'mst_store_products.display_flag',
                            'mst_store_products.is_timeslot_based_product',
                            'mst_store_products.timeslot_start_time',
                            'mst_store_products.timeslot_end_time',
                            'mst_store_product_varients.product_varient_id',
                            'mst_store_product_varients.variant_name',
                            'mst_store_product_varients.product_varient_price',
                            'mst_store_product_varients.product_varient_offer_price',
                            'mst_store_product_varients.product_varient_base_image',
                            'mst_store_product_varients.stock_count',
                            'mst_store_product_varients.store_id'
                        )
                        ->where('mst_store_products.store_id', $store_id)
                        ->where('mst_store_products.product_cat_id', $category_id)
                        ->where('mst_store_products.display_flag', 1)->get()
                    ) {

                        foreach ($data['storeProducts'] as $product) {
                            $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                            $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
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
                    $data['status'] = 2;
                    $data['message'] = "Category not found ";
                    return response($data);
                }
            } else {
                $data['status'] = 3;
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



    public function listAllProductCategory(Request $request)
    {
        $data = array();
        try {
            if ($data['productCategoryDetails']  = Mst_categories::select('category_id', 'business_type_id', 'category_description', 'category_icon', 'category_name')
                ->where('category_status', 1)
                ->orderBy('category_id', 'DESC')->get()
            ) {
                foreach ($data['productCategoryDetails'] as $productCategory) {
                    $productCategory->category_icon = '/assets/uploads/category/icons/' . $productCategory->category_icon;
                }
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

    public function mostVisitedProducts(Request $request)
    {
        $data = array();
        try {
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                $customer_id = $request->customer_id;


                $mostVisitedPrdts = Trn_MostVisitedProductsByCustomer::select('product_varient_id')
                    ->where('customer_id', '=', $customer_id)
                    ->orderBy('visit_count', 'DESC')
                    ->limit(2)->pluck('product_varient_id')->toArray();
                //dd($mostVisitedPrdts);


                if (
                    $data['mostVisitedProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                    ->select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.stock_count',
                        'mst_store_product_varients.store_id'
                    )
                    ->whereIn('mst_store_product_varients.product_varient_id', $mostVisitedPrdts)
                    ->where('mst_store_products.display_flag', 1)->get()
                ) {

                    foreach ($data['mostVisitedProducts'] as $product) {
                        $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                        $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
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
                $data['message'] = "Customer not found ";
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


    public function addReview(Request $request)
    {
        $data = array();
        try {
            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'store_id'   => 'required',
                        'product_id'   => 'required',
                        'product_varient_id'   => 'required',
                        'rating'   => 'required',

                    ],
                    [
                        'store_id.required'  => 'Store required',
                        'product_id.required'  => 'Product required',
                        'product_varient_id.required'  => 'Variant required',
                        'rating.required'  => 'Rating required',
                    ]
                );

                if (!$validator->fails()) {
                    $customer_id = $request->customer_id;

                    $review = new Trn_ReviewsAndRating;
                    $review->customer_id = $request->customer_id;
                    $review->store_id = $request->store_id;
                    $review->product_id = $request->product_id;
                    $review->product_varient_id = $request->product_varient_id;
                    $review->rating = $request->rating;
                    $review->review = $request->review;
                    if ($review->save()) {
                        $data['status'] = 1;
                        $data['message'] = "Review added";
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
                $data['message'] = "Customer not found ";
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

    public function OfferProductes(Request $request)
    {
        $data = array();
        try {

            if (
                $data['offerProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                ->select(
                    'mst_store_products.product_id',
                    'mst_store_products.product_name',
                    'mst_store_products.product_code',
                    'mst_store_products.product_base_image',
                    'mst_store_products.show_in_home_screen',
                    'mst_store_products.product_status',
                    'mst_store_products.display_flag',
                    'mst_store_products.is_timeslot_based_product',
                    'mst_store_products.timeslot_start_time',
                    'mst_store_products.timeslot_end_time',
                    'mst_store_product_varients.product_varient_id',
                    'mst_store_product_varients.variant_name',
                    'mst_store_product_varients.product_varient_price',
                    'mst_store_product_varients.product_varient_offer_price',
                    'mst_store_product_varients.product_varient_base_image',
                    'mst_store_product_varients.stock_count',
                    'mst_store_product_varients.store_id'
                )
                ->where('mst_store_products.display_flag', 1)
                ->where('mst_store_products.show_in_home_screen', 1)->get()
            ) {

                foreach ($data['offerProducts'] as $product) {
                    $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                    $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
                }
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



    public function listReview(Request $request)
    {
        $data = array();
        try {
            if (isset($request->product_varient_id) && Mst_store_product_varient::find($request->product_varient_id)) {
                if ($data['Reviews'] = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->get()) {
                    $sumRating = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->sum('rating');
                    $countRating = Trn_ReviewsAndRating::where('product_varient_id', $request->product_varient_id)->where('isVisible', 1)->count();

                    if ($countRating == 0) {
                        $countRating = 1;
                    }

                    $data['Rating'] = $sumRating / $countRating;
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
                $data['message'] = "Product not found ";
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

    public function listStores(Request $request)
    {
        $data = array();
        $expiredStores=array();
        $today = Carbon::now()->toDateString();
        try {
           $listedStores= Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                ->where('trn__store_admins.role_id', 0)
                ->where('mst_stores.online_status', 1)
                ->where('trn__store_admins.store_account_status', 1)
                ->orderBy('mst_stores.store_id', 'DESC')->get();
            foreach($listedStores as $store)
            {
                $getParentExpiry = Trn_StoreAdmin::where('store_id','=',$store->store_id)->where('role_id','=',0)->first();
                if($getParentExpiry)
                {
                    $parentExpiryDate = $getParentExpiry->expiry_date;
                    if($today>=$parentExpiryDate)
                    {
                        array_push($expiredStores,$store->store_id);
                    }
                
                }
            }
            //dd($expiredStores);
          

            if ($data['storeDetails']  = Mst_store::join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                ->where('trn__store_admins.role_id', 0)
                ->where('mst_stores.online_status', 1)
                ->where('trn__store_admins.store_account_status', 1)
                ->whereNotIn('mst_stores.store_id',$expiredStores)
                ->orderBy('mst_stores.store_id', 'DESC')->get()
            ) {
                // foreach($data['productCategoryDetails'] as $productCategory){
                //     $productCategory->category_icon = '/assets/uploads/category/icons/'.$productCategory->category_icon;
                // }
                
               

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


    public function storeProductsByName(Request $request)
    {
        $data = array();
        try {
            if (isset($request->product)) {
                $product = $request->product;

                if (
                    $data['storeProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                    ->select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.stock_count',
                        'mst_store_product_varients.store_id'
                    )
                    ->where('mst_store_products.product_name', 'LIKE', "%{$product}%")
                    ->orWhere('mst_store_product_varients.variant_name', 'LIKE', "%{$product}%")
                    ->where('mst_store_products.display_flag', 1)->get()
                ) {

                    foreach ($data['storeProducts'] as $product) {
                        $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                        $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
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
                $data['status'] = 2;
                $data['message'] = "Product not found ";
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


    public function storeProductsByStoreName(Request $request)
    {
        $data = array();
        try {
            if (isset($request->store)) {
                $store = $request->store;

                if (
                    $data['storeProducts']  = Mst_store_product::join('mst_store_product_varients', 'mst_store_product_varients.product_id', '=', 'mst_store_products.product_id')
                    ->join('mst_stores', 'mst_stores.store_id', '=', 'mst_store_products.store_id')
                    ->join('trn__store_admins', 'trn__store_admins.store_id', '=', 'mst_stores.store_id')
                    ->select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.stock_count',
                        'mst_store_product_varients.store_id',
                        'mst_stores.store_name'
                    )
                    ->where('trn__store_admins.role_id', 0)
                    ->where('mst_stores.online_status', 1)
                    ->where('trn__store_admins.store_account_status', 1)
                    ->where('mst_stores.store_name', 'LIKE', "%{$store}%")
                    ->where('mst_store_products.display_flag', 1)->get()
                ) {

                    foreach ($data['storeProducts'] as $product) {
                        $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                        $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
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
                $data['status'] = 2;
                $data['message'] = "Product not found ";
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


    public function singleProductVariant(Request $request)
    {
        $data = array();
        try {
            if (isset($request->product_varient_id) && Mst_store_product_varient::find($request->product_varient_id)) {
                if ($data['productVariantsDetails']  = Mst_store_product_varient::where('product_varient_id', '=', $request->product_varient_id)
                    ->join('mst_store_products', 'mst_store_products.product_id', '=', 'mst_store_product_varients.product_id')
                    ->select(
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_product_varients.product_varient_id',
                        'mst_store_product_varients.product_id',
                        'mst_store_product_varients.store_id',
                        'mst_store_product_varients.variant_name',
                        'mst_store_product_varients.product_varient_price',
                        'mst_store_product_varients.product_varient_offer_price',
                        'mst_store_product_varients.product_varient_base_image',
                        'mst_store_product_varients.stock_count',
                        'mst_store_product_varients.created_at'
                    )
                    ->first()
                ) {
                    $data['productVariantsDetails']->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $data['productVariantsDetails']->product_varient_base_image;
                    $data['productVariantsDetails']->variantImages = Mst_product_image::where('product_varient_id', $data['productVariantsDetails']->product_varient_id)
                        ->select('product_image_id', 'product_image')
                        ->orderBy('image_flag', 'DESC')->get();
                    foreach ($data['productVariantsDetails']->variantImages as $varImg) {
                        $varImg->product_image = '/assets/uploads/products/base_product/base_image/' . $varImg->product_image;
                    }
                    $data['productVariantsDetails']->varianAttributes = Trn_ProductVariantAttribute::where('product_varient_id', $data['productVariantsDetails']->product_varient_id)->get();

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
                $data['message'] = "Product variant not found ";
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


    public function listProd(Request $request)
    {
        $data = array();
        try {
        } catch (\Exception $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        } catch (\Throwable $e) {
            $response = ['status' => '0', 'message' => $e->getMessage()];
            return response($response);
        }
    }


    public function RecentlyVisited(Request $request)
    {
        $data = array();
        try {

            if (isset($request->customer_id) && Trn_store_customer::find($request->customer_id)) {
                $business_type_id = $request->business_type_id;
                $customer_id = $request->customer_id;

                if (
                    $data['recentlyVisitedProducts']  = Trn_RecentlyVisitedProducts::join('mst_store_products', 'mst_store_products.product_id', '=', 'trn__recently_visited_products.product_id')
                    ->join('mst_stores', 'mst_stores.store_id', '=', 'trn__recently_visited_products.store_id')
                    ->select(
                        'mst_store_products.product_id',
                        'mst_store_products.product_name',
                        'mst_store_products.product_code',
                        'mst_store_products.product_base_image',
                        'mst_store_products.show_in_home_screen',
                        'mst_store_products.product_status',
                        'mst_store_products.is_removed',
                        'mst_store_products.display_flag',
                        'mst_store_products.is_timeslot_based_product',
                        'mst_store_products.timeslot_start_time',
                        'mst_store_products.timeslot_end_time',
                        'mst_store_products.store_id',
                        'mst_stores.business_type_id'
                    )
                    ->where('trn__recently_visited_products.customer_id', $customer_id)
                    ->where('mst_store_products.display_flag', 1)
                    ->where('mst_store_products.is_removed',0)
                    ->take(3)->get()
                ) {

                    foreach ($data['recentlyVisitedProducts'] as $product) {
                        $product->product_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_base_image;
                        $product->product_varient_base_image = '/assets/uploads/products/base_product/base_image/' . $product->product_varient_base_image;
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
                $data['message'] = "Customer not found ";
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

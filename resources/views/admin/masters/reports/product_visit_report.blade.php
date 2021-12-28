@extends('admin.layouts.app')
@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-12">
            <div class="card">
                <div class="row">
                    <div class="col-12" >
    
                      @if ($message = Session::get('status'))
                      <div class="alert alert-success">
                         <p>{{ $message }}<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></p>
                      </div>
                      @endif
                      <div class="col-lg-12">
                             @if ($errors->any())
                             <div class="alert alert-danger">
                                <h6>Whoops!</h6> There were some problems with your input.<br><br>
                                <ul>
                                   @foreach ($errors->all() as $error)
                                   <li>{{ $error }}</li>
                                   @endforeach
                                </ul>
                             </div>
                             @endif
                             <div class="card-header">
                                <h3 class="mb-0 card-title">{{$pageTitle}}</h3>
                             </div>
                             
                             <div class="card-body border">
                                <form action="{{route('admin.product_visit_reports')}}" method="GET" enctype="multipart/form-data">
                                   @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">From Date</label>
                                                   <input type="date" class="form-control"  name="date_from" id="date_fromc"  value="{{@$datefrom}}" placeholder="From Date">
                            
                                             </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">To Date</label>
                                                 <input type="date" class="form-control" name="date_to"   id="date_toc" value="{{@$dateto}}" placeholder="To Date">
                                            </div>
                                         </div>
                                        @if(auth()->user()->user_role_id  == 0) 
                                            <div class="col-md-4">
                                              <div class="form-group">
                                                 <label class="form-label">Sub Admin</label>
                                                 <div id="subadminl"></div>
                                                       <select  name="subadmin_id" id="subadminId" class="form-control select2-show-search" data-placeholder="Sub Admin" >
                                                          <option value="">Sub Admin</option>
                                                             @foreach($subadmins as $key)
                                                             <option {{request()->input('subadmin_id') == $key->id ? 'selected':''}} value="{{$key->id}}"> {{$key->name }} </option>
                                                             @endforeach
                                                       </select>
                                              </div>
                                           </div>
                                        @endif
                                         
                                         <div class="col-md-4">
                                            <div class="form-group">
                                              <label class="form-label">Store </label>
                                               <select  name="store_id" id="storeId" class="form-control select2-show-search" data-placeholder="Store"  >
                                                     <option value="">Store</option>
                                                    @foreach($stores as $key)
                                                    <option {{request()->input('store_id') == $key->store_id ? 'selected':''}} value="{{$key->store_id }}"> {{$key->store_name }} </option>
                                                    @endforeach
                                                  </select>
                                            </div>
                                         </div>
                                         
                                         
                                          <div class="col-md-4">
                                          <div class="form-group">
                                              <label class="form-label">Customer</label>
                                                  <select name="customer_id" id="customer_id" class="form-control select2-show-search" data-placeholder="Customer" >
                                                       <option value="" >Customer</option>
                                                       @foreach ($customers as $key)
                                                            <option value="{{ $key->customer_id }}" {{request()->input('customer_id') == $key->customer_id ? 'selected':''}} >{{ $key->customer_first_name }} {{ $key->customer_last_name }} - {{ $key->customer_mobile_number }} </option>
                                                       @endforeach
                                                  </select>
                                           </div>
                                          </div>
                                         
                                         <div class="col-md-4">
                                            <div class="form-group">
                                              <label class="form-label">Product </label>
                                               <select  name="product_id" id="productId" class="form-control select2-show-search" data-placeholder="Product"  >
                                                    <option value="">Product</option>
                                                  
                                                  </select>
                                            </div>
                                         </div>
                                         
                                          <div class="col-md-4">
                                            <div class="form-group">
                                              <label class="form-label">Vendor </label>
                                               <select  name="vendor_id" id="VendorId" class="form-control select2-show-search" data-placeholder="Vendor"  >
                                                <option value="">Vendor</option>
                                                    @foreach($agencies as $key)
                                                    <option {{request()->input('vendor_id') == $key->agency_id ? 'selected':''}} value="{{$key->agency_id }}"> {{$key->agency_name }} </option>
                                                    @endforeach
                                                  </select>
                                            </div>
                                         </div>
                                         
                                         
                                         <div class="col-md-4">
                                            <div class="form-group">
                                              <label class="form-label">Category </label>
                                               <select  name="category_id" id="categoryId" class="form-control select2-show-search" data-placeholder="Category"  >
                                                <option value="">Category</option>
                                                    @foreach($categories as $key)
                                                    <option {{request()->input('category_id') == $key->category_id ? 'selected':''}} value="{{$key->category_id }}"> {{$key->category_name }} </option>
                                                    @endforeach
                                                  </select>
                                            </div>
                                         </div>
                                         
                                          <div class="col-md-4">
                                            <div class="form-group">
                                              <label class="form-label">Sub Category </label>
                                               <select  name="sub_category_id" id="subCategoryId" class="form-control select2-show-search" data-placeholder="Sub Category"  >
                                                <option value="">Sub Category</option>
                                                    @foreach($subCategories as $key)
                                                    <option {{request()->input('sub_category_id') == $key->sub_category_id ? 'selected':''}} value="{{$key->sub_category_id }}"> {{$key->sub_category_name }} </option>
                                                    @endforeach
                                                  </select>
                                            </div>
                                         </div>
                                         
                                         
                                         <div class="col-md-12">
                                            <div class="form-group">
                                                <center>
                                                   <button type="submit" class="btn btn-raised btn-primary"><i class="fa fa-check-square-o"></i> Filter</button>
                                                   <button type="reset" id="reset" class="btn btn-raised btn-success">Reset</button>
                                                   <a href="{{route('admin.product_visit_reports')}}"  class="btn btn-info">Cancel</a>
                                                </center>
                                            </div>
                                          </div>
                     
                     
                                    </div>
                                </form>
                            </div>
               
                        
                            <div class="card-body">
                                <div class="table-responsive">
                                   <table id="exampletable" class="table table-striped table-bordered text-nowrap w-100">
                                      <thead>
                                         <tr>
                                            <th class="wd-15p">SL.No</th>
                                            <th class="wd-15p">Date</th>
                                            <th class="wd-15p">Time</th>
                                            <th class="wd-15p">Store<br>Name</th>
                                            <th class="wd-15p">Vendor</th>
                                            <th class="wd-15p">Category<br>Name</th>
                                            <th class="wd-15p">Sub<br>category</th>
                                            <th class="wd-15p">Brand</th>
                                            <th class="wd-15p">Product<br>Code</th>
                                            <th class="wd-15p">Product<br>Name</th>
                                            <th class="wd-15p">Customer</th>
                                            <th class="wd-15p">Customer<br>Phone</th>
                                           
                                         </tr>
                                      </thead>
                                      <tbody>
                                          
                                        @php
                                        $i = 0;
                                        @endphp
                                        @foreach ($data as $d)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ \Carbon\Carbon::parse($d->created_at)->format('d-m-Y')}}</td>
                                            <td>{{ \Carbon\Carbon::parse($d->created_at)->format('H:i:s')}}</td>
                                            <td>{{ $d->store_name }}</td>
                                            <td>{{ $d->agency_name }}</td>
                                            <td>{{ $d->category_name }}</td>
                                            <td>{{ $d->sub_category_name }}</td>
                                            <td>{{ $d->product_brand }}</td>
                                            <td>{{ $d->product_code }}</td>
                                            <td>
                                                @if($d->variant_name == $d->product_name)
                                                    {{ $d->product_name }}
                                                @else
                                                    {{ $d->product_name }} {{$d->variant_name }}
                                                @endif
                                            </td>
                                            <td>{{ $d->customer_first_name }} {{ $d->customer_last_name }}</td>
                                            <td>{{ $d->customer_mobile_number }}</td>
                                            
                                        </tr>
                                        @endforeach
                               
                                    
                                      </tbody>
                                   </table>
                                </div>
                            </div>
                      </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>



$(function(e) {
	 $('#exampletable').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdf',
                title: 'Product Visit Report',
                footer: true,
                exportOptions: {
                     columns: [0,1,2,3,4,5,6,7,8,9,10,11]
                 },
                 orientation : 'landscape',
                pageSize : 'LEGAL',
            },
            {
                extend: 'excel',
                title: 'Product Visit Report',
                footer: true,
                exportOptions: {
                     columns: [0,1,2,3,4,5,6,7,8,9,10,11]
                 }
            }
         ]
    } );

} );


    $(document).ready(function() {
        
        $("#subadminId").on('change', function(){    
            
         let subadminId = $('#subadminId').val();
         
         var _token= $('input[name="_token"]').val();
            $.ajax({
              type:"GET",
              url:"{{ url('admin/store-name-list') }}?subadmin_id="+subadminId,
    
              success:function(res){
                    if(res){
                       // console.log(res);
                        $('#storeId').prop("diabled",false);
                        $('#storeId').empty();
                        $('#storeId').append('<option value="">Store</option>');
                        $.each(res,function(store_id,store_name)
                        {
                          $('#storeId').append('<option value="'+store_id+'">'+store_name+'</option>');
                        });
                    }else
                    {
                      $('#storeId').empty();
                    }
                }
    
            });
        });
    });
    
    
    $(document).ready(function() {
 
     let subadminId = $('#subadminId').val();
      if ( typeof subadminId === "undefined") {
          subadminId = '';
      }
     let storeId = $('#storeId').val();
     
     var _token= $('input[name="_token"]').val();
        $.ajax({
          type:"GET",
          url:"{{ url('admin/product-name-list') }}?subadmin_id="+subadminId+'&store_id'+storeId,

          success:function(res){
                if(res){
                   // console.log(res);
                    $('#productId').prop("diabled",false);
                    $('#productId').empty();
                    $('#productId').append('<option value="">Product</option>');
                    $.each(res,function(product_id,product_name)
                    {
                      $('#productId').append('<option value="'+product_id+'">'+product_name+'</option>');
                    });
                    
                    let productId = getUrlParameter('product_id');
                    if ( typeof productId !== "undefined" && productId) {
                        $("#productId option").each(function(){
                            if($(this).val()==productId){ 
                                $(this).attr("selected","selected");    
                            }
                        });
                    } 
    
                }else
                {
                  $('#storeId').empty();
                }
            }

        });

    });
    
    
    $(document).ready(function() {
        
        $("#categoryId").on('change', function(){    
            
        let categoryId = $('#categoryId').val();
        
       // console.log(categoryId);

        var _token= $('input[name="_token"]').val();
        
            $.ajax({
              type:"GET",
              url:"{{ url('admin/sub-category-list') }}?category_id="+categoryId,
    
              success:function(res){
                    if(res){
                       // console.log(res);
                        $('#subCategoryId').prop("diabled",false);
                        $('#subCategoryId').empty();
                        $('#subCategoryId').append('<option value="">Sub Category</option>');
                        $.each(res,function(sub_category_id,sub_category_name)
                        {
                          $('#subCategoryId').append('<option value="'+sub_category_id+'">'+sub_category_name+'</option>');
                        });
                        
                        let subCategoryId = getUrlParameter('sub_category_id');
                        if ( typeof subCategoryId !== "undefined" && subCategoryId) {
                            $("#subCategoryId option").each(function(){
                                if($(this).val()==subCategoryId){ 
                                    $(this).attr("selected","selected");    
                                }
                            });
                        } 
                    
                    
                    }else
                    {
                      $('#storeId').empty();
                    }
                }
    
            });
        });
    });
    
    
    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
        return false;
    };

</script>

@endsection


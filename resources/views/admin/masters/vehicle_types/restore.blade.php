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
                        <div class="card-body">
                                    <a  href=" {{ url('admin/vihicle_types/list') }}"  class="btn btn-block btn-info text-white">
                                     List Vehicle Type </a>
                                    
                                <br>
                            <div class="table-responsive">
                            <table id="example" class="table table-striped table-bdataed text-nowrap w-100">
                                <thead>
                                    <tr>
                                        <th class="wd-15p">SL.No</th>
                                        <th class="wd-15p">{{__('Vehicle Type')}}</th>
                                        <th class="wd-15p">{{__('Action')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $i = 0;
                                    @endphp
                                    @foreach ($vehicle_types as $vehicle_type)
                                    <tr>
                                        <td>{{ ++$i }}</td>
                                        <td>{{ $vehicle_type->vehicle_type_name}}</td>

                                        <td>
                                            <form action="{{route('admin.restore_vehicle_type',$vehicle_type->vehicle_type_id)}}" method="POST">
                                                @csrf
                                                 
                                                @method('POST')
                                                <button type="submit" onclick="return confirm('Do you want to restore this item?');"  class="btn btn-sm btn-danger">Restore</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach

                                </tbody>

                            </table>

                            {{-- table responsive end --}}
                            </div>
                        {{-- Card body end --}}
                        </div>
                    {{-- col 12 end --}}
                </div>
            {{-- row end --}}
            </div>
        {{-- card --}}



        </div>
        {{-- row justify end --}}
    </div>
{{-- container end --}}
</div>





@endsection

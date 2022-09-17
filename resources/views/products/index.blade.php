@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>

    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control" value="{{ request()->get('title') }}">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                        <option value="">Select A Variant</option>
                        @foreach($variants as $variant):
                        <option value="{{ $variant->id }}" @if(request()->get('variant') == $variant->id) selected @endif>{{ $variant->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="First name" placeholder="From" class="form-control" value="{{ request()->get('price_from') }}">
                        <input type="text" name="price_to" aria-label="Last name" placeholder="To" class="form-control" value="{{ request()->get('price_to') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control" value="{{ request()->get('date') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach ($lists as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->title }} <br> Created at : {{ $item->created_at }}</td>
                        <td>{{ Str::limit($item->description, 100) }}</td>
                        <td>
                            <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">
                                @foreach($item->varientPrices as $info):
                                <dt class="col-sm-3 pb-0">
                                    {{ $info['varient'] }}
                                </dt>
                                <dd class="col-sm-9">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 pb-0">Price : {{ number_format($info['price'],2) }}</dt>
                                        <dd class="col-sm-8 pb-0">InStock : {{ number_format($info['stock'],2) }}</dd>
                                    </dl>
                                </dd>
                                @endforeach
                            </dl>
                            <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('product.edit', 1) }}" class="btn btn-success">Edit</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{($lists->currentPage()-1)* $lists->perPage()+($lists->total() ? 1:0)}} to {{($lists->currentPage()-1)*$lists->perPage()+count($lists)}} out of {{ $lists->total() }}</p>
                </div>
                <div class="col-md-2">
                    @if ($lists->hasPages())
                        {{ $lists->withQueryString()->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@extends('layout.layout')

@section('title', 'Location Scope Test')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Location Scope Test</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User Information -->
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5>Current User Information</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>User ID:</strong> {{ auth()->id() }}</p>
                                    <p><strong>Name:</strong> {{ auth()->user()->full_name }}</p>
                                    <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                                    
                                    <h6 class="mt-3">Roles:</h6>
                                    <ul>
                                        @forelse(auth()->user()->roles as $role)
                                            <li>{{ $role->name }} 
                                                @if(isset($role->bypass_location_scope) && $role->bypass_location_scope)
                                                    <span class="badge bg-success">Bypass Location Scope</span>
                                                @endif
                                            </li>
                                        @empty
                                            <li>No roles assigned</li>
                                        @endforelse
                                    </ul>

                                    <h6 class="mt-3">Assigned Locations:</h6>
                                    <ul>
                                        @forelse(auth()->user()->locations as $location)
                                            <li>{{ $location->name ?? $location->id }}</li>
                                        @empty
                                            <li>No locations assigned</li>
                                        @endforelse
                                    </ul>

                                    <h6 class="mt-3">Location Scope Status:</h6>
                                    <p><strong>Can Bypass Location Scope:</strong> 
                                        <span class="badge {{ auth()->user()->canBypassLocationScope() ? 'bg-success' : 'bg-danger' }}">
                                            {{ auth()->user()->canBypassLocationScope() ? 'Yes' : 'No' }}
                                        </span>
                                    </p>
                                    
                                    <p><strong>Selected Location:</strong> 
                                        {{ session('selected_location', 'None') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Data Access Test -->
                        <div class="col-md-8">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5>Data Access Test</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Sales Data -->
                                        <div class="col-md-6">
                                            <h6>Sales (With Location Scope)</h6>
                                            <div class="table-responsive" style="max-height: 300px;">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Invoice</th>
                                                            <th>Location ID</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $sales = \App\Models\Sale::with('location')->limit(20)->get();
                                                        @endphp
                                                        @forelse($sales as $sale)
                                                            <tr>
                                                                <td>{{ $sale->id }}</td>
                                                                <td>{{ $sale->invoice_no }}</td>
                                                                <td>{{ $sale->location_id ?? 'NULL' }}</td>
                                                                <td>{{ number_format($sale->final_total, 2) }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4">No sales found</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <p><strong>Total Sales Count:</strong> {{ $sales->count() }}</p>
                                        </div>

                                        <!-- Customers Data -->
                                        <div class="col-md-6">
                                            <h6>Customers (With Location Scope)</h6>
                                            <div class="table-responsive" style="max-height: 300px;">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Name</th>
                                                            <th>Location ID</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $customers = \App\Models\Customer::limit(20)->get();
                                                        @endphp
                                                        @forelse($customers as $customer)
                                                            <tr>
                                                                <td>{{ $customer->id }}</td>
                                                                <td>{{ $customer->name ?? $customer->full_name }}</td>
                                                                <td>{{ $customer->location_id ?? 'NULL' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="3">No customers found</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <p><strong>Total Customers Count:</strong> {{ $customers->count() }}</p>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Without Location Scope Test -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h6>Data Without Location Scope (Admin Only)</h6>
                                            @php
                                                $allSales = \App\Models\Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)->count();
                                                $allCustomers = \App\Models\Customer::withoutGlobalScope(\App\Scopes\LocationScope::class)->count();
                                            @endphp
                                            <div class="alert alert-warning">
                                                <p><strong>Total Sales (All Locations):</strong> {{ $allSales }}</p>
                                                <p><strong>Total Customers (All Locations):</strong> {{ $allCustomers }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- All Locations List -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h6>All Available Locations</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Name</th>
                                                            <th>Status</th>
                                                            <th>Users Count</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $locations = \App\Models\Location::withCount('users')->get();
                                                        @endphp
                                                        @forelse($locations as $location)
                                                            <tr>
                                                                <td>{{ $location->id }}</td>
                                                                <td>{{ $location->name }}</td>
                                                                <td>
                                                                    <span class="badge {{ $location->is_active ? 'bg-success' : 'bg-danger' }}">
                                                                        {{ $location->is_active ? 'Active' : 'Inactive' }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $location->users_count }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4">No locations found</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card border-secondary">
                                <div class="card-header">
                                    <h5>Test Actions</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('test.location.set') }}" class="d-inline-block">
                                        @csrf
                                        <div class="input-group" style="width: 300px; display: inline-block;">
                                            <select name="location_id" class="form-select">
                                                <option value="">Select Location</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" 
                                                        {{ session('selected_location') == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary">Set Location</button>
                                        </div>
                                    </form>

                                    <a href="{{ route('test.location.clear') }}" class="btn btn-secondary ms-2">Clear Location</a>
                                    <a href="{{ route('test.location.scope') }}" class="btn btn-info ms-2">Refresh</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
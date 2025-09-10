@extends('layout.layout')


@section('title')
    Cheque Business Guide - Marazin POS
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">📋 Cheque Handling Business Guide</h4>
                <p class="text-muted">Clear explanation of how cheque payments work in your POS system</p>
            </div>
            <div class="card-body">
                
                <!-- Real Business Scenario -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Your Business Scenario</h5>
                    <p><strong>Customer comes with ₹17,000 bill → Gives cheque → 3 days valid date</strong></p>
                    <p><strong>Question:</strong> Should this show as "paid" immediately or wait for bank clearance?</p>
                    <p><strong>Answer:</strong> Show as PAID immediately (so business continues) but TRACK the risk separately</p>
                </div>

                <!-- Workflow Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6><i class="fas fa-handshake"></i> Step 1: Sale Time</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li>✅ Customer gives cheque</li>
                                    <li>✅ Status: "Pending"</li>
                                    <li>✅ Sale shows: "PAID" ₹17,000</li>
                                    <li>✅ Customer balance: Updated</li>
                                    <li>✅ Business continues normally</li>
                                </ul>
                                <div class="alert alert-success">
                                    <small><strong>Why?</strong> In real business, you treat cheque as payment received to complete the sale</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-white">
                                <h6><i class="fas fa-university"></i> Step 2: Bank Deposit</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li>🏦 You take cheque to bank</li>
                                    <li>🏦 Status: "Deposited"</li>
                                    <li>📊 Sale still shows: "PAID"</li>
                                    <li>⚠️ Risk tracking: ₹17,000 "At Risk"</li>
                                    <li>⏰ Waiting for bank confirmation</li>
                                </ul>
                                <div class="alert alert-warning">
                                    <small><strong>Risk Period:</strong> Money is not yet confirmed in your account</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6><i class="fas fa-check-circle"></i> Step 3: Final Status</h6>
                            </div>
                            <div class="card-body">
                                <h6 class="text-success">✅ If CLEARED:</h6>
                                <ul class="list-unstyled text-success">
                                    <li>✅ Status: "Cleared"</li>
                                    <li>✅ Sale: Still "PAID"</li>
                                    <li>✅ Risk: ₹0 (Safe now)</li>
                                    <li>✅ Money confirmed in bank</li>
                                </ul>
                                
                                <h6 class="text-danger mt-3">❌ If BOUNCED:</h6>
                                <ul class="list-unstyled text-danger">
                                    <li>❌ Status: "Bounced"</li>
                                    <li>❌ Sale: "DUE" ₹17,000</li>
                                    <li>❌ Customer balance: Increases</li>
                                    <li>❌ Follow-up required</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Benefits -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line"></i> Business Benefits</h5>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li><strong>Cash Flow:</strong> Sales complete immediately</li>
                                    <li><strong>Customer Service:</strong> No payment delays</li>
                                    <li><strong>Risk Management:</strong> Track all pending amounts</li>
                                    <li><strong>Reporting:</strong> Clear separation of safe vs at-risk money</li>
                                    <li><strong>Follow-up:</strong> Automated reminders for pending cheques</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs"></i> System Features</h5>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li><strong>Smart Totals:</strong> Shows "Total Received" vs "Safe Amount"</li>
                                    <li><strong>Risk Tracking:</strong> "At Risk" amount for pending cheques</li>
                                    <li><strong>Auto Reminders:</strong> Alerts for overdue cheques</li>
                                    <li><strong>Status History:</strong> Complete audit trail</li>
                                    <li><strong>Bulk Actions:</strong> Update multiple cheques at once</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5><i class="fas fa-rocket"></i> Quick Actions</h5>
                                <div class="btn-group">
                                    <a href="{{ route('pos-create') }}" class="btn btn-primary">
                                        <i class="fas fa-cash-register"></i> Create Sale with Cheque
                                    </a>
                                    <a href="{{ route('cheque-management') }}" class="btn btn-warning">
                                        <i class="fas fa-list-check"></i> Manage Cheques
                                    </a>
                                    <a href="{{ route('list-sale') }}" class="btn btn-info">
                                        <i class="fas fa-eye"></i> View Sales
                                    </a>
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

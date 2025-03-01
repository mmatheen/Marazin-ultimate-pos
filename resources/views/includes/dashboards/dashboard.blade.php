@extends('layout.layout')
@section('content')
<!--      -->
        </div>
    </div>
    <script>
        $(document).ready(function() {
            fetchDashboardData();

            function fetchDashboardData() {
                $.ajax({
                    url: "/dashboard-data",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        $("#totalSales").text("$" + response.totalSales.toFixed(2));
                        $("#totalPurchases").text("$" + response.totalPurchases.toFixed(2));
                        $("#totalSalesReturn").text("$" + response.totalSalesReturn.toFixed(2));
                        $("#totalPurchaseReturn").text("$" + response.totalPurchaseReturn.toFixed(2));
                        $("#stockTransfer").text(response.stockTransfer);
                        $("#stockAdjustment").text(response.stockAdjustment);
                        $("#totalProducts").text(response.totalProducts);
                        $("#expenses").text("$" + response.expenses.toFixed(2));

                        updateCharts(response);
                    }
                });
            }

            function updateCharts(data) {
                const ctx1 = document.getElementById('salesChart').getContext('2d');
                const ctx2 = document.getElementById('stockChart').getContext('2d');

                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: ['Sales', 'Purchases', 'Sales Return', 'Purchase Return'],
                        datasets: [{
                            label: 'Amount ($)',
                            data: [data.totalSales, data.totalPurchases, data.totalSalesReturn, data.totalPurchaseReturn],
                            backgroundColor: ['blue', 'green', 'red', 'orange']
                        }]
                    }
                });

                new Chart(ctx2, {
                    type: 'pie',
                    data: {
                        labels: ['Stock Transfers', 'Stock Adjustments'],
                        datasets: [{
                            data: [data.stockTransfer, data.stockAdjustment],
                            backgroundColor: ['purple', 'yellow']
                        }]
                    }
                });
            }
        });
        </script>

@endsection

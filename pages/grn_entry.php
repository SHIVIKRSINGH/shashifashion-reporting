<?php
require_once "../includes/config.php";
include "../includes/header.php";
?>

<!DOCTYPE html>
<html>

<head>

    <title>GRN Entry</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">

    <style>
        .table-input {
            width: 100%;
            border: none;
            background: transparent;
        }

        .table td {
            vertical-align: middle;
        }

        /* barcode scanner */

        #scanner-container {
            width: 100%;
            max-width: 500px;
            border: 2px solid #ddd;
            margin-top: 15px;
        }
    </style>

</head>

<body class="bg-light">

    <div class="container-fluid mt-4">

        <h4 class="mb-3">📦 GRN Entry</h4>


        <!-- ================= HEADER ================= -->

        <div class="card mb-3">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-2">
                        <label>Receipt No</label>
                        <input type="text" class="form-control" id="receipt_no">
                    </div>

                    <div class="col-md-2">
                        <label>Receipt Date</label>
                        <input type="date" class="form-control" id="receipt_date">
                    </div>

                    <div class="col-md-3">
                        <label>Supplier</label>
                        <select class="form-control select2" id="supplier">
                            <option value="">Select Supplier</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Branch</label>
                        <select class="form-control" id="branch">
                            <option value="SHIVI-ND">SHIVI-ND</option>
                            <option value="SHASHI-ND">SHASHI-ND</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Invoice No</label>
                        <input type="text" class="form-control" id="invoice_no">
                    </div>

                </div>

                <div class="row mt-3">

                    <div class="col-md-2">
                        <label>Bill Date</label>
                        <input type="date" class="form-control" id="bill_date">
                    </div>

                </div>

            </div>
        </div>


        <!-- ================= ITEM ENTRY PANEL ================= -->

        <div class="card mb-3">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <label>Barcode</label>

                        <input type="text" id="barcode" class="form-control" placeholder="Scan or type barcode">

                        <button class="btn btn-primary btn-sm mt-2" onclick="startScanner()">
                            📷 Scan Barcode
                        </button>

                    </div>

                    <div class="col-md-4">
                        <label>Item Search</label>

                        <select id="item_search" class="form-control select2">
                            <option value="">Search Item</option>
                        </select>

                    </div>

                    <div class="col-md-1">
                        <label>Qty</label>
                        <input type="number" id="qty" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label>Purchase Rate</label>
                        <input type="number" id="pur_rate" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <label>Disc%</label>
                        <input type="number" id="disc" class="form-control" value="0">
                    </div>

                    <div class="col-md-1">
                        <label>GST%</label>
                        <input type="number" id="gst" class="form-control" value="0">
                    </div>

                </div>

                <div id="scanner-container"></div>

                <div class="mt-3">

                    <button class="btn btn-primary" id="add_item">
                        Add Item
                    </button>

                </div>

            </div>
        </div>



        <!-- ================= ITEMS TABLE ================= -->

        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-striped" id="grn_table">

                        <thead class="table-dark text-center">

                            <tr>

                                <th>Sl</th>
                                <th>Barcode</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Pur Rate</th>
                                <th>Disc%</th>
                                <th>GST%</th>
                                <th>Net Amt</th>
                                <th>MRP</th>
                                <th>Sales Price</th>
                                <th>Batch</th>
                                <th>Exp Date</th>
                                <th>Remove</th>

                            </tr>

                        </thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- ================= TOTALS ================= -->

        <div class="card mt-3">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-2">
                        <label>Total Qty</label>
                        <input type="text" id="total_qty" class="form-control" readonly>
                    </div>

                    <div class="col-md-2">
                        <label>Gross Amount</label>
                        <input type="text" id="gross_amt" class="form-control" readonly>
                    </div>

                    <div class="col-md-2">
                        <label>Net Amount</label>
                        <input type="text" id="net_amt" class="form-control" readonly>
                    </div>

                    <div class="col-md-2">

                        <button class="btn btn-success mt-4" id="save_grn">
                            Save GRN
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

    <!-- BARCODE LIBRARY -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>


    <script>
        $('.select2').select2();

        let row = 1;


        // ================= ADD ITEM =================

        $("#add_item").click(function() {

            let barcode = $("#barcode").val();
            let item = $("#item_search option:selected").text();
            let qty = parseFloat($("#qty").val());
            let rate = parseFloat($("#pur_rate").val());
            let disc = parseFloat($("#disc").val());
            let gst = parseFloat($("#gst").val());

            if (!item || !qty || !rate) {
                alert("Enter item, qty and rate");
                return;
            }

            let gross = qty * rate;

            let disc_amt = gross * disc / 100;

            let gst_amt = (gross - disc_amt) * gst / 100;

            let net = gross - disc_amt + gst_amt;


            $("#grn_table tbody").append(`

<tr>

<td>${row}</td>
<td>${barcode}</td>
<td>${item}</td>
<td>${qty}</td>
<td>${rate.toFixed(2)}</td>
<td>${disc}</td>
<td>${gst}</td>
<td class="net">${net.toFixed(2)}</td>

<td><input class="table-input" placeholder="MRP"></td>
<td><input class="table-input" placeholder="SP"></td>
<td><input class="table-input" placeholder="Batch"></td>
<td><input type="date" class="table-input"></td>

<td>
<button class="btn btn-danger btn-sm remove">X</button>
</td>

</tr>

`);

            row++;

            calculateTotals();
            clearInputs();

        });



        // ================= REMOVE ROW =================

        $(document).on("click", ".remove", function() {

            $(this).closest("tr").remove();

            calculateTotals();

        });



        // ================= CALCULATE TOTALS =================

        function calculateTotals() {

            let total_qty = 0;
            let total_amt = 0;

            $("#grn_table tbody tr").each(function() {

                let qty = parseFloat($(this).find("td:eq(3)").text());
                let net = parseFloat($(this).find(".net").text());

                total_qty += qty;
                total_amt += net;

            });

            $("#total_qty").val(total_qty.toFixed(2));
            $("#gross_amt").val(total_amt.toFixed(2));
            $("#net_amt").val(total_amt.toFixed(2));

        }



        // ================= CLEAR INPUTS =================

        function clearInputs() {

            $("#barcode").val("");
            $("#qty").val("");
            $("#pur_rate").val("");
            $("#disc").val("0");
            $("#gst").val("0");

        }



        // ================= BARCODE CAMERA SCANNER =================

        function startScanner() {

            document.getElementById("scanner-container").innerHTML = "";

            Quagga.init({

                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#scanner-container'),
                    constraints: {
                        facingMode: "environment"
                    }
                },

                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "upc_reader"
                    ]
                }

            }, function(err) {

                if (err) {
                    console.log(err);
                    return;
                }

                Quagga.start();

            });


            Quagga.onDetected(function(data) {

                let barcode = data.codeResult.code;

                $("#barcode").val(barcode);

                Quagga.stop();

            });

        }



        // ================= AUTO DATE =================

        document.getElementById("receipt_date").value =
            new Date().toISOString().split("T")[0];

        document.getElementById("bill_date").value =
            new Date().toISOString().split("T")[0];
    </script>

</body>

</html>
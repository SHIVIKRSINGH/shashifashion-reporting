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

        #scanner-container {
            max-width: 420px;
            margin-top: 10px;
            display: none;
        }

        #scanner-container video {
            width: 100%;
        }
    </style>

</head>

<body class="bg-light">

    <div class="container-fluid mt-4">

        <h4 class="mb-3">📦 GRN Entry</h4>

        <!-- HEADER -->

        <div class="card mb-3">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-2">
                        <label>Branch</label>
                        <select class="form-control" id="branch">
                            <option value="SHIVI-ND">SHIVI-ND</option>
                            <option value="SHASHI-ND">SHASHI-ND</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Receipt Date</label>
                        <input type="date" id="receipt_date" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label>Supplier</label>
                        <select id="supplier" class="form-control"></select>
                    </div>

                    <div class="col-md-3">
                        <label>Invoice No</label>
                        <input type="text" id="invoice_no" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label>Bill Date</label>
                        <input type="date" id="bill_date" class="form-control">
                    </div>

                </div>

            </div>
        </div>



        <!-- ITEM PANEL -->

        <div class="card mb-3">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <label>Barcode</label>
                        <input type="text" id="barcode" class="form-control">

                        <button class="btn btn-primary btn-sm mt-2" onclick="openCamera()">
                            📷 Scan Barcode
                        </button>

                    </div>

                    <div class="col-md-4">
                        <label>Item Search</label>
                        <select id="item_search" class="form-control"></select>
                    </div>

                    <div class="col-md-1">
                        <label>Qty</label>
                        <input type="number" id="qty" value="1" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label>CP</label>
                        <input type="number" id="pur_rate" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <label>Disc%</label>
                        <input type="number" id="disc" value="0" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <label>GST%</label>
                        <input type="number" id="gst" class="form-control">
                    </div>

                </div>

                <div id="scanner-container">

                    <div id="camera"></div>

                    <button class="btn btn-success btn-sm mt-2" onclick="captureBarcode()">
                        Capture Barcode
                    </button>

                    <button class="btn btn-danger btn-sm mt-2" onclick="stopCamera()">
                        Close
                    </button>

                </div>

                <button class="btn btn-primary mt-3" id="add_item">Add Item</button>

            </div>
        </div>



        <!-- TABLE -->

        <div class="card">
            <div class="card-body">

                <table class="table table-bordered table-striped" id="grn_table">

                    <thead class="table-dark text-center">

                        <tr>
                            <th>Sl</th>
                            <th>Barcode</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>CP</th>
                            <th>Disc%</th>
                            <th>GST%</th>
                            <th>Net</th>
                            <th>MRP</th>
                            <th>SP</th>
                            <th>Batch</th>
                            <th>Exp</th>
                            <th>Remove</th>
                        </tr>

                    </thead>

                    <tbody></tbody>

                </table>

            </div>
        </div>



        <!-- TOTALS -->

        <div class="card mt-3">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-2">
                        <label>Total Qty</label>
                        <input type="text" id="total_qty" class="form-control" readonly>
                    </div>

                    <div class="col-md-2">
                        <label>Gross</label>
                        <input type="text" id="gross_amt" class="form-control" readonly>
                    </div>

                    <div class="col-md-2">
                        <label>Net</label>
                        <input type="text" id="net_amt" class="form-control" readonly>
                    </div>

                </div>

            </div>
        </div>

    </div>



    <!-- JS LIBRARIES -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>


    <script>
        $(document).ready(function() {

            let row = 1;
            let detected = null;

            /* AUTO DATE */

            $("#receipt_date").val(new Date().toISOString().split("T")[0]);
            $("#bill_date").val(new Date().toISOString().split("T")[0]);



            /* SUPPLIER SEARCH */

            $('#supplier').select2({
                placeholder: "Search Supplier",
                minimumInputLength: 1,
                ajax: {
                    url: "ajax/search_supplier.php",
                    dataType: "json",
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term,
                            branch_id: $("#branch").val()
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });


            /* ITEM SEARCH */

            $('#item_search').select2({
                placeholder: "Search Item",
                minimumInputLength: 1,
                ajax: {
                    url: "ajax/search_item.php",
                    dataType: "json",
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term,
                            branch_id: $("#branch").val()
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });


            /* BARCODE LOOKUP */

            $("#barcode").on("change", function() {

                let barcode = $(this).val();

                $.getJSON("ajax/get_item_barcode.php", {
                    barcode: barcode,
                    branch_id: $("#branch").val()
                }, function(data) {

                    if (!data || !data.item_id) {
                        alert("Item not found");
                        return;
                    }

                    $("#item_search").append(
                        new Option(data.item_name, data.item_id, true, true)
                    ).trigger("change");

                    $("#pur_rate").val(data.cp);
                    $("#gst").val(data.gst);

                });

            });


            /* ADD ITEM */

            $("#add_item").click(function() {

                let barcode = $("#barcode").val();
                let item = $("#item_search option:selected").text() || "";
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
<td>${rate}</td>
<td>${disc}</td>
<td>${gst}</td>
<td class="net">${net.toFixed(2)}</td>
<td><input class="table-input"></td>
<td><input class="table-input"></td>
<td><input class="table-input"></td>
<td><input type="date" class="table-input"></td>
<td><button class="btn btn-danger btn-sm remove">X</button></td>
</tr>
`);

                row++;

                calculateTotals();

            });


            /* REMOVE */

            $(document).on("click", ".remove", function() {
                $(this).closest("tr").remove();
                calculateTotals();
            });


            function calculateTotals() {

                let qty = 0;
                let amt = 0;

                $("#grn_table tbody tr").each(function() {
                    qty += parseFloat($(this).find("td:eq(3)").text());
                    amt += parseFloat($(this).find(".net").text());
                });

                $("#total_qty").val(qty.toFixed(2));
                $("#gross_amt").val(amt.toFixed(2));
                $("#net_amt").val(amt.toFixed(2));

            }

        });


        /* CAMERA FUNCTIONS */

        function openCamera() {

            $("#scanner-container").show();

            Quagga.init({

                inputStream: {
                    type: "LiveStream",
                    target: document.querySelector('#camera'),
                    constraints: {
                        facingMode: "environment"
                    }
                },

                decoder: {
                    readers: ["code_128_reader", "ean_reader", "ean_8_reader", "upc_reader"]
                }

            }, function(err) {

                if (err) return console.log(err);

                Quagga.start();

            });

            Quagga.onDetected(function(data) {
                detected = data.codeResult.code;
            });

        }

        function captureBarcode() {

            if (!detected) {
                alert("No barcode detected");
                return;
            }

            $("#barcode").val(detected).trigger("change");
            stopCamera();

        }

        function stopCamera() {

            Quagga.stop();
            $("#scanner-container").hide();

        }
    </script>

</body>

</html>
<?php
require_once "../includes/config.php";
include "../includes/header.php";
?>

<!DOCTYPE html>
<html>

<head>
    <title>GRN Entry</title>
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
            border: 2px solid #007bff;
            padding: 10px;
            background: #fff;
        }

        #scanner-container video {
            width: 100%;
        }

        /* Ensure Select2 fits Bootstrap styling */
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-4">
        <h4 class="mb-3">📦 GRN Entry</h4>

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
                        <select id="supplier" class="form-control" style="width:100%"></select>
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

        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Barcode</label>
                        <input type="text" id="barcode" class="form-control">
                        <button class="btn btn-outline-primary btn-sm mt-2" onclick="openCamera()">
                            📷 Scan Barcode
                        </button>
                    </div>

                    <div class="col-md-4">
                        <label>Item Search</label>
                        <select id="item_search" class="form-control" style="width:100%"></select>
                    </div>

                    <div class="col-md-1">
                        <label>Qty</label>
                        <input type="number" id="qty" value="1" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <label>CP</label>
                        <input type="number" id="pur_rate" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <label>MRP</label>
                        <input type="number" id="mrp" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <label>SP</label>
                        <input type="number" id="sp" class="form-control">
                    </div>

                    <div class="col-md-1">
                        <label>GST%</label>
                        <input type="number" id="gst" class="form-control">
                    </div>
                </div>

                <div id="scanner-container">
                    <div id="camera"></div>
                    <div class="mt-2 text-center">
                        <button class="btn btn-success btn-sm" onclick="captureBarcode()">Capture</button>
                        <button class="btn btn-danger btn-sm" onclick="stopCamera()">Close</button>
                    </div>
                </div>

                <button class="btn btn-primary mt-3" id="add_item">Add Item</button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-bordered" id="grn_table">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Sl</th>
                            <th>Barcode</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>CP</th>
                            <th>MRP</th>
                            <th>SP</th>
                            <th>GST</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

    <script>
        $(document).ready(function() {
            // Set default dates
            $("#receipt_date, #bill_date").val(new Date().toISOString().split("T")[0]);

            /* SUPPLIER SEARCH */
            $('#supplier').select2({
                placeholder: "Search Supplier",
                allowClear: true,
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
                allowClear: true,
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
            $("#barcode").on('change', function() {
                let barcode = $(this).val();
                if (!barcode) return;

                $.getJSON("ajax/get_item_barcode.php", {
                    barcode: barcode,
                    branch_id: $("#branch").val()
                }, function(data) {
                    if (data && data.item_id) {
                        let newOption = new Option(data.item_name, data.item_id, true, true);
                        $("#item_search").append(newOption).trigger("change");

                        $("#pur_rate").val(data.cp);
                        $("#mrp").val(data.mrp);
                        $("#sp").val(data.sp);
                        $("#gst").val(data.gst);
                    } else {
                        alert("Item not found");
                    }
                });
            });

            /* ADD ITEM TO TABLE */
            let slCount = 1;
            $("#add_item").click(function() {
                let barcode = $("#barcode").val() || "-";
                let itemText = $("#item_search option:selected").text();
                let itemId = $("#item_search").val();
                let qty = $("#qty").val();
                let cp = $("#pur_rate").val();
                let mrp = $("#mrp").val();
                let sp = $("#sp").val();
                let gst = $("#gst").val();

                if (!itemId || !qty) {
                    alert("Please select an item and enter quantity");
                    return;
                }

                let newRow = `
                    <tr>
                        <td class="text-center">${slCount}</td>
                        <td>${barcode}</td>
                        <td>${itemText}</td>
                        <td class="text-center">${qty}</td>
                        <td class="text-end">${cp}</td>
                        <td class="text-end">${mrp}</td>
                        <td class="text-end">${sp}</td>
                        <td class="text-center">${gst}%</td>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm remove">X</button>
                        </td>
                    </tr>`;

                $("#grn_table tbody").append(newRow);
                slCount++;

                // Clear fields for next entry
                $("#barcode, #pur_rate, #mrp, #sp, #gst").val("");
                $("#item_search").val(null).trigger("change");
                $("#qty").val(1);
            });

            $(document).on("click", ".remove", function() {
                $(this).closest("tr").remove();
            });
        });

        /* CAMERA FUNCTIONS */
        let detectedCode = null;

        function openCamera() {
            $("#scanner-container").show();
            Quagga.init({
                inputStream: {
                    name: "Live",
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
                if (err) {
                    console.error(err);
                    return;
                }
                Quagga.start();
            });

            Quagga.onDetected(function(data) {
                detectedCode = data.codeResult.code;
                // Optional: visual feedback that a code was detected
            });
        }

        function captureBarcode() {
            if (!detectedCode) {
                alert("No barcode detected yet. Please hold the barcode steady.");
                return;
            }
            $("#barcode").val(detectedCode).trigger("change");
            stopCamera();
        }

        function stopCamera() {
            Quagga.stop();
            $("#scanner-container").hide();
            detectedCode = null;
        }
    </script>
</body>

</html>
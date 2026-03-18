<?php
require_once "../includes/config.php";
include "../includes/header.php";
?>

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

    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
        line-height: 36px !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<div class="container-fluid mt-4">
    <h4 class="mb-3">📦 GRN Entry</h4>

    <!-- Header Info -->
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
                    <select id="supplier" class="form-control" style="width:100%">
                        <option></option>
                    </select>
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

    <!-- Item Entry -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label>Barcode</label>
                    <input type="text" id="barcode" class="form-control">
                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="openCamera()">📷 Scan Barcode</button>
                </div>
                <div class="col-md-4">
                    <label>Item Search</label>
                    <select id="item_search" class="form-control" style="width:100%">
                        <option></option>
                    </select>
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

            <button class="btn btn-primary mt-3" id="add_item">➕ Add Item</button>
        </div>
    </div>

    <!-- GRN Table -->
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
            <div class="text-end mt-2">
                <button class="btn btn-success" id="save_grn">💾 Save GRN</button>
            </div>
        </div>
    </div>
</div>

<!-- All scripts loaded dynamically to guarantee correct order -->
<script>
    // Dynamically load a script, then call a callback
    function loadScript(src, callback) {
        var s = document.createElement('script');
        s.src = src;
        s.onload = callback;
        s.onerror = function() {
            console.error('Failed to load: ' + src);
        };
        document.head.appendChild(s);
    }

    // Load chain: jQuery → Select2 + Quagga → initPage
    function loadDependencies() {
        loadScript('https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js', function() {
            loadScript('https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js', function() {
                initPage();
            });
        });
    }

    if (typeof jQuery === 'undefined') {
        loadScript('https://code.jquery.com/jquery-3.6.0.min.js', loadDependencies);
    } else {
        loadDependencies();
    }
</script>

<script>
    // ─── Main Init ───────────────────────────────────────────────────────────
    function initPage() {
        $(document).ready(function() {

            // Set default dates to today
            var today = new Date().toISOString().split("T")[0];
            $("#receipt_date, #bill_date").val(today);

            // ── Supplier Select2 ──────────────────────────────────────────────
            $('#supplier').select2({
                placeholder: "Search Supplier...",
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: "ajax/search_supplier.php",
                    dataType: 'json',
                    delay: 300,
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
                    },
                    error: function(xhr, status, error) {
                        console.error("Supplier AJAX error:", status, error);
                    },
                    cache: true
                }
            });

            // ── Item Search Select2 ───────────────────────────────────────────
            $('#item_search').select2({
                placeholder: "Search Item...",
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: "ajax/search_item.php",
                    dataType: 'json',
                    delay: 300,
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
                    },
                    error: function(xhr, status, error) {
                        console.error("Item AJAX error:", status, error);
                    },
                    cache: true
                }
            });

            // ── Barcode Lookup ────────────────────────────────────────────────
            $("#barcode").on('change', function() {
                var barcode = $.trim($(this).val());
                if (!barcode) return;

                $.getJSON("ajax/get_item_barcode.php", {
                    barcode: barcode,
                    branch_id: $("#branch").val()
                }, function(data) {
                    if (data && data.item_id) {
                        var newOption = new Option(data.item_name, data.item_id, true, true);
                        $("#item_search").empty().append(newOption).trigger("change");
                        $("#pur_rate").val(data.cp);
                        $("#mrp").val(data.mrp);
                        $("#sp").val(data.sp);
                        $("#gst").val(data.gst);
                        $("#qty").focus();
                    } else {
                        alert("⚠️ Item not found for barcode: " + barcode);
                        $("#barcode").val("").focus();
                    }
                }).fail(function() {
                    alert("❌ Error connecting to server. Check ajax/get_item_barcode.php");
                });
            });

            // ── Add Item to Table ─────────────────────────────────────────────
            var slCount = 1;

            $("#add_item").on('click', function() {
                var barcode = $.trim($("#barcode").val()) || "-";
                var itemText = $("#item_search option:selected").text();
                var itemId = $("#item_search").val();
                var qty = $.trim($("#qty").val());
                var cp = $.trim($("#pur_rate").val());
                var mrp = $.trim($("#mrp").val());
                var sp = $.trim($("#sp").val());
                var gst = $.trim($("#gst").val());

                if (!itemId || itemId === "") {
                    alert("⚠️ Please select an item first.");
                    return;
                }
                if (!qty || parseFloat(qty) <= 0) {
                    alert("⚠️ Please enter a valid quantity.");
                    return;
                }

                var row = '<tr data-item-id="' + itemId + '">' +
                    '<td class="text-center">' + slCount++ + '</td>' +
                    '<td>' + barcode + '</td>' +
                    '<td>' + itemText + '</td>' +
                    '<td>' + qty + '</td>' +
                    '<td>' + (cp || 0) + '</td>' +
                    '<td>' + (mrp || 0) + '</td>' +
                    '<td>' + (sp || 0) + '</td>' +
                    '<td class="text-center">' + (gst || 0) + '%</td>' +
                    '<td class="text-center"><button class="btn btn-danger btn-sm remove-row">✕</button></td>' +
                    '</tr>';

                $("#grn_table tbody").append(row);

                // Reset item fields
                $("#barcode").val("");
                $("#pur_rate, #mrp, #sp, #gst").val("");
                $("#qty").val(1);
                $("#item_search").val(null).trigger("change");
                $("#barcode").focus();
            });

            // ── Remove Row ────────────────────────────────────────────────────
            $(document).on("click", ".remove-row", function() {
                $(this).closest("tr").remove();
                // Re-number rows
                $("#grn_table tbody tr").each(function(i) {
                    $(this).find("td:first").text(i + 1);
                });
                slCount = $("#grn_table tbody tr").length + 1;
            });

            // ── Save GRN ──────────────────────────────────────────────────────
            $("#save_grn").on('click', function() {
                var rows = [];
                $("#grn_table tbody tr").each(function() {
                    var cells = $(this).find("td");
                    rows.push({
                        barcode: cells.eq(1).text(),
                        item_id: $(this).data("item-id"),
                        item_name: cells.eq(2).text(),
                        qty: cells.eq(3).text(),
                        cp: cells.eq(4).text(),
                        mrp: cells.eq(5).text(),
                        sp: cells.eq(6).text(),
                        gst: cells.eq(7).text()
                    });
                });

                if (rows.length === 0) {
                    alert("⚠️ No items added to GRN.");
                    return;
                }

                var payload = {
                    branch: $("#branch").val(),
                    receipt_date: $("#receipt_date").val(),
                    supplier_id: $("#supplier").val(),
                    invoice_no: $("#invoice_no").val(),
                    bill_date: $("#bill_date").val(),
                    items: rows
                };

                $.ajax({
                    url: "ajax/save_grn.php",
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify(payload),
                    success: function(res) {
                        alert("✅ GRN saved successfully!");
                        $("#grn_table tbody").empty();
                        slCount = 1;
                    },
                    error: function() {
                        alert("❌ Failed to save GRN. Check ajax/save_grn.php");
                    }
                });
            });

        }); // end document.ready
    } // end initPage
</script>

<!-- Camera / Quagga Functions -->
<script>
    var detectedCode = null;

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
                readers: ["code_128_reader", "ean_reader", "upc_reader"]
            }
        }, function(err) {
            if (err) {
                console.error("Quagga init error:", err);
                alert("❌ Could not start camera: " + err);
                return;
            }
            Quagga.start();
        });

        Quagga.onDetected(function(data) {
            detectedCode = data.codeResult.code;
        });
    }

    function captureBarcode() {
        if (detectedCode) {
            $("#barcode").val(detectedCode).trigger("change");
            stopCamera();
        } else {
            alert("No barcode detected yet. Hold steady and try again.");
        }
    }

    function stopCamera() {
        Quagga.stop();
        $("#scanner-container").hide();
        detectedCode = null;
    }
</script>
<?php
require_once "../includes/config.php";
include "../includes/header.php";
?>

<!-- Select2 CSS from cdnjs (reliable) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">

<style>
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
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select class="form-control" id="branch">
                        <option value="SHIVI-ND">SHIVI-ND</option>
                        <option value="SHASHI-ND">SHASHI-ND</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Receipt Date</label>
                    <input type="date" id="receipt_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select id="supplier" style="width:100%">
                        <option></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Invoice No</label>
                    <input type="text" id="invoice_no" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bill Date</label>
                    <input type="date" id="bill_date" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <!-- Item Entry -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Barcode</label>
                    <input type="text" id="barcode" class="form-control" placeholder="Scan or type">
                    <button class="btn btn-outline-primary btn-sm mt-1" onclick="openCamera()">📷 Scan</button>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Item Search</label>
                    <select id="item_search" style="width:100%">
                        <option></option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Qty</label>
                    <input type="number" id="qty" value="1" min="1" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">CP</label>
                    <input type="number" id="pur_rate" class="form-control" step="0.01">
                </div>
                <div class="col-md-1">
                    <label class="form-label">MRP</label>
                    <input type="number" id="mrp" class="form-control" step="0.01">
                </div>
                <div class="col-md-1">
                    <label class="form-label">SP</label>
                    <input type="number" id="sp" class="form-control" step="0.01">
                </div>
                <div class="col-md-1">
                    <label class="form-label">GST%</label>
                    <input type="number" id="gst" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label d-block">&nbsp;</label>
                    <button class="btn btn-primary w-100" id="add_item">➕ Add</button>
                </div>
            </div>

            <div id="scanner-container" class="mt-2">
                <div id="camera"></div>
                <div class="mt-2 text-center">
                    <button class="btn btn-success btn-sm" onclick="captureBarcode()">Capture</button>
                    <button class="btn btn-danger btn-sm" onclick="stopCamera()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- GRN Table -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm" id="grn_table">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Sl</th>
                        <th>Barcode</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>CP</th>
                        <th>MRP</th>
                        <th>SP</th>
                        <th>GST%</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="text-end mt-2">
                <button class="btn btn-success px-4" id="save_grn">💾 Save GRN</button>
            </div>
        </div>
    </div>
</div>

<!--
    SCRIPT LOADING ORDER — very important:
    1. jQuery   → from cdnjs (skip if header.php already loaded it)
    2. Select2  → must come AFTER jQuery
    3. Quagga   → barcode scanner
    4. Page JS  → runs inside $(document).ready()

    All from cdnjs.cloudflare.com which is highly reliable.
-->

<!-- 1. jQuery (cdnjs) — safe to include even if header.php loads it,
        because we check window.jQuery before calling $() -->
<script>
    if (typeof jQuery === 'undefined') {
        document.write('<scr' + 'ipt src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"><\/scr' + 'ipt>');
    }
</script>

<!-- 2. Select2 JS (cdnjs — version 4.0.13, stable) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<!-- 3. Quagga barcode scanner -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

<!-- 4. Page Logic -->
<script>
    $(document).ready(function() {

        // ── Default dates ──────────────────────────────────────────────────────
        var today = new Date().toISOString().split("T")[0];
        $("#receipt_date, #bill_date").val(today);

        // ── Supplier Select2 ───────────────────────────────────────────────────
        $('#supplier').select2({
            placeholder: "Search supplier...",
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: "ajax/search_supplier.php",
                dataType: "json",
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
                cache: true
            }
        });

        // ── Item Search Select2 ────────────────────────────────────────────────
        $('#item_search').select2({
            placeholder: "Search item...",
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: "ajax/search_item.php",
                dataType: "json",
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
                cache: true
            }
        });

        // ── When item picked from dropdown, fill GST if returned ──────────────
        $('#item_search').on('select2:select', function(e) {
            var d = e.params.data;
            if (d.gst !== undefined) {
                $("#gst").val(d.gst);
            }
        });

        // ── Barcode lookup — fires on Enter key press or on field blur ─────────
        $("#barcode").on('keypress', function(e) {
            if (e.which !== 13) return; // only Enter key
            lookupBarcode($.trim($(this).val()));
        });
        $("#barcode").on('blur', function() {
            lookupBarcode($.trim($(this).val()));
        });

        function lookupBarcode(barcode) {
            if (!barcode) return;
            $.getJSON("ajax/get_item_barcode.php", {
                    barcode: barcode,
                    branch_id: $("#branch").val()
                })
                .done(function(data) {
                    if (data && data.item_id) {
                        var opt = new Option(data.item_name, data.item_id, true, true);
                        $('#item_search').empty().append(opt).trigger('change');
                        $("#pur_rate").val(data.cp);
                        $("#mrp").val(data.mrp);
                        $("#sp").val(data.sp);
                        $("#gst").val(data.gst);
                        $("#qty").val(1).focus();
                    } else {
                        alert("⚠️ Item not found for barcode: " + barcode);
                        $("#barcode").val("").focus();
                    }
                })
                .fail(function(xhr) {
                    console.error("Barcode lookup failed:", xhr.responseText);
                    alert("❌ Server error during barcode lookup.");
                });
        }

        // ── Add Item to GRN table ─────────────────────────────────────────────
        var slCount = 1;

        $("#add_item").on('click', function() {
            var barcode = $.trim($("#barcode").val()) || "-";
            var itemId = $("#item_search").val();
            var itemText = $("#item_search option:selected").text();
            var qty = $.trim($("#qty").val());
            var cp = parseFloat($("#pur_rate").val()) || 0;
            var mrp = parseFloat($("#mrp").val()) || 0;
            var sp = parseFloat($("#sp").val()) || 0;
            var gst = parseFloat($("#gst").val()) || 0;

            if (!itemId) {
                alert("⚠️ Please select an item first.");
                return;
            }
            if (!qty || parseFloat(qty) <= 0) {
                alert("⚠️ Please enter a valid quantity.");
                return;
            }

            var row =
                '<tr data-item-id="' + itemId + '">' +
                '<td class="text-center">' + slCount++ + '</td>' +
                '<td>' + barcode + '</td>' +
                '<td>' + itemText + '</td>' +
                '<td class="text-center">' + qty + '</td>' +
                '<td class="text-end">' + cp.toFixed(2) + '</td>' +
                '<td class="text-end">' + mrp.toFixed(2) + '</td>' +
                '<td class="text-end">' + sp.toFixed(2) + '</td>' +
                '<td class="text-center">' + gst + '%</td>' +
                '<td class="text-center"><button class="btn btn-danger btn-sm remove-row">✕</button></td>' +
                '</tr>';

            $("#grn_table tbody").append(row);

            // Reset entry fields for next item
            $("#barcode").val("");
            $("#pur_rate, #mrp, #sp, #gst").val("");
            $("#qty").val(1);
            $("#item_search").val(null).trigger("change");
            $("#barcode").focus();
        });

        // ── Remove row + renumber ─────────────────────────────────────────────
        $(document).on("click", ".remove-row", function() {
            $(this).closest("tr").remove();
            $("#grn_table tbody tr").each(function(i) {
                $(this).find("td:first").text(i + 1);
            });
            slCount = $("#grn_table tbody tr").length + 1;
        });

        // ── Save GRN ──────────────────────────────────────────────────────────
        $("#save_grn").on('click', function() {
            var rows = [];
            $("#grn_table tbody tr").each(function() {
                var c = $(this).find("td");
                rows.push({
                    barcode: c.eq(1).text(),
                    item_id: $(this).data("item-id"),
                    item_name: c.eq(2).text(),
                    qty: c.eq(3).text(),
                    cp: c.eq(4).text(),
                    mrp: c.eq(5).text(),
                    sp: c.eq(6).text(),
                    gst: c.eq(7).text()
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

            $("#save_grn").prop("disabled", true).text("Saving...");

            $.ajax({
                url: "ajax/save_grn.php",
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify(payload),
                success: function() {
                    alert("✅ GRN saved successfully!");
                    $("#grn_table tbody").empty();
                    slCount = 1;
                },
                error: function(xhr) {
                    console.error("Save GRN error:", xhr.responseText);
                    alert("❌ Failed to save GRN. Check ajax/save_grn.php");
                },
                complete: function() {
                    $("#save_grn").prop("disabled", false).html("💾 Save GRN");
                }
            });
        });

    }); // end document.ready
</script>

<!-- Camera / Quagga -->
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
                console.error("Quagga error:", err);
                alert("❌ Camera error: " + err);
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
            $("#barcode").val(detectedCode);
            lookupBarcode(detectedCode);
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
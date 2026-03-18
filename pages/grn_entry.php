<?php
require_once "../includes/config.php";
include "../includes/header.php";
?>

<!-- Select2 CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">

<style>
    #scanner-container {
        max-width: 420px;
        margin-top: 10px;
        display: none;
        border: 2px solid #007bff;
        padding: 10px;
        border-radius: 6px;
        background: #fff;
    }

    #scanner-container video {
        width: 100%;
    }

    .select2-container .select2-selection--single {
        height: 36px !important;
        border: 1px solid #ced4da !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 34px !important;
        font-size: 13px;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 34px !important;
    }

    .form-label {
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .form-control,
    .form-select {
        font-size: 13px;
    }

    #grn_table thead th {
        font-size: 12px;
        white-space: nowrap;
    }

    #grn_table tbody td {
        font-size: 12px;
        vertical-align: middle;
    }

    #totals-bar {
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }

    #totals-bar .tot-box {
        text-align: center;
        padding: 6px 14px;
    }

    #totals-bar .tot-box .tot-label {
        font-size: 11px;
        color: #6c757d;
    }

    #totals-bar .tot-box .tot-val {
        font-size: 15px;
        font-weight: 700;
    }

    /* ── PLU Popup ── */
    #plu-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    #plu-modal-overlay.show {
        display: flex;
    }

    #plu-modal {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, .3);
        width: 560px;
        max-width: 96vw;
        overflow: hidden;
    }

    #plu-modal .modal-hdr {
        background: #343a40;
        color: #fff;
        padding: 10px 16px;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #plu-modal .modal-hdr button {
        background: none;
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
    }

    #plu-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    #plu-table thead tr {
        background: #495057;
        color: #fff;
    }

    #plu-table th,
    #plu-table td {
        padding: 7px 10px;
        border: 1px solid #dee2e6;
        text-align: center;
    }

    #plu-table tbody tr {
        cursor: pointer;
    }

    #plu-table tbody tr:hover,
    #plu-table tbody tr.selected {
        background: #cff4fc;
    }

    #plu-modal .modal-ftr {
        padding: 10px 16px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        text-align: center;
        font-size: 12px;
        color: #6c757d;
    }
</style>

<div class="container-fluid mt-3">
    <h5 class="mb-3">📦 GRN Entry</h5>

    <!-- Header card -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select class="form-control form-control-sm" id="branch">
                        <option value="SHIVI-ND">SHIVI-ND</option>
                        <option value="SHASHI-ND">SHASHI-ND</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Receipt Date</label>
                    <input type="date" id="receipt_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select id="supplier" style="width:100%">
                        <option></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Invoice No</label>
                    <input type="text" id="invoice_no" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bill Date</label>
                    <input type="date" id="bill_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Type</label>
                    <select class="form-control form-control-sm" id="grn_type">
                        <option value="Local">Local</option>
                        <option value="Import">Import</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Item entry card -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Barcode</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="barcode" class="form-control" placeholder="Scan / type">
                        <button class="btn btn-outline-secondary" onclick="openCamera()" title="Open camera">📷</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Item Search</label>
                    <select id="item_search" style="width:100%">
                        <option></option>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Qty</label>
                    <input type="number" id="qty" value="1" min="1" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Cost Price</label>
                    <input type="number" id="pur_rate" class="form-control form-control-sm" step="0.01">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Dis%</label>
                    <input type="number" id="dis_pct" value="0" class="form-control form-control-sm" step="0.01">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Dis Amt</label>
                    <input type="number" id="dis_amt" value="0" class="form-control form-control-sm" step="0.01" readonly>
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">GST%</label>
                    <input type="number" id="gst_pct" value="0" class="form-control form-control-sm" step="0.01">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">GST Amt</label>
                    <input type="number" id="gst_amt" value="0" class="form-control form-control-sm" step="0.01" readonly>
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">MRP</label>
                    <input type="number" id="mrp" class="form-control form-control-sm" step="0.01">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Sale Price</label>
                    <input type="number" id="sp" class="form-control form-control-sm" step="0.01">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Batch</label>
                    <input type="text" id="batch" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label">Exp Date</label>
                    <input type="date" id="exp_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <label class="form-label d-block">&nbsp;</label>
                    <button class="btn btn-primary btn-sm w-100" id="add_item">➕ Add</button>
                </div>
            </div>

            <!-- Net Rate / Margin display -->
            <div class="d-flex gap-3 mt-2">
                <span class="badge bg-secondary fs-6">NET RATE: <strong id="lbl_net_rate">0.00</strong></span>
                <span class="badge bg-info text-dark fs-6">MARGIN%: <strong id="lbl_margin">0.00</strong></span>
            </div>

            <!-- Camera -->
            <div id="scanner-container" class="mt-2">
                <div id="camera"></div>
                <div class="mt-2 text-center">
                    <button class="btn btn-success btn-sm me-1" onclick="captureBarcode()">✅ Capture</button>
                    <button class="btn btn-danger btn-sm" onclick="stopCamera()">✕ Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- GRN Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="grn_table">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Sl</th>
                            <th>Barcode</th>
                            <th>PLU</th>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Cost Price</th>
                            <th>Dis%</th>
                            <th>Dis Amt</th>
                            <th>GST%</th>
                            <th>GST Amt</th>
                            <th>MRP</th>
                            <th>Sale Price</th>
                            <th>Net Amt</th>
                            <th>Batch</th>
                            <th>Exp Date</th>
                            <th>✕</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <!-- Totals -->
            <div id="totals-bar" class="d-flex flex-wrap justify-content-end px-3 py-2 gap-4">
                <div class="tot-box">
                    <div class="tot-label">Items</div>
                    <div class="tot-val" id="tot_items">0</div>
                </div>
                <div class="tot-box">
                    <div class="tot-label">Total Qty</div>
                    <div class="tot-val" id="tot_qty">0</div>
                </div>
                <div class="tot-box">
                    <div class="tot-label">Total GST</div>
                    <div class="tot-val" id="tot_gst">0.00</div>
                </div>
                <div class="tot-box">
                    <div class="tot-label">Grand Total</div>
                    <div class="tot-val text-success" id="tot_grand">0.00</div>
                </div>
                <div class="tot-box align-self-center">
                    <button class="btn btn-success px-4" id="save_grn">💾 Save GRN</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     PLU SELECTION POPUP
     Shows when barcode has multiple PLUs
     ══════════════════════════════════════ -->
<div id="plu-modal-overlay">
    <div id="plu-modal">
        <div class="modal-hdr">
            <span id="plu-modal-title">Select PLU</span>
            <button onclick="closePluModal()" title="Close">✕</button>
        </div>
        <div style="padding:12px;">
            <table id="plu-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>PLU</th>
                        <th>Barcode</th>
                        <th>Cost Price</th>
                        <th>MRP</th>
                        <th>Sale Price</th>
                    </tr>
                </thead>
                <tbody id="plu-table-body"></tbody>
            </table>
        </div>
        <div class="modal-ftr">Double-click a row or select and press <kbd>Enter</kbd> to confirm</div>
    </div>
</div>

<!-- Scripts -->
<script>
    if (typeof jQuery === 'undefined') {
        document.write('<scr' + 'ipt src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"><\/scr' + 'ipt>');
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

<script>
    $(document).ready(function() {

        /* ── Default dates ─────────────────────────────────────────── */
        var today = new Date().toISOString().split("T")[0];
        $("#receipt_date, #bill_date").val(today);

        /* ── Supplier Select2 ──────────────────────────────────────── */
        $('#supplier').select2({
            placeholder: "Search supplier...",
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: "ajax/search_supplier.php",
                dataType: "json",
                delay: 300,
                data: function(p) {
                    return {
                        term: p.term,
                        branch_id: $("#branch").val()
                    };
                },
                processResults: function(d) {
                    return {
                        results: d
                    };
                },
                cache: true
            }
        });

        /* ── Item Search Select2 ───────────────────────────────────── */
        $('#item_search').select2({
            placeholder: "Type to search item...",
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: "ajax/search_item.php",
                dataType: "json",
                delay: 300,
                data: function(p) {
                    return {
                        term: p.term,
                        branch_id: $("#branch").val()
                    };
                },
                processResults: function(d) {
                    return {
                        results: d
                    };
                },
                cache: true
            }
        });

        /* When item picked from dropdown → autofill prices */
        $('#item_search').on('select2:select', function(e) {
            var d = e.params.data;
            if (d.cp !== undefined) $("#pur_rate").val(d.cp);
            if (d.mrp !== undefined) $("#mrp").val(d.mrp);
            if (d.sp !== undefined) $("#sp").val(d.sp);
            if (d.gst !== undefined) $("#gst_pct").val(d.gst);
            // clear barcode/PLU since user searched by name
            $("#barcode").val("");
            recalcEntry();
            $("#qty").focus();
        });

        /* ── Recalc dis/gst/net in entry form ──────────────────────── */
        function recalcEntry() {
            var cp = parseFloat($("#pur_rate").val()) || 0;
            var qty = parseFloat($("#qty").val()) || 1;
            var disPct = parseFloat($("#dis_pct").val()) || 0;
            var gstPct = parseFloat($("#gst_pct").val()) || 0;
            var mrp = parseFloat($("#mrp").val()) || 0;

            var disAmt = +((cp * qty * disPct / 100).toFixed(2));
            var taxable = (cp * qty) - disAmt;
            var gstAmt = +((taxable * gstPct / 100).toFixed(2));
            var netAmt = +(taxable + gstAmt).toFixed(2);
            var netRate = qty > 0 ? +(netAmt / qty).toFixed(2) : 0;
            var margin = mrp > 0 ? +(((mrp - netRate) / mrp) * 100).toFixed(2) : 0;

            $("#dis_amt").val(disAmt);
            $("#gst_amt").val(gstAmt);
            $("#lbl_net_rate").text(netRate.toFixed(2));
            $("#lbl_margin").text(margin.toFixed(2));
        }
        $("#pur_rate,#qty,#dis_pct,#gst_pct,#mrp,#sp").on('input change', recalcEntry);

        /* ════════════════════════════════════════════════════════════
           BARCODE LOOKUP + PLU POPUP
           ════════════════════════════════════════════════════════════ */
        $("#barcode").on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                lookupBarcode($.trim($(this).val()));
            }
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
                    if (!data || !data.item_id) {
                        alert("⚠️ Item not found for barcode: " + barcode);
                        $("#barcode").val("").focus();
                        return;
                    }

                    if (data.plus.length === 1) {
                        // Only one PLU → fill directly, no popup
                        fillFromPlu(data, data.plus[0]);
                    } else {
                        // Multiple PLUs → show selection popup
                        openPluModal(data);
                    }
                })
                .fail(function(xhr) {
                    console.error("Barcode error:", xhr.responseText);
                    alert("❌ Server error on barcode lookup.");
                });
        }

        /* ── Fill entry fields from selected PLU ───────────────────── */
        function fillFromPlu(itemData, plu) {
            var opt = new Option(itemData.item_name, itemData.item_id, true, true);
            $('#item_search').empty().append(opt).trigger('change');
            $("#barcode").val(plu.bar_code);
            $("#pur_rate").val(plu.cp);
            $("#mrp").val(plu.mrp);
            $("#sp").val(plu.sp);
            $("#gst_pct").val(itemData.gst);
            recalcEntry();
            $("#qty").val(1).focus();
            // store selected PLU code for table row
            $("#barcode").data("plu", plu.plu);
        }

        /* ════════════════════════════════════════════════════════════
           PLU MODAL
           ════════════════════════════════════════════════════════════ */
        var _pendingItemData = null;

        function openPluModal(data) {
            _pendingItemData = data;
            $("#plu-modal-title").text("Select PLU — " + data.item_name + " (" + data.plus.length + " variants)");

            var tbody = $("#plu-table-body").empty();
            $.each(data.plus, function(i, p) {
                var tr = $('<tr>')
                    .attr('data-idx', i)
                    .html(
                        '<td>' + (i + 1) + '</td>' +
                        '<td><strong>' + p.plu + '</strong></td>' +
                        '<td>' + p.bar_code + '</td>' +
                        '<td class="text-end">' + p.cp.toFixed(2) + '</td>' +
                        '<td class="text-end">' + p.mrp.toFixed(2) + '</td>' +
                        '<td class="text-end">' + p.sp.toFixed(2) + '</td>'
                    );
                tbody.append(tr);
            });

            // Click to highlight
            $("#plu-table-body tr").on('click', function() {
                $("#plu-table-body tr").removeClass('selected');
                $(this).addClass('selected');
            });

            // Double-click to select
            $("#plu-table-body tr").on('dblclick', function() {
                confirmPluSelection($(this).data('idx'));
            });

            // Auto-select first row
            $("#plu-table-body tr:first").addClass('selected');

            $("#plu-modal-overlay").addClass('show');
        }

        function confirmPluSelection(idx) {
            if (_pendingItemData) {
                fillFromPlu(_pendingItemData, _pendingItemData.plus[idx]);
            }
            closePluModal();
        }

        window.closePluModal = function() {
            $("#plu-modal-overlay").removeClass('show');
            _pendingItemData = null;
        };

        // Enter key inside PLU table confirms selected row
        $(document).on('keypress', function(e) {
            if (e.which === 13 && $("#plu-modal-overlay").hasClass('show')) {
                var idx = parseInt($("#plu-table-body tr.selected").data('idx'));
                if (!isNaN(idx)) confirmPluSelection(idx);
            }
        });

        // Click outside modal to close
        $("#plu-modal-overlay").on('click', function(e) {
            if ($(e.target).is('#plu-modal-overlay')) closePluModal();
        });

        /* ── Add item to GRN table ─────────────────────────────────── */
        var slCount = 1;

        $("#add_item").on('click', function() {
            var barcode = $.trim($("#barcode").val()) || "-";
            var plu = $("#barcode").data("plu") || "-";
            var itemId = $("#item_search").val();
            var itemText = $("#item_search option:selected").text();
            var qty = parseFloat($("#qty").val()) || 0;
            var cp = parseFloat($("#pur_rate").val()) || 0;
            var disPct = parseFloat($("#dis_pct").val()) || 0;
            var disAmt = parseFloat($("#dis_amt").val()) || 0;
            var gstPct = parseFloat($("#gst_pct").val()) || 0;
            var gstAmt = parseFloat($("#gst_amt").val()) || 0;
            var mrp = parseFloat($("#mrp").val()) || 0;
            var sp = parseFloat($("#sp").val()) || 0;
            var batch = $.trim($("#batch").val()) || "-";
            var expDate = $("#exp_date").val() || "-";
            var taxable = (cp * qty) - disAmt;
            var netAmt = +(taxable + gstAmt).toFixed(2);

            if (!itemId) {
                alert("⚠️ Please select an item.");
                return;
            }
            if (qty <= 0) {
                alert("⚠️ Please enter a valid quantity.");
                return;
            }

            var row =
                '<tr data-item-id="' + itemId + '">' +
                '<td class="text-center">' + slCount++ + '</td>' +
                '<td>' + barcode + '</td>' +
                '<td>' + plu + '</td>' +
                '<td>' + itemText + '</td>' +
                '<td class="text-center">' + qty + '</td>' +
                '<td class="text-end">' + cp.toFixed(2) + '</td>' +
                '<td class="text-center">' + disPct + '%</td>' +
                '<td class="text-end">' + disAmt.toFixed(2) + '</td>' +
                '<td class="text-center">' + gstPct + '%</td>' +
                '<td class="text-end">' + gstAmt.toFixed(2) + '</td>' +
                '<td class="text-end">' + mrp.toFixed(2) + '</td>' +
                '<td class="text-end">' + sp.toFixed(2) + '</td>' +
                '<td class="text-end fw-bold">' + netAmt.toFixed(2) + '</td>' +
                '<td>' + batch + '</td>' +
                '<td>' + expDate + '</td>' +
                '<td class="text-center"><button class="btn btn-danger btn-sm remove-row px-2 py-0">✕</button></td>' +
                '</tr>';

            $("#grn_table tbody").append(row);
            updateTotals();
            resetEntryFields();
            $("#barcode").focus();
        });

        /* ── Remove row ────────────────────────────────────────────── */
        $(document).on("click", ".remove-row", function() {
            $(this).closest("tr").remove();
            renumber();
            updateTotals();
        });

        function renumber() {
            slCount = 1;
            $("#grn_table tbody tr").each(function() {
                $(this).find("td:first").text(slCount++);
            });
        }

        function updateTotals() {
            var items = 0,
                qty = 0,
                gst = 0,
                grand = 0;
            $("#grn_table tbody tr").each(function() {
                var c = $(this).find("td");
                items++;
                qty += parseFloat(c.eq(4).text()) || 0;
                gst += parseFloat(c.eq(9).text()) || 0;
                grand += parseFloat(c.eq(12).text()) || 0;
            });
            $("#tot_items").text(items);
            $("#tot_qty").text(qty);
            $("#tot_gst").text(gst.toFixed(2));
            $("#tot_grand").text(grand.toFixed(2));
        }

        function resetEntryFields() {
            $("#barcode").val("").removeData("plu");
            $("#pur_rate,#mrp,#sp,#batch,#exp_date").val("");
            $("#dis_pct,#dis_amt,#gst_pct,#gst_amt").val("0");
            $("#qty").val(1);
            $("#item_search").val(null).trigger("change");
            $("#lbl_net_rate").text("0.00");
            $("#lbl_margin").text("0.00");
        }

        /* ── Save GRN ──────────────────────────────────────────────── */
        $("#save_grn").on('click', function() {
            if (!$("#supplier").val()) {
                alert("⚠️ Select a supplier.");
                return;
            }
            if (!$("#invoice_no").val()) {
                alert("⚠️ Enter Invoice No.");
                return;
            }

            var rows = [];
            $("#grn_table tbody tr").each(function() {
                var c = $(this).find("td");
                rows.push({
                    barcode: c.eq(1).text(),
                    plu: c.eq(2).text(),
                    item_id: $(this).data("item-id"),
                    item_name: c.eq(3).text(),
                    qty: c.eq(4).text(),
                    cp: c.eq(5).text(),
                    dis_pct: c.eq(6).text(),
                    dis_amt: c.eq(7).text(),
                    gst_pct: c.eq(8).text(),
                    gst_amt: c.eq(9).text(),
                    mrp: c.eq(10).text(),
                    sp: c.eq(11).text(),
                    net_amt: c.eq(12).text(),
                    batch: c.eq(13).text(),
                    exp_date: c.eq(14).text()
                });
            });

            if (rows.length === 0) {
                alert("⚠️ No items in GRN.");
                return;
            }

            var payload = {
                branch: $("#branch").val(),
                grn_type: $("#grn_type").val(),
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
                    alert("✅ GRN saved!");
                    $("#grn_table tbody").empty();
                    slCount = 1;
                    updateTotals();
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert("❌ Failed to save GRN.");
                },
                complete: function() {
                    $("#save_grn").prop("disabled", false).html("💾 Save GRN");
                }
            });
        });

    }); // end ready
</script>

<!-- Camera -->
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
            stopCamera();
            $("#barcode").val(detectedCode);
            // trigger lookup
            var ev = jQuery.Event("keypress");
            ev.which = 13;
            $("#barcode").trigger(ev);
        } else {
            alert("No barcode detected yet. Hold steady.");
        }
    }

    function stopCamera() {
        Quagga.stop();
        $("#scanner-container").hide();
        detectedCode = null;
    }
</script>
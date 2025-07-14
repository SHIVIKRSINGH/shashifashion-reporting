from flask import Flask, request, jsonify
import mysql.connector
import traceback

print("=== Running sync_invoice.py ===")

app = Flask(__name__)

def get_conn():
    return mysql.connector.connect(
        host="127.0.0.1",
        user="shivendra",
        password="24199319@Shiv",
        database="softgen_db"
    )

def insert(cursor, table, row):
    if row:
        keys = ','.join(row)
        vals = ','.join(['%s'] * len(row))
        sql = f"REPLACE INTO {table} ({keys}) VALUES ({vals})"
        cursor.execute(sql, list(row.values()))

@app.route("/sync_invoice", methods=["POST"])
def sync_invoice():
    try:
        data = request.get_json()
        sync_type = data.get("type")
        hdr = data.get("hdr")
        det = data.get("det", [])
        pay = data.get("pay", [])

        if not hdr or not sync_type:
            return jsonify({"status": "error", "msg": "Missing hdr or type"}), 400

        conn = get_conn()
        cursor = conn.cursor()

        if sync_type == "invoice":
            insert(cursor, "t_invoice_hdr", hdr)
            for d in det:
                insert(cursor, "t_invoice_det", d)
            for p in pay:
                insert(cursor, "t_invoice_pay_det", p)

        elif sync_type == "sale_return":
            insert(cursor, "t_sr_hdr", hdr)
            for d in det:
                insert(cursor, "t_sr_det", d)
            for p in pay:
                insert(cursor, "t_sr_pay_det", p)

        else:
            return jsonify({"status": "error", "msg": f"Unsupported type: {sync_type}"}), 400

        conn.commit()
        return jsonify({"status": "success"}), 200

    except Exception as e:
        traceback.print_exc()
        return jsonify({"status": "error", "msg": str(e)}), 500

    finally:
        if 'cursor' in locals(): cursor.close()
        if 'conn' in locals(): conn.close()

if __name__ == "__main__":
    print("=== Running sync_invoice.py ===")
    app.run(host="127.0.0.1", port=5051)

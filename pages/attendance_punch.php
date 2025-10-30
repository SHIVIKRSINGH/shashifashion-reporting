<?php
require_once __DIR__ . '/../includes/config.php';

// Ensure employee is logged in
if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit;
}

// Employee data
$emp_id    = $_SESSION['emp_id'];
$emp_name  = $_SESSION['emp_name'];
$branch_id = $_SESSION['branch_id'];

// Get today's record if any
$today = date('Y-m-d');
$stmt = $con->prepare("SELECT * FROM attendance WHERE emp_id=? AND att_date=?");
$stmt->bind_param("is", $emp_id, $today);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();

// Office location — your shop
$OFFICE_LAT = 28.61588;
$OFFICE_LON = 77.42187;
$ALLOWED_RADIUS_KM = 0.2; // 200 m
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance Punch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f8f8f8;
        }

        .container {
            max-width: 450px;
            margin-top: 60px;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
        }

        .btn-punch {
            font-size: 18px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .status-box {
            background: #fafafa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card p-4">
            <h4 class="text-center mb-3">Hi, <?= htmlspecialchars($emp_name) ?></h4>
            <p class="text-center text-muted">Mark your attendance below</p>

            <div class="status-box">
                <strong>Date:</strong> <?= date('d M Y') ?><br>
                <strong>Status:</strong>
                <?php
                if (!$attendance) echo "<span class='text-secondary'>Not marked yet</span>";
                else {
                    if ($attendance['clock_out']) echo "<span class='text-success'>Day Completed</span>";
                    elseif ($attendance['clock_in']) echo "<span class='text-warning'>Working</span>";
                }
                ?>
            </div>

            <div class="mt-4">
                <button id="btnIn" class="btn btn-success w-100 btn-punch" onclick="markAttendance('clock_in')">Punch In</button>
                <button id="btnLunchOut" class="btn btn-warning w-100 btn-punch" onclick="markAttendance('lunch_out')">Lunch Out</button>
                <button id="btnLunchIn" class="btn btn-info w-100 btn-punch" onclick="markAttendance('lunch_in')">Lunch In</button>
                <button id="btnOut" class="btn btn-danger w-100 btn-punch" onclick="markAttendance('clock_out')">Punch Out</button>
            </div>

            <div id="statusMsg" class="mt-3 text-center text-primary"></div>

            <a href="logout.php" class="btn btn-link w-100 mt-3">Logout</a>
        </div>
    </div>

    <script>
        const officeLat = <?= $OFFICE_LAT ?>;
        const officeLon = <?= $OFFICE_LON ?>;
        const allowedRadiusKm = <?= $ALLOWED_RADIUS_KM ?>;

        function markAttendance(type) {
            if (!navigator.geolocation) {
                alert("Geolocation is not supported.");
                return;
            }
            document.getElementById('statusMsg').innerText = "Fetching location...";
            navigator.geolocation.getCurrentPosition(success, error);

            function success(pos) {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;

                const distance = getDistanceKm(lat, lon, officeLat, officeLon);
                if (distance > allowedRadiusKm) {
                    document.getElementById('statusMsg').innerHTML =
                        `<span class='text-danger'>You are outside the 200m radius (${distance.toFixed(3)} km)</span>`;
                    return;
                }

                // AJAX call
                fetch('attendance_submit.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `type=${type}&lat=${lat}&lon=${lon}`
                    })
                    .then(r => r.text())
                    .then(data => {
                        document.getElementById('statusMsg').innerHTML = data;
                        setTimeout(() => location.reload(), 2000);
                    })
                    .catch(e => alert("Error submitting attendance."));
            }

            function error() {
                alert("Unable to fetch your location. Please allow location access.");
            }
        }

        function getDistanceKm(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a =
                Math.sin(dLat / 2) ** 2 +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }
    </script>
</body>

</html>
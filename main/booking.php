<?php
session_start();
include('../config/connect.php');

// Check if user is logged in
$must_login = !isset($_SESSION['username']);
if (!isset($_GET['table_id']) && !$must_login) {
    header("location: table.php");
    exit();
}

$table = null;
if (!$must_login) {
    $table_id = mysqli_real_escape_string($conn, $_GET['table_id']);
    $sql_table = "SELECT t.*, ty.type_name, ty.min_customer, ty.max_customer 
                  FROM table_info t 
                  JOIN type_table ty ON t.type_table = ty.type_table 
                  WHERE t.table_id = '$table_id'";
    $res_table = mysqli_query($conn, $sql_table);
    $table = mysqli_fetch_assoc($res_table);
    
    if (!$table) {
        header("location: table.php");
        exit();
    }
}

// Calculate allowed date range (Max 3 days advance)
$min_date = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+3 days'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Booking</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/booking.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <?php if ($must_login): ?>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Please Log In',
            text: 'You need to be logged in to reserve a table.',
            confirmButtonColor: '#9B6E60',
            confirmButtonText: 'Go to Login',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../include/login.php';
            }
        });
    </script>
    <?php exit(); endif; ?>

    <div class="booking-wrapper">
        <div class="booking-card">
            <div class="card-header">
                <h2>Booking Details</h2>
                <p>Selected Table: <span class="table-name"><?php echo htmlspecialchars($table['table_name']); ?></span></p>
            </div>

            <form action="../config/save_booking.php" method="POST" class="booking-form">
                <input type="hidden" name="table_id" value="<?php echo $table['table_id']; ?>">

                <div class="table-spec-box">
                    <div class="spec-item">
                        <strong>Table Size:</strong> 
                        <span><?php echo htmlspecialchars($table['type_name']); ?></span>
                    </div>
                    <div class="spec-item">
                        <strong>Capacity:</strong> 
                        <span><?php echo $table['min_customer']; ?> - <?php echo $table['max_customer']; ?> People</span>
                    </div>
                    <p class="table-desc">Zone/Details: <?php echo htmlspecialchars($table['table_name_th']); ?></p>
                </div>

                <div class="form-group">
                    <label>Phone Number (10 digits):</label>
                    <input type="text" name="phone_no" maxlength="10" pattern="\d{10}" placeholder="e.g. 0812345678" required>
                </div>

                <div class="form-row">
                    <div class="form-group" style="position: relative;">
                        <label>Reservation Date:</label>
                        <input type="text" id="rent_date" name="rent_date" placeholder="Select Date" readonly style="cursor: pointer; background-color: var(--white);" required>
                        
                        <div id="custom_calendar" class="calendar-popup" style="display: none;">
                            <div class="calendar-header">
                                <button type="button" class="calendar-btn" id="prev_month">&lt;</button>
                                <span id="month_year_display"></span>
                                <button type="button" class="calendar-btn" id="next_month">&gt;</button>
                            </div>
                            <div class="calendar-grid" id="calendar_days"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Reservation Time:</label>
                        <select name="rent_time" required>
                            <option value="">-- Select Time --</option>
                            <?php
                                $start = 10;
                                $end = 21;
                                for ($h = $start; $h <= $end; $h++) {
                                    $time_val = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00";
                                    $display_time = date("g:i A", strtotime($time_val));
                                    echo "<option value='$time_val'>$display_time</option>";
                                    if($h != $end) {
                                        $time_half = str_pad($h, 2, '0', STR_PAD_LEFT) . ":30";
                                        $display_half = date("g:i A", strtotime($time_half));
                                        echo "<option value='$time_half'>$display_half</option>";
                                    }
                                }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Party Size:</label>
                        <select name="num_players" required>
                            <option value="">-- Players --</option>
                            <?php 
                            for ($i = $table['min_customer']; $i <= $table['max_customer']; $i++) {
                                echo "<option value='$i'>$i " . ($i > 1 ? "People" : "Person") . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="booking-policy">
                    <p>📌 <strong>Policy:</strong> Advanced booking up to 3 days / Max 3 online bookings per day / Cannot book the same table twice until paid.</p>
                    <label class="checkbox-container">
                        <input type="checkbox" required> I accept the terms and safety policies.
                    </label>
                </div>

                <button type="submit" class="btn-confirm">Confirm Booking</button>
                <a href="table.php" class="btn-back">Change Table</a>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['booking_status']) && $_SESSION['booking_status'] == 'error'): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Transaction Failed',
                text: '<?php echo $_SESSION['booking_msg']; ?>',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Got it, try again'
            });
        </script>
        <?php 
            unset($_SESSION['booking_status']);
            unset($_SESSION['booking_msg']); 
        ?>
    <?php endif; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const dateInput = document.getElementById('rent_date');
        const calendarPopup = document.getElementById('custom_calendar');
        const daysContainer = document.getElementById('calendar_days');
        const monthYearDisplay = document.getElementById('month_year_display');
        
        // PHP Dates to JS
        const minDateStr = "<?php echo $min_date; ?>";
        const maxDateStr = "<?php echo $max_date; ?>";
        
        let currentDate = new Date(minDateStr);
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();

        // Toggle calendar
        dateInput.addEventListener('click', function(e) {
            calendarPopup.style.display = calendarPopup.style.display === 'block' ? 'none' : 'block';
            renderCalendar(currentMonth, currentYear);
            e.stopPropagation();
        });

        // Close when clicked outside
        document.addEventListener('click', function(e) {
            if (!calendarPopup.contains(e.target) && e.target !== dateInput) {
                calendarPopup.style.display = 'none';
            }
        });

        document.getElementById('prev_month').addEventListener('click', () => {
            currentMonth--;
            if(currentMonth < 0) { currentMonth = 11; currentYear--; }
            renderCalendar(currentMonth, currentYear);
        });

        document.getElementById('next_month').addEventListener('click', () => {
            currentMonth++;
            if(currentMonth > 11) { currentMonth = 0; currentYear++; }
            renderCalendar(currentMonth, currentYear);
        });

        function renderCalendar(month, year) {
            daysContainer.innerHTML = '';
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            monthYearDisplay.textContent = `${monthNames[month]} ${year}`;

            const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayNames.forEach(day => {
                let div = document.createElement('div');
                div.className = 'calendar-day-header';
                div.textContent = day;
                daysContainer.appendChild(div);
            });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) {
                daysContainer.appendChild(document.createElement('div'));
            }

            for (let i = 1; i <= daysInMonth; i++) {
                let dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                let dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                
                let statusText = 'Available';
                
                // Allow only dates within min-max range
                if (dateStr < minDateStr || dateStr > maxDateStr) {
                    dayDiv.classList.add('disabled');
                    statusText = '-';
                } else {
                    dayDiv.addEventListener('click', function() {
                        dateInput.value = dateStr;
                        calendarPopup.style.display = 'none';
                    });
                }

                dayDiv.innerHTML = `<span>${i}</span><span class="day-status">${statusText}</span>`;
                daysContainer.appendChild(dayDiv);
            }
        }
    });
    </script>
</body>
</html>
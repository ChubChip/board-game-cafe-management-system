<?php
session_start();
include('../config/connect.php'); 

// ---------------------------------------------------------
// ตรวจสอบ Email ผ่าน AJAX
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'check_email') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $sql = "SELECT customer_id, username FROM customer WHERE email = '$email'";
    $res = mysqli_query($conn, $sql);
    
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo json_encode(['status' => 'found', 'customer_id' => $row['customer_id'], 'username' => $row['username']]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
    exit();
}
// ---------------------------------------------------------
// Pre-calculate receipt ID (rent_id) for display
// ---------------------------------------------------------
$today_prefix = date("Ymd");

$sql_check = "SELECT COUNT(*) as total FROM rental WHERE rent_id LIKE '$today_prefix%'";
$res_check = mysqli_query($conn, $sql_check);
$row_check = mysqli_fetch_assoc($res_check);

$next_number = $row_check['total'] + 1;

$expected_rent_id = $today_prefix . str_pad($next_number, 3, '0', STR_PAD_LEFT);


// ---------------------------------------------------------
// Handle Checkout Form Submission
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'checkout') {
    
    $cust_type = $_POST['cust_type']; 
    $actual_date = mysqli_real_escape_string($conn, $_POST['actual_date']); 
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    $pay_method = mysqli_real_escape_string($conn, $_POST['pay_method']);
    $pay_time = date('Y-m-d H:i:s');
    
    $played_games = isset($_POST['played_games']) ? $_POST['played_games'] : [];

    // ----- Handle overnight duration (e.g., 23:00 to 01:00) -----
    $end_date_str = $actual_date;
    if (strtotime($end_time) < strtotime($start_time)) {
        $end_date_str = date('Y-m-d', strtotime($actual_date . ' + 1 day'));
    }
    
    $start_dt = $actual_date . ' ' . $start_time . ':00';
    $end_dt = $end_date_str . ' ' . $end_time . ':00';

    mysqli_begin_transaction($conn);
    try {
        $target_rent_id = null;

        if ($cust_type == 'reserve') {
            // ----- Booking Type -----
            $rent_id = mysqli_real_escape_string($conn, $_POST['rent_id']);
            $target_rent_id = $rent_id; 

            mysqli_query($conn, "UPDATE rental SET total_amount = '$total_amount', status = 'Paid' WHERE rent_id = '$rent_id'");
            mysqli_query($conn, "UPDATE rental_detail SET start_time = '$start_dt', end_time = '$end_dt', duration = TIMEDIFF('$end_dt', '$start_dt') WHERE rent_id = '$rent_id'");
            mysqli_query($conn, "INSERT INTO payment (rent_id, pay_time, amount, pay_method) VALUES ('$rent_id', '$pay_time', '$total_amount', '$pay_method')");

        } else {
            // ----- Walk-in Type -----
            $table_id = mysqli_real_escape_string($conn, $_POST['walkin_table']);
            $num_players = mysqli_real_escape_string($conn, $_POST['walkin_pax']); 
            $walkin_customer_id = !empty($_POST['walkin_customer_id']) ? "'" . mysqli_real_escape_string($conn, $_POST['walkin_customer_id']) . "'" : "NULL";
            
            $rent_date = $actual_date; 

            $date_prefix = date('Ymd', strtotime($actual_date));
            $sql_last = "SELECT rent_id FROM rental WHERE rent_id LIKE '$date_prefix%' ORDER BY rent_id DESC LIMIT 1";
            $res_last = mysqli_query($conn, $sql_last);
            
            if (mysqli_num_rows($res_last) > 0) {
                $row_l = mysqli_fetch_assoc($res_last);
                $l_id = $row_l['rent_id'];
                $l_num = intval(substr($l_id, 8)); 
                $n_num = $l_num + 1;
            } else {
                $n_num = 1; 
            }
            
            $new_rent_id = $today_prefix . str_pad($next_number, 3, '0', STR_PAD_LEFT);
            $target_rent_id = $new_rent_id;

            mysqli_query($conn, "INSERT INTO rental (rent_id, customer_id, rent_date, rent_time, num_players, booking_type, total_amount, status) VALUES ('$new_rent_id', $walkin_customer_id, '$rent_date', '$start_time', '$num_players', 'Walk-in', '$total_amount', 'Paid')");
            mysqli_query($conn, "INSERT INTO rental_detail (rent_id, table_id, start_time, end_time, duration) VALUES ('$new_rent_id', '$table_id', '$start_dt', '$end_dt', TIMEDIFF('$end_dt', '$start_dt'))");
            mysqli_query($conn, "INSERT INTO payment (rent_id, pay_time, amount, pay_method) VALUES ('$new_rent_id', '$pay_time', '$total_amount', '$pay_method')");
        }

        // Insert played board games if any
        if (!empty($played_games) && $target_rent_id != null) {
            foreach ($played_games as $bg_id) {
                $bg_id = mysqli_real_escape_string($conn, $bg_id);
                mysqli_query($conn, "INSERT INTO rental_boardgame (rent_id, boardgame_id) VALUES ('$target_rent_id', '$bg_id')");
            }
        }

        mysqli_commit($conn);
        $_SESSION['success'] = "Checkout and data recording completed successfully! Receipt ID: " . $target_rent_id;
        header("Location: admin_checkout.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error occurred: " . mysqli_error($conn) . "');</script>";
    }
}

// ---------------------------------------------------------
// Load data for Form usage
// ---------------------------------------------------------
$tables = [];
$sql_tables = "SELECT t.table_id, t.table_name, tt.min_customer, tt.max_customer 
               FROM table_info t 
               JOIN type_table tt ON t.type_table = tt.type_table";
$res_tables = mysqli_query($conn, $sql_tables);
while($row = mysqli_fetch_assoc($res_tables)) { $tables[] = $row; }

$boardgames = [];
$res_bg = mysqli_query($conn, "SELECT boardgame_id, boardgame_name FROM boardgame");
while($row = mysqli_fetch_assoc($res_bg)) { $boardgames[] = $row; }

$unpaid_bookings = [];
$sql_unpaid = "SELECT r.rent_id, r.rent_date, r.rent_time, r.num_players, rd.table_id, c.username 
               FROM rental r JOIN rental_detail rd ON r.rent_id = rd.rent_id JOIN customer c ON r.customer_id = c.customer_id 
               WHERE r.status = 'Unpaid' AND r.booking_type = 'Online'";
$res_unpaid = mysqli_query($conn, $sql_unpaid);
while($row = mysqli_fetch_assoc($res_unpaid)) {
    $row['rent_time'] = date('H:i', strtotime($row['rent_time']));
    $unpaid_bookings[] = $row;
}

$current_time = date('H:i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Table-Talk Checkout</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/admin.css">
    <link rel="stylesheet" href="../style/back.css">
</head>

<body>


    <div class="container">
        <button onclick="window.location.href='index.php'" class="back-btn">← Back</button>
        <h1>🧾 Proceed to Payment</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <script>Swal.fire({ icon: 'success', title: 'Success', text: '<?php echo $_SESSION['success']; ?>', confirmButtonColor: '#576d5c' });</script>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form id="checkoutForm" method="POST" action="admin_checkout.php">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" id="h_total_amount" name="total_amount" value="0">
            <input type="hidden" id="walkin_customer_id" name="walkin_customer_id" value="">

            <div class="radio-group">
                <label style="margin-bottom:0; font-size: 18px;">Customer Type:</label>
                <label><input type="radio" name="cust_type" value="reserve" checked onchange="toggleForm()"> Booking</label>
                <label><input type="radio" name="cust_type" value="walkin" onchange="toggleForm()"> Walk-in</label>
            </div>

            <div id="section_reserve" class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label>Search by booking date:</label>
                        <input type="date" id="search_date" value="<?php echo date('Y-m-d'); ?>" onchange="filterCustomers()">
                    </div>
                    <div class="form-group">
                        <label>Select table (booked):</label>
                        <select id="search_table" onchange="filterCustomers()">
                            <option value="">-- Select table --</option>
                            <?php foreach($tables as $t): ?>
                                <option value="<?php echo $t['table_id']; ?>"><?php echo $t['table_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Bookings:</label>
                        <select id="rent_id" name="rent_id" onchange="fillReserveData()">
                            <option value="">-- Please select date and table first --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Booking Time:</label>
                        <input type="time" id="disp_rent_time" readonly>
                    </div>
                </div>
            </div>

            <div id="section_walkin" class="form-section" style="display: none;">
                <div class="running-number-box">
                    <label style="margin: 0; color: #1976d2; font-weight: bold; font-size: 1.1em;">
                        Receipt ID (Auto-run): <span id="disp_expected_id" style="color: #333; margin-left: 10px;"><?php echo $expected_rent_id; ?></span>
                    </label>
                    <div style="font-size: 0.85em; color: #666; margin-top: 5px;">*Referenced from the current date. If the date is changed during saving, the ID will be automatically adjusted.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Customer Category: <span style="color:red">*</span></label>
                        <select name="walkin_cust_type" id="walkin_cust_type" onchange="toggleEmailField()">
                            <option value="Guest">Guest (General Customer)</option>
                            <option value="Member">Member</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select table: <span style="color:red">*</span></label>
                        <select name="walkin_table" id="walkin_table" onchange="updatePartySizeOptions()">
                            <option value="">-- Select table --</option>
                            <?php foreach($tables as $t): ?>
                                <option value="<?php echo $t['table_id']; ?>" 
                                        data-min="<?php echo $t['min_customer']; ?>" 
                                        data-max="<?php echo $t['max_customer']; ?>">
                                    <?php echo $t['table_name']; ?> 
                                    (Capacity: <?php echo $t['min_customer']."-".$t['max_customer']; ?> persons)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" id="email_row" style="display: none;">
                    <div class="form-group" style="width: 100%;">
                        <label>Email (For Member Search): <span style="color:red">*</span></label>
                        <input type="email" id="walkin_email" placeholder="Enter customer email..." style="width: 100%;">
                    </div>
                </div>
            </div>

            <div class="form-section" id="section_calc" style="opacity: 0.5; pointer-events: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label style="color:#1976d2;">📅 Date of Visit: <span style="color:red">*</span></label>
                        <input type="date" id="actual_date" name="actual_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Party Size: <span style="color:red">*</span></label>
                        <select id="num_players" name="walkin_pax" required>
                            <option value="">-- Please select a table first --</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label style="color:#d32f2f;">🎲 Board Games to Play (Select multiple): <span style="color:red">*</span></label>
                    <select id="played_games" name="played_games[]" multiple="multiple" style="width: 100%;" required>
                        <?php foreach($boardgames as $bg): ?>
                            <option value="<?php echo $bg['boardgame_id']; ?>"><?php echo $bg['boardgame_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label style="color:#2e7d32;">⏰ Start Time: <span style="color:red">*</span></label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label style="color:#d32f2f;">⏰ End Time (Default: Current): <span style="color:red">*</span></label>
                        <input type="time" id="end_time" name="end_time" value="<?php echo $current_time; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="width: 50%;">
                        <label>Payment Method:</label>
                        <select name="pay_method" id="pay_method" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer (PromptPay)</option>
                        </select>
                    </div>
                </div>

                <button type="button" class="btn-calc" onclick="calculateBill()" style="margin-top:10px;">🧮 Calculate Total</button>
            </div>

            <div class="receipt-box" id="receipt_box">
                <div class="receipt-title">Payment Summary (Table Talk)</div>
                <div class="receipt-details">
                    <p><span>Type:</span> <span id="r_type_cust">-</span></p>
                    <p><span>Customer Name:</span> <span id="r_name">-</span></p>
                    <p><span>Party Size:</span> <span id="r_pax">- persons</span></p>
                    <p><span>Games Played:</span> <span id="r_games" style="text-align: right; max-width: 60%; color:#5D4038;">-</span></p>
                    <p id="r_rent_time_row" style="display:none; color: #9B6E60;">
                        <span>Original Booking:</span> 
                        <span id="r_rent_time">-</span>
                    </p>
                    <p style="color:#1976d2;"><span>Actual Play Date:</span> <span id="r_actual_date">-</span></p>
                    <p><span>Actual Play Time:</span> <span><span id="r_start"></span> - <span id="r_end"></span></span></p>
                    <p><span>Total Play Duration:</span> <span id="r_duration" style="font-weight:bold;">-</span></p>
                    <p><span>Billing Type:</span> <span id="r_type">-</span></p>
                </div>
                <div class="receipt-total">
                    Total Amount: <span id="r_total_price">0</span> ฿
                </div>
                <button type="button" class="btn-save" onclick="validateBeforeSubmit()">💾 Confirm Payment</button>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#played_games').select2({
                placeholder: "Search or select a game...",
                allowClear: true
            });
        });

        const unpaidBookings = <?php echo json_encode($unpaid_bookings); ?>;

        function updatePartySizeOptions() {
            const tableSelect = document.getElementById('walkin_table');
            const numPlayersSelect = document.getElementById('num_players');
            
            numPlayersSelect.innerHTML = '<option value="">-- Please select number of players --</option>';
            
            if (tableSelect.value !== "") {
                const selectedOption = tableSelect.options[tableSelect.selectedIndex];
                const min = parseInt(selectedOption.getAttribute('data-min'));
                const max = parseInt(selectedOption.getAttribute('data-max'));
                
                for (let i = min; i <= max; i++) {
                    const opt = document.createElement('option');
                    opt.value = i;
                    opt.text = i + ' persons';
                    numPlayersSelect.appendChild(opt);
                }
            } else {
                numPlayersSelect.innerHTML = '<option value="">-- Please select a table first --</option>';
            }
        }

        function toggleEmailField() {
            const custType = document.getElementById('walkin_cust_type').value;
            if (custType === 'Member') {
                document.getElementById('email_row').style.display = 'block';
            } else {
                document.getElementById('email_row').style.display = 'none';
                document.getElementById('walkin_email').value = ""; 
            }
        }

        function toggleForm() {
            const type = document.querySelector('input[name="cust_type"]:checked').value;
            const secReserve = document.getElementById('section_reserve');
            const secWalkin = document.getElementById('section_walkin');
            const secCalc = document.getElementById('section_calc');
            
            document.getElementById('receipt_box').style.display = "none";
            document.getElementById('start_time').value = "";
            document.getElementById('end_time').value = "<?php echo $current_time; ?>"; 
            document.getElementById('actual_date').value = "<?php echo date('Y-m-d'); ?>"; 
            $('#played_games').val(null).trigger('change');

            const numPlayersSelect = document.getElementById('num_players');

            if (type === 'reserve') {
                secReserve.style.display = "block";
                secWalkin.style.display = "none";
                secCalc.style.opacity = "0.5";
                secCalc.style.pointerEvents = "none";
                document.getElementById('walkin_table').removeAttribute('required');
                
                numPlayersSelect.innerHTML = '<option value="">-- Loading... --</option>';
                numPlayersSelect.setAttribute('readonly', 'readonly');
                numPlayersSelect.style.pointerEvents = 'none'; 
            } else {
                secReserve.style.display = "none";
                secWalkin.style.display = "block";
                secCalc.style.opacity = "1";
                secCalc.style.pointerEvents = "auto";
                document.getElementById('walkin_table').setAttribute('required', 'required');
                
                numPlayersSelect.removeAttribute('readonly'); 
                numPlayersSelect.style.pointerEvents = 'auto'; 
                updatePartySizeOptions(); 
                toggleEmailField(); 
            }
        }

        function filterCustomers() {
            const sDate = document.getElementById('search_date').value;
            const sTable = document.getElementById('search_table').value;
            const selectRent = document.getElementById('rent_id');
            
            selectRent.innerHTML = '<option value="">-- Select Customer --</option>';
            document.getElementById('disp_rent_time').value = "";
            document.getElementById('section_calc').style.opacity = "0.5";
            document.getElementById('section_calc').style.pointerEvents = "none";

            if(!sDate || !sTable) return;

            let found = false;
            unpaidBookings.forEach(b => {
                if(b.rent_date == sDate && b.table_id == sTable) {
                    const option = document.createElement('option');
                    option.value = b.rent_id;
                    option.dataset.date = b.rent_date; 
                    option.dataset.time = b.rent_time;
                    option.dataset.pax = b.num_players;
                    option.dataset.name = b.username;
                    option.text = `${b.username} (Booked for: ${b.rent_time})`;
                    selectRent.appendChild(option);
                    found = true;
                }
            });
            if(!found) selectRent.innerHTML = '<option value="">-- No bookings found --</option>';
        }

        function fillReserveData() {
            const select = document.getElementById('rent_id');
            const calcSection = document.getElementById('section_calc');
            
            if(select.value !== "") {
                const opt = select.options[select.selectedIndex];
                const pax = opt.dataset.pax;

                document.getElementById('disp_rent_time').value = opt.dataset.time; 
                document.getElementById('actual_date').value = opt.dataset.date;
                document.getElementById('start_time').value = opt.dataset.time;     
                
                const numPlayersSelect = document.getElementById('num_players');
                numPlayersSelect.innerHTML = `<option value="${pax}">${pax} persons</option>`;
                numPlayersSelect.value = pax;
                
                calcSection.style.opacity = "1";
                calcSection.style.pointerEvents = "auto";
            }
        }

        function calculateBill() {
            const type = document.querySelector('input[name="cust_type"]:checked').value;
            const actualDate = document.getElementById('actual_date').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const numPlayers = parseInt(document.getElementById('num_players').value);
            
            const playedGamesSelected = $('#played_games').select2('data');
            
            if(playedGamesSelected.length === 0) {
                Swal.fire({icon:'warning', title:'Alert', text:'Please specify the board games the customer played (do not leave blank)'});
                return;
            }

            let customerName = "";
            let rentDateStr = "-";
            let rentTimeStr = "-";
            
            if(type === 'reserve') {
                const sel = document.getElementById('rent_id');
                if(!sel.value) { Swal.fire('Alert', 'Please select a booking', 'warning'); return; }
                customerName = sel.options[sel.selectedIndex].dataset.name;
                rentDateStr = sel.options[sel.selectedIndex].dataset.date;
                rentTimeStr = sel.options[sel.selectedIndex].dataset.time;
            } else {
                const selTable = document.getElementById('walkin_table');
                if(!selTable.value) { Swal.fire('Alert', 'Please select a table', 'warning'); return; }
                
                const walkinCustType = document.getElementById('walkin_cust_type').value;
                customerName = "Walk-in (" + walkinCustType + ")";
            }

            if(!actualDate || !startTime || !endTime || isNaN(numPlayers)) {
                Swal.fire({icon:'warning', title:'Alert', text:'Please fill in all required fields: Date, Time, and Number of Players'});
                return;
            }

            const [startH, startM] = startTime.split(':').map(Number);
            const [endH, endM] = endTime.split(':').map(Number);
            
            let totalStartMins = (startH * 60) + startM;
            let totalEndMins = (endH * 60) + endM;

            if (totalEndMins < totalStartMins) {
                totalEndMins += (24 * 60); 
            }

            let diffMins = totalEndMins - totalStartMins;

            if(diffMins <= 0) {
                Swal.fire({icon:'error', title:'Error', text:'Invalid end time. End time must be after start time.'});
                return;
            }

            let playedHours = Math.floor(diffMins / 60);
            let playedMins = diffMins % 60;
            let displayDuration = `${playedHours} hours ${playedMins} minutes`;

            let billableHours = playedHours;
            if (playedMins > 10) billableHours += 1;
            if (billableHours === 0) billableHours = 1;

            let pricePerPerson = billableHours > 5 ? 180 : billableHours * 40;
            let billTypeStr = billableHours > 5 ? `All-Day Pass (Over 5 Hours) - ${pricePerPerson} ฿/Person` : `Hourly Rate (${billableHours} hr.) - ${pricePerPerson} ฿/Person`;
            let totalPrice = pricePerPerson * numPlayers;

            let gamesText = playedGamesSelected.map(game => game.text).join(', ');

            document.getElementById('r_type_cust').innerText = type === 'reserve' ? "Reserve (Booking)" : "Walk-in";
            document.getElementById('r_name').innerText = customerName;
            document.getElementById('r_pax').innerText = numPlayers;
            document.getElementById('r_games').innerText = gamesText;
            document.getElementById('r_actual_date').innerText = actualDate;
            document.getElementById('r_start').innerText = startTime;
            
            let endDisplay = endTime;
            if ((endH * 60 + endM) < (startH * 60 + startM)) {
                endDisplay += " (Next Day)";
            }
            document.getElementById('r_end').innerText = endDisplay;

            document.getElementById('r_duration').innerText = displayDuration;
            document.getElementById('r_type').innerText = billTypeStr;
            document.getElementById('r_total_price').innerText = totalPrice.toLocaleString();
            document.getElementById('h_total_amount').value = totalPrice;

            if(type === 'reserve') {
                document.getElementById('r_rent_time_row').style.display = "flex";
                document.getElementById('r_rent_time').innerText = `${rentDateStr} at ${rentTimeStr}`;
            } else {
                document.getElementById('r_rent_time_row').style.display = "none";
            }

            document.getElementById('receipt_box').style.display = "block";
        }

        // Function to update QR Code when the PromptPay ID is edited
        function updatePromptPayQR() {
            const phone = document.getElementById('swal-promptpay-id').value;
            const amount = document.getElementById('swal-amount').value;
            const img = document.getElementById('swal-qr-img');
            
            if (phone.length >= 10) {
                img.src = `https://promptpay.io/${phone}/${amount}.png`;
            }
        }

        // Finalize Checkout handling for QR Display
        function finalizeCheckout() {
            const payMethod = document.getElementById('pay_method').value;
            const totalAmount = document.getElementById('h_total_amount').value;

            if (payMethod === 'Bank Transfer') {
                let defaultPromptPay = "0637755734"; 

                Swal.fire({
                    title: 'Scan to pay via PromptPay',
                    html: `
                        <div style="margin-bottom: 10px; text-align: left;">
                            <label style="font-weight: bold;">Tel (Can edit):</label>
                            <input type="text" id="swal-promptpay-id" class="swal2-input" value="${defaultPromptPay}" style="text-align:center; width: 90%; font-size: 1.2em; margin-top: 5px;" onkeyup="updatePromptPayQR()">
                        </div>
                        <div style="margin-bottom: 10px; text-align: left;">
                            <label style="font-weight: bold;">Amount (THB):</label>
                            <input type="text" id="swal-amount" class="swal2-input" value="${totalAmount}" readonly style="text-align:center; width: 90%; background:#f0f0f0; font-size: 1.2em; margin-top: 5px;">
                        </div>
                        <div style="margin-top: 15px;">
                            <img id="swal-qr-img" src="https://promptpay.io/${defaultPromptPay}/${totalAmount}.png" style="width: 250px; height: 250px; border: 2px solid #ddd; padding: 10px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Payment Confirmed (Save data)',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#576d5c',
                    cancelButtonColor: '#d33',
                    width: '450px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('checkoutForm').submit();
                    }
                });
            } else {
                document.getElementById('checkoutForm').submit();
            }
        }

        async function validateBeforeSubmit() {
            const playedGames = $('#played_games').val();
            if(!playedGames || playedGames.length === 0) {
                Swal.fire({icon:'warning', title:'Alert', text:'Please specify the board games the customer played (do not leave blank)'});
                return;
            }

            const numPlayers = document.getElementById('num_players').value;
            if(!numPlayers) {
                Swal.fire({icon:'warning', title:'Alert', text:'Please select the number of players'});
                return;
            }

            const type = document.querySelector('input[name="cust_type"]:checked').value;
            
            if (type === 'walkin') {
                const custType = document.getElementById('walkin_cust_type').value;
                if (custType === 'Member') {
                    const email = document.getElementById('walkin_email').value.trim();
                    if (!email) {
                        Swal.fire({icon:'warning', title:'Alert', text:'Please enter the member\'s E-mail'});
                        return;
                    }
                    
                    try {
                        const response = await $.post('admin_checkout.php', { action: 'check_email', email: email });
                        const res = JSON.parse(response);
                        
                        if (res.status === 'found') {
                            document.getElementById('walkin_customer_id').value = res.customer_id;
                            finalizeCheckout();
                        } else {
                            Swal.fire({
                                title: 'Email not found in system',
                                text: "Do you want to edit the email or proceed as a regular customer (Guest)?",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Continue as Guest',
                                cancelButtonText: 'Enter Email Again',
                                confirmButtonColor: '#9B6E60',
                                cancelButtonColor: '#5D4038',
                                reverseButtons: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    document.getElementById('walkin_cust_type').value = 'Guest';
                                    toggleEmailField();
                                    document.getElementById('walkin_customer_id').value = ""; 
                                    finalizeCheckout();
                                }
                            });
                            return; 
                        }
                    } catch (error) {
                        Swal.fire('Error', 'Database Error', 'error');
                        return;
                    }
                } else {
                    document.getElementById('walkin_customer_id').value = "";
                    finalizeCheckout();
                }
            } else {
                finalizeCheckout();
            }
        }
    </script>
</body>
</html>
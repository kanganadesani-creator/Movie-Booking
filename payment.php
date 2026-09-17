<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = mysqli_prepare($conn, "
    SELECT b.*, sh.show_date, sh.show_time, m.title, t.name AS theatre_name
    FROM bookings b
    JOIN shows sh ON b.show_id = sh.id
    JOIN movies m ON sh.movie_id = m.id
    JOIN theatres t ON sh.theatre_id = t.id
    WHERE b.id = ? AND b.user_id = ?
");
$userId = currentUserId();
mysqli_stmt_bind_param($stmt, "ii", $bookingId, $userId);
mysqli_stmt_execute($stmt);
$booking = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$booking) { header("Location: movies.php"); exit(); }

if ($booking['status'] === 'confirmed') {
    header("Location: ticket.php?booking_id=" . $bookingId);
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {

    $method = sanitize($_POST['method'] ?? '');

    if (!in_array($method, ['UPI', 'Card', 'Net Banking'])) {

        $error = "Please select a payment method.";

    } else {

        // Default values
        $upiId = null;
        $upiMobile = null;
        $upiAccountLast4 = null;

        $cardName = null;
        $cardLast4 = null;
        $cardExpiryMonth = null;
        $cardExpiryYear = null;

        $bankName = null;
        $accountName = null;
        $accountLast4 = null;
        $ifsc = null;


        // =========================
        // UPI
        // =========================

        if ($method === 'UPI') {

            $upiId = trim($_POST['upi_id'] ?? '');

            $upiMobile = trim($_POST['upi_mobile'] ?? '');

            $upiAccount = preg_replace('/\D/','',$_POST['upi_account'] ?? '');

            if (!preg_match(
                '/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/',
                $upiId
            )) {

                $error = "Please enter a valid UPI ID.";

            } elseif (!preg_match(
                '/^[6-9][0-9]{9}$/',
                $upiMobile
            )) {

                $error = "Please enter a valid 10-digit mobile number.";

            } elseif (!preg_match(
                '/^[0-9]{9,18}$/',
                $upiAccount
            )) {

                $error = "Please enter a valid bank account number.";

            } else {
                // Store only last 4 digits
                $upiAccountLast4 = substr(
                    $upiAccount,
                    -4
                );
            }
        }
        // =========================
        // CARD
        // =========================

        elseif ($method === 'Card') {

            $cardNumber = preg_replace(
                '/\D/',
                '',
                $_POST['card_number'] ?? ''
            );

            $cardName = trim(
                $_POST['card_name'] ?? ''
            );

            $cardExpiryMonth =
                $_POST['valid_month'] ?? '';

            $cardExpiryYear =
                $_POST['valid_year'] ?? '';

            $cvv = $_POST['cvv'] ?? '';


            if (!preg_match(
                '/^[0-9]{16}$/',
                $cardNumber
            )) {

                $error = "Please enter a valid 16-digit card number.";

            } elseif (!preg_match(
                '/^[a-zA-Z ]+$/',
                $cardName
            )) {

                $error = "Please enter a valid card holder name.";

            } elseif (
                $cardExpiryMonth === '' ||
                $cardExpiryYear === ''
            ) {

                $error = "Please select card expiry month and year.";

            } elseif (!preg_match(
                '/^[0-9]{3,4}$/',
                $cvv
            )) {

                $error = "Please enter a valid CVV.";

            } else {

                // Store only last 4 digits
                $cardLast4 = substr(
                    $cardNumber,
                    -4
                );
            }
        }

        // NET BANKING

        elseif ($method === 'Net Banking') {

            $bankName = trim(
                $_POST['bank_name'] ?? ''
            );

            $accountName = trim(
                $_POST['account_name'] ?? ''
            );

            $accountNumber = preg_replace(
                '/\D/',
                '',
                $_POST['account_number'] ?? ''
            );

            $ifsc = strtoupper(
                trim($_POST['ifsc'] ?? '')
            );


            if ($bankName === '') {

                $error = "Please select your bank.";

            } elseif (!preg_match(
                '/^[a-zA-Z ]+$/',
                $accountName
            )) {

                $error = "Please enter a valid account holder name.";

            } elseif (!preg_match(
                '/^[0-9]{9,18}$/',
                $accountNumber
            )) {

                $error = "Please enter a valid account number.";

            } elseif (!preg_match(
                '/^[A-Z]{4}0[A-Z0-9]{6}$/',
                $ifsc
            )) {

                $error = "Please enter a valid IFSC code.";

            } else {

                // Store only last 4 digits
                $accountLast4 = substr(
                    $accountNumber,
                    -4
                );
            }
        }
        
        // SAVE PAYMENT
        if (!$error) {

            $txnId = generateTransactionId();
            mysqli_begin_transaction($conn);

            try {


                // Save payment details
                $detailStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO payment_details
                    (
                        booking_id,
                        payment_method,
                        upi_id,
                        upi_mobile,
                        upi_account_last4,
                        card_name,
                        card_last4,
                        card_expiry_month,
                        card_expiry_year,
                        bank_name,
                        account_name,
                        account_last4,
                        ifsc
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $detailStmt,
                    "issssssssssss",
                    $bookingId,
                    $method,
                    $upiId,
                    $upiMobile,
                    $upiAccountLast4,
                    $cardName,
                    $cardLast4,
                    $cardExpiryMonth,
                    $cardExpiryYear,
                    $bankName,
                    $accountName,
                    $accountLast4,
                    $ifsc
                );

                mysqli_stmt_execute($detailStmt);


                // Save transaction
                $insertPay = mysqli_prepare(
                    $conn,
                    "INSERT INTO payments
                    (
                        booking_id,
                        transaction_id,
                        method,
                        amount,
                        status
                    )
                    VALUES (?, ?, ?, ?, 'success')"
                );

                mysqli_stmt_bind_param(
                    $insertPay,
                    "issd",
                    $bookingId,
                    $txnId,
                    $method,
                    $booking['total_amount']
                );

                mysqli_stmt_execute($insertPay);


                // Confirm booking
                $updateBooking = mysqli_prepare(
                    $conn,
                    "UPDATE bookings
                     SET status = 'confirmed'
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $updateBooking,
                    "i",
                    $bookingId
                );

                mysqli_stmt_execute($updateBooking);


                mysqli_commit($conn);

                header(
                    "Location: ticket.php?booking_id=" . $bookingId
                );

                exit();

            } catch (Exception $e) {

                mysqli_rollback($conn);

                $error = "Payment failed. Please try again.";
            }
        }
    }
}
$pageTitle = "Payment";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="cb-card p-4 p-md-5">
                <h4 class="fw-bold mb-1">Complete Your Payment</h4>
                <p class="text-secondary mb-4"><?= sanitize($booking['title']) ?> &bull; <?= sanitize($booking['theatre_name']) ?></p>

                <div class="d-flex justify-content-between align-items-center cb-card p-3 mb-4" style="background:var(--cb-surface-2)">
                    <span class="text-secondary">Amount Payable</span>
                    <h4 class="text-warning mb-0">₹<?= number_format($booking['total_amount'], 0) ?></h4>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                    <label class="form-label mb-3">Choose Payment Method</label>
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <label class="pay-option d-block text-center" onclick="selectPayOption(this,'method_upi')">
                            <input type="radio" name="method" value="UPI"  id="method_upi" onclick="showPaymentDetails()">
                                <i class="fa-solid fa-mobile-screen fa-lg d-block mb-2"></i>
                                <span class="small">UPI</span>
                                
                            </label>
                            
                        </div>
                        <div class="col-4">
                            <label class="pay-option d-block text-center" onclick="selectPayOption(this,'method_card')">
                            <input type="radio" name="method" value="Card" id="method_card" onclick="showPaymentDetails()">
                                <i class="fa-solid fa-credit-card fa-lg d-block mb-2"></i>
                                <span class="small">Card</span>
                            </label>
                        </div>
                        <div class="col-4">
                            <label class="pay-option d-block text-center" onclick="selectPayOption(this,'method_nb')">
                            <input type="radio" name="method" value="Net Banking" id="method_nb" onclick="showPaymentDetails()">
                                <i class="fa-solid fa-building-columns fa-lg d-block mb-2"></i>
                                <span class="small">Net Banking</span>
                            </label>
                        </div>
                    </div>
                      <div id="upiDetails" style="display:none;" class="mb-4">
                         <h6 class="fw-bold mb-3"> Enter UPI Details</h6>
                         <!-- UPI ID -->
                         <div class="mb-3">
                            <label class="form-label">UPI ID</label>
                            <input type="text" name="upi_id" id="upi_id" class="form-control" placeholder="example@upi">
                            <small class="text-secondary">Example: kangana@upi</small>
                        </div>

                        <!-- BANK ACCOUNT NUMBER -->

                        <div class="mb-3">
                            <label class="form-label">Bank Account Number</label>
                            <input type="password" name="upi_account" id="upi_account" class="form-control" placeholder="Enter bank account number" maxlength="18" inputmode="numeric">
                        </div>
                        
                        <!-- MOBILE NUMBER -->
                        <div class="mb-3">
                            <label class="form-label"> Mobile Number </label>
                            <input type="text" name="upi_mobile" id="upi_mobile" class="form-control" placeholder="Enter 10-digit mobile number" maxlength="10" inputmode="numeric">
                        </div>
                    </div>
                    <!-- CARD DETAILS -->
                    <div id="cardDetails" style="display:none;" class="mb-4">
                        <h6 class="fw-bold mb-3">Enter Card Details</h6>
                    
                        <!-- Card Number -->
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" name="card_number" id="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" inputmode="numeric">
                        </div>
                        
                        <!-- Card Holder Name -->
                        <div class="mb-3">
                            <label class="form-label">Card Holder Name</label>
                            <input type="text" name="card_name" id="card_name" class="form-control" placeholder="Enter card holder name">
                        </div>
                        <!-- Valid Month -->
                        <div class="col-4">
                            <label class="form-label">Valid Month</label>
                
                            <select name="valid_month" id="valid_month" class="form-select">
                                <option value="">Month</option>
                        <div class="row">

                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= sprintf('%02d', $m) ?>">
                        <?= sprintf('%02d', $m) ?>
                    </option>

                <?php endfor; ?>

            </select>
        </div>

        <!-- Valid Year -->
        <div class="col-4">
            <label class="form-label">Valid Year</label>

            <select name="valid_year" id="valid_year" class="form-select">
                <option value="">Year</option>

                <?php
                $currentYear = date('Y');

                for ($y = $currentYear; $y <= $currentYear + 10; $y++):
                ?>
                    <option value="<?= $y ?>">
                        <?= $y ?>
                    </option>

                <?php endfor; ?>

            </select>
        </div>

        <!-- CVV -->
        <div class="col-4">
            <label class="form-label">CVV</label>

            <input type="password" name="cvv" id="cvv" class="form-control" placeholder="123" maxlength="4" inputmode="numeric">
        </div>
    </div>

</div>
<!-- NET BANKING DETAILS -->
<div id="netBankingDetails" style="display:none;" class="mb-4">

    <h6 class="fw-bold mb-3">Enter Net Banking Details</h6>

    <!-- Select Bank -->
    <div class="mb-3">
        <label class="form-label">Select Bank</label>

        <select name="bank_name"
                id="bank_name"
                class="form-select">

            <option value="">Select Bank</option>
            <option value="SBI">State Bank of India</option>
            <option value="HDFC">HDFC Bank</option>
            <option value="ICICI">ICICI Bank</option>
            <option value="Axis">Axis Bank</option>
            <option value="Kotak">Kotak Mahindra Bank</option>
            <option value="BOB">Bank of Baroda</option>

        </select>
    </div>

    <!-- Account Holder Name -->
    <div class="mb-3">
        <label class="form-label">Account Holder Name</label>

        <input type="text"
               name="account_name"
               id="account_name"
               class="form-control"
               placeholder="Enter account holder name">
    </div>

    <!-- Account Number -->
    <div class="mb-3">
        <label class="form-label">Account Number</label>

        <input type="password"
               name="account_number"
               id="account_number"
               class="form-control"
               placeholder="Enter account number"
               maxlength="18"
               inputmode="numeric">
    </div>

    <!-- IFSC Code -->
    <div class="mb-3">
        <label class="form-label">IFSC Code</label>

        <input type="text"
               name="ifsc"
               id="ifsc"
               class="form-control"
               placeholder="Example: SBIN0001234"
               maxlength="11">
    </div>

</div>
                    <div class="alert alert-info small">
                        <i class="fa-solid fa-circle-info"></i> This is a simulated payment for demo purposes. No real transaction will occur.
                    </div>

                    <button type="submit" name="pay_now" value="1" class="btn btn-primary btn-lg w-100">
                        <i class="fa-solid fa-lock"></i> Pay Now ₹<?= number_format($booking['total_amount'], 0) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>

function showPaymentDetails() {

    document.getElementById("upiDetails").style.display = "none";
    document.getElementById("cardDetails").style.display = "none";
    document.getElementById("netBankingDetails").style.display = "none";

    if (document.getElementById("method_upi").checked) {
        document.getElementById("upiDetails").style.display = "block";
    }

    if (document.getElementById("method_card").checked) {
        document.getElementById("cardDetails").style.display = "block";
    }

    if (document.getElementById("method_nb").checked) {
        document.getElementById("netBankingDetails").style.display = "block";
    }

}

</script>
<?php include 'includes/footer.php'; ?>
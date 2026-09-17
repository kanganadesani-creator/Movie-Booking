<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$userId = currentUserId();

$showId = (int)($_GET['show_id'] ?? $_POST['show_id'] ?? 0);

if ($showId <= 0) {
    header("Location: movies.php");
    exit();
}

//  GET SHOW DETAILS

$showStmt = mysqli_prepare($conn, "
    SELECT
        sh.id,
        sh.show_date,
        sh.show_time,
        sh.price,
        sh.screen_id,
        m.title,
        m.poster,
        t.name AS theatre_name,
        t.city,
        sc.screen_name
    FROM shows sh
    JOIN movies m ON sh.movie_id = m.id
    JOIN theatres t ON sh.theatre_id = t.id
    JOIN screens sc ON sh.screen_id = sc.id
    WHERE sh.id = ?
");

mysqli_stmt_bind_param($showStmt, "i", $showId);
mysqli_stmt_execute($showStmt);

$show = mysqli_stmt_get_result($showStmt)->fetch_assoc();

//  SHOW NOT FOUND

if (!$show) {
    header("Location: movies.php");
    exit();
}
/* =========================================================
   IMPORTANT:
   CHECK SHOW DATE + TIME

   If show time is already passed,
   booking is completely blocked.
========================================================= */

$showDateTime = strtotime(
    $show['show_date'] . ' ' . $show['show_time']
);

$currentDateTime = time();

if ($showDateTime <= $currentDateTime) {

    $pageTitle = "Show Expired";

    include 'includes/header.php';
    include 'includes/navbar.php';
    ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="cb-card p-5 text-center">
                    <i class="fa-solid fa-clock fa-3x text-danger mb-3"></i>
                        
                    <h3 class="fw-bold mb-3">Show Has Ended</h3>
                    <p class="text-secondary mb-2">Sorry, booking is no longer available for:</p>

                    <h5 class="fw-bold">
                        <?= sanitize($show['title']) ?>
                    </h5>

                    <p class="text-secondary">
                        <?= sanitize($show['theatre_name']) ?>
                        &bull;
                        <?= sanitize($show['screen_name']) ?>
                    </p>
                    <p class="text-warning">
                        <?= date(
                            'd M Y',
                            strtotime($show['show_date'])
                        ) ?>
                        &nbsp;
                        <?= date(
                            'h:i A',
                            strtotime($show['show_time'])
                        ) ?>
                    </p>
                    <a href="movie-details.php?id=<?= (int)($show['movie_id'] ?? 0) ?>" class="btn btn-warning mt-3">
                        <i class="fa-solid fa-arrow-left"></i>Back to Movie</a>   
                </div>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}
//    GET MOVIE ID FOR BACK BUTTON

$movieIdStmt = mysqli_prepare(
    $conn,
    "SELECT movie_id FROM shows WHERE id = ?"
);
mysqli_stmt_bind_param(
    $movieIdStmt,
    "i",
    $showId
);

mysqli_stmt_execute($movieIdStmt);

$movieIdResult =
    mysqli_stmt_get_result($movieIdStmt)->fetch_assoc();

$movieId = (int)($movieIdResult['movie_id'] ?? 0);

/* =========================================================
   PROCESS SEAT SELECTION
========================================================= */

$error = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['continue_payment'])
) {

    /*
     * CHECK AGAIN BEFORE CREATING BOOKING.
     *
     * This is important because the user may keep
     * the seat-selection page open until the show expires.
     */

    $checkStmt = mysqli_prepare($conn, "
        SELECT show_date, show_time, price
        FROM shows
        WHERE id = ?
    ");
    mysqli_stmt_bind_param(
        $checkStmt,
        "i",
        $showId
    );
    mysqli_stmt_execute($checkStmt);
    $latestShow =
        mysqli_stmt_get_result($checkStmt)->fetch_assoc();

    if (!$latestShow) {
        $error = "This show is no longer available.";

    } else {
        $latestShowDateTime = strtotime(
            $latestShow['show_date']
            . ' '
            . $latestShow['show_time']
        );
        /* SECOND EXPIRY CHECK */
        if ($latestShowDateTime <= time()) {
            $error =
                "Sorry, this show has already started or ended. "
                . "Booking is no longer available.";
        } else {
            /* ==========================================
               GET SELECTED SEATS
            ========================================== */
            $selectedSeats =
                $_POST['seats'] ?? [];
            if (!is_array($selectedSeats)) {
                $selectedSeats = [];
            }
            /* Convert IDs to integers */
            $selectedSeats = array_map(
                'intval',
                $selectedSeats
            );
            /* Remove invalid IDs and duplicates */
            $selectedSeats = array_unique(
                array_filter(
                    $selectedSeats,
                    function ($seatId) {
                        return $seatId > 0;
                    }
                )
            );
            if (empty($selectedSeats)) {
                $error =
                    "Please select at least one seat.";
            } else {
              /* ==========================================
                   CHECK SELECTED SEATS EXIST FOR THIS SCREEN
                ========================================== */
                $placeholders =
                    implode(
                        ',',
                        array_fill(
                            0,
                            count($selectedSeats),
                            '?'
                        )
                    );
                $types = str_repeat(
                    'i',
                    count($selectedSeats)
                );
                $sql = "
                    SELECT id, seat_label, seat_type
                    FROM seats
                    WHERE screen_id = ?
                    AND id IN ($placeholders)
                ";
                $seatStmt =
                    mysqli_prepare($conn, $sql);
                $params = array_merge(
                    [$show['screen_id']],
                    $selectedSeats
                );

                mysqli_stmt_bind_param(
                    $seatStmt,
                    'i' . $types,
                    ...$params
                );

                mysqli_stmt_execute(
                    $seatStmt
                );
                $seatResult =
                    mysqli_stmt_get_result(
                        $seatStmt
                    );
                $validSeats = [];
                while (
                    $seat = mysqli_fetch_assoc(
                        $seatResult
                    )
                ) {
                    $validSeats[
                        (int)$seat['id']
                    ] = $seat;
                }
                if (
                    count($validSeats)
                    !== count($selectedSeats)
                ) {
                   $error =
                        "One or more selected seats are invalid.";

                } else {
                    /* ==========================================
                       CHECK ALREADY BOOKED SEATS
                    ========================================== */
                    $bookedSql = "
                        SELECT seat_id
                        FROM booking_seats
                        WHERE show_id = ?
                        AND seat_id IN ($placeholders)
                    ";
                    $bookedStmt =
                        mysqli_prepare(
                            $conn,
                            $bookedSql
                        );


                    $bookedParams =
                        array_merge(
                            [$showId],
                            $selectedSeats
                        );


                    mysqli_stmt_bind_param(
                        $bookedStmt,
                        'i' . $types,
                        ...$bookedParams
                    );

                    mysqli_stmt_execute(
                        $bookedStmt
                    );


                    $bookedResult =
                        mysqli_stmt_get_result(
                            $bookedStmt
                        );


                    $alreadyBooked = [];

                    while (
                        $booked =
                            mysqli_fetch_assoc(
                                $bookedResult
                            )
                    ) {

                        $alreadyBooked[] =
                            (int)$booked['seat_id'];
                    }


                    if (!empty($alreadyBooked)) {

                        $error =
                            "One or more selected seats are already booked. "
                            . "Please select different seats.";

                    } else {

                        /* ==========================================
                           CALCULATE TOTAL
                        ========================================== */

                        $totalAmount = 0;


                        foreach (
                            $selectedSeats as $seatId
                        ) {

                            $seat =
                                $validSeats[$seatId];


                            /*
                             * Premium seats cost extra.
                             * Normal seat = show price
                             * Premium seat = show price + 50
                             */

                            if (
                                $seat['seat_type']
                                === 'premium'
                            ) {

                                $totalAmount +=
                                    (float)$latestShow['price']
                                    + 50;

                            } else {

                                $totalAmount +=
                                    (float)$latestShow['price'];
                            }
                        }
                        /* ==========================================
                           CREATE BOOKING
                        ========================================== */

                        mysqli_begin_transaction(
                            $conn
                        );
                        try {
                            $bookingCode =
                                generateBookingCode();
                            $bookingStmt =
                                mysqli_prepare(
                                    $conn,
                                    "
                                    INSERT INTO bookings
                                    (
                                        booking_code,
                                        user_id,
                                        show_id,
                                        total_amount,
                                        status
                                    )
                                    VALUES
                                    (?, ?, ?, ?, 'pending')
                                    "
                                );


                            mysqli_stmt_bind_param(
                                $bookingStmt,
                                "siid",
                                $bookingCode,
                                $userId,
                                $showId,
                                $totalAmount
                            );


                            mysqli_stmt_execute(
                                $bookingStmt
                            );


                            $bookingId =
                                mysqli_insert_id(
                                    $conn
                                );
                            /* ======================================
                               INSERT BOOKING SEATS
                            ====================================== */
                            $bookingSeatStmt =
                                mysqli_prepare(
                                    $conn,
                                    "
                                    INSERT INTO booking_seats
                                    (
                                        booking_id,
                                        show_id,
                                        seat_id
                                    )
                                    VALUES (?, ?, ?)
                                    "
                                );
                            foreach (
                                $selectedSeats
                                as $seatId
                            ) {

                                mysqli_stmt_bind_param(
                                    $bookingSeatStmt,
                                    "iii",
                                    $bookingId,
                                    $showId,
                                    $seatId
                                );
                               mysqli_stmt_execute(
                                    $bookingSeatStmt
                                );
                            }
                            mysqli_commit(
                                $conn
                            );
                            header(
                                "Location: payment.php?booking_id="
                                . $bookingId
                            );

                            exit();

                        } catch (
                            mysqli_sql_exception $e
                        ) {

                            mysqli_rollback(
                                $conn
                            );
                            if (
                                strpos(
                                    $e->getMessage(),
                                    'unique_seat_per_show'
                                ) !== false
                            ) {

                                $error =
                                    "Sorry, one of the selected seats "
                                    . "was just booked by another user. "
                                    . "Please select again.";

                            } else {

                                $error =
                                    "Unable to create booking. "
                                    . "Please try again.";
                            }
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   GET ALL SEATS FOR THIS SCREEN
========================================================= */

$seatsStmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        seat_label,
        seat_type
    FROM seats
    WHERE screen_id = ?
    ORDER BY 
    LEFT(seat_label, 1),
    CAST(SUBSTRING(seat_label, 2) AS UNSIGNED)
    "
);

mysqli_stmt_bind_param(
    $seatsStmt,
    "i",
    $show['screen_id']
);

mysqli_stmt_execute(
    $seatsStmt
);

$seatsResult =
    mysqli_stmt_get_result(
        $seatsStmt
    );


/* =========================================================
   GET BOOKED SEATS
========================================================= */

$bookedStmt = mysqli_prepare(
    $conn,
    "
    SELECT seat_id
    FROM booking_seats
    WHERE show_id = ?
    "
);

mysqli_stmt_bind_param(
    $bookedStmt,
    "i",
    $showId
);

mysqli_stmt_execute(
    $bookedStmt
);

$bookedResult =
    mysqli_stmt_get_result(
        $bookedStmt
    );


$bookedSeats = [];

while (
    $row =
        mysqli_fetch_assoc(
            $bookedResult
        )
) {

    $bookedSeats[] =
        (int)$row['seat_id'];
}


$pageTitle = "Select Seats";

include 'includes/header.php';
include 'includes/navbar.php';
?>


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="cb-card p-4 p-md-5">
                <!-- ==========================================
                     SHOW INFORMATION
                =========================================== -->
                <div class="mb-4">
                    <h3 class="fw-bold mb-2">
                        <?= sanitize($show['title']) ?>
                    </h3>
                    <p class="text-secondary mb-1">
                        <i class="fa-solid fa-building"></i>
                        <?= sanitize(
                            $show['theatre_name']
                        ) ?>
                       &bull;
                        <?= sanitize(
                            $show['screen_name']
                        ) ?>

                    </p>
                    <p class="text-secondary mb-0">
                        <i class="fa-solid fa-calendar"></i>

                        <?= date(
                            'D, d M Y',
                            strtotime(
                                $show['show_date']
                            )
                        ) ?>

                        &nbsp;&nbsp;

                        <i class="fa-solid fa-clock"></i>

                        <?= date(
                            'h:i A',
                            strtotime(
                                $show['show_time']
                            )
                        ) ?>

                    </p>
                </div>


                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>
                <!-- ==========================================
                     SCREEN
                =========================================== -->
                <div class="text-center mb-4">

                    <div style=" width:80%; height:45px; margin:auto; background:#444; border-radius:50% 50% 0 0; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold;">
                        SCREEN
                    </div>
                </div>
                <!-- ==========================================
                     SEAT LEGEND
                =========================================== -->

                <div class="d-flex justify-content-center gap-4 flex-wrap mb-4">
                    <span>
                        <span style=" display:inline-block; width:18px; height:18px;  background:#198754; border-radius:4px; vertical-align:middle;"></span>
                        Available
                    </span>
                    <span>
                        <span style=" display:inline-block; width:18px; height:18px; background:#dc3545; border-radius:4px; vertical-align:middle;"></span>
                        Booked
                    </span>
                    <span>
                        <span style=" display:inline-block; width:18px;  height:18px; background:#ffc107; border-radius:4px;  vertical-align:middle;"></span>Selected </span>

                </div>
                <!-- ==========================================
                     SEAT FORM
                =========================================== -->

                <form method="POST" id="seatForm">
                    <input type="hidden" name="show_id" value="<?= $showId ?>">
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                        <?php
                        $seatCount = 0;
                        while (
                            $seat =
                                mysqli_fetch_assoc(
                                    $seatsResult
                                )
                        ):
                            $seatId =
                                (int)$seat['id'];

                            $isBooked =
                                in_array(
                                    $seatId,
                                    $bookedSeats
                                );

                            $seatCount++;

                        ?>
                            <?php if ($isBooked): ?>

                                <!-- BOOKED SEAT -->

                                <label style=" width:48px; height:42px; background:#dc3545; color:#fff; border-radius:6px; display:flex; align-items:center; justify-content:center; cursor:not-allowed; font-size:12px;">
                                    <?= sanitize(
                                        $seat['seat_label']
                                    ) ?>
                                </label>
                            <?php else: ?>

                                <!-- AVAILABLE SEAT -->
                                <label class="seat-label" style=" width:48px; height:42px; background:#198754; color:#fff; border-radius:6px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px;">
                                    <input type="checkbox" name="seats[]" value="<?= $seatId ?>" style="display:none;" onchange="updateSeat(this)">
                                    <?= sanitize(
                                        $seat['seat_label']
                                    ) ?>
                                </label>

                            <?php endif; ?>
                            <?php if ($seatCount % 10 === 0): ?>
                           <div style="flex-basis:100%; height:0;"></div>
                           <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                    <?php if ($seatCount === 0): ?>
                        <div class="alert alert-warning">
                            No seats are available for this screen.
                        </div>
                    <?php else: ?>

                        <div class="cb-card p-3 mb-4" style=" background:var(--cb-surface-2);">
                            <div class=" d-flex justify-content-between align-items-center">
                                <span class="text-secondary">
                                    Selected Seats:
                                </span>
                                <strong id="selectedCount"> 0 </strong>
                            </div>

                            <div class=" d-flex justify-content-between align-items-center mt-2">
                                <span class="text-secondary">
                                    Total Amount:
                                </span>
                                <h4 class="text-warning mb-0" id="totalAmount">
                                    ₹0
                                </h4>
                            </div>
                        </div>
                        <button type="submit" name="continue_payment" value="1" class="btn btn-primary btn-lg w-100">
                            <i class=" fa-solid fa-arrow-right"></i>
                            Continue to Payment
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

const normalPrice =
    <?= (float)$show['price'] ?>;


/*
 * Change seat colour when selected
 */

function updateSeat(checkbox) {

    const label =
        checkbox.parentElement;

    if (checkbox.checked) {

        label.style.background =
            "#ffc107";

        label.style.color =
            "#000";

    } else {

        label.style.background =
            "#198754";

        label.style.color =
            "#fff";
    }


    updateTotal();
}

function updateTotal() {

    const selected =
        document.querySelectorAll(
            'input[name="seats[]"]:checked'
        );

    let total = 0;
    selected.forEach(function (checkbox) {

        const label =
            checkbox.parentElement;

        if (
            label.dataset.type ===
            "premium"
        ) {

            total +=
                normalPrice + 50;

        } else {

            total +=
                normalPrice;
        }

    });


    document.getElementById(
        "selectedCount"
    ).innerText =
        selected.length;


    document.getElementById(
        "totalAmount"
    ).innerText =
        "₹" + total.toFixed(0);
}


/*
 * Final check before submitting
 */

document.getElementById(
    "seatForm"
).addEventListener(
    "submit",
    function (event) {

        const selected =
            document.querySelectorAll(
                'input[name="seats[]"]:checked'
            );


        if (selected.length === 0) {

            event.preventDefault();

            alert(
                "Please select at least one seat."
            );

            return;
        }

        const showDate =
            "<?= $show['show_date'] ?>";

        const showTime =
            "<?= $show['show_time'] ?>";

        const showDateTime =
            new Date(
                showDate + "T" + showTime
            );

        if (
            showDateTime <= new Date()
        ) {
            event.preventDefault();
            alert(
                "Sorry, this show has already started or ended. Booking is no longer available."
            );
            window.location.href =
                "movie-details.php?id=<?= $movieId ?>";
        }

    }
);
</script>
<?php include 'includes/footer.php'; ?>
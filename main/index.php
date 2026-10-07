<?php 
session_start();
include '../config/connect.php';

$sql_promo = "SELECT * FROM promotions
              WHERE status = 'active' 
              AND NOW() BETWEEN start_date AND end_date 
              ORDER BY promo_id DESC LIMIT 1";
$res_promo = $conn->query($sql_promo);
$promo = $res_promo->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="../img/web/table-talk.png">
    <title>Table-Talk Index</title>

    <link rel="stylesheet" href="../style/history.css">
    <link rel="stylesheet" href="../style/styles.css">
    <link rel="stylesheet" href="../style/footer.css">
    
    </head>

<body>
<?php if($promo): ?>
    <style>
        /* CSS for Splash Page */
        .splash-overlay {
            display: none; /* Hidden by default, opened via JS */
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }
        .splash-content {
            background-color: var(--cream, #F5EBE1);
            width: 90%;
            max-width: 400px;
            border-radius: 15px;
            text-align: center;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: popIn 0.5s ease;
        }
        @keyframes popIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .splash-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            color: white; /* Added shadow for visibility on images */
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
            cursor: pointer;
            z-index: 10;
        }
        .splash-image {
            width: 100%;
            height: auto;
            max-height: 250px;
            object-fit: cover;
            display: block;
        }
        .splash-text {
            padding: 20px;
            color: var(--brown-dark, #5D4038);
        }
        .splash-text h2 { margin: 0 0 10px 0; font-size: 20px; }
        .splash-text p { font-size: 14px; margin-bottom: 20px; color: #555; }
        .splash-btn {
            display: inline-block;
            background-color: var(--green, #576d5c);
            color: white;
            padding: 10px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .splash-btn:hover { background-color: var(--green-hover, #4a5c4b); color: white; }
    </style>

    <div id="splashScreen" class="splash-overlay">
        <div class="splash-content">
            <span class="splash-close" onclick="closeSplash()">&times;</span>
            
            <img src="<?php echo $promo['image_url']; ?>" alt="Promotion" class="splash-image">
            
            <div class="splash-text">
                <h2><?php echo $promo['title']; ?></h2>
                <p><?php echo $promo['description']; ?></p>
                
                <a href="<?php echo $promo['action_link']; ?>" class="splash-btn">
                    <?php echo $promo['button_text']; ?>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>


    <div class="container">
        <?php
        include '../config/connect.php';
        include '../config/install.php';
        include '../include/topbar.php'; // Topbar including the chat system will be pulled here
        
        // --- QUERY to fetch top 3 popular games (based on rental count) ---
        $sql_popular = "SELECT b.boardgame_id, b.boardgame_name, b.upload, COUNT(rb.boardgame_id) as rent_count 
                        FROM boardgame b
                        LEFT JOIN rental_boardgame rb ON b.boardgame_id = rb.boardgame_id
                        GROUP BY b.boardgame_id
                        ORDER BY rent_count DESC
                        LIMIT 3"; // Fetching 3 games
        $result_popular = mysqli_query($conn, $sql_popular);
        $popular_games = [];
        while($row = mysqli_fetch_assoc($result_popular)) {
            $popular_games[] = $row;
        }
        ?>

        <header class="hero-banner">
            <div class="banner-overlay">
                <h1>Table<br>Talk</h1>
            </div>
        </header>

        <main class="content-grid">
            <div class="popular-section">
                <h2>Popular Board Games</h2>
                <div class="game-carousel">
                    <div class="carousel-box" id="popularCarousel">
                        <?php 
                        // Loop to display game cards
                        foreach($popular_games as $index => $game): 
                            // Set the 2nd image (index 1) as large at start
                            $size_class = ($index === 1) ? 'large' : 'small';
                        ?>
                            <div class="card-item <?php echo $size_class; ?>" 
                                 style="background-image: url('../img/boardgame/<?php echo htmlspecialchars($game['upload']); ?>'); cursor: pointer;"
                                 onclick="location.href='boardgame_detail.php?id=<?php echo $game['boardgame_id']; ?>'">
                                
                                <div class="game-title-overlay">
                                    <?php echo htmlspecialchars($game['boardgame_name']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="carousel-dots" id="carouselDots">
                        <?php 
                        // Loop to display dots based on game count
                        foreach($popular_games as $index => $game): 
                            $active_class = ($index === 1) ? 'active' : '';
                        ?>
                            <span class="<?php echo $active_class; ?>" onclick="setCarousel(<?php echo $index; ?>)"></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="menu-side">
                <div class="menu-row">
                    <a href="table.php" class="menu-box" style="text-decoration: none; color: inherit; cursor: pointer; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 10px;">
                        <div class="m-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 123 123" fill="none">
                                <path d="M22.1656 46.125H100.834L96.4781 30.75H26.65L22.1656 46.125ZM86.1 56.375H37.0281L35.6187 66.625H87.3812L86.1 56.375ZM21.2687 96.7344L26.7781 56.375H15.375C13.6667 56.375 12.3222 55.6917 11.3416 54.325C10.361 52.9583 10.0826 51.4635 10.5062 49.8406L17.8094 24.2156C18.151 23.1052 18.749 22.2083 19.6031 21.525C20.4573 20.8417 21.4823 20.5 22.6781 20.5H100.322C101.518 20.5 102.543 20.8417 103.397 21.525C104.251 22.2083 104.849 23.1052 105.191 24.2156L112.494 49.8406C112.921 51.4635 112.644 52.9583 111.663 54.325C110.683 55.6917 109.337 56.375 107.625 56.375H96.35L101.731 96.7344C101.902 98.2719 101.495 99.618 100.511 100.773C99.5275 101.928 98.2685 102.503 96.7344 102.5C95.4531 102.5 94.3205 102.093 93.3365 101.28C92.3525 100.467 91.7768 99.4216 91.6094 98.1437L88.7906 76.875H34.2094L31.3906 98.1437C31.2198 99.425 30.6441 100.472 29.6635 101.285C28.6829 102.099 27.5503 102.503 26.2656 102.5C24.7281 102.5 23.4691 101.924 22.4885 100.773C21.5079 99.6215 21.1013 98.2753 21.2687 96.7344Z" fill="#5D4038"/>
                            </svg>
                        </div>
                        <span>Table Booking</span>
                    </a>
                    <a href="boardgame.php" class="menu-box" style="text-decoration: none; color: inherit; cursor: pointer;">
                        <div class="m-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="123" height="123" viewBox="0 0 123 123" fill="none">
                                <path d="M30.7039 53.3397C31.1036 57.5678 28.8474 61.2347 25.6955 61.4884C22.5436 61.7383 19.6608 58.5172 19.2649 54.2891C18.8651 50.0571 21.1214 46.394 24.2733 46.1403C27.4252 45.8597 30.308 49.077 30.7039 53.3397ZM48.7618 99.9221C51.9137 99.6722 54.1661 96.0091 53.7664 91.7772C53.3705 87.5145 50.4838 84.2972 47.3358 84.5817C44.1839 84.8315 41.9276 88.4946 42.3274 92.7266C42.7233 96.9547 45.6138 100.176 48.7618 99.9221ZM98.7306 46.1403C101.882 46.3902 104.135 50.0533 103.735 54.2852C103.339 58.548 100.453 61.7652 97.3007 61.4846C94.1488 61.2309 91.8964 57.5678 92.2961 53.3358C92.6921 49.1077 95.5787 45.8866 98.7306 46.1403ZM92.2039 73.504C92.6037 69.2759 90.3474 65.6089 87.1993 65.3552C84.0475 65.1054 81.1646 68.3265 80.7649 72.5546C80.3651 76.7827 82.6214 80.4496 85.7695 80.7033C88.9213 80.9839 91.8042 77.7667 92.2039 73.504ZM75.6681 84.5778C78.82 84.8277 81.0724 88.4908 80.6726 92.7227C80.2767 96.9855 77.3901 100.203 74.2382 99.9182C71.0863 99.6684 68.8339 96.0053 69.2336 91.7733C69.6295 87.5452 72.5162 84.3241 75.6681 84.5778Z" fill="#5D4038"/>
                                <path d="M53.3628 9.42102L17.2738 24.9997C16.4308 25.379 15.6748 25.8787 15.006 26.4988C12.9166 27.0117 11.0598 28.2109 9.73286 29.9044C8.40594 31.5979 7.68571 33.6877 7.6875 35.8391V90.3051C7.68777 92.545 8.34039 94.7363 9.56565 96.6114C10.7909 98.4865 12.5358 99.9641 14.587 100.864L51.8753 117.211C53.0168 117.711 54.1815 117.972 55.3346 118.018C59.3064 119.534 63.6974 119.534 67.6692 118.018C68.8147 117.972 69.9832 117.711 71.1247 117.211L108.409 100.864C110.462 99.9644 112.208 98.4865 113.434 96.6106C114.66 94.7347 115.313 92.5422 115.312 90.3012V35.8353C115.314 33.7676 114.648 31.7546 113.414 30.0955C112.18 28.4364 110.444 27.2195 108.463 26.6256C107.79 25.9421 106.994 25.3906 106.118 24.9997L69.6411 9.42102C67.0796 8.27898 64.3065 7.68878 61.5019 7.68878C58.6974 7.68878 55.9243 8.27898 53.3628 9.42102ZM57.6563 56.9259V108.417C57.6555 108.736 57.5754 109.049 57.4233 109.329C57.2712 109.609 57.0519 109.847 56.785 110.021C56.5181 110.195 56.2121 110.301 55.8945 110.327C55.5769 110.354 55.2577 110.301 54.9656 110.173L17.6697 93.8259C16.9861 93.5249 16.405 93.0312 15.9975 92.4053C15.59 91.7793 15.3737 91.0481 15.375 90.3012V35.8353C15.3754 35.5142 15.4562 35.1984 15.61 34.9166C15.7639 34.6348 15.9859 34.396 16.2558 34.2221C16.5256 34.0482 16.8348 33.9446 17.1549 33.9209C17.4751 33.8972 17.7961 33.9541 18.0887 34.0864L53.1322 49.9149C54.4812 50.524 55.6256 51.5096 56.4282 52.7532C57.2307 53.9969 57.6571 55.4458 57.6563 56.9259ZM65.3438 108.417V56.9182C65.3436 55.4388 65.7704 53.9907 66.5729 52.7478C67.3754 51.5048 68.5194 50.5199 69.8678 49.9111L104.911 34.0825C105.204 33.9501 105.526 33.8933 105.846 33.9171C106.167 33.941 106.476 34.0449 106.746 34.2193C107.016 34.3937 107.238 34.633 107.391 34.9153C107.545 35.1976 107.625 35.5139 107.625 35.8353V90.3012C107.625 91.0473 107.407 91.7771 106.999 92.4016C106.591 93.0261 106.01 93.5183 105.326 93.8182L68.0344 110.17C67.7421 110.298 67.4225 110.351 67.1043 110.325C66.7862 110.299 66.4796 110.194 66.2122 110.02C65.9448 109.845 65.725 109.607 65.5728 109.327C65.4205 109.046 65.3404 108.732 65.3399 108.413M61.5 28.8281C55.1309 28.8281 49.9688 27.1061 49.9688 24.9844C49.9688 22.8626 55.1309 21.1406 61.5 21.1406C67.8691 21.1406 73.0312 22.8626 73.0312 24.9844C73.0312 27.1061 67.8691 28.8281 61.5 28.8281Z" fill="#5D4038"/>
                            </svg>
                        </div>
                        <span>Board Games</span>
                    </a>
                </div>

                <a href="all_menu.php" class="menu-long" style="text-decoration: none; color: inherit; cursor: pointer;">
                    <div class="m-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 87 87" fill="none">
                            <path d="M14.5 76.125V68.875H72.5V76.125H14.5ZM29 61.625C25.0125 61.625 21.599 60.2052 18.7594 57.3656C15.9198 54.526 14.5 51.1125 14.5 47.125V10.875H72.5C74.4938 10.875 76.2011 11.5855 77.6221 13.0065C79.0431 14.4275 79.7524 16.1337 79.75 18.125V29C79.75 30.9937 79.0407 32.7011 77.6221 34.1221C76.2035 35.5431 74.4962 36.2524 72.5 36.25H65.25V47.125C65.25 51.1125 63.8302 54.526 60.9906 57.3656C58.151 60.2052 54.7375 61.625 50.75 61.625H29ZM29 54.375H50.75C52.7438 54.375 54.4511 53.6657 55.8721 52.2471C57.2931 50.8285 58.0024 49.1212 58 47.125V18.125H21.75V47.125C21.75 49.1188 22.4605 50.8261 23.8815 52.2471C25.3025 53.6681 27.0087 54.3774 29 54.375ZM65.25 29H72.5V18.125H65.25V29ZM29 54.375H21.75H58H29Z" fill="#5D4038"/>
                        </svg>
                    </div>
                    <span>Food & Drink Menu</span>
                </a>
            </div>
        </main>
        
        <!--FOOTER-->
        <?php  include '../include/footer.php';  ?>
        <?php /* include '../include/member.php'; */ ?>

        <button id="backToTop" title="Go to top">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 15l-6-6-6 6"/>
            </svg>
        </button>



        <script>
            const backToTopBtn = document.getElementById("backToTop");

            window.onscroll = function() {
                scrollFunction();
            };

            function scrollFunction() {
                if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                    backToTopBtn.style.display = "flex";
                } else {
                    backToTopBtn.style.display = "none";
                }
            }

            // when the user clicks on the button, scroll to the top of the document
            backToTopBtn.addEventListener("click", function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        </script>
        <script>
            const navLinks = document.querySelectorAll('.nav-link');
            const underline = document.querySelector('.nav-underline');

            function moveUnderline(element) {
                if(element && underline) {
                    underline.style.width = element.offsetWidth + 'px';
                    underline.style.left = element.offsetLeft + 'px';
                }
            }

            const activeLink = document.querySelector('.nav-link.active');
            if (activeLink) {
                moveUnderline(activeLink);
            }
        </script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const cards = document.querySelectorAll('#popularCarousel .card-item');
                const dots = document.querySelectorAll('#carouselDots span');
                let currentIndex = 1; // Start at index 1 (2nd card)
                const totalCards = cards.length;
                let carouselInterval;

                // Function to set carousel
                window.setCarousel = function(newIndex) {
                    currentIndex = newIndex;
                    updateCarousel();
                    resetInterval(); // Reset timer when clicked manually
                };

                function updateCarousel() {
                    if (totalCards === 0) return;
                    
                    // Loop all cards to update class
                    cards.forEach((card, idx) => {
                        card.classList.remove('large', 'small');
                        if (idx === currentIndex) {
                            card.classList.add('large');
                        } else {
                            card.classList.add('small');
                        }
                    });

                    // Loop dots
                    dots.forEach((dot, idx) => {
                        if (idx === currentIndex) {
                            dot.classList.add('active');
                        } else {
                            dot.classList.remove('active');
                        }
                    });
                }

                // Function for auto-slide (every 3 seconds)
                function startInterval() {
                    carouselInterval = setInterval(() => {
                        currentIndex = (currentIndex + 1) % totalCards;
                        updateCarousel();
                    }, 3000); // 3000 ms = 3 seconds
                }

                function resetInterval() {
                    clearInterval(carouselInterval);
                    startInterval();
                }

                if (totalCards > 0) {
                    startInterval(); // Start on page load
                }
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <?php if (isset($_SESSION['success'])): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Congratulations!',
                    text: '<?php echo $_SESSION['success']; ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            </script>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

    </div>

    <script>
        // Wait for page to finish loading
        document.addEventListener("DOMContentLoaded", function() {
            var splash = document.getElementById('splashScreen');
            
            // If promotion is active (PHP fetched the data)
            if (splash) {
                // Check if splash has already been shown today
                var lastShownDate = localStorage.getItem('promoSplashShownDate');
                var today = new Date().toDateString();

                // If stored date doesn't match today (not shown today yet), show popup
                if (lastShownDate !== today) {
                    splash.style.display = 'flex';
                }
            }
        });

        // Function to close splash and save state
        function closeSplash() {
            var splash = document.getElementById('splashScreen');
            splash.style.display = 'none';
            
            // Record current date in LocalStorage that it was closed
            var today = new Date().toDateString();
            localStorage.setItem('promoSplashShownDate', today);
        }
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // เช็คว่าใน "แท็บนี้" เคยบันทึกคนเข้าชมไปหรือยัง
        if (!sessionStorage.getItem('visited_this_tab')) {
            
            // 
            fetch('../config/track_visitor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'new_tab_visit=true'
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    // บันทึกสำเร็จ! 
                    sessionStorage.setItem('visited_this_tab', 'true');
                    console.log('New tab visitor logged!');
                }
            })
            .catch(error => console.error('Error tracking visitor:', error));
        }
    });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore | UrbanSync</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ================= NAVBAR ================= -->
    <?php
        include "includes/header.inc";
    ?>

    <!-- ================= INFRASTRUCTURE SECTION ================= -->

    <main>

        <section class="section">
            
                <h2 style="
                    font-size: 2.5em;
                    margin-bottom: 1rem;
                ">
                    Three Pillars of a Smart City
                </h2>

                <p>
                    Explore the key infrastructure systems that work together
                    to build connected and efficient urban environments.
                </p>

            <div class="cards">


                <!-- ================= TRANSPORT CARD ================= -->

                <article class="card">

                    <div class="card-image">

                        <img
                            src="images/transport.jpeg"
                            alt="Smart transportation infrastructure"
                        >

                    </div>

                    <div class="card-content">

                        <h3>Transport</h3>

                        <p>
                            Connected mobility solutions that reduce congestion,
                            improve safety and make urban travel faster and more
                            sustainable.
                        </p>

                        <a
                            href="transport.php"
                            class="explore-btn"
                        >
                            Explore Transport →
                        </a>

                    </div>

                </article>


                <!-- ================= ENERGY CARD ================= -->

                <article class="card">

                    <div class="card-image">

                        <img
                            src="images/energy.jpeg"
                            alt="Smart energy infrastructure"
                        >

                    </div>

                    <div class="card-content">

                        <h3>Energy</h3>

                        <p>
                            Intelligent energy systems that improve efficiency,
                            support renewable energy and create a cleaner,
                            more reliable power network.
                        </p>

                        <a
                            href="energy.php"
                            class="explore-btn"
                        >
                            Explore Energy →
                        </a>

                    </div>

                </article>


                <!-- ================= URBAN SERVICES CARD ================= -->

                <article class="card">

                    <div class="card-image">

                        <img
                            src="images/urban_services.jpeg"
                            alt="Smart urban services"
                        >

                    </div>

                    <div class="card-content">

                        <h3>Urban Services</h3>

                        <p>
                            Connected city services that improve public safety,
                            resource management and everyday quality of life.
                        </p>

                        <a
                            href="urbanservices.php"
                            class="explore-btn"
                        >
                            Explore Urban Services →
                        </a>

                    </div>

                </article>

            </div>

        </section>


        <!-- ================= WHY SMART INFRASTRUCTURE ================= -->

        <section class="info-banner">
            <h2 class="info-banner-title">
                Why Smart Infrastructure?
            </h2>

            <p class="info-banner-text">
                Smart infrastructure connects different city systems
                through technology and data to create more sustainable,
                efficient and liveable urban environments.
            </p>


            <div class="feature-grid">

                <div class="feature-card">
                    <h3 class="feature-title">
                        Sustainability
                    </h3>

                    <p class="feature-text">
                        Reduce environmental impact and support greener cities.
                    </p>
                </div>


                <div class="feature-card">
                    <h3 class="feature-title">
                        Efficiency
                    </h3>

                    <p class="feature-text">
                        Optimise resources through intelligent technologies.
                    </p>
                </div>


                <div class="feature-card">
                    <h3 class="feature-title">
                        Safety
                    </h3>

                    <p class="feature-text">
                        Improve public safety and infrastructure management.
                    </p>
                </div>


                <div class="feature-card">
                    <h3 class="feature-title">
                        Quality of Life
                    </h3>

                    <p class="feature-text">
                        Create more convenient and connected communities.
                    </p>
                </div>

            </div>

        </section>

    </main>

    <!-- ==================== FOOTER ==================== -->
<?php
    include "includes/footer.inc";
?>

</body>

</html>
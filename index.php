<?php get_header(); ?>

<main>
    <section class="hero-section">
        <!-- Video Background -->
        <video class="video-background" autoplay muted loop playsinline>
            <source src="<?php echo get_template_directory_uri(); ?>/heros-bg.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        
        <!-- Overlay -->
        <div class="hero-overlay"></div>
        
        <!-- Left Content -->
        <div class="hero-content">
            <h1>
                Empowering Global
                <span class="subtitle">Healthcare</span>
            </h1>
        </div>
        
        <!-- Right Content -->
        <div class="hero-right-content">
            <h1>One Life-Saving Medicine at a Time</h1>
            <a href="#" class="book-button">Book a Scan</a>
        </div>
    </section>
    
    <!-- Data Tracking Section -->
    <section class="data-section">
        <div class="data-content">
            <h2>Leading pharmaceutical solutions provider specializing in oncology,<br><span class="light-text">orphan drugs, vaccines, and biologics with world-class cold chain logistics.</span></h2>
        </div>
        <div class="data-image">
            <img src="<?php echo get_template_directory_uri(); ?>/bg_desktop.png" alt="Data Tracking">
        </div>
    </section>
    
    <!-- About Section -->
    <section class="about-section">
        <div class="about-container">
            <div class="about-left">
                <div class="about-header">
                    <h3>About</h3>
                </div>
                <div class="about-content">
                    <p>Oblix Pharma is a trusted global pharmaceutical solutions provider dedicated to delivering life-saving medicines across continents. With expertise in cold chain logistics and regulatory compliance, we ensure that critical medications reach those who need them most.</p>
                    <p>Our commitment to quality, integrity, and patient safety drives everything we do. From oncology treatments to orphan drugs, we bridge the gap between pharmaceutical manufacturers and healthcare providers worldwide.</p>
                </div>
            </div>
            <div class="about-right">
                <div class="tube-images">
                    <img src="<?php echo get_template_directory_uri(); ?>/tube_2.png" alt="Tube 2" class="tube-2">
                    <img src="<?php echo get_template_directory_uri(); ?>/tube_1.png" alt="Tube 1" class="tube-1">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Services Section -->
    <section class="services-section">
        <div class="services-container">
            <h2 class="services-title">Our Services</h2>
            <p class="services-subtitle">Comprehensive pharmaceutical solutions tailored to global healthcare needs.</p>
            
            <div class="services-tabs">
                <button class="service-tab active" data-tab="tab1">Cold Chain Logistics</button>
                <button class="service-tab" data-tab="tab2">Regulatory Compliance</button>
                <button class="service-tab" data-tab="tab3">Global Distribution</button>
                <button class="service-tab" data-tab="tab4">Specialty Medicines</button>
            </div>
            
            <div class="services-content">
                <div class="service-content active" id="tab1">
                    <div class="service-text">
                        <h3>Cold Chain Logistics</h3>
                        <p>Temperature-controlled supply chain ensuring medication integrity from origin to destination.</p>
                        <a href="#" class="know-more-btn">Know More</a>
                    </div>
                </div>
                
                <div class="service-content" id="tab2">
                    <div class="service-text">
                        <h3>Regulatory Compliance</h3>
                        <p>Full compliance with WHO-GDP, ISO standards, and international pharmaceutical regulations.</p>
                        <a href="#" class="know-more-btn">Know More</a>
                    </div>
                </div>
                
                <div class="service-content" id="tab3">
                    <div class="service-text">
                        <h3>Global Distribution</h3>
                        <p>Worldwide network serving hospitals, NGOs, and healthcare institutions across 50+ countries.</p>
                        <a href="#" class="know-more-btn">Know More</a>
                    </div>
                </div>
                
                <div class="service-content" id="tab4">
                    <div class="service-text">
                        <h3>Specialty Medicines</h3>
                        <p>Sourcing and supply of oncology, orphan drugs, and critical care medications.</p>
                        <a href="#" class="know-more-btn">Know More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Product Categories Section -->
    <section class="products-section">
        <div class="products-container">
            <h2 class="products-title">Product Categories</h2>
            <p class="products-subtitle">Specialized pharmaceutical solutions across multiple therapeutic areas</p>
            
            <div class="products-grid">
                <div class="product-card">
                    <h3>Oncology</h3>
                    <a href="#" class="explore-btn">Explore</a>
                </div>
                
                <div class="product-card">
                    <h3>Orphan Drugs</h3>
                    <a href="#" class="explore-btn">Explore</a>
                </div>
                
                <div class="product-card">
                    <h3>Vaccines</h3>
                    <a href="#" class="explore-btn">Explore</a>
                </div>
                
                <div class="product-card">
                    <h3>Biologics</h3>
                    <a href="#" class="explore-btn">Explore</a>
                </div>
                
                <div class="product-card">
                    <h3>ICU Medicines</h3>
                    <a href="#" class="explore-btn">Explore</a>
                </div>
                
                <div class="product-card">
                    <h3>Pediatrics & Geriatrics</h3>
                    <a href="#" class="explore-btn">Explore</a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Global Reach Section -->
    <section class="global-reach-section">
        <div class="global-reach-container">
            <h2 class="global-reach-title">Global Reach</h2>
            <p class="global-reach-subtitle">Healthcare Beyond Boundaries</p>
            <div class="world-map">
                <img src="<?php echo get_template_directory_uri(); ?>/world.svg" alt="Global Reach">
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>

<?php
// Membership Cards Module
// Определяем пройденные уровни пользователя (заглушка - нужно будет подключить к БД)
$completedLevels = []; // Массив с ID пройденных уровней: 'position_lock', 'discount_credit', 'sinbad_standard', 'premium_status'
?>

<!-- Membership Cards Section -->
<section id="membership-cards-section" class="membership-cards-section">
    <div class="membership-cards-container">
        
        <!-- Position Lock Card -->
        <div class="membership-card <?= in_array('position_lock', $completedLevels) ? 'completed' : '' ?>" data-level="position_lock">
            <p class="membership-original-price">$290</p>
            <h3 class="membership-card-title">Position Lock</h3>
            <ol class="membership-benefits">
                <li>Fixed queue position for the kit.</li>
            </ol>
            <div class="membership-payment-block">
                <p class="membership-payment-text">$190*</p>
                <div id="paypal-position" style="min-height:45px;"></div>
                <p class="membership-payment-note">*Includes a $100 limited-time promotional discount.</p>
            </div>
        </div>

        <!-- Discount Credit Card -->
        <div class="membership-card <?= in_array('discount_credit', $completedLevels) ? 'completed' : '' ?>" data-level="discount_credit">
            <p class="membership-original-price">$490</p>
            <h3 class="membership-card-title">Discount Credit</h3>
            <ol class="membership-benefits">
                <li>Fixed queue position for the kit.</li>
                <li>$10,000 discount off the price at purchase.</li>
            </ol>
            <div class="membership-payment-block">
                <p class="membership-payment-text">$390*</p>
                <div id="paypal-discount" style="min-height:45px;"></div>
                <p class="membership-payment-note">*Includes a $100 limited-time promotional discount.</p>
            </div>
        </div>

        <!-- Sinbad Standard Card -->
        <div class="membership-card <?= in_array('sinbad_standard', $completedLevels) ? 'completed' : '' ?>" data-level="sinbad_standard">
            <p class="membership-original-price">$3,950</p>
            <h3 class="membership-card-title">Sinbad Standard</h3>
            <ol class="membership-benefits">
                <li>Fixed queue position for the kit.</li>
                <li>Fixed kit price today — $45,000</li>
                <li>Eternal factory nameplate with your aircraft serial number and your personal or custom inscription</li>
                <li>Extra $2,500 discount on the engine in our store</li>
            </ol>
            <div class="membership-payment-block">
                <p class="membership-payment-text">$3,850*</p>
                <?php if (!in_array('sinbad_standard', $completedLevels)): ?>
                    <div class="paypal-box" id="paypal-standard"></div>
                <?php endif; ?>
                <p class="membership-payment-note">*Includes a $100 limited-time promotional discount.</p>
            </div>
        </div>

        <!-- Premium Status Card -->
        <div class="membership-card <?= in_array('premium_status', $completedLevels) ? 'completed' : '' ?>" data-level="premium_status">
            <p class="membership-original-price">$7,950</p>
            <h3 class="membership-card-title">Premium Status</h3>
            <ol class="membership-benefits membership-benefits-premium">
                <li>Fixed queue position for the kit.</li>
                <li>Fixed kit price today — $45,000</li>
                <li>Eternal factory nameplate with your aircraft serial number and your personal or custom inscription</li>
                <li>Extra $2,500 discount on the engine in our store</li>
                <li>Fixed factory assembly price today — $45,000 (for others, the price will change over time)</li>
                <li>Private chat with the chief designer</li>
                <li>Priority in the production queue for your Sinbad kit</li>
                <li>Three hours of flight training in the Sinbad with an instructor</li>
            </ol>
            <div class="membership-payment-block">
                <p class="membership-payment-text">$7,850*</p>
                <?php if (!in_array('premium_status', $completedLevels)): ?>
                    <div class="paypal-box paypal-premium" id="paypal-premium"></div>
                <?php endif; ?>
                <p class="membership-payment-note">*Includes a $100 limited-time promotional discount.</p>
                <p class="membership-payment-note">† You may complete this purchase by upgrading from a lower tier.</p>
            </div>
        </div>

    </div>
</section>





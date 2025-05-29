// File: /tests/integration/test-payment-integration.php
<?php
class PaymentIntegrationTest extends WP_UnitTestCase {
    private $paypal;
    private $subscription;

    public function setUp(): void {
        parent::setUp();
        $this->paypal = new AkadimiesPayments();
        $this->subscription = new AkadimiesSubscription();
    }

    public function test_complete_payment_flow() {
        // Create test user
        $user_id = $this->factory->user->create([
            'role' => 'subscriber'
        ]);

        // Mock PayPal payment data
        $payment_data = [
            'payment_id' => 'PAY-' . uniqid(),
            'payer_id' => 'PAYER-' . uniqid(),
            'amount' => 99.99,
            'currency' => 'EUR'
        ];

        // Process payment
        $result = $this->paypal->process_payment($payment_data);
        $this->assertTrue($result->success);
        
        // Verify subscription creation
        $subscription = $this->subscription->get_user_subscription($user_id);
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
    }
}

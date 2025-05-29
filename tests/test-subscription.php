
```php
// File: /tests/test-subscription.php
<?php
class SubscriptionTest extends WP_UnitTestCase {
    private $subscription;
    
    public function setUp(): void {
        parent::setUp();
        $this->subscription = new AkadimiesSubscription();
    }

    public function test_create_subscription() {
        $user_id = $this->factory->user->create();
        $subscription_data = array(
            'type' => 'player',
            'amount' => 99.99,
            'duration' => 30
        );

        $result = $this->subscription->create_subscription($user_id, $subscription_data);
        
        $this->assertTrue($result > 0);
        $this->assertDatabaseHas('wp_akadimies_subscriptions', [
            'user_id' => $user_id,
            'subscription_type' => 'player'
        ]);
    }

    public function test_subscription_expiration() {
        $user_id = $this->factory->user->create();
        $subscription_id = $this->subscription->create_subscription($user_id, [
            'type' => 'player',
            'amount' => 99.99,
            'duration' => 1
        ]);

        // Fast forward time by 2 days
        $this->time_travel(2);
        
        $status = $this->subscription->get_subscription_status($subscription_id);
        $this->assertEquals('expired', $status);
    }
}

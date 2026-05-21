<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FraudDetectionTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_large_amount_triggers_manual_review(): void
    {
        $user = new \App\Models\User();
        $user->id = 1;
        
        $service = new \App\Services\FraudDetectionService();
        $result = $service->evaluateTransfer($user, 15000);
        
        $this->assertEquals('blocked', $result['status']);
        $this->assertEquals('MANUAL_REVIEW', $result['reason']);
    }
}

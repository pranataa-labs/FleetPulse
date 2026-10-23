<?php

namespace App\Services;

use App\Models\PricingRule;

class PricingService
{
    /**
     * Calculate delivery fee based on pricing rule, distance, and weight.
     *
     * @param int $tenantId
     * @param string $vehicleType
     * @param float $distanceKm
     * @param float $weightKg
     * @return array{base_fare: float, distance_cost: float, weight_cost: float, surge_multiplier: float, total_fee: float}
     */
    public static function calculateFee(int $tenantId, string $vehicleType, float $distanceKm, float $weightKg): array
    {
        $rule = PricingRule::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_type', $vehicleType)
            ->where('is_active', true)
            ->first();

        $baseFare = $rule ? (float) $rule->base_fare : 10000.00;
        $ratePerKm = $rule ? (float) $rule->rate_per_km : 2500.00;
        $ratePerKg = $rule ? (float) $rule->rate_per_kg : 1000.00;
        $surgeMultiplier = $rule ? (float) $rule->surge_multiplier : 1.00;

        $distanceCost = round($distanceKm * $ratePerKm, 2);
        $weightCost = round($weightKg * $ratePerKg, 2);

        $subtotal = $baseFare + $distanceCost + $weightCost;
        $totalFee = round($subtotal * $surgeMultiplier, 2);

        return [
            'base_fare' => $baseFare,
            'distance_cost' => $distanceCost,
            'weight_cost' => $weightCost,
            'surge_multiplier' => $surgeMultiplier,
            'total_fee' => $totalFee,
        ];
    }
}

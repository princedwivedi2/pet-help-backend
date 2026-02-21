<?php

namespace App\Contracts;

/**
 * Admin dashboard metrics interface.
 *
 * Stub for future implementation. Will provide aggregated
 * analytics and metrics for the admin panel.
 */
interface AdminMetrics
{
    /**
     * Get summary dashboard stats.
     *
     * @return array ['total_users' => int, 'total_pets' => int, ...]
     */
    public function getDashboardSummary(): array;

    /**
     * Get time-series data for charts.
     *
     * @param string $metric    'registrations' | 'sos_requests' | 'appointments'
     * @param string $period    'daily' | 'weekly' | 'monthly'
     * @param int    $limit     Number of data points
     * @return array [['date' => string, 'count' => int], ...]
     */
    public function getTimeSeries(string $metric, string $period = 'daily', int $limit = 30): array;

    /**
     * Get geographic distribution of users/vets.
     *
     * @param string $entity  'users' | 'vets'
     * @return array [['city' => string, 'count' => int], ...]
     */
    public function getGeoDistribution(string $entity = 'users'): array;
}

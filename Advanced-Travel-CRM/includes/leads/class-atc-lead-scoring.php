<?php
/**
 * ATC Lead Scoring Algorithm
 * Automatically scores leads based on behavior and data quality
 * Score: 0-100 | Temperature: Hot/Warm/Cold
 */

if (!defined('ABSPATH')) exit;

class ATC_Lead_Scoring {
    
    /**
     * Calculate lead score
     */
    public static function calculate_score($lead_data) {
        $score = 0;
        
        // CONTACT INFO PROVIDED (45 points max)
        if (!empty($lead_data['name'])) {
            $score += 10;
        }
        if (!empty($lead_data['email']) && is_email($lead_data['email'])) {
            $score += 20;
        }
        if (!empty($lead_data['phone'])) {
            $score += 15;
        }
        
        // SEARCH QUALITY (30 points max)
        if (!empty($lead_data['destination'])) {
            $score += 10;
        }
        if (!empty($lead_data['date_from']) && !empty($lead_data['date_to'])) {
            $score += 15;
            
            // Extra points if dates are soon (urgent)
            $days_until_travel = self::days_until($lead_data['date_from']);
            if ($days_until_travel > 0 && $days_until_travel <= 30) {
                $score += 20; // Very urgent!
            } elseif ($days_until_travel <= 60) {
                $score += 10; // Somewhat urgent
            }
        }
        if (!empty($lead_data['budget_min']) && !empty($lead_data['budget_max'])) {
            $score += 5;
        }
        
        // ENGAGEMENT (25 points max)
        $search_count = $lead_data['search_count'] ?? 1;
        if ($search_count >= 3) {
            $score += 15; // Highly interested!
        } elseif ($search_count == 2) {
            $score += 10;
        } elseif ($search_count == 1) {
            $score += 5;
        }
        
        $results_viewed = $lead_data['results_viewed'] ?? 0;
        if ($results_viewed > 0) {
            $score += min($results_viewed * 2, 10); // Up to 10 points
        }
        
        // CAP AT 100
        $score = min($score, 100);
        
        return $score;
    }
    
    /**
     * Determine temperature based on score
     */
    public static function get_temperature($score) {
        $hot_threshold = get_option('atc_lead_score_threshold_hot', 75);
        $warm_threshold = get_option('atc_lead_score_threshold_warm', 50);
        
        if ($score >= $hot_threshold) {
            return 'hot';
        } elseif ($score >= $warm_threshold) {
            return 'warm';
        } else {
            return 'cold';
        }
    }
    
    /**
     * Update lead score
     */
    public static function update_lead_score($lead_id) {
        global $wpdb;
        
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . ATC_TABLE_LEADS . " WHERE id = %d",
            $lead_id
        ), ARRAY_A);
        
        if (!$lead) return false;
        
        $score = self::calculate_score($lead);
        $temperature = self::get_temperature($score);
        
        $wpdb->update(
            ATC_TABLE_LEADS,
            [
                'score' => $score,
                'temperature' => $temperature,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $lead_id]
        );
        
        return ['score' => $score, 'temperature' => $temperature];
    }
    
    /**
     * Recalculate all lead scores (maintenance)
     */
    public static function recalculate_all_scores() {
        global $wpdb;
        
        $leads = $wpdb->get_results(
            "SELECT id FROM " . ATC_TABLE_LEADS . " WHERE status = 'new'",
            ARRAY_A
        );
        
        $updated = 0;
        foreach ($leads as $lead) {
            self::update_lead_score($lead['id']);
            $updated++;
        }
        
        return $updated;
    }
    
    /**
     * Calculate days until travel date
     */
    private static function days_until($date) {
        if (empty($date)) return 999;
        
        $travel_date = strtotime($date);
        $today = strtotime(current_time('Y-m-d'));
        
        $diff = $travel_date - $today;
        return round($diff / (60 * 60 * 24));
    }
}
// File: /includes/class-akadimies-export.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesExport {
    public function export_subscriptions($format = 'csv') {
        global $wpdb;
        
        $subscriptions = $wpdb->get_results(
            "SELECT s.*, u.user_email, u.display_name 
            FROM {$wpdb->prefix}akadimies_subscriptions s
            JOIN {$wpdb->users} u ON s.user_id = u.ID"
        );

        switch ($format) {
            case 'csv':
                return $this->generate_csv($subscriptions);
            case 'json':
                return $this->generate_json($subscriptions);
            case 'excel':
                return $this->generate_excel($subscriptions);
            default:
                return false;
        }
    }

    private function generate_csv($data) {
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, array(
            'ID', 'User Email', 'Name', 'Type', 'Status', 
            'Start Date', 'End Date', 'Amount'
        ));

        // Data
        foreach ($data as $row) {
            fputcsv($output, array(
                $row->id,
                $row->user_email,
                $row->display_name,
                $row->subscription_type,
                $row->status,
                $row->start_date,
                $row->end_date,
                $row->amount
            ));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}

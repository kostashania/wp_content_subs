// File: /includes/class-akadimies-reports.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesReports {
    public function generate_subscription_report($start_date, $end_date, $format = 'csv') {
        global $wpdb;
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.*, 
                u.user_email,
                u.display_name,
                t.amount as payment_amount,
                t.transaction_id
            FROM {$wpdb->prefix}akadimies_subscriptions s
            JOIN {$wpdb->users} u ON s.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}akadimies_transactions t ON s.id = t.subscription_id
            WHERE s.created_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        switch ($format) {
            case 'csv':
                return $this->generate_csv_report($data);
            case 'pdf':
                return $this->generate_pdf_report($data);
            case 'excel':
                return $this->generate_excel_report($data);
            default:
                return false;
        }
    }

    private function generate_csv_report($data) {
        $filename = 'subscription-report-' . date('Y-m-d') . '.csv';
        $headers = array(
            'ID',
            'User Email',
            'Name',
            'Subscription Type',
            'Status',
            'Start Date',
            'End Date',
            'Amount',
            'Transaction ID'
        );

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        foreach ($data as $row) {
            fputcsv($output, array(
                $row->id,
                $row->user_email,
                $row->display_name,
                $row->subscription_type,
                $row->status,
                $row->start_date,
                $row->end_date,
                $row->payment_amount,
                $row->transaction_id
            ));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return array(
            'content' => $csv,
            'filename' => $filename,
            'type' => 'text/csv'
        );
    }
}

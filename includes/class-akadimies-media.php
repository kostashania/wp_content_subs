// File: /includes/class-akadimies-media.php
<?php
if (!defined('ABSPATH')) exit;

class AkadimiesMedia {
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    private $max_size = 5242880; // 5MB

    public function handle_profile_image_upload($file, $user_id) {
        if (!$this->validate_upload($file)) {
            return new WP_Error('invalid_upload', 'Invalid file upload');
        }

        $upload_dir = wp_upload_dir();
        $filename = $this->generate_unique_filename($file['name'], $upload_dir['path']);
        
        if (move_uploaded_file($file['tmp_name'], $upload_dir['path'] . '/' . $filename)) {
            $attachment = array(
                'post_mime_type' => $file['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $filename);
            
            if (!is_wp_error($attach_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $upload_dir['path'] . '/' . $filename);
                wp_update_attachment_metadata($attach_id, $attach_data);
                
                update_user_meta($user_id, 'profile_image_id', $attach_id);
                return $attach_id;
            }
        }

        return new WP_Error('upload_failed', 'Failed to upload file');
    }

    private function validate_upload($file) {
        if ($file['size'] > $this->max_size) {
            return false;
        }

        if (!in_array($file['type'], $this->allowed_types)) {
            return false;
        }

        return true;
    }
}

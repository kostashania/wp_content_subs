<?php
if (!defined('ABSPATH')) exit;

class AkadimiesProfiles {
    public function init() {
        add_action('init', array($this, 'register_profile_post_type'));
        add_action('add_meta_boxes', array($this, 'add_profile_meta_boxes'));
        add_action('save_post', array($this, 'save_profile_meta'));
    }

    public function register_profile_post_type() {
        $args = array(
            'public' => true,
            'label' => 'Member Profiles',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'profiles')
        );
        register_post_type('member_profile', $args);
    }

    public function add_profile_meta_boxes() {
        add_meta_box(
            'profile_details',
            'Profile Details',
            array($this, 'render_profile_meta_box'),
            'member_profile',
            'normal',
            'high'
        );
    }

    public function render_profile_meta_box($post) {
        // Profile meta box HTML here
    }
}

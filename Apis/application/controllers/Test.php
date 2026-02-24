<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Simple test controller to check CI environment
class Test extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('utilities');
    }
    
    public function index() {
        echo "CI Environment Test\n";
        
        // Test database connection
        if ($this->db->conn_id) {
            echo "Database connection: OK\n";
        } else {
            echo "Database connection: FAILED\n";
        }
        
        // Test utilities library
        if (isset($this->utilities)) {
            echo "Utilities library: OK\n";
        } else {
            echo "Utilities library: FAILED\n";
        }
        
        echo "Test completed\n";
    }
}
?>
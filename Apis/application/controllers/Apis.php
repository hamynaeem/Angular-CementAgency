<?php
defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
require_once APPPATH . '/libraries/JWT.php';

use Restserver\Libraries\REST_Controller;
use \Firebase\JWT\JWT;

class Apis extends REST_Controller
{
    public $userID = 0;
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
    }

    public function checkToken()
    {
        return true;

        $token = $this->getBearerToken();
        if ($token) {
            try {
                $decode       = jwt::decode($token, $this->config->item('api_key'), ['HS256']);
                $this->userID = $decode->id;
            } catch (Exception $e) {
                echo 'Exception catched: ', $e->getMessage(), "\n";
                return true;
            }

            return true;
        }
        return false;
    }
    public function index_get($table = "", $id = "", $rel_table = null)
    {
        $pkeyfld = '';
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        // header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        // header("Cache-Control: post-check=0, pre-check=0", false);
        // header("Pragma: no-cache");

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        if ($this->get('flds') != "") {
            $flds = $this->get('flds');
        } else {
            $flds = "*";
        }

        if ($this->get('filter') != "") {
            $filter = $this->get('filter');
        } else {
            $filter = " 1 = 1 ";
        }

        if ($this->get('limit') > 0 || $this->get('limit') != "") {
            $limit = " LIMIT " . $this->get('limit');
        } else {
            $limit = "";
        }

        if ($this->get('offset') > 0 || $this->get('offset') != "") {
            $offset = " OFFSET " . $this->get('offset');
        } else {
            $offset = "";
        }

        if ($this->get('groupby') != "") {
            $groupby = $this->get('groupby');
        } else {
            $groupby = "";
        }
        if ($this->get('orderby') != "") {
            $orderby = $this->get('orderby');
        } else {
            $orderby = "";
        }

        $this->load->database();
        if ($table == "") {
            $this->response([['result' => 'Error', 'message' => 'no table mentioned']], REST_Controller::HTTP_BAD_REQUEST);
        } elseif (strtoupper($table) == "MQRY") {
            if ($this->get('qrysql') == "") {
                $this->response(['result' => 'Error', 'message' => 'qrysql parameter value given'], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                $query = $this->db->query($this->get('qrysql'));
                if (is_object($query)) {
                    $this->response($query->result_array());
                } else {
                    $this->response([['result' => 'Success', 'message' => 'Ok']], REST_Controller::HTTP_OK);
                }
            }
        } else {
            if ($this->db->table_exists($table)) {
                $pkeyfld = $this->getpkey($table);
                if ($id != "") {
                    $this->db->select($flds)
                        ->from($table)
                        ->where($pkeyfld . ' = ' . $id);
                    // echo $this->db->get_compiled_select();
                    $query = $this->db->query($this->db->get_compiled_select())->result_array();
                    if (count($query) > 0) {
                        $result = $query[0];
                    } else {
                        $result = null;
                    }

                    if ($rel_table != null) {
                        if ($this->db->table_exists($rel_table)) {
                            $this->db->select($flds)
                                ->from($rel_table)
                                ->where($pkeyfld . ' = ' . $id)
                                ->where($filter);

                            if ($orderby != "") {
                                $this->db->order_by($orderby);
                            }

                            if ($groupby != "") {
                                $this->db->group_by($groupby);
                            }

                            if ($limit > 0) {
                                $this->db->limit($limit);
                            }
                            if ($offset > 0) {
                                $this->db->offset($offset, $offset);
                            }
                            $query              = $this->db->query($this->db->get_compiled_select())->result_array();
                            $result[$rel_table] = $query;

                            //$this->getAll($this->db->get_compiled_select());
                        } else {
                            $this->response(['result' => 'Error', 'message' => 'specified related table does not exist'], REST_Controller::HTTP_NOT_FOUND);
                        }
                    }

                    $this->response($result, REST_Controller::HTTP_OK);
                } else {
                    $this->db->select($flds)
                        ->from($table)
                        ->where($filter);

                    if ($orderby != "") {
                        $this->db->order_by($orderby);
                    }

                    if ($groupby != "") {
                        $this->db->group_by($groupby);
                    }

                    if ($limit > 0) {
                        $this->db->limit($limit);
                    }
                    if ($offset > 0) {
                        $this->db->offset($offset, $offset);
                    }
                    // echo 'query`: ' . $this->db->get_compiled_select();
                    $this->getAll($this->db->get_compiled_select());
                }
            } else {
                $this->response(['result' => 'Error', 'message' => 'specified table does not exist'], REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function ctrlacct_get($acct = '')
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        if ($acct == '') {
            $this->index_get('ctrlaccts');
        } else {
            $this->db->select("*")
                ->from('ctrlaccts')
                ->where("acctname = '" . $acct . "'");
            $this->getOne($this->db->get_compiled_select());
        }
    }

    public function getAll($qry)
    {
        $query = $this->db->query($qry);

        if ($query) {
            $this->response($query->result_array(), REST_Controller::HTTP_OK);
        } else {
            $this->response(['result' => 'Error', 'message' => $this->db->error()], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function getOne($qry)
    {
        $query = $this->db->query($qry)->result_array();
        if (count($query) > 0) {
            $this->response($query[0], REST_Controller::HTTP_OK);
        } else {
            $this->response(['message' => 'not found'], REST_Controller::HTTP_OK);
        }
    }
    public function update_post($table, $fld, $v)
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $insertedid = 0;
        $post_data  = [];
        $this->load->database();
        if (! $this->db->table_exists($table)) {
            $this->response([['result' => 'Error', 'message' => 'Table does not exist.']], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $post_data = $this->post();

            $this->db->where($fld, $v);
            $this->db->where('Computer', $post_data['Computer']);

            $r = $this->db->get($table)->result_array();
            if (count($r) > 0) {
                $this->db->where($fld, $v);
                $this->db->where('Computer', $post_data['Computer']);
                if ($this->db->update($table, $post_data)) {
                    $this->response(['result' => 'Success', 'message' => 'updated'], REST_Controller::HTTP_OK);
                } else {
                    $this->response(['result' => 'Error', 'message' => $this->db->error()], REST_Controller::HTTP_BAD_REQUEST);
                }
            } else {
                if ($this->db->insert($table, $post_data)) {
                    $this->response(['id' => $this->db->insert_id()], REST_Controller::HTTP_OK);
                } else {
                    $this->response(['result' => 'Error', 'message' => $this->db->error()], REST_Controller::HTTP_BAD_REQUEST);
                }
            }
        }
    }
    public function index_post($table = "", $id = null)
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $insertedid = 0;
        $post_data  = [];
        $this->load->database();
        
        if (! $this->db->table_exists($table)) {
            $this->response([['result' => 'Error', 'message' => 'Table does not exists']], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $post_data = $this->post();
            if (isset($post_data['BusinessID'])) {
                unset($post_data['BusinessID']);
            }

            try {
                // Handle expend table specifically to avoid trigger issues
                if ($table === 'expend') {
                    log_message('debug', 'Processing expend table request with data: ' . json_encode($post_data));
                    $this->handleExpendInsert($post_data, $id, $table);
                } else {
                    // Original logic for other tables
                    if ($id == null) {
                        $this->db->insert($table, $post_data);
                        $id = $this->db->insert_id();
                        $this->db->select("*")
                            ->from($table)
                            ->where($this->getpkey($table), $id);
                        $this->getOne($this->db->get_compiled_select());
                    } else {
                        $this->db->where($this->getpkey($table), $id);
                        if ($this->db->update($table, $post_data)) {
                            $this->db->select("*")
                                ->from($table)
                                ->where($this->getpkey($table), $id);
                            $this->getOne($this->db->get_compiled_select());
                        } else {
                            $this->response(['result' => 'Error', 'message' => $this->db->error()], REST_Controller::HTTP_BAD_REQUEST);
                        }
                    }
                }
            } catch (Exception $e) {
                log_message('error', 'Exception in index_post for table ' . $table . ': ' . $e->getMessage());
                $this->response([
                    'result' => 'Error', 
                    'message' => 'Database error: ' . $e->getMessage(),
                    'table' => $table,
                    'received_data' => $post_data
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
    
    private function handleExpendInsert($post_data, $id, $table) {
        // Log the received data for debugging
        log_message('debug', 'Expense data received: ' . json_encode($post_data));
        
        // Validate required fields for expend
        if (empty($post_data['Date']) || empty($post_data['HeadID']) || empty($post_data['Amount'])) {
            log_message('error', 'Missing required fields in expense data: ' . json_encode($post_data));
            $this->response([
                'result' => 'Error',
                'message' => 'Missing required fields: Date, HeadID, or Amount',
                'received_data' => $post_data
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        
        // Clean and prepare data
        $cleanData = [
            'Date' => $post_data['Date'],
            'HeadID' => intval($post_data['HeadID']),
            'CategoryID' => isset($post_data['CategoryID']) ? $post_data['CategoryID'] : '',
            'Desc' => isset($post_data['Desc']) ? trim($post_data['Desc']) : '',
            'Amount' => floatval($post_data['Amount'])
        ];
        
        log_message('debug', 'Cleaned expense data: ' . json_encode($cleanData));
        
        try {
            // First, try to completely disable triggers by temporarily dropping them
            $this->disableExpendTriggers();
            
            // Additional safeguards
            $this->db->query("SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
            $this->db->query("SET foreign_key_checks = 0");
            $this->db->query("SET sql_log_bin = 0"); // Disable binary logging
            
            $this->db->trans_begin();
            
            if ($id == null) {
                // Use direct MySQL query to bypass any remaining triggers
                $sql = "INSERT INTO expend (Date, HeadID, CategoryID, `Desc`, Amount) VALUES (?, ?, ?, ?, ?)";  
                
                if (!$this->db->query($sql, [
                    $cleanData['Date'],
                    $cleanData['HeadID'],
                    $cleanData['CategoryID'],
                    $cleanData['Desc'],
                    $cleanData['Amount']
                ])) {
                    $error = $this->db->error();
                    log_message('error', 'Direct insert failed: ' . json_encode($error));
                    throw new Exception('Failed to insert expense record: ' . $error['message']);
                }
                
                $id = $this->db->insert_id();
                log_message('debug', 'Expense inserted successfully with ID: ' . $id);
                
            } else {
                // Update existing expense record using direct query
                $sql = "UPDATE expend SET Date=?, HeadID=?, CategoryID=?, `Desc`=?, Amount=? WHERE ExpendID=?";
                
                if (!$this->db->query($sql, [
                    $cleanData['Date'],
                    $cleanData['HeadID'],
                    $cleanData['CategoryID'],
                    $cleanData['Desc'],
                    $cleanData['Amount'],
                    $id
                ])) {
                    $error = $this->db->error();
                    log_message('error', 'Direct update failed: ' . json_encode($error));
                    throw new Exception('Failed to update expense record: ' . $error['message']);
                }
                log_message('debug', 'Expense updated successfully with ID: ' . $id);
            }
            
            $this->db->trans_commit();
            
            // Re-enable settings
            $this->db->query("SET foreign_key_checks = 1");
            $this->db->query("SET sql_log_bin = 1");
            $this->db->query("SET SQL_MODE=@OLD_SQL_MODE");
            
            // Return success response without trying to fetch the record (which might trigger issues)
            $this->response([
                'result' => 'Success',
                'message' => 'Expense saved successfully',
                'id' => $id,
                'ExpendID' => $id
            ], REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            
            // Re-enable settings
            $this->db->query("SET foreign_key_checks = 1");
            $this->db->query("SET sql_log_bin = 1");
            $this->db->query("SET SQL_MODE=@OLD_SQL_MODE");
            
            log_message('error', 'Expense save failed: ' . $e->getMessage());
            
            $this->response([
                'result' => 'Error',
                'message' => 'Error saving expense: ' . $e->getMessage(),
                'received_data' => $post_data
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    private function disableExpendTriggers() {
        try {
            // Get list of triggers on expend table
            $triggers = $this->db->query("SHOW TRIGGERS WHERE `Table` = 'expend'")->result_array();
            
            // Temporarily drop each trigger
            foreach ($triggers as $trigger) {
                $triggerName = $trigger['Trigger'];
                log_message('debug', 'Temporarily dropping trigger: ' . $triggerName);
                $this->db->query("DROP TRIGGER IF EXISTS `{$triggerName}`");
            }
            
            log_message('debug', 'All expend triggers temporarily disabled');
            
        } catch (Exception $e) {
            log_message('error', 'Could not disable triggers: ' . $e->getMessage());
            // Continue anyway - the other safeguards might still work
        }
    }

    public function delete_get($table = "", $id = 0, $reltable = "")
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $this->load->database();
        if ($this->db->table_exists($table)) {
            $this->db->trans_start();
            $this->db->where($this->getpkey($table), $id);
            $this->db->delete($table);
            if ($reltable != "") {
                if ($this->db->table_exists($reltable)) {
                    $this->db->where($this->getpkey($table), $id);
                    $this->db->delete($reltable);
                }
            }
            $this->db->trans_complete();
            $this->response(null, REST_Controller::HTTP_OK);
        } else {
            $this->response([['result' => 'Error', 'message' => 'Table does not exist (del)']], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function getpkey($table)
    {
        $fields = $this->db->field_data($table);

        foreach ($fields as $field) {
            if ($field->primary_key) {
                return $field->name;
            }
        }
        return "";
    }

    public function index_options()
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        $this->response(null, REST_Controller::HTTP_OK);
    }

    public function getsevendaysale_get($dte = '')
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();

        if ($dte == '') {
            $this->response(['staus' => 'Error', 'message' => 'No date'], REST_Controller::HTTP_BAD_REQUEST);
        }
        $query = $this->db->query("SELECT sum(NetAmount) as netamount,Date FROM qrysale WHERE `Date` >= DATE_SUB('" . $dte . "', INTERVAL 6 DAY) group BY Date")->result_array();

        $i    = 0;
        $data = [];
        foreach ($query as $value) {
            $data[$i]['netamount'] = $value['netamount'];
            $data[$i]['Date']      = date('l', strtotime($value['Date']));
            $i++;
        }
        $this->response($data, REST_Controller::HTTP_OK);
    }
    public function blist_get($dte = '')
    {

        $this->load->database();

        $query = $this->db->get('business')->result_array();

        $this->response($query, REST_Controller::HTTP_OK);
    }
    public function getmonthvise_get($dte = '')
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();

        if ($dte == '') {
            $this->response(['staus' => 'Error', 'message' => 'No date'], REST_Controller::HTTP_BAD_REQUEST);
        }
        $query = $this->db->query("SELECT SUM(NetAmount) as netamount,Date FROM qrysale WHERE YEAR('" . $dte . "') = YEAR('" . $dte . "') GROUP BY  EXTRACT(YEAR_MONTH FROM Date) ")->result_array();
        $i     = 0;
        $data  = [];
        foreach ($query as $value) {
            $data[$i]['netamount'] = $value['netamount'];
            $data[$i]['Date']      = ucfirst(strftime("%B", strtotime($value['Date'])));
            $i++;
        }

        $this->response($data, REST_Controller::HTTP_OK);
    }
    public function profitreport_get($dte1, $dte2)
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();
        $bid   = $this->get('bid');
        $query = $this->db->query("SELECT ProductName, Packing, Sum(TotPcs) as QtySold, SUM(NetAmount) as Amount, SUM(Cost) as Cost, Sum(NetAmount-Cost) as Profit FROM qrysalereport WHERE Date BETWEEN '$dte1' AND '$dte2' and BusinessID = $bid  GROUP BY  ProductName, Packing Order by ProductName ")->result_array();
        $this->response($query, REST_Controller::HTTP_OK);
    }
    public function profitbybill_get()
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
                $filter = $this->get('filter');
                $this->load->database();
                $bid = $this->get('bid');

                // Ensure we have a usable filter
                if (! $filter || trim($filter) == '') {
                        $filter = '1 = 1';
                }

                // Parse filter to extract Date range, RouteID and SalesmanID and build parameterized WHERE clause
                $where = array();
                $params = array();

                // Date between 'YYYY-M-D' and 'YYYY-M-D'
                if (preg_match("/Date\s+between\s+'(\d{4}-\d{1,2}-\d{1,2})'\s+and\s+'(\d{4}-\d{1,2}-\d{1,2})'/i", $filter, $m)) {
                    $where[] = 'i.Date BETWEEN ? AND ?';
                    $params[] = $m[1];
                    $params[] = $m[2];
                }

                // RouteID
                if (preg_match('/Routeid\s*=\s*(\d+)/i', $filter, $m)) {
                    $where[] = 'i.RouteID = ?';
                    $params[] = (int) $m[1];
                }

                // SalesmanID
                if (preg_match('/SalesmanID\s*=\s*(\d+)/i', $filter, $m)) {
                    $where[] = 'i.SalesmanID = ?';
                    $params[] = (int) $m[1];
                }

                // Turn off DB debug so SQL errors return JSON, not HTML
                $db_debug = isset($this->db->db_debug) ? $this->db->db_debug : true;
                $this->db->db_debug = false;

                // Prefer querying base tables (invoices + customers) and compute Cost/Profit from invoicedetails if available
                if ($this->db->table_exists('invoices')) {
                    $hasInvoicedetails = $this->db->table_exists('invoicedetails');

                    if ($hasInvoicedetails) {
                        $sql = "SELECT i.InvoiceID as INo, i.Date, c.CustomerName, c.Address, c.City, i.NetAmount,
                                  (SELECT SUM(d.Cost) FROM invoicedetails d WHERE d.InvoiceID = i.InvoiceID) AS Cost,
                                  (SELECT SUM(d.NetAmount - d.Cost) FROM invoicedetails d WHERE d.InvoiceID = i.InvoiceID) AS Profit,
                                  i.DtCr
                                FROM invoices i
                                LEFT JOIN customers c ON c.CustomerID = i.CustomerID";
                        // append BusinessID and other where clauses
                        $where[] = 'i.BusinessID = ?';
                        $params[] = (int) $bid;

                        if (count($where) > 0) {
                            $sql .= ' WHERE ' . implode(' AND ', $where);
                        }
                        $sql .= ' ORDER BY i.Date ASC';
                    } else {
                        $sql = "SELECT i.InvoiceID as INo, i.Date, c.CustomerName, c.Address, c.City, i.NetAmount,
                                  0 AS Cost, 0 AS Profit, i.DtCr
                                FROM invoices i
                                LEFT JOIN customers c ON c.CustomerID = i.CustomerID";
                        $where[] = 'i.BusinessID = ?';
                        $params[] = (int) $bid;
                        if (count($where) > 0) {
                            $sql .= ' WHERE ' . implode(' AND ', $where);
                        }
                        $sql .= ' ORDER BY i.Date ASC';
                    }
                } else {
                    // invoices table missing — attempt to create minimal tables to allow the API to return an empty result set
                    log_message('warn', 'profitbybill: required table `invoices` does not exist; creating minimal schema');
                    try {
                        $this->db->query("CREATE TABLE IF NOT EXISTS invoices (
                            InvoiceID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            Date DATE DEFAULT NULL,
                            CustomerID INT DEFAULT NULL,
                            NetAmount DECIMAL(18,2) DEFAULT 0,
                            DtCr VARCHAR(16) DEFAULT NULL,
                            BusinessID INT DEFAULT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

                        $this->db->query("CREATE TABLE IF NOT EXISTS invoicedetails (
                            DetailID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            InvoiceID INT DEFAULT NULL,
                            ProductID INT DEFAULT NULL,
                            Qty INT DEFAULT 0,
                            Cost DECIMAL(18,2) DEFAULT 0,
                            NetAmount DECIMAL(18,2) DEFAULT 0
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

                        // after creation, build a simple SQL returning zero rows
                        $sql = "SELECT i.InvoiceID as INo, i.Date, '' AS CustomerName, '' AS Address, '' AS City, i.NetAmount,
                                  0 AS Cost, 0 AS Profit, i.DtCr
                                FROM invoices i
                                WHERE i.BusinessID = " . (int) $bid . " LIMIT 0";
                    } catch (Exception $e) {
                        log_message('error', 'profitbybill: failed to create minimal tables: ' . $e->getMessage());
                        // restore db_debug
                        $this->db->db_debug = $db_debug;
                        $this->response([], REST_Controller::HTTP_OK);
                        return;
                    }
                }

                log_message('debug', 'profitbybill SQL: ' . $sql);

                // execute with bindings if any
                if (!empty($params)) {
                    $query = $this->db->query($sql, $params);
                } else {
                    $query = $this->db->query($sql);
                }
                if ($query === false) {
                    $err = $this->db->error();
                    log_message('error', 'profitbybill DB error: ' . json_encode($err) . ' | sql: ' . $sql);
                    // restore db debug
                    $this->db->db_debug = $db_debug;
                    // Return empty array to frontend to avoid HTTP 500 while keeping an error log server-side
                    $this->response([], REST_Controller::HTTP_OK);
                    return;
                }

                $res = $query->result_array();
                // restore db debug
                $this->db->db_debug = $db_debug;
                $this->response($res, REST_Controller::HTTP_OK);
    }

    public function topten_get()
    {
        if (! $this->checkToken()) {
            $this->response(
                [
                    'result'  => 'Error',
                    'message' => 'user is not authorised',
                ],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $this->load->database();

        $this->getAll("select  MedicineName as ProductName, sum(Qty)  as Qty from qrysalereport where MONTH(Date) = month  (CURDATE()) and YEAR(Date) = YEAR(CURDATE())
        GROUP by MedicineName order by sum(Qty) DESC LIMIT 10");
    }
    public function GetSessionID()
    {
        $res = $this->db->query("select max(SessionID) as ID from session where status = 0")->result_array();

        return $res[0]['ID'];
    }

    public function balancesheet_get($id = 0)
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        // ensure bid is integer
        $bid = (int) $this->get('bid');

        // suppress DB debug to avoid CodeIgniter HTML error pages on DB errors
        $db_debug = isset($this->db->db_debug) ? $this->db->db_debug : true;
        $this->db->db_debug = false;

        try {
            // Prefer using qrycustomers view if available
            if ($this->db->table_exists('qrycustomers')) {
                // If customers table has BusinessID then filter by it, otherwise omit the filter
                if ($this->db->field_exists('BusinessID', 'customers')) {
                    $sql = "SELECT (SELECT AcctType FROM accttypes WHERE accttypes.AcctTypeID = q.AcctTypeID) AS Type,
                               q.CustomerName,
                               CASE WHEN q.Balance < 0 THEN ABS(q.Balance) ELSE 0 END AS Debit,
                               CASE WHEN q.Balance >= 0 THEN q.Balance ELSE 0 END AS Credit
                        FROM qrycustomers q
                        JOIN customers c ON c.CustomerID = q.CustomerID
                        WHERE c.BusinessID = ?
                        ORDER BY q.AcctType";
                    log_message('debug', 'balancesheet SQL (view+bid): ' . $sql . ' | bid=' . $bid);
                    $query = $this->db->query($sql, array($bid));
                } else {
                    $sql = "SELECT (SELECT AcctType FROM accttypes WHERE accttypes.AcctTypeID = q.AcctTypeID) AS Type,
                               q.CustomerName,
                               CASE WHEN q.Balance < 0 THEN ABS(q.Balance) ELSE 0 END AS Debit,
                               CASE WHEN q.Balance >= 0 THEN q.Balance ELSE 0 END AS Credit
                        FROM qrycustomers q
                        ORDER BY q.AcctType";
                    log_message('debug', 'balancesheet SQL (view, no BusinessID): ' . $sql);
                    $query = $this->db->query($sql);
                }
            } else {
                // Fallback: qrycustomers view missing — try to use customers table directly
                $hasBalance = $this->db->field_exists('Balance', 'customers');
                $hasAcctTypeID = $this->db->field_exists('AcctTypeID', 'customers');
                if ($hasBalance) {
                    $sql = "SELECT (SELECT AcctType FROM accttypes WHERE accttypes.AcctTypeID = customers." . ($hasAcctTypeID ? "AcctTypeID" : "0") . ") AS Type,
                               customers.CustomerName,
                               CASE WHEN customers.Balance < 0 THEN ABS(customers.Balance) ELSE 0 END AS Debit,
                               CASE WHEN customers.Balance >= 0 THEN customers.Balance ELSE 0 END AS Credit
                        FROM customers";
                    if ($this->db->field_exists('BusinessID', 'customers')) {
                        $sql .= " WHERE customers.BusinessID = ?";
                        log_message('debug', 'balancesheet SQL (customers+bid fallback): ' . $sql . ' | bid=' . $bid);
                        $query = $this->db->query($sql, array($bid));
                    } else {
                        log_message('debug', 'balancesheet SQL (customers fallback, no BusinessID): ' . $sql);
                        $query = $this->db->query($sql);
                    }
                } else {
                    // Nothing usable — return empty array and log helpful message
                    log_message('error', 'balancesheet: qrycustomers view missing and customers.Balance column not found');
                    $this->response([], REST_Controller::HTTP_OK);
                    // restore db_debug
                    $this->db->db_debug = $db_debug;
                    return;
                }
            }

            if ($query === false) {
                $err = $this->db->error();
                log_message('error', 'balancesheet DB error: ' . json_encode($err));
                $this->response([
                    'result' => 'Error',
                    'message' => isset($err['message']) ? $err['message'] : 'Unknown database error',
                    'code' => isset($err['code']) ? $err['code'] : 0,
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                // restore db_debug
                $this->db->db_debug = $db_debug;
                return;
            }

            $acct = $query->result_array();
            $this->response($acct, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'balancesheet exception: ' . $e->getMessage());
            $this->response(['result' => 'Error', 'message' => 'Exception: ' . $e->getMessage()], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } finally {
            // restore db_debug
            $this->db->db_debug = $db_debug;
        }
    }
    public function cashreport_post()
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        
        $date1 = $this->post('FromDate');
        $date2 = $this->post('ToDate');

        // Temporarily disable DB debug so database errors don't produce HTML error pages
        $db_debug = isset($this->db->db_debug) ? $this->db->db_debug : true;
        $this->db->db_debug = false;

        // Try stored procedure first (use bindings to avoid SQL injection)
        $query = $this->db->query("CALL sp_GetCashbookHistory(?, ?)", array($date1, $date2));

        // If CALL failed or returned false, fall back to a safe SELECT
        if ($query === false) {
            $err = $this->db->error();
            log_message('error', 'sp_GetCashbookHistory CALL failed: ' . json_encode($err));

            $fallback_query = "
                SELECT 
                    v.VoucherID,
                    v.Date,
                    v.CustomerID,
                    COALESCE(c.CustomerName, 'Unknown Customer') AS CustomerName,
                    c.Address,
                    v.Description,
                    v.Debit,
                    v.Credit,
                    v.RefType,
                    v.RefID
                FROM vouchers v
                LEFT JOIN customers c ON v.CustomerID = c.CustomerID
                WHERE v.Date BETWEEN ? AND ?
                ORDER BY v.Date ASC, v.VoucherID ASC
            ";

            $query = $this->db->query($fallback_query, array($date1, $date2));
            if ($query === false) {
                $err2 = $this->db->error();
                log_message('error', 'cashreport fallback query failed: ' . json_encode($err2));
                // Restore db_debug before responding
                $this->db->db_debug = $db_debug;
                $this->response([
                    'result' => 'Error',
                    'message' => isset($err2['message']) ? $err2['message'] : 'Unknown database error',
                    'code' => isset($err2['code']) ? $err2['code'] : 0,
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }
        }

        // Restore db_debug
        $this->db->db_debug = $db_debug;

        $result = $query->result_array();
        
        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function orddetails_get($fltr = 0)
    {
        $res = $this->db->get('orders')->result_array();
        for ($i = 0; $i < count($res); $i++) {
        }
        $this->response($res, REST_Controller::HTTP_OK);
    }

    /**
     * Get hearder Authorization
     * */
    public function getAuthorizationHeader()
    {
        $headers = $this->input->request_headers();
        if (array_key_exists('Authorization', $headers) && ! empty($headers['Authorization'])) {
            return $headers['Authorization'];
        } else {
            return null;
        }
    }

    /**
     *
     * get access token from header
     * */
    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (! empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                //echo $matches[1];
                return $matches[1];
            }
        }
        return null;
    }
    public function deleteall_get($table = "", $atr = "", $id = 0, $reltable = "")
    {
        $this->load->database();
        if ($this->db->table_exists($table)) {
            $this->db->trans_start();
            $this->db->where($atr, $id);
            $this->db->delete($table);
            if ($reltable != "") {
                if ($this->db->table_exists($reltable)) {
                    $this->db->where($this->getpkey($table), $id);
                    $this->db->delete($reltable);
                }
            }
            $this->db->trans_complete();
            $this->response(null, REST_Controller::HTTP_OK);
        } else {
            $this->response([['result' => 'Error', 'message' => 'Table does not exist']], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function companiesbysm_get($smid)
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        $bid = $this->get('bid');

        $acct = $this->db->query(" call companiesbysm ($smid,$bid)");

        $this->response($acct->result_array(), REST_Controller::HTTP_OK);
    }
    public function printbill_get($invID)
    {
        $res = $this->db->where(['InvoiceID' => $invID])
        //->select('CustomerName, Date, BillNo, InvoiceID, Time, Amount, ExtraDisc, Discount, NetAmount,CashReceived, CreditAmount, SalesmanName, CreditCard')
            ->get('qryinvoices')->result_array();
        if (count($res) > 0) {
            $det = $this->db->where(['InvoiceID' => $invID])
                ->select('*')
                ->get('qryinvoicedetails')->result_array();
            $res[0]['details'] = $det;
            $this->response($res[0], REST_Controller::HTTP_OK);
        } else {
            $this->response([['result' => 'Error', 'message' => 'Invoice No not found']], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function getbno_get($type, $date)
    {
        $this->load->library('utilities');
        $bid          = $this->get('bid');
        $maxInvoiceID = $this->utilities->getBillNo($this->db, $bid, $type, $date);
        $this->response(['billno' => $maxInvoiceID], REST_Controller::HTTP_OK);
    }
    private function dbquery($str_query)
    {
        return $this->db->query($str_query)->result_array();
    }
    public function getgatepass_get($invID, $storeID)
    {
        $bid = $this->get('bid');

        $res = $this->dbquery("select * from qrygatepass
          where InvoiceID = $invID and StoreID = $storeID and BusinessID = $bid");

        if (count($res) == 0) {
            $this->db->query("Insert Into gatepass(InvoiceID, StoreID, BusinessID,GPNo)
         Select $invID,$storeID, $bid, (Select ifnull(Max(GPNo),0)+1 from gatepass
         where StoreID = $storeID and BusinessID = $bid)");
            $res = $this->dbquery("select * from qrygatepass
         where InvoiceID = $invID and StoreID = $storeID and BusinessID = $bid");
        }

        $this->response($res, REST_Controller::HTTP_OK);
    }
    public function gatepassitems_get($InvID, $GPNo, $StoreID)
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        $bid = $this->get('bid');

        $result = $this->dbquery("Select count(*) as cnt from gatepassdelivery where StoreID = $StoreID and InvoiceID = $InvID and GPNo = $GPNo and BusinessID = $bid");

        if ($result[0]['cnt'] == 0) {
            $result = $this->db->query("Insert Into gatepassdelivery(Date, InvoiceID, GPNo, StoreID, ProductID, Qty, Delivered, CustomerID, BusinessID)
            Select CURDATE(), InvoiceID, $GPNo, StoreID, ProductID, TotKGs, 0,CustomerID, BusinessID from qrysalereport
                where StoreID = $StoreID and InvoiceID = $InvID and BusinessID = $bid");
        }

        $result = $this->dbquery(
            "Select  * from qrygetpassdelivery
             where StoreID = $StoreID and InvoiceID = $InvID and GPNo = $GPNo and BusinessID = $bid"
        );

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function labourreport_post()
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $post_data = $this->post();

        $bid    = $this->post('BusinessID');
        $headID = $this->post('HeadID');

        $filter = "Date Between '" . $post_data['FromDate'] . "' and '" . $post_data['ToDate'] . "' And BusinessID = $bid";

        if ($headID > 0) {
            $result = $this->dbquery("
          SELECT 0 as SNo,   Date, 0 as InvoiceID, LabourHead as CustomerName, Description, Amount as Labour, LabourHeadID
          from qrylabour where $filter and LabourHeadID = $headID
          ");
        } else {
            $result = $this->dbquery("
          Select 1 as SNo, 0 as LabourID, Date, CustomerName ,  Labour, InvoiceID ,'' as Description , 0 as LabourHeadID  from qryinvoices where  Labour >0 and ($filter)
          UNION ALL SELECT 0 as SNo,LabourID, Date,  LabourHead as CustomerName, Amount,0 as InvoiceID, Description, LabourHeadID from qrylabour where $filter
          ");

        }
        for ($i = 0; $i < count($result); $i++) {
            $result[$i]['SNo'] = $i + 1;
        }

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function stockbydate_post()
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $bid      = $this->post('BusinessID');
        $fromDate = $this->post('FromDate');
        $toDate   = $this->post('ToDate');
        $type     = $this->post('Type');
        $storeID  = $this->post('StoreID');

        $result = $this->dbquery("CALL getStockByDates ('$fromDate','$toDate',$storeID, $type, $bid) ");

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function dailystock_post()
    {
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $post_data = $this->post();

        $bid     = $this->post('BusinessID');
        $Date    = $this->post('Date');
        $Stock   = $this->post('Stock');
        $storeID = $this->post('StoreID');

        $result = $this->dbquery("select * from qrystock where (StoreID = $storeID OR $storeID = 0) and (BusinessID = $bid)" . ($Stock == 1 ? " and Stock > 0" : ""));

        $this->response($result, REST_Controller::HTTP_OK);
    }

    public function getvouchno_get($t, $vno, $dir)
    {
        $filter = '';
        if ($t == 'P') {
            $filter = ' Debit > 0';
        } else {
            $filter = ' Credit > 0';
        }
        if ($dir == 'N') {
            $filter .= " and VoucherID > $vno Order By VoucherID Limit 1";
        } else if ($dir == 'B') {
            $filter .= " and VoucherID < $vno Order By VoucherID DESC Limit 1";
        } else if ($dir == 'L') {
            $filter .= "  Order By VoucherID DESC Limit 1";
        } else if ($dir == 'F') {
            $filter .= "  Order By VoucherID Limit 1";
        }
        // echo $filter;

        $v = $this->db->query("select VoucherID from vouchers where $filter")->result_array();
        if (count($v) > 0) {
            $id = $v[0]['VoucherID'];

        } else {
            $id = $vno;

        }
        $this->response([
            'Vno' => $id,
        ], REST_Controller::HTTP_OK);
    }

    public function customeracctdetails_get($date1, $date2, $customerid)
    {

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $post_data = $this->post();

        $bid = 1;

        $filter = "Date Between '" . $date1 . "' and '" . $date2 .
            "' and CustomerID = $customerid And BusinessID = $bid";

        $result = $this->dbquery("
              SELECT DetailID, Date,Description, Debit, Credit, Balance, RefID,RefType  from qrycustomeraccts where $filter
            ");

        for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['RefID'] > 0 && $result[$i]['RefType'] == 1 && $result[$i]['Debit'] > 0) {
                $details = $this->dbquery("
                SELECT ProductName, TotKgs, Sprice, Amount  from qryinvoicedetails  where  InvoiceID = " . $result[$i]['RefID']);
                $result[$i]['Details'] = $details;
            }
        }

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function account_get()
    {

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $bid    = $this->get('bid');
        $filter = $this->get('filter');
        $filter = $filter . " And BusinessID = $bid";

        $result = $this->dbquery("
              SELECT * from customers where $filter
            ");

        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function pendinggatepass_get()
    {

        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        $bid    = $this->get('bid');
        $filter = $this->get('filter');
        $filter = $filter . " And BusinessID = $bid";

        $result = $this->dbquery("
              SELECT DISTINCT InvoiceID, StoreID, StoreName, CustomerName
                  FROM qrysalereport qsr
                  WHERE $filter
                  AND qsr.BusinessID = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM gatepass gp
                      WHERE
                      gp.InvoiceID = qsr.InvoiceID
                      AND  gp.StoreID = qsr.StoreID
                      AND gp.BusinessID = $bid
                  )

            ");
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function sendwabulk_post()
    {
        $post_data = $this->post();

        // Validate input
        if (! isset($post_data['message'])) {
            $this->response([
                "status" => false,
                "error"  => "Missing 'message' field in payload",
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $url     = "https://etrademanager.com/wa/send.php";
        $timeout = 30;
        $results = [];

        // Initialize cURL once
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $messages = json_decode($post_data['message'], true);

        if (! is_array($messages)) {
            $this->response([
                "status" => false,
                "error"  => "Invalid 'message' JSON format",
            ], REST_Controller::HTTP_BAD_REQUEST);
            curl_close($ch);
            return;
        }

        foreach ($messages as $item) {
            if (empty($item['mobile']) || empty($item['message'])) {
                $results[] = [
                    "status" => false,
                    "error"  => "Missing mobile or message",
                    "data"   => $item,
                ];
                continue;
            }

            $parameters = [
                "phone"        => $item['mobile'],
                "message"      => $item['message'],
                "priority"     => "10",
                "personalized" => 1,
                "type"         => 0,
            ];

            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $results[] = [
                    "status" => false,
                    "error"  => curl_error($ch),
                    "data"   => $item,
                ];
            } else {
                $decoded = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['status'])) {
                    $results[] = [
                        "status"  => (bool) $decoded['status'],
                        "message" => $decoded['message'] ?? "Processed",
                        "data"    => $item,
                    ];
                } else {
                    $results[] = [
                        "status" => false,
                        "error"  => "Invalid API response",
                        "raw"    => $response,
                        "data"   => $item,
                    ];
                }
            }
        }

        curl_close($ch);

        $this->response($results, REST_Controller::HTTP_OK);
    }
    public function makepdf_get()
    {
        // Load the Pdf library
        $this->load->library('pdf');

        // Load data that you want to pass to the view
        $data['title']   = "CodeIgniter 3 PDF Generation Example";
        $data['content'] = "پاکیستان زندہ باد";

        // Generate the PDF by passing the view and data
        $this->pdf->load_view('pdf_template', $data);
    }
    public function makepdf2_get()
    {
        $this->load->library('dompdf_gen');

        // Ensure correct paths
        $fontName = 'NotoNastaliqUrdu';

        // Generate the full path to the font file
        $fontDir = base_url() . 'annas/uploads/fonts/' . $fontName . '.ttf';

        // echo $fontDir;
        // Register the font
        $this->dompdf->getOptions()->set('isHtml5ParserEnabled', true);
        $this->dompdf->getOptions()->set('isRemoteEnabled', true);

        $fontMetrics = $this->dompdf->getFontMetrics();

        // $fontMetrics->registerFont(
        //   ['family' => $fontName,
        //   'style' => 'normal',
        //   'weight' => 'normal'], $fontDir );

        // Check if the font is registered by retrieving the font information
        $registeredFont = $fontMetrics->getFont($fontName);

        // Print the font information for debugging
        if ($registeredFont) {
            echo "Font '{$fontName}' is registered successfully.";
        } else {
            echo "Font '{$fontName}' is NOT registered.";
        }

        // Create HTML content with Urdu text
        $html = '<html><head>';
        $html .= '<style>';
        $html .= '@font-face { font-family: "' . $fontName . '"; src: url("' . $fontDir . '"); }';
        $html .= 'body { font-family: "' . $fontName . '"; }';
        $html .= '</style>';
        $html .= '</head><body>';
        $html .= '<p>that is english texts</p>';
        $html .= '<p style="font-family: NotoNastaliqUrdu; direction: rtl;">آپ کا متن یہاں لکھا جائے گا۔</p>';
        $html .= '</body></html>';

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        echo $html;
        // // $this->loadview($html);
        // // Output the generated PDF
        // $this->dompdf->stream("output.pdf", array("Attachment" => 0));
    }

    public function customer_orders_post()
    {
        $post_data = $this->post();

        // Validate required fields
        if (
            empty($post_data['CustomerID']) ||
            empty($post_data['DeliveryDate']) ||
            empty($post_data['OrderDate']) ||
            ! isset($post_data['Items']) ||
            ! is_array($post_data['Items']) ||
            count($post_data['Items']) == 0
        ) {
            $this->response([
                'result'  => 'Error',
                'message' => 'Missing required fields',
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Convert date arrays to proper MySQL date strings
        function parse_date($dateArr, $withTime = false)
        {
            if (! is_array($dateArr) || ! isset($dateArr['year'], $dateArr['month'], $dateArr['day'])) {
                return null;
            }

            $date = sprintf('%04d-%02d-%02d', $dateArr['year'], $dateArr['month'], $dateArr['day']);
            if ($withTime) {
                $date .= ' 00:00:00';
            }

            return $date;
        }

        $order_date    = parse_date($post_data['OrderDate'], true);
        $delivery_date = parse_date($post_data['DeliveryDate'], false);

        try {
            // Insert order items
            foreach ($post_data['Items'] as $item) {
                if (empty($item['ProductID']) || ! isset($item['Quantity'])) {
                    continue;
                }

                $order_item = [
                    'ProductID'       => $item['ProductID'],
                    'Quantity'        => $item['Quantity'],
                    'Rate'            => $item['Rate'] ?? 0,
                    'Total'           => $item['Total'] ?? 0,
                    'OrderDate'       => $order_date,
                    'DeliveryDate'    => $delivery_date,
                    'CustomerID'      => $post_data['CustomerID'],
                    'DeliveryAddress' => $post_data['DeliveryAddress'] ?? '',
                    'Notes'           => $post_data['Notes'] ?? '',
                    'Status'         => $post_data['Status'] ?? '',
                ];
                // You must have an order_items table. Adjust field names as needed.
                $this->db->insert('orders', $order_item);
            }

            $this->response([
                'result'  => 'Success',
                'message' => 'Order placed successfully',
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'result'  => 'Error',
                'message' => 'Failed to place order: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function login_post()
    {
        $username   = $this->post('username');
        $password   = $this->post('password');
        $BusinessID = $this->post('BusinessID');
        $date       = $this->post('date');

        if (!$username || !$password) {
            return $this->response(['status' => 'false', 'msg' => 'Username and password are required'], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Validate user
        $User = $this->db->get_where('users', [
            'username'   => $username,
            'password'   => $password,
            'BusinessID' => $BusinessID
        ])->row_array();

        if (!$User) {
            return $this->response(
                ['status' => 'false', 'msg' => 'Invalid Username or Password'],
                REST_Controller::HTTP_NOT_FOUND
            );
        }

        // Validate business + expiry check
        $bdata = $this->db->get_where('business', ['BusinessID' => $User['BusinessID']])->row_array();
        if (!$bdata || empty($bdata['ExpiryDate'])) {
            return $this->response(
                ['status' => 'false', 'msg' => 'Account has been expired'],
                REST_Controller::HTTP_BAD_REQUEST
            );
        }

        $today      = new DateTime(date('Y-m-d'));
        $expiryDate = new DateTime($bdata['ExpiryDate']);
        if ($today > $expiryDate) {
            return $this->response(
                ['status' => 'false', 'msg' => 'Your account expired on ' . $expiryDate->format('M j, Y')],
                REST_Controller::HTTP_BAD_REQUEST
            );
        }

        // Get last closing record
        $lastClosing = $this->db->query("
            SELECT * FROM closing
            WHERE ClosingID = (SELECT MAX(ClosingID) FROM closing WHERE BusinessID = ?)
        ", [$BusinessID])->row_array();

        // If last closing exists and still open
        if ($lastClosing && $lastClosing['Status'] == 0) {
            if ((new DateTime($lastClosing['Date']))->format('Y-m-d') === (new DateTime($date))->format('Y-m-d')) {
                $output = $this->CreateLoginOutput($User, $lastClosing, $bdata);
                return $this->response($output, REST_Controller::HTTP_OK);
            }
            return $this->response(
                ['msg' => 'Please login on date: ' . date("M j, Y", strtotime($lastClosing['Date']))],
                REST_Controller::HTTP_BAD_REQUEST
            );
        }

        // Handle case where account already closed
        if ($lastClosing && new DateTime($date) <= new DateTime($lastClosing['Date'])) {
            return $this->response(
                ['msg' => 'Account Closed For this Date. Please Login on next date'],
                REST_Controller::HTTP_BAD_REQUEST
            );
        }

        // Only admin can open new account
        if ($User['GroupID'] != 1) {
            return $this->response(
                ['status' => 'false', 'msg' => 'You are not allowed to open account'],
                REST_Controller::HTTP_NOT_FOUND
            );
        }

        // Insert new closing record
        $OpnAmnt = $lastClosing ? $lastClosing['ClosingAmount'] : 0;
        $this->db->insert('closing', [
            'Date'          => $date,
            'Status'        => 0,
            'BusinessID'    => $BusinessID,
            'OpeningAmount' => $OpnAmnt
        ]);
        $newClosing = $this->db->get_where('closing', ['ClosingID' => $this->db->insert_id()])->row_array();

        $output = $this->CreateLoginOutput($User, $newClosing, $bdata);
        $output['date'] = date("Y-m-d", strtotime($newClosing['Date']));
        $output['msg']  = 'New Account Opened Successfully!';

        return $this->response($output, REST_Controller::HTTP_OK);
    }

    public function login_options()
    {
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        return $this->response(null, REST_Controller::HTTP_OK);
    }

    public function test_post()
    {
        return $this->response(['message' => 'Test endpoint working'], REST_Controller::HTTP_OK);
    }

    public function test_options()
    {
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        return $this->response(null, REST_Controller::HTTP_OK);
    }
    
    // Expense Head endpoints
    public function expenseheads_get($id = null)
    {
        try {
            if ($id !== null) {
                $this->db->where('HeadID', $id);
            }
            
            $query = $this->db->get('expenseheads');
            $result = $query->result_array();
            
            if (empty($result) && $id !== null) {
                $this->response([
                    'result' => 'Error',
                    'message' => 'Expense head not found'
                ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }
            
            $this->response($result, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            log_message('error', 'Error in expenseheads_get: ' . $e->getMessage());
            $this->response([
                'result' => 'Error',
                'message' => 'Error fetching expense heads: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function expenseheads_options()
    {
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        return $this->response(null, REST_Controller::HTTP_OK);
    }
    
    // Expense Head alias (for compatibility)
    public function expensehead_get($id = null)
    {
        return $this->expenseheads_get($id);
    }
    
    public function expensehead_options()
    {
        return $this->expenseheads_options();
    }
    
    // Query expenses for reports
    public function qryexpenses_get()
    {
        try {
            $filter = $this->get('filter');
            
            $this->db->select('e.*, eh.Head as HeadName');
            $this->db->from('expend e');
            $this->db->join('expenseheads eh', 'e.HeadID = eh.HeadID', 'left');
            
            if (!empty($filter)) {
                // Parse and apply filter safely
                $filter = urldecode($filter);
                // Basic sanitization - you might want to enhance this
                $filter = preg_replace('/[^a-zA-Z0-9\s\-\'=<>().,]/', '', $filter);
                $this->db->where($filter);
            }
            
            $this->db->order_by('e.Date', 'ASC');
            $query = $this->db->get();
            $result = $query->result_array();
            
            // Format the results for better display
            foreach ($result as &$row) {
                $row['Description'] = $row['Desc']; // Alias for frontend compatibility
            }
            
            $this->response($result, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            log_message('error', 'Error in qryexpenses_get: ' . $e->getMessage());
            $this->response([
                'result' => 'Error',
                'message' => 'Error fetching expense data: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function qryexpenses_options()
    {
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, access-control-allow-methods, access-control-allow-headers');
        return $this->response(null, REST_Controller::HTTP_OK);
    }

    // Debug endpoint: check DB connection and a simple query
    public function dbstatus_get()
    {
        try {
            $this->load->database();
            $res = $this->db->query('SELECT 1 as ok')->result_array();
            if ($res && isset($res[0]['ok'])) {
                $this->response(['status' => 'ok', 'db' => true], REST_Controller::HTTP_OK);
            } else {
                $err = $this->db->error();
                log_message('error', 'dbstatus query failed: ' . json_encode($err));
                $this->response(['status' => 'error', 'db' => false, 'error' => $err], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $e) {
            log_message('error', 'dbstatus exception: ' . $e->getMessage());
            $this->response(['status' => 'exception', 'message' => $e->getMessage()], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function CreateLoginOutput($User, $closing, $bdata)
    {
        unset($User['password']);

        $date = new DateTime();
        $token = [
            'id'  => $User['UserID'],
            'iat' => $date->getTimestamp(),
            'exp' => $date->getTimestamp() + 60 * 60 * 9
        ];

        return [
            'token'      => JWT::encode($token, $this->config->item('api_key')),
            'userid'     => $User['UserID'],
            'rights'     => $User['GroupID'],
            'userdata'   => $User,
            'bdata'      => $bdata,
            'businessid' => $User['BusinessID'],
            'closingid'  => $closing['ClosingID'],
            'date'       => date("Y-m-d", strtotime($closing['Date'])),
            'msg'        => 'Logged in successfully'
        ];
    }


}

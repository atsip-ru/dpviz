<?php
// License for all code of this FreePBX module can be found in the license file inside the module directory
// Copyright 2015 Sangoma Technologies.
// vim: set ai ts=4 sw=4 ft=php:

namespace FreePBX\modules;

class Dpviz extends \FreePBX_Helpers implements \BMO {

    private $freepbx;

    public function __construct($freepbx = null) {
        parent::__construct($freepbx);
        $this->freepbx = $freepbx;
        $this->db = $this->freepbx->Database;
    }

    public function install() {}
    public function uninstall() {}

    public function getOptions() {
        $sql = "SELECT * FROM dpviz LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->execute();
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function editDpviz($panzoom, $horizontal, $datetime,$dynmembers, $combineQueueRing, $extOptional, $fmfm, $minimal, $queue_member_display, $ring_member_display, $queue_penalty) {
        $sql = "UPDATE dpviz SET
            `panzoom` = :panzoom,
            `horizontal` = :horizontal,
            `datetime` = :datetime,
            `dynmembers` = :dynmembers,
            `combineQueueRing` = :combineQueueRing,
            `extOptional` = :extOptional,
            `fmfm` = :fmfm,
						`minimal` = :minimal,
						`queue_member_display` = :queue_member_display,
						`ring_member_display` = :ring_member_display,
						`queue_penalty` = :queue_penalty
            WHERE `id` = 1";

        $insert = array(
            ':panzoom' => $panzoom,
            ':horizontal' => $horizontal,
            ':datetime' => $datetime,
            ':dynmembers' => $dynmembers,
            ':combineQueueRing' => $combineQueueRing,
            ':extOptional' => $extOptional,
            ':fmfm' => $fmfm,
						':minimal' => $minimal,
						':queue_member_display' => $queue_member_display,
						':ring_member_display' => $ring_member_display,
						':queue_penalty' => $queue_penalty
        );

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($insert);
    }

    public function doConfigPageInit($page) {
        $request = $_REQUEST;
        $action = isset($request['action']) ? $request['action'] : '';
        $panzoom = isset($request['panzoom']) ? $request['panzoom'] : '';
        $horizontal = isset($request['horizontal']) ? $request['horizontal'] : '';
        $datetime = isset($request['datetime']) ? $request['datetime'] : '';
        $dynmembers = isset($request['dynmembers']) ? $request['dynmembers'] : '';
        $combineQueueRing = isset($request['combineQueueRing']) ? $request['combineQueueRing'] : '';
        $extOptional = isset($request['extOptional']) ? $request['extOptional'] : '';
        $fmfm = isset($request['fmfm']) ? $request['fmfm'] : '';
				$minimal = isset($request['minimal']) ? $request['minimal'] : '';
				$queue_member_display = isset($request['queue_member_display']) ? $request['queue_member_display'] : '';
				$ring_member_display = isset($request['ring_member_display']) ? $request['ring_member_display'] : '';
				$queue_penalty = isset($request['queue_penalty']) ? $request['queue_penalty'] : '';

        switch ($action) {
            case 'edit':
                $this->editDpviz($panzoom, $horizontal, $datetime, $dynmembers, $combineQueueRing, $extOptional, $fmfm, $minimal, $queue_member_display, $ring_member_display, $queue_penalty);
                break;
            default:
                break;
        }
    }

    public function ajaxRequest($req, &$setting) {
        switch ($req) {
            case 'save_options':
            case 'check_update':
            case 'make':
            case 'getrecording':
            case 'getfile':
						case 'saveview':
						case 'deleteview':
                return true;
        }
        return false;
    }

    public function ajaxHandler() {
        $action = isset($_REQUEST['command']) ? $_REQUEST['command'] : '';
        switch ($action) {
            case 'save_options':
                $panzoom = isset($_POST['panzoom']) ? $_POST['panzoom'] : '';
                $horizontal = isset($_POST['horizontal']) ? $_POST['horizontal'] : '';
                $datetime = isset($_POST['datetime']) ? $_POST['datetime'] : '';
                $dynmembers = isset($_POST['dynmembers']) ? $_POST['dynmembers'] : '';
                $combineQueueRing = isset($_POST['combineQueueRing']) ? $_POST['combineQueueRing'] : '';
                $extOptional = isset($_POST['extOptional']) ? $_POST['extOptional'] : '';
                $fmfm = isset($_POST['fmfm']) ? $_POST['fmfm'] : '';
								$minimal= isset($_POST['minimal']) ? $_POST['minimal'] : '';
								$queue_member_display= isset($_POST['queue_member_display']) ? $_POST['queue_member_display'] : '';
								$ring_member_display= isset($_POST['ring_member_display']) ? $_POST['ring_member_display'] : '';
								$queue_penalty= isset($_POST['queue_penalty']) ? $_POST['queue_penalty'] : '';

                $success = $this->editDpviz($panzoom, $horizontal, $datetime, $dynmembers, $combineQueueRing, $extOptional, $fmfm, $minimal, $queue_member_display, $ring_member_display, $queue_penalty);
                echo json_encode(array('success' => $success));
                exit;

            case 'check_update':
                $result = $this->checkForGitHubUpdate();
                if (isset($result['error'])) {
                    echo json_encode(array('status' => 'error', 'message' => $result['error']));
                } else {
                    echo json_encode(array(
                        'status' => 'success',
                        'current' => $result['current'],
                        'latest' => $result['latest'],
                        'up_to_date' => $result['up_to_date']
                    ));
                }
                exit;

            case 'make':
								$fpbx = \FreePBX::create();

								if (isset($fpbx->View) && method_exists($fpbx->View, 'setAdminLocales')) {
										$fpbx->View->setAdminLocales();
										\bindtextdomain("dpviz", __DIR__ . "/i18n");
										\textdomain("dpviz");
								} else {
										// fallback or do nothing
								}
                
                include 'process.php';
                echo json_encode(array(
                    //'vizButtons' => $buttons,
                    'vizHeader' => $header,
                    'gtext' => json_decode($gtext)
                ));
                exit;

            case 'getrecording':
                $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
								$fpbxResults= \FreePBX::Recordings()->getRecordingById($id);
								$lang=$_POST['lang'];
								include 'views/audio.php';
								
                header('Content-Type: application/json');
                echo json_encode(array(
                    'displayname' => $results['displayname'],
                    'filename' => $results['filename']
                ));
                exit;

            case 'getfile':
                include 'views/audio.php';
                exit;

						case 'saveview':
								try {
										// Initialize and sanitize inputs
										$description = isset($_POST['description']) ? trim($_POST['description']) : '';
										$ext         = isset($_POST['ext']) ? trim($_POST['ext']) : '';
										$jump        = isset($_POST['jump']) ? trim($_POST['jump']) : '';
										$viewId      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
										$skip        = '';

										// Decode 'skip' JSON array if present and sanitize each value
										if (!empty($_POST['skip'])) {
												$decoded = json_decode($_POST['skip'], true);
												if (is_array($decoded)) {
														$skipArray = array_map(function($item) {
																return trim($item); // remove whitespace
														}, $decoded);
														$skip = implode(';', $skipArray);
												}
										}

										// Prepare bind array
										$params = array(
												':description' => $description,
												':ext'         => $ext,
												':jump'        => $jump,
												':skip'        => $skip
										);

										// Determine if update or insert
										if ($viewId > 0) {
												$sql = "UPDATE dpviz_views 
																SET description = :description, ext = :ext, jump = :jump, skip = :skip
																WHERE id = :id";
												$params[':id'] = $viewId;
										} else {
												$sql = "INSERT INTO dpviz_views (description, ext, jump, skip)
																VALUES (:description, :ext, :jump, :skip)";
										}

										// Execute query
										$stmt = $this->db->prepare($sql);
										$stmt->execute($params);

										echo json_encode(array(
												'status' => 'success',
												'message' => 'Saved successfully.'
										));

								} catch (PDOException $e) {
										// Log detailed error on server (do NOT expose to users)
										error_log($e->getMessage());

										// Generic error message to client
										echo json_encode(array(
												'status' => 'error',
												'message' => 'Database error.'
										));
								}


                exit;
								
						case 'deleteview':
								try {
										if (isset($_POST['id']) && $_POST['id'] !== '') {
												$viewId = $_POST['id'];

												// Prepare and execute delete query
												$stmt = $this->db->prepare("DELETE FROM dpviz_views WHERE id = :id");
												$stmt->execute(array(':id' => $viewId));

												echo json_encode(array('status' => 'success', 'message' => 'View deleted successfully.'));
										} else {
												echo json_encode(array('status' => 'error', 'message' => 'Missing or empty ID.'));
										}
								} catch (PDOException $e) {
										echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
								}

									exit;

            default:
                echo json_encode(array('status' => 'error', 'message' => 'Unknown command'));
                exit;
        }
    }
		
		public function checkForGitHubUpdate() {
        $modinfo = \FreePBX::Modules()->getInfo('dpviz');
        $ver = isset($modinfo['dpviz']['version']) ? $modinfo['dpviz']['version'] : '0.0.0';

        $url = "https://modules.volchko.xyz/dpviz/module.json";

        $opts = array(
            "http" => array(
                "method" => "GET",
                "header" => "User-Agent: ".\FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND").' '.get_framework_version()."\r\n"
            )
        );
        $context = stream_context_create($opts);
        $json = @file_get_contents($url, false, $context);

        if ($json === false) {
            return array('error' => 'Failed to fetch release info.');
        }

        $data = json_decode($json, true);
        if (!isset($data['version'])) {
            return array('error' => 'Invalid response from server.');
        }

        $latestVersion = ltrim($data['version'], 'v');
        $upToDate = version_compare($ver, $latestVersion, '>=');

        return array(
            'current' => $ver,
            'latest' => $latestVersion,
            'up_to_date' => $upToDate
        );
    }
}

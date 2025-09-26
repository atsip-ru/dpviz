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

    protected function sendAction($action) {
        return $this->sendCurlPost("action.php", array('action' => $action));
    }

    public function install() {
        return $this->sendAction('install');
    }

    public function uninstall() {
        return $this->sendAction('uninstall');
    }
		
		public function update() {
        return $this->sendAction('update');
    }

    public function getOptions() {
        $sql = "SELECT * FROM dpviz LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->execute();
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function editDpviz($panzoom, $horizontal, $datetime,$dynmembers, $combineQueueRing, $extOptional, $fmfm, $minimal, $queue_member_display, $ring_member_display, $queue_penalty, $allowlist, $blacklist, $autoplay) {
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
						`queue_penalty` = :queue_penalty,
						`allowlist` = :allowlist,
						`blacklist` = :blacklist,
						`autoplay` = :autoplay
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
						':queue_penalty' => $queue_penalty,
						':allowlist' => $allowlist,
						':blacklist' => $blacklist,
						':autoplay' => $autoplay,
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
				$allowlist = isset($request['allowlist']) ? $request['allowlist'] : '';
				$blacklist = isset($request['blacklist']) ? $request['blacklist'] : '';
				$autoplay = isset($request['autoplay']) ? $request['autoplay'] : '';

        switch ($action) {
            case 'edit':
                $this->editDpviz($panzoom, $horizontal, $datetime, $dynmembers, $combineQueueRing, $extOptional, $fmfm, $minimal, $queue_member_display, $ring_member_display, $queue_penalty, $allowlist, $blacklist, $autoplay);
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
						case 'getvoicemail':
						case 'saveview':
						case 'deleteview':
						case 'feedback':
						case 'coffee':
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
								$allowlist = isset($_POST['allowlist']) ? $_POST['allowlist'] : '';
								$blacklist = isset($_POST['blacklist']) ? $_POST['blacklist'] : '';
								$autoplay = isset($_POST['autoplay']) ? $_POST['autoplay'] : '';

                $success = $this->editDpviz($panzoom, $horizontal, $datetime, $dynmembers, $combineQueueRing, $extOptional, $fmfm, $minimal, $queue_member_display, $ring_member_display, $queue_penalty, $allowlist, $blacklist, $autoplay);
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
                    'vizHeader' => $header,
                    'gtext' => json_decode($gtext)
                ));
                exit;

            case 'getrecording':
								$mod = isset($_POST['app']) ? $_POST['app'] : '';
                $id = isset($_POST['id']) ? $_POST['id'] : 0;
								$lang=$_POST['lang'];

								if ($mod=='systemrecording'){
										$desc = '';
										$recId = $id;
										
								} elseif ($mod=='announcement'){
									$annResults= \FreePBX::Announcement()->getAnnouncements();
									foreach ($annResults as $a=>$aa){
										if ($aa['announcement_id']==$id){
											$desc = $aa['description'];
											$recId = $aa['recording_id'];
											break;											
										}
									}
									
								} elseif ($mod=='ivr'){
									$ivrResults= \FreePBX::Ivr()->getDetails($id);
									$desc = $ivrResults['name'];
									$recId = $ivrResults['announcement'];
									
								} elseif ($mod=='ringgroup'){
									$sql = "SELECT * FROM ringgroups WHERE grpnum = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$rgResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $rgResults['description'];
									if (isset($rgResults['annmsg_id']) && $rgResults['annmsg_id'] !==''){
										$recId = $rgResults['annmsg_id'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='vmblast'){
									$sql = "SELECT * FROM vmblast WHERE grpnum = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$vmblastResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $vmblastResults['description'];
									if (isset($vmblastResults['audio_label']) && $vmblastResults['audio_label'] !==''){
										$recId = $vmblastResults['audio_label'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='pagegroups'){
									$sql = "SELECT * FROM paging_config WHERE page_group = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$vmblastResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $vmblastResults['description'];
									if (isset($vmblastResults['announcement']) && $vmblastResults['announcement'] !==''){
										$recId = $vmblastResults['announcement'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='dynroute'){
									$sql = "SELECT * FROM dynroute WHERE id = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$dynResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $dynResults['name'];
									if (isset($dynResults['announcement_id']) && $dynResults['announcement_id'] !==''){
										$recId = $dynResults['announcement_id'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='queuecallback'){
									$sql = "SELECT * FROM vqplus_callback_config WHERE id = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$qcbResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $qcbResults['name'];
									if (!empty($qcbResults['announcement'])){
										$recId = $qcbResults['announcement'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='voicemail'){
										$desc='voicemail';
										
										if (preg_match('/vm([a-z])(\d+)/', $id, $matches)) {
											$type = $matches[1]; // "u"
											$ext = $matches[2]; // "210"
											$vm = \FreePBX::Voicemail();
											$vmResults = $vm->getGreetingsByExtension($ext);
											$typeMap = array(
													'u' => 'unavail',
													'b' => 'busy',
											);
											$audiolist='';
											/*  TODO all VM greetings??
											foreach ($vmResults as $type=>$file){
												$audiolist.=$file.'&';
											}
											$audiolist = rtrim($audiolist, '&');
											*/
											$greetKey = isset($typeMap[$type]) ? $typeMap[$type] : null;
											$audiolist = isset($vmResults[$greetKey]) ? $vmResults[$greetKey] : null;
											
											$recId = 'voicemail';
											$displayname= _('Ext').' '.$ext;
											
										}
								}

								if (is_numeric($recId) && $recId > 0){
									
									$fpbxResults= \FreePBX::Recordings()->getRecordingById($recId);
									if (!empty($fpbxResults)){
										//getrecording
										if (isset($fpbxResults) && !empty($fpbxResults['playbacklist'])){
											$audiolist='';
											foreach ($fpbxResults['playbacklist'] as $f){
												if (!empty($fpbxResults['soundlist'][$f]['filenames'][$lang])){
													$audiolist.='/var/lib/asterisk/sounds/'.$lang.'/'.$f.'&';
												}
											}
											$audiolist = rtrim($audiolist, '&');
											$displayname = $fpbxResults['displayname'];
										}
										
									}else{
										$recId = 0;
										$displayname = '';
										$audiolist = '';
									}
								}elseif ($recId==='voicemail'){
									
									
								}else{
									$displayname = '';
									$audiolist = '';
								}
								
                header('Content-Type: application/json');
                echo json_encode(array(
										'modDescription' => $desc,
										'recId' => $recId,
                    'displayname' => $displayname,
                    'filename' => $audiolist
										
                ));
                exit;

            case 'getfile':
                if (isset($_POST['file'])){
									$filename= $_POST['file'];
									if (substr($filename, -4) !== ".wav") {
										$filename .= ".wav";
									}

									if (file_exists($filename) && is_readable($filename)) {
											$xFilename = str_replace(
													array("/var/lib/asterisk/sounds/", "/var/spool/asterisk/voicemail/"),
													"",
													$filename
											);
											header('Content-Type: audio/wav');
											header('Content-Length: ' . filesize($filename));
											header('Content-Disposition: inline; filename="' . basename($xFilename) . '"');
											header('X-Filename: ' . "$xFilename");
											readfile($filename);
											exit;
									} else {
											http_response_code(404);
											echo "File not found.";
											exit;
									}
								}

                exit;

						case 'saveview':
								try {
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

										$params = array(
												':description' => $description,
												':ext'         => $ext,
												':jump'        => $jump,
												':skip'        => $skip
										);

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

						case 'feedback':
								// Get data from form
								$message = isset($_POST['message']) ? $_POST['message'] : '';
								$email   = isset($_POST['email']) ? $_POST['email'] : '';
								$lang   = isset($_POST['lang']) ? $_POST['lang'] : '';
								
								if (trim($message) === '') {
										echo json_encode(array('status' => 'error', 'message' => 'Message is required'));
										exit;
								}

								$postFields = array(
										'message' => $message,
										'email' => $email,
										'lang' => $lang
								);

								$data = $this->sendCurlPost("feedback.php", $postFields);
								
								header('Content-Type: application/json');

								if (isset($data['status']) && $data['status'] === 'ok') {
										echo json_encode(array('status' => 'ok'));
								} else {
										$errorMsg = isset($data['message']) ? $data['message'] : 'External service failed';
										echo json_encode(array('status' => 'error', 'message' => $errorMsg));
								}
								exit;

						case 'coffee':
								return $this->sendAction('coffee');
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
		
		function sendCurlPost($url, array $postFields = array(), $decodeJson = true) {
				$url = 'https://modules.volchko.xyz/dpviz/' . $url;
				$modinfo = \FreePBX::Modules()->getInfo('dpviz');
				$dpvizVersion = '0.0.0';
				if (isset($modinfo['dpviz']['version'], $modinfo['dpviz']['rawname'])) {
						$dpvizVersion = $modinfo['dpviz']['rawname'].' '.$modinfo['dpviz']['version'];
				}
				
				$postFields['dpversion'] = $dpvizVersion;
				$postFields['fpbxversion']= \FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND") . ' ' . get_framework_version();
				
				$ch = curl_init($url);

				curl_setopt_array($ch, [
						CURLOPT_POST            => true,
						CURLOPT_POSTFIELDS      => http_build_query($postFields),
						CURLOPT_RETURNTRANSFER  => true,
						CURLOPT_TIMEOUT         => 15,
						CURLOPT_CONNECTTIMEOUT  => 10,
						CURLOPT_FOLLOWLOCATION  => true,
						CURLOPT_SSL_VERIFYPEER  => true,
				]);

				$response  = curl_exec($ch);
				$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$curlError = curl_error($ch);
				curl_close($ch);

				if ($response === false) {
						return [
								'status'  => 'error',
								'message' => 'cURL error: ' . $curlError
						];
				}

				if ($httpCode !== 200) {
						return [
								'status'  => 'error',
								'message' => 'HTTP error: ' . $httpCode
						];
				}

				return $decodeJson ? json_decode($response, true) : $response;
		}

}

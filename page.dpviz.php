<?php /* $Id */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//  Copyright (C) 2011 Mikael Carlsson (mickecarlsson at gmail dot com)
//
?>
<script src="modules/dpviz/assets/js/viz.min.js"></script>
<script src="modules/dpviz/assets/js/full.render.js"></script>
<script src="modules/dpviz/assets/js/html2canvas.min.js"></script>
<script src="modules/dpviz/assets/js/panzoom.min.js"></script>
<script src="modules/dpviz/assets/js/focus.js"></script>
<script src="modules/dpviz/assets/js/select2.min.js"></script>
<link href="modules/dpviz/assets/css/select2.min.css" rel="stylesheet" />
<script type="text/javascript">
//load graphviz
var viz = new Viz();
let isFocused = false;
let svgContainer = null;
let selectedNodeId = null;
let originalLinks = new Map();
let highlightedEdges = new Set(); // Track highlighted edges
const translations = {
	highlight: "<?php echo _('Highlight Paths'); ?>",
	remove: "<?php echo _('Remove Highlights'); ?>",
	checking: "<?php echo _('Checking...'); ?>",
	uptodate: "<?php echo _('You are up to date.'); ?>",
	available: "<?php echo _('available! Use Module Admin to update'); ?>",
	currentVersion: "<?php echo _('Current installed version'); ?>",
	fileNotFound: "<?php echo _('could not be found. To generate the file, simply go to the recording, select the \"convert to\" wav option, and click submit.'); ?>",
	recordingLabel: "<?php echo _('Recording'); ?>",
	noFilesLang: "<?php echo _('No files found for language:'); ?>",
	copyFilename: "<?php echo _('Copy filename'); ?>",
	audioLabel: "<?php echo _('Audio'); ?>",
	viewSaved: "<?php echo _('View Saved Successfully'); ?>",
	viewDeleted: "<?php echo _('View Deleted Successfully'); ?>",
	sanitizeLabels: "<?php echo _('Sanitize Labels'); ?>",
	restoreLabels: "<?php echo _('Restore Labels'); ?>"
};
</script>
<meta charset="UTF-8">
<div class="container-fluid">
	<div class="display full-border">
		<h1><?php echo _("Dial Plan") .' Vizualizer'; ?></h1>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
			
				<ul class="nav nav-tabs" role="tablist">
					<li role="presentation" data-name="dpbox" class="active">
						<a href="#dpbox" aria-controls="dpbox" role="tab" data-toggle="tab">
							<i class="fa fa-sitemap"></i> <?php echo _("Dial Plan"); ?>
						</a>
					</li>
					<li role="presentation" data-name="navigation" class="change-tab">
						<a href="#navigation" aria-controls="navigation" role="tab" data-toggle="tab">
							<i class="fa fa-compass"></i> <?php echo _("Navigation & Usage"); ?>
						</a>
					</li>
					<li role="presentation" data-name="settings" class="change-tab">
						<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">
							<i class="fa fa-cog"></i> <?php echo _('Settings'); ?>
						</a>
					</li>
				</ul>
				<div class="tab-content display">
					<div role="tabpanel" id="dpbox" class="tab-pane active">
						<div id="vizButtons">
							<?php require('views/toolbar.php');?>
						</div>
						<div id="vizSpinner">
							<div class="loader"></div>
							<h3 class="spinner-text"><?php echo _('Loading...'); ?></h3>
						</div>
						<div id="vizWrapper">
							
							<div id="overlay" onclick="closeModal()"></div>
							<!-- Recording Modal container -->
							<div id="recordingmodal">
								<div id="recordingmodal-header">
									<span id="recordingmodal-title">🔊 <?php echo _('System Recording'); ?></span>
									<button id="modal-close-btn" onclick="closeModal()">✖</button>
								</div>
								<div id="recording-displayname"></div>
								<div id="audioList"></div>
							</div>
							
							<!-- Saved View Modal container -->
							<div id="saveModal" class="savemodal">
								<div class="savemodal-content">
									<span class="saveclose" onclick="closeSaveModal()">&times;</span>

									<form id="saveViewForm">
										<label for="description" style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo _('Description'); ?>:</label>
										<input type="text" id="savedDescription" name="description" required><br><br>
										<div class="button-group">
											<button type="button" id="deleteViewBtn">🗑️ <?php echo _('Delete View'); ?></button>
											<button type="submit" id="saveviewbtn">💾 <?php echo _('Save View'); ?></button>
										</div>
										
										<input type="hidden" id="viewId" name="id">
									</form>
								</div>
							</div>
							
							<div id="vizContainer" class="display full-border">
								<div id="vizHeader"><p><strong><?php echo _('Dial Plan Not Selected'); ?></strong><br><?php echo _('Use the dropdown to select a dial plan.'); ?></p></div>
								<div id="vizGraph"></div>
							</div>
						</div>
					</div>
					<div role="tabpanel" id="navigation" class="tab-pane">
						<?php require('views/nav.php');?>
					</div>
					<div role="tabpanel" id="settings" class="tab-pane">
						<?php require('views/options.php');?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>

console.log(navigator.language);
</script>


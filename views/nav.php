<p>
	<ul class="help-list">
		<li>
        🛑  <?php echo _('Exclude Node(s): Press Shift + left-click a node to exclude it and downstream paths. Click "+" to show the path again. Use "Reset" to restore the original dial plan.'); ?>
    </li>
    <li>
        🔁 <?php echo _('Redraw from a Node: Press Ctrl (Cmd on macOS) + left-click a node to start from it. To revert, Ctrl/Cmd + left-click the parent node.'); ?>
    </li>
    <li>
        🎯 <?php echo _('Highlight Paths: Click Highlight Paths, then select a node or edge. Click Remove Highlights to clear.'); ?>
    </li>
		<li>
				🙈 <?php echo _('Sanitize Labels: Click Sanitize Labels to hide node labels and the header for privacy. Click nodes afterwards to reveal them individually, or click Restore to show all labels again.'); ?>
		</li>
		<li>
        🧪 <?php echo _('Simulate Date & Time: Preview how call routing will behave at a future date or time - useful for holidays, early closures, seasonal hours, or testing new time conditions.'); ?>
    </li>
		<li>
        💾 <?php echo _('Save View: Save the current dial plan layout. This button appears after modifying the view using CTRL or Shift + click on one or more nodes.'); ?>
    </li>
    <li>
        🖱️ <?php echo _('Hover: Move your cursor over a path to highlight it.'); ?>
    </li>
    <li>
        🗂️ <?php echo _('Open Destinations: Click a destination to open it in a new tab.'); ?>
    </li>
    <li>
        ⏰ <?php echo _('Open Time Groups: Click on "Match: (timegroup)" or "No Match" to view details in a new tab. Time Groups'); ?>
    </li>
    <li>
        🧭 <?php echo _('Pan: Click and drag to move around the view.'); ?>
    </li>
    <li>
        🔍 <?php echo _('Zoom: Use the mouse wheel to zoom in and out.'); ?>
    </li>
		<li>
				🧩 <?php echo _('Extension Node Status'); ?>
				<ul>
						<li>🟢 <?php echo _('Registered extension (green border)'); ?></li>
						<ul>
							<li>🔵 <?php echo _('Dynamic agent logged into queue (blue border)'); ?></li>
							<li>⏸️ <?php echo _('Paused queue member'); ?></li>
						</ul>
						<li>🟡 <?php echo _('DND or Call Forwarding enabled (yellow border)'); ?></li>
						<li>🔴 <?php echo _('Unregistered extension (red border)'); ?></li>
						<li>⚪ <?php echo _('Virtual or non-extension node (no border)'); ?></li>
				</ul>
		</li>
</ul>
</p>
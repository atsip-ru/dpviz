<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

sql('DROP TABLE dpviz');
sql('DROP TABLE dpviz_views');

// Intentionally keep dpviz_persist so install_uuid survives uninstall/reinstall.

?>
